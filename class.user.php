<?php
/**
 * Creates session and login information
 * when User class is initiated, start session and check for user object.
 * If not found, check for persistent login cookie.
 */
class User {

    /**
     * Starts session and checks for User login.
     * Takes care of the basic login and session creation, checking for a cookie if the user session is nonexistant.
     */
    public function __construct($vce) {

        // global $vce;
        $vce->user = $this;

        // start session
        $user_session = self::start_session($vce);

        // check is session for user info exists
        // if (isset($_SESSION['user'])) {
        if (!empty($user_session)) {

            // get user info object
            // $user_object = $_SESSION['user'];

            // set user info values
            // foreach ($user_object as $key => $value)
            foreach ($user_session as $key => $value) {
                $this->$key = $value;
            }

            // set session
            // $_SESSION['user'] = $this;
            // self::store_session($this, $vce);

            // a good session, return to exit this method
            return true;

            // if session doesn't exits, check if persistent login cookie exists
        } else {

            // check if persistent login cookie exists
            // sometimes $_COOKIE['_pl'] returns "deleted" when using Safari
            if (defined('PERSISTENT_LOGIN') && PERSISTENT_LOGIN && isset($_COOKIE['_pl']) && strlen($_COOKIE['_pl']) > 10) {

                // get cookie data
                $cookie_value = hex2bin($_COOKIE['_pl']);

                $length = self::vector_length();

                $vector = base64_encode(substr($cookie_value, 0, $length));
                $encrypted = base64_encode(substr($cookie_value, $length));

                $decrypted = self::decryption($encrypted, $vector);

                //create hash value of time
                $time_hash = hash('sha256', $decrypted);

                // save cookie hash to object so it can be deleted when new one is set
                $this->time_hash = $time_hash;

                // search for persistent_login value that matches cookie value
                $query = "SELECT * FROM " . TABLE_PREFIX . "users_meta WHERE meta_key='persistent_login' AND meta_value LIKE '%" . $time_hash . "%'";
                $persistent_login = $vce->db->get_data_object($query);

                // value has been found
                if (isset($persistent_login[0]->meta_value)) {

                    $persistent_login_values = json_decode($persistent_login[0]->meta_value);

                    // cycle though to make sure that the match is true
                    foreach ($persistent_login_values as $each_time_stamp => $each_time_hash) {

                        if ($each_time_hash == $time_hash) {

                            // create user object for the user_id of the persistent_login match
                            self::make_user_object($persistent_login[0]->user_id);

                            // load login hook
                            // at_user_login
                            if (isset($vce->site->hooks['user_at_login'])) {
                                foreach ($vce->site->hooks['user_at_login'] as $hook) {
                                    call_user_func($hook, $persistent_login[0]->user_id);
                                }
                            }

                            // a good persistent login, return to exit this method
                            return;

                        }

                    }

                }

                // throw an error so that we can debug this
                // global $site;
                // $site->log( "persistent login issue");

            }
            /* end persistent login */

            /* alternative login */

            // load login hook
            if (isset($vce->site->hooks['user_alternative_login'])) {
                foreach ($vce->site->hooks['user_alternative_login'] as $hook) {
                    call_user_func($hook, $this);
                }
            }

            /* end alternative login */

        }

        // if no session or persistent login, then create a non logged-in user session

        // check is session for user info exists
        if (empty($user_session)) {

            // user is not logged-in, role_id is set to x, because x is fun and "I want to believe."
            $this->role_id = "x";

            // add a session vector
            $this->session_vector = self::create_vector();

            // set session
            //$_SESSION['user'] = $this;
            self::store_session($this, $vce);

        }

    }

    /**
     * Creates the password_hash and sends it to make_user_object
     * @param array $input  contains email and password
     * @global object $db
     * @return object user object
     */
    public function login($input) {

        // is the user already logged in?
        if (!isset($this->user_id)) {

            global $vce;

            // here is where we will need to validate again
            $hash = self::create_hash(strtolower($input['email']), $input['password']);

            // get user_id,role_id, and hash by crypt value
            $query = "SELECT user_id FROM " . TABLE_PREFIX . "users WHERE hash='" . $hash . "' LIMIT 1";
            $user_id = $vce->db->get_data_object($query);

            if ($user_id) {

                self::make_user_object($user_id[0]->user_id);

                // load login hook
                if (isset($vce->site->hooks['at_user_login'])) {
                    foreach ($vce->site->hooks['at_user_login'] as $hook) {
                        call_user_func($hook, $user_id[0]->user_id);
                    }
                }

                return true;

            } else {

                return false;

            }

        }

        // return false if already logged in
        return false;
    }

    /**
     * Logs user out.
     * @return user is logged out
     */
    public function logout() {

        // user is logged in
        if (isset($this->user_id)) {

            global $vce;

            // save it for later, your legs give way, you hit the ground
            // fyi, $this = $vce->user
            $user_id = $this->user_id;

            // check if persistent login cookie exists
            if (defined('PERSISTENT_LOGIN') && PERSISTENT_LOGIN && isset($_COOKIE['_pl'])) {

                // get cookie data
                $cookie_value = hex2bin($_COOKIE['_pl']);

                $length = self::vector_length();

                $vector = base64_encode(substr($cookie_value, 0, $length));
                $encrypted = base64_encode(substr($cookie_value, $length));

                $decrypted = self::decryption($encrypted, $vector);

                //create hash value of time

                $time_hash = hash('sha256', $decrypted);

                // delete persistent login cookie
                unset($_COOKIE['_pl']);

                // check for https within site_url
                if (parse_url($vce->site->site_url, PHP_URL_SCHEME) == "https") {
                    $cookie_secure = true;
                } else {
                    $cookie_secure = false;
                }

                // get url path
                $url_path = parse_url($vce->site->site_url, PHP_URL_PATH);
                // if this has a value, set cookie_path
                if (empty($url_path)) {
                    $url_path = '/';
                }

                // set cookie to clear
                setcookie('_pl', '', time() - 42000, $url_path, '', $cookie_secure, true);

                // search for persistent_login for this user_id
                $query = "SELECT * FROM " . TABLE_PREFIX . "users_meta WHERE meta_key='persistent_login' AND user_id='" . $this->user_id . "'";
                $persistent_login = $vce->db->get_data_object($query);

                // update if record is found, else create a new record
                if (isset($persistent_login[0]->meta_value)) {

                    $persistent_login_values = json_decode($persistent_login[0]->meta_value, true);

                    // check that array has elements
                    if (!empty($persistent_login_values)) {

                        // cycle through current persistent_login to remove old records
                        foreach ($persistent_login_values as $each_time_stamp => $each_time_hash) {

                            if ($each_time_hash == $time_hash) {

                                // clean up the persistent_login by removing previous cookie value
                                unset($persistent_login_values[$each_time_stamp]);

                            }

                        }

                        $update = array('meta_value' => json_encode($persistent_login_values));
                        $update_where = array('user_id' => $this->user_id, 'meta_key' => 'persistent_login');
                        $update = $vce->db->update('users_meta', $update, $update_where, 1);

                    }

                }

            }

            // clear user object properties and
            foreach ($this as $key => $value) {
                unset($this->$key);
            }

            // delete user session
            if (isset($_SESSION)) {
                unset($_SESSION['user']);

                // Destroy all data registered to session
                session_destroy();
            }

            // load logout hook
            if (isset($vce->site->hooks['user_logout_complete'])) {
                foreach ($vce->site->hooks['user_logout_complete'] as $hook) {
                    call_user_func($hook, $user_id);
                }
            }

            // load logout hook
            if (isset($vce->site->hooks['user_logout_override'])) {
                foreach ($vce->site->hooks['user_logout_override'] as $hook) {
                    call_user_func($hook, $user_id);
                }
            }

        }

    }

    /**
     * Creates user object from user_id
     * @global object $db
     * @global object $site
     * @param string $user_id
     * @return call to self::store_session()
     *
     * note: if the previous value of $vce->user is needed, then it should be a clone of the object
     * $user = clone $vce->user
     *
     */
    public function make_user_object($user_id) {

        global $vce;

        // create array to contain values
        $user_object = array();

        // clear user session
        if (isset($_SESSION)) {
            unset($_SESSION['user']);
        }

        // clear user object properties
        foreach ($this as $key => $value) {
            unset($this->$key);
        }

        // get user_id,role_id, and vector
        $query = "SELECT user_id,vector,role_id FROM  " . TABLE_PREFIX . "users WHERE user_id='" . $user_id . "' LIMIT 1";
        $results = $vce->db->get_data_object($query);

        if ($results) {

            // loop through results
            foreach ($results[0] as $key => $value) {
                //add values to user object
                $user_object[$key] = $value;
            }

            // grab all user meta data that has no minutia
            $query = "SELECT meta_key,meta_value FROM  " . TABLE_PREFIX . "users_meta WHERE user_id='" . $user_object['user_id'] . "'";
            $metadata = $vce->db->get_data_object($query);

            if ($metadata) {

                // look through metadata
                foreach ($metadata as $array_key => $each_metadata) {

                    // decrypt the values using vi/vector for decrypting user meta data
                    $value = self::decryption($each_metadata->meta_value, $user_object['vector']);

                    // add the values into the user object
                    $user_object[$each_metadata->meta_key] = $vce->db->clean($value);
                }

                // we can then remove vector from the user object
                unset($user_object['vector'], $user_object['lookup'], $user_object['persistent_login']);

                // add user meta data specific to site roles.
                $roles = json_decode($vce->site->roles, true);

                // check if role associated info is an array
                if (is_array($roles[$user_object['role_id']])) {
                    // add key=>value to user object if they don't already exist.
                    // user_meta key=>value takes precidence over role key=>value
                    // this allows for user specific granulation of permissions, et cetera
                    foreach ($roles[$user_object['role_id']] as $role_meta_key => $role_meta_value) {
                        // check if the value is an array
                        if (is_array($role_meta_value)) {
                            $suffix = '_' . $role_meta_key;
                            foreach ($role_meta_value as $sub_meta_key => $sub_meta_value) {
                                // add simple key=>value to user object
                                if (!isset($user_object[$sub_meta_key . $suffix])) {
                                    $user_object[$sub_meta_key . $suffix] = $sub_meta_value;
                                } else {
                                    // add on to existing
                                    $user_object[$sub_meta_key . $suffix] .= ',' . $sub_meta_value;
                                }
                            }
                        } else {
                            // add simple key=>value to user object
                            if (!isset($user_object[$role_meta_key])) {
                                $user_object[$role_meta_key] = $role_meta_value;
                            } else {
                                $user_object[$role_meta_key] .= ',' . $role_meta_value;
                            }
                        }
                    }
                }

                // create a session vector
                // this is used to create an edit / delete token for components
                $user_object['session_vector'] = self::create_vector();

                // rekey user object
                foreach ($user_object as $key => $value) {
                    $this->$key = $value;
                }

                // hook
                if (isset($vce->site->hooks['user_make_user_object'])) {
                    foreach ($vce->site->hooks['user_make_user_object'] as $hook) {
                        call_user_func($hook, $this, $vce);
                    }
                }

                return self::store_session($this, $vce);

            }

        } else {

            // user is not logged-in, role_id is set to x, because x is fun and "I want to believe."
            $this->role_id = "x";
            $this->session_vector = self::create_vector();

            return self::store_session($this, $vce);

        }

    }

    /**
     * Starts session
     * @global object $db
     * @param object $user_object
     * @return bool true
     */
    private function store_session($user_object, $vce) {

        // create persistent login cookie
        if (defined('PERSISTENT_LOGIN') && PERSISTENT_LOGIN && !empty($this->user_id)) {

            // create new cookie data

            $identifier = md5($user_object->email . SITE_KEY);
            $token = md5(uniqid(rand(), TRUE));

            $vector = self::create_vector();

            $current_time = time();
            $cookie_expires = $current_time + mt_rand(518400, 604800); // 6 to 7 days

            $encrypted = self::encryption($current_time, $vector);

            // cookie value
            $cookie_value = bin2hex(base64_decode($vector) . base64_decode($encrypted));

            // check for https within site_url
            if (parse_url($vce->site->site_url, PHP_URL_SCHEME) == "https") {
                $cookie_secure = true;
            } else {
                $cookie_secure = false;
            }

            // get url path
            $url_path = parse_url($vce->site->site_url, PHP_URL_PATH);
            // if this has a value, set cookie_path
            if (empty($url_path)) {
                $url_path = '/';
            }

            // set cookie
            setcookie('_pl', $cookie_value, $cookie_expires, $url_path, '', $cookie_secure, true);

            // get hash value for time
            $time_hash = hash('sha256', $current_time);

            // search for persistent_login for this user_id
            $query = "SELECT * FROM " . TABLE_PREFIX . "users_meta WHERE meta_key='persistent_login' AND user_id='" . $this->user_id . "'";
            $persistent_login = $vce->db->get_data_object($query);

            // update if record is found, else create a new record
            if (!empty($persistent_login[0]->meta_value)) {

                $persistent_login_values = json_decode($persistent_login[0]->meta_value, true);

                // cycle through current persistent_login to remove old records
                foreach ($persistent_login_values as $each_time_stamp => $each_time_hash) {

                    if ($each_time_stamp < $current_time) {

                        // clean up the persistent_login by removing old records
                        unset($persistent_login_values[$each_time_stamp]);

                    }

                    if ($each_time_hash == $this->time_hash) {

                        // clean up the persistent_login by removing previous cookie value
                        unset($persistent_login_values[$each_time_stamp]);

                    }

                }

                // clean up object
                unset($this->time_hash);

                // add new persistent_login value
                $persistent_login_values[$cookie_expires] = $time_hash;

                $update = array('meta_value' => json_encode($persistent_login_values));
                $update_where = array('user_id' => $this->user_id, 'meta_key' => 'persistent_login');
                $update = $vce->db->update('users_meta', $update, $update_where, 1);

            } else {

                // add new persistent_login value
                $persistent_login_values[$cookie_expires] = $time_hash;

                $user_data = array(
                    'user_id' => $this->user_id,
                    'meta_key' => 'persistent_login',
                    'meta_value' => json_encode($persistent_login_values),
                    'minutia' => 'false',
                );
                $vce->db->insert('users_meta', $user_data);

            }

        }

        // generate a new session id key
        // session_regenerate_id(true);

        // hook
        if (isset($vce->site->hooks['user_store_session_override'])) {
            foreach ($vce->site->hooks['user_store_session_override'] as $hook) {
                call_user_func($hook, $user_object, $vce);
            }
        }

        // check if php sessions are being used
        if (isset($_SESSION)) {
            // store standard php session
            $_SESSION['user'] = $user_object;
        }

        return true;

    }

    /**
     * Starts session
     * @return sets ini values
     */
    private function start_session($vce) {

        // hook that can be used to override sessions
        if (isset($vce->site->hooks['user_start_session_override'])) {
            foreach ($vce->site->hooks['user_start_session_override'] as $hook) {
                return call_user_func($hook, $vce);
            }
        }

        // hook that can be used to create a session handler
        if (isset($vce->site->hooks['user_sys_session_method'])) {
            foreach ($vce->site->hooks['user_sys_session_method'] as $hook) {
                call_user_func($hook, $vce);
            }
        }

        // set hash algorithm
        ini_set('session.hash_function', 'sha512');

        // send hash
        ini_set('session.hash_bits_per_character', 5);

        // set additional entropy
        ini_set('session.entropy_file', '/dev/urandom');

        // set additional entropy
        ini_set('session.entropy_length', 256);

        // prevents session module to use uninitialized session ID
        ini_set('session.use_strict_mode', true);

        // SESSION FIXATION PREVENTION

        // do not include the identifier in the URL, and not to read the URL for identifiers.
        ini_set('session.use_trans_sid', 0);

        // tells browsers not to store cookie to permanent storage
        ini_set('session.cookie_lifetime', 0);

        // force the session to only use cookies, not URL variables.
        ini_set('session.use_only_cookies', true);

        // make sure the session cookie is not accessible via javascript.
        ini_set('session.cookie_httponly', true);

        global $site;

        // check for https within site_url
        if (parse_url($site->site_url, PHP_URL_SCHEME) == "https") {
            // set to true if using https
            ini_set('session.cookie_secure', true);
        } else {
            ini_set('session.cookie_secure', false);
        }

        // get url path
        $url_path = parse_url($site->site_url, PHP_URL_PATH);
        // if this has a value, set cookie_path
        if (!empty($url_path)) {
            ini_set('session.cookie_path', $url_path);
        }

        // chage session name
        session_name('_s');

        // set the cache expire to 30 minutes / HTTP cache expiration time
        session_cache_expire(30);

        // start the session
        session_start();

        // get the user session
        $user_session = isset($_SESSION['user']) ? $_SESSION['user'] : false;

        // return the user session value
        return $user_session;

    }

    /**
     * Return true if the user exists (based on email in use)
     *
     * @param string $email
     * @return boolean true if user exists
     */
    public static function user_exists($email) {

        global $db;

        $lookup = user::lookup($email);

        // check
        $query = "SELECT id FROM " . TABLE_PREFIX . "users_meta WHERE meta_key='lookup' and meta_value='" . $lookup . "'";
        $lookup_check = $db->get_data_object($query);

        if (!empty($lookup_check)) {
            return true;
        } else {
            return false;
        }
    }

    public static function generate_password() {

        // anonymous function to generate password
        $random_password = function ($password = null) use (&$random_password) {
            $charset = "+-*#&@!?0123456789abcdefghijklmnopqrstuxyvwzABCDEFGHIJKLMNOPQRSTUXYVWZ";
            $newchar = substr($charset, mt_rand(0, (strlen($charset) - 1)), 1);
            if (strlen($password) == 8) {
                return $password;
            }
            return $random_password($password . $newchar);
        };

        // get a new random password
        return $random_password();
    }

    /**
     * Create a new user
     *
     * @param string $attributes array of attributes and values.  Must include email. If no password it will be generated
     * @param string $role_id the role_id.
     * @return integer the new user id, or 0 if an error
     */
    public static function create_user($role_id, $attributes) {

        if (empty($attributes['email'])) {
            return 0;
        }

        global $vce;

        $lookup = user::lookup($attributes['email']);

        $vector = $vce->user->create_vector();

        if (empty($attributes['password'])) {
            $attributes['password'] = user::generate_password();
        }

        // call to user class to create_hash function
        $hash = user::create_hash($attributes['email'], $attributes['password']);

        $user_data = array(
            'vector' => $vector,
            'hash' => $hash,
            'role_id' => $role_id,
        );

        $new_user_id = $vce->db->insert('users', $user_data);

        user::add_user_meta_data($new_user_id, $vector, $attributes);

        // the argument is treated as an integer, and presented as an unsigned decimal number.
        sscanf(crc32($attributes['email']), "%u", $front);
        sscanf(crc32(strrev($attributes['email'])), "%u", $back);
        // ilkyo id
        $ilkyo_id = $front . substr($back, 0, (14 - strlen($front)));

        $records = array();

        // add a lookup
        $records[] = array(
            'user_id' => $new_user_id,
            'meta_key' => 'lookup',
            'meta_value' => $lookup,
            'minutia' => $ilkyo_id,
        );

        $vce->db->insert('users_meta', $records);

        return $new_user_id;
    }

    /**
     * Update an existing user
     *
     * @param integer $user_id the user id.
     * @param string $attributes array of attributes and values.
     * @param string $role_id the role_id.
     * @return null
     */
    public static function update_user($user_id, $role_id, $attributes) {

        global $vce;

        $query = "SELECT role_id, vector FROM " . TABLE_PREFIX . "users WHERE user_id='" . $user_id . "'";
        $user_info = $vce->db->get_data_object($query);

        $current_role_id = $user_info[0]->role_id;
        $vector = $user_info[0]->vector;

        // has role_id been updated?
        if (isset($role_id) && $role_id != $current_role_id) {

            $update = array('role_id' => $role_id);
            $update_where = array('user_id' => $user_id);
            $vce->db->update('users', $update, $update_where);

        }

        // delete old meta data
        foreach ($attributes as $key => $value) {

            // delete user meta from database
            $where = array('user_id' => $user_id, 'meta_key' => $key);
            $vce->db->delete('users_meta', $where);

        }

        user::add_user_meta_data($user_id, $vector, $attributes);
    }

    /**
     * Delete a user
     *
     * @param integer $user_id
     * @return void
     */
    public static function delete_user($user_id) {

        global $vce;

        // delete user from database
        $where = array('user_id' => $user_id);
        $vce->db->delete('users', $where);

        // delete user from database
        $where = array('user_id' => $user_id);
        $vce->db->delete('users_meta', $where);
    }

    /**
     * Add meta data to existing user
     *
     * @param integer $user_id
     * @param string $vector
     * @param array $attributes
     * @return void
     */
    private static function add_user_meta_data($user_id, $vector, $attributes) {

        global $vce;

        $user_attributes = json_decode($vce->site->user_attributes, true);

        // start with default
        $meta_attributes = array('email' => 'text');

        // assign values into attributes for order preserving hash in minutia column
        if (isset($user_attributes)) {
            foreach ($user_attributes as $user_attributes_key => $user_attributes_value) {
                if (isset($user_attributes_value['sortable']) && $user_attributes_value['sortable']) {
                    $value = isset($user_attributes_value['type']) ? $user_attributes_value['type'] : null;
                    $meta_attributes[$user_attributes_key] = $value;
                }
            }
        }

        $records = array();

        foreach ($attributes as $key => $value) {

            // encode user data
            $encrypted = user::encryption($value, $vector);

            $minutia = null;

            // if this is a sortable text attribute
            if (isset($meta_attributes[$key])) {
                // check if this is a text field
                if ($meta_attributes[$key] == 'text') {
                    $minutia = user::order_preserving_hash($value);
                }
                // other option will go here
            }

            $records[] = array(
                'user_id' => $user_id,
                'meta_key' => $key,
                'meta_value' => $encrypted,
                'minutia' => $minutia,
            );

        }

        // check that $records is not empty
        if (!empty($records)) {
            $vce->db->insert('users_meta', $records);
        }
    }

    /**
     * Creates vector.
     * @return string encrypted unique vector
     */
    public static function create_vector() {
        if (OPENSSL_VERSION_NUMBER) {
            return base64_encode(openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc')));
        } else {
            return base64_encode(mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC), MCRYPT_DEV_URANDOM));
        }
    }

    /**
     * Get vector length
     * @return string vector length
     *
     * Note: If this changes, also update in vce-media.php
     */
    public static function vector_length() {
        if (OPENSSL_VERSION_NUMBER) {
            return openssl_cipher_iv_length('aes-256-cbc');
        } else {
            return mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);

        }
    }

    /**
     * Encrypts text with vector as salt
     * @param string $encode_text
     * @param string $vector
     * @return string
     */
    public static function encryption($encode_text, $vector) {
        if (isset($vector) && !empty($vector)) {
            if (OPENSSL_VERSION_NUMBER) {
                return base64_encode(openssl_encrypt($encode_text, 'aes-256-cbc', hex2bin(SITE_KEY), OPENSSL_RAW_DATA, base64_decode($vector)));
            } else {
                return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, hex2bin(SITE_KEY), $encode_text, MCRYPT_MODE_CBC, base64_decode($vector)));
            }
        }
    }

    /**
     * Decrypts text with vector as salt
     * @param string $decode_text
     * @param string $vector
     * @return string
     *
     * Note: If this changes, also update in vce-media.php
     */
    public static function decryption($decode_text, $vector) {
        if (isset($vector) && !empty($vector)) {
            if (OPENSSL_VERSION_NUMBER) {
                return trim(openssl_decrypt(base64_decode($decode_text), 'aes-256-cbc', hex2bin(SITE_KEY), OPENSSL_RAW_DATA, base64_decode($vector)));
            } else {
                return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, hex2bin(SITE_KEY), base64_decode($decode_text), MCRYPT_MODE_CBC, base64_decode($vector)));
            }
        }
    }

    /**
     * Creates hash of $email and $password
     * @param string $email
     * @param string $password
     * @return string encrypted $email and $password
     */
    public static function create_hash($email, $password) {

        // SITE_KEY
        // this constant is created at install and stored in vce-config.php
        // bin2hex(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));

        // get salt
        $user_salt = substr(hash('md5', str_replace('@', hex2bin(SITE_KEY), $email)), 0, 22);

        // combine credentials
        $credentials = $email . $password;

        // new hash value
        return crypt($credentials, '$2y$10$' . $user_salt . '$');

    }

    /**
     * Encrypts $email
     * @param string $email
     * @return string encrypted $email
     */
    public static function lookup($email) {

        // get salt
        $user_salt = substr(hash('md5', str_replace('@', hex2bin(SITE_KEY), $email)), 0, 22);

        // create lookup
        return crypt($email, '$2y$10$' . $user_salt . '$');

    }

    /**
     * Takes an email address and return a user id if user exists.
     * @param string $email
     * @return string $user_id
     */
    public static function email_to_id($email) {

        global $vce;

        // get lookup crypt for email
        $lookup = self::lookup($email);

        // get value
        $query = "SELECT user_id FROM  " . TABLE_PREFIX . "users_meta WHERE meta_key='lookup' AND meta_value='" . $lookup . "'";
        $user = $vce->db->get_data_object($query);

        // if user_id exists, return it, otherwise null
        return isset($user[0]->user_id) ? $user[0]->user_id : null;

    }

    /**
     * Gets users based on roles or user_ids and filters by meta_data
     * @param array $users_info
     * @return array $site_users
     */
    public static function get_users($users_info = array(), $key_by_user_id = false) {

        global $db;

        // convert pipeline to comma, and trim any comma that are out of place
        $user_ids = isset($users_info['user_ids']) ? trim(str_replace('|', ',', $users_info['user_ids']), ',') : null;
        $roles = isset($users_info['roles']) ? trim(str_replace('|', ',', $users_info['roles']), ',') : null;

        $site_users = array();

        if (isset($users_info['roles'])) {
            if ($users_info['roles'] == "all") {
                $query = "SELECT user_id, role_id, vector FROM " . TABLE_PREFIX . "users";
            } else {
                $query = "SELECT user_id, role_id, vector FROM " . TABLE_PREFIX . "users WHERE role_id in (" . $roles . ")";
            }
        } else if (isset($users_info['user_ids']) && !empty($users_info['user_ids'])) {
            $query = "SELECT user_id, role_id, vector FROM " . TABLE_PREFIX . "users WHERE user_id in (" . $user_ids . ")";
        } else {
            // nothing to look for so return false
            return false;
        }

        $all_users = $db->get_data_object($query);

        // remove user_id and roles if it's been sent
        unset($users_info['user_ids'], $users_info['roles']);

        // return false if results are empty
        if (empty($all_users)) {
            return false;
        }

        // rekey userdata
        foreach ($all_users as $each_user) {
            $users[$each_user->user_id]['user_id'] = $each_user->user_id;
            $users[$each_user->user_id]['role_id'] = $each_user->role_id;
            $users_vector[$each_user->user_id] = $each_user->vector;
        }

        // get all meta_data for selected users
        $query = "SELECT user_id, meta_key, meta_value FROM  " . TABLE_PREFIX . "users_meta WHERE meta_key NOT IN ('lookup','persistent_login') AND user_id IN (" . implode(',', array_keys($users)) . ")";
        $meta_data = $db->get_data_object($query);

        // add values to users array
        foreach ($meta_data as $meta_item) {
            // skip persistant_login
            if ($meta_item->meta_key == 'persistant_login') {
                continue;
            }
            $users[$meta_item->user_id][$meta_item->meta_key] = user::decryption($meta_item->meta_value, $users_vector[$meta_item->user_id]);
        }

        // return this as an object
        foreach ($users as $each_user) {
            // if key_by_user_id is true, then key array by user_id
            if ($key_by_user_id) {
                $users_list[$each_user['user_id']] = (object) $each_user;
            } else {
                $users_list[] = (object) $each_user;
            }
        }

        return $users_list;

    }

    /*
     * create order preserving hash
     * @param string $sring
     * @return $hash
     */
    public static function order_preserving_hash($string) {

        // get cipher
        $cipher = self::oph_cipher();

        // call and return hash output for string
        return self::oph_output($string, $cipher);

    }

    /*
     * create order preserving hash cipher
     * @return array $cipher
     */
    public static function oph_cipher() {

        // set the range
        $range = array_merge(range('0', '9'), range('a', 'z'));

        // to do: allow for a hook to change the value of $range

        // A "modular exponentiation” function, with a numerical starting point based on a site specific key, to assign a numerical value from 0 - 100 for the range of the alphabet.
        $mef = function ($previous, $counter = 0, $additional = 0, $total = 0, $cipher = array()) use (&$mef, $range) {

            // modulo is set to prime number
            $modulo = 101;

            // calculate the value of current
            $current = ($previous * 4) % $modulo;

            // if the value of current equals zero due to the tabulated_key value, reduce modulo by one and try again
            while ($current == 0) {
                $current = ($previous * 4) % $modulo--;
            }

            // get bracket that each range item can be
            $bracket = (99 / count($range)) + $additional;

            // get an obfuscated value within a range
            $location = ceil(($bracket * $current) / 99);

            // additional to add to next time through
            $new_additional = $bracket - $location;

            // add to array which will be returned
            $cipher[$range[$counter]] = $total + $location;

            // keep track of values
            $total += $location;

            // advance counter
            $counter++;

            // check to see if we should do a recursive call back to this function
            if ($counter < count($range)) {

                return $mef($current, $counter, $new_additional, $total, $cipher);

            } else {

                return $cipher;

            }

        };

        $tabulated_key = 0;

        // get ascii total for site_key to use as $previous to start
        for ($i = 0, $j = 64, $tab = 0; $i < $j; $i++) {
            $tabulated_key += ord(SITE_KEY[$i]);
        }

        // call to the annonymous function to get cipher values and return them
        return $mef($tabulated_key);

    }

    /*
     * create order preserving hash cipher
     * @param string $string
     * @return array $cipher
     * @return array $hashed
     */
    public static function oph_output($string, $cipher) {

        // can swap
        $string = str_replace('.', 'a', $string);

        // if (strpos($string = htmlentities($string, ENT_QUOTES, 'UTF-8'), '&') !== false) {
        //    $string = html_entity_decode(preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|tilde|uml);~i', '$1', $string), ENT_QUOTES, 'UTF-8');
        // }

        // get only the letters in lowercase
        $string = preg_replace('/[^\da-z]/i', '', strtolower($string));

        if (strlen($string) == 0) {

            return null;

        }

        // split the string into an array of individual letters
        $letters = str_split($string);

        $decistring = "";

        foreach ($letters as $each_letter) {

            $position = $cipher[$each_letter];

            // add a zero if under 10
            if ($position < 10) {
                $decistring .= '0';
            }

            $decistring .= (string) $position;

        }

        $grab = 4;

        while (strlen($decistring) % $grab != 0) {

            $decistring .= '0';

        }

        $limit = 30;

        if (strlen($decistring) < $limit) {

            $decistring .= '00' . rand(0, 9) . rand(0, 9);

            do {

                $decistring .= rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9);

            } while (strlen($decistring) < ($limit + 1));

        }

        // clip off anything extra
        $decistring = substr($decistring, 0, $limit);

        // split into grab values
        preg_match_all("/\d{" . $grab . "}/", $decistring, $grouplets);

        $full_value = "";

        $tabulated_key = 0;

        // get ascii total for site_key to use as $previous to start
        for ($i = 0, $j = 64, $tab = 0; $i < $j; $i++) {
            $tabulated_key += ord(SITE_KEY[$i]);
        }

        // 26 to the 4th power, minus the tabulated_key
        $field = 456976 - $tabulated_key;

        foreach ($grouplets[0] as $each_key => $each_value) {

            /*
            While it would be nice to skip every 2nd $grouplets to reduce the total length of the hashed value,
            there is an issue when dealing with decimal values resulting from aara and aarb, example:

            $names = array(
            'aara',
            'aarb'
            );

            values are too close together in the first group, so it can cause wrong sorting depending on site key value

            aara
            ->
            0404 <= 1st key
            6704 <= 2nd key
            18461.8304 <= 1
            30.6417990934 <= 2
            18492.4721991 <= total

            6704 <= 1st key
            0404 <= 2nd key
            306356.7104 <= 1
            1.846552332 <= 2
            306358.556952 <= total

            - - -

            aarb
            ->
            0404 <= 1st key
            6705 <= 2nd key
            18461.8304 <= 1
            30.6463697675 <= 2
            18492.4767698 <= total

            6705 <= 1st key
            0404 <= 2nd key
            306402.408 <= 1
            1.846552332 <= 2
            306404.254552 <= total

            // the skip every other would look like this
            if ($each_key % 2 != 0) {
            continue;
            }

             */

            // second_key is used to salt
            $second_key = ($each_key + 1) < count($grouplets[0]) ? $grouplets[0][$each_key + 1] : $grouplets[0][0];

            // echo $each_value . ' <= 1st key <br>';
            // echo $second_key . ' <= 2nd key<br>';
            //
            // echo ($field * ($each_value / 10000)) . ' <= 1<br>';
            // echo ($second_key / (9999 / ($field / 9999))) . ' <= 2<br>';

            $seccond_value = $second_key / (9999 / ($field / 9999));

            $number = ($field * ($each_value / 10000)) + $seccond_value;

            // echo $number . ' <= total<br><br>';

            $alphas = range('a', 'z');

            $divider = floor($number / 26);

            //echo $divider . '<br>';

            $primary = floor($divider / 676);

            //echo $primary . ' <= 1<br>';

            $secondary = floor($divider / 26) - ($primary * 26);

            //echo $secondary . ' <= 2<br>';

            // 676 = 26 * 26
            $tertiary = $divider - ($primary * 676) - ($secondary * 26);

            //echo $tertiary . ' <= 3<br>';

            // 17576 = 26 * 26 * 26
            $quaternary = $number - ($primary * 17576) - ($secondary * 676) - ($tertiary * 26);

            // echo $quaternary . ' <= 4<br>';
            // echo '- - -<br>';

            // prevent over extention on all of these
            $primary_number = ($primary < 26) ? $alphas[$primary] : $alphas[25];

            $secondary_number = ($secondary < 26) ? $alphas[$secondary] : $alphas[25];

            $tertiary_number = ($tertiary < 26) ? $alphas[$tertiary] : $alphas[25];

            $quaternary_number = ($quaternary < 26) ? $alphas[$quaternary] : $alphas[25];

            $quat = $primary_number . $secondary_number . $tertiary_number . $quaternary_number;

            //echo '=> ' . $quat . '<br>';

            $full_value .= $quat;

        }

        return $full_value;

    }

    /**
     * Allows for calling object properties from template pages in theme and then return or print them.
     * @param string $name
     * @param array $args
     */
    public function __call($name, $args) {
        if (isset($this->$name)) {
            if ($args) {
                // return object property
                return $this->$name;
            } else {
                // print object property
                echo $this->$name;
            }
        } else {
            if (!VCE_DEBUG) {
                return false;
            } else {
                // print name of none existant component
                echo 'Call to non-existant property ' . '$' . strtolower(get_class()) . '->' . $name . '()' . ' in ' . debug_backtrace()[0]['file'] . ' on line ' . debug_backtrace()[0]['line'];
            }
        }
    }

    /**
     * Returns false instead of "Notice: Undefined property error" when reading data from inaccessible properties
     */
    public function __get($var) {
        return false;
    }

}