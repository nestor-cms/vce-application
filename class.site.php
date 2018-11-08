<?php
/**
 * Creates object to hold site data.
 * Retrieves site meta data and calls the components listed there to create the login,
 * and other components relevant to the entire site.
 */

class Site {

    /**
     * Instantiates site object
     * In addition to the Login, builds a $hooks array, preloads javascript, css, themes,
     * and other layout-directing information.
     */
    public function __construct($vce) {

        // load vce object and add this property to it
        // global $vce;
        $vce->site = $this;

        // check that memory_limit is at least set to 40M
        if (vce::convert_to_bytes(ini_get('memory_limit')) < 41943040) {
            @ini_set('memory_limit', '40M');
        }

        // Set timezone
        if (defined('TIMEZONE')) {
            date_default_timezone_set(TIMEZONE);
        } elseif (!ini_get('date.timezone')) {
            date_default_timezone_set('UTC');
        }

        // for site object key => values
        $site = array();

        // array for any cron_tasks that might exist
        $cron_task = array();

        $query = "SELECT * FROM " . TABLE_PREFIX . "site_meta";
        $components_meta = $vce->db->get_data_object($query);

        foreach ($components_meta as $each_meta) {

            // cron_task
            if ($each_meta->meta_key == "cron_task") {

                // timestamp of cron_task is older than current time
                if ($each_meta->minutia < time()) {

                    // add to array using timestamp as key
                    $cron_task[$each_meta->minutia] = array(
                        'id' => $each_meta->id,
                        'value' => $each_meta->meta_value,
                    );

                }

                // skip to next
                continue;
            }

            // create hierarchical json object of roles and place into into $site->site_roles
            if ($each_meta->meta_key == "roles") {
                // get and decode roles that are stored in database
                $roles = json_decode($each_meta->meta_value, true);

                // create a hierarchical roles array
                foreach ($roles as $roles_key => $roles_value) {
                    // move the range up to tens
                    if (isset($roles_value['role_hierarchy'])) {
                        $current_hierarchy = ($roles_value['role_hierarchy'] * 100);
                        while (isset($roles_hierarchical[$current_hierarchy])) {
                            // if the current key exists, add one and see if that exists
                            $current_hierarchy++;
                        }
                        $roles_hierarchical[$current_hierarchy][$roles_key] = $roles[$roles_key];
                    }
                }

                if (isset($roles_hierarchical)) {
                    // ksort by keys asc / krsort by keys desc
                    ksort($roles_hierarchical);
                    // rekey to make it look nice
                    $roles_hierarchical = array_values($roles_hierarchical);
                    // cast as object
                    $this->site_roles = json_encode((object) $roles_hierarchical);
                }
            }

            $key = $each_meta->meta_key;

            $site[$key] = $each_meta->meta_value;

            if (!empty($each_meta->minutia)) {
                $minutia = $key . "_minutia";
                $site[$minutia] = $each_meta->minutia;
            }

        }

        // rekey the object
        foreach ($site as $key => $value) {
            $this->$key = $value;
        }

        // ignore database field and set site_url from $_SERVER Server and execution environment information
        if (defined('DYNAMIC_SITE_URL') && DYNAMIC_SITE_URL === true) {

            $ssl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
            $sp = strtolower($_SERVER['SERVER_PROTOCOL']);
            $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
            $port = $_SERVER['SERVER_PORT'];
            $port = ((!$ssl && $port == '80') || ($ssl && $port == '443')) ? '' : ':' . $port;
            $host = (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null);
            $host = isset($host) ? $host : $_SERVER['SERVER_NAME'] . $port;

            // are we installed in a sub-directory?
            $directory = null;
            if (!empty($_SERVER['DOCUMENT_ROOT']) && !empty($_SERVER['SCRIPT_FILENAME'])) {
                // directory is the difference
                $directory = str_replace($_SERVER['DOCUMENT_ROOT'], '', str_replace('/index.php', '', $_SERVER['SCRIPT_FILENAME']));
            }

            // set site_url to dynamic version
            $this->site_url = $protocol . '://' . $host . $directory;

        }

        // load Component class
        require_once 'class.component.php';

        // declare array for hooks
        $hooks = array();

        // preload components listed in $site->preload_components that have some sort of effect on layout et cetera
        foreach (json_decode($this->preloaded_components, true) as $type => $path) {
            // check that this component hasn't been disabled
            if (isset(json_decode($this->activated_components, true)[$type])) {
                require_once BASEPATH . $path;
                $each_component = new $type();
                // call to preload function and cycle through returned array to add hooks to variable
                $preload_component = $each_component->preload_component();

                // error prevention if nothing is returned, and allows method to be used for other things besides hooks
                if (!empty($preload_component)) {
                    foreach ($preload_component as $hook_name => $instructions) {
                        $priority = -1;
                        $function = '';
                        if (!isset($hooks[$hook_name])) {
                            $hooks[$hook_name] = array();
                        }

                        if (is_array($instructions)) {
                            $priority = $instructions['priority'];
                            $function = $instructions['function'];
                        } else {
                            $function = $instructions;
                        }

                        // if the hook calls "at_site_hook_initiation"
                        if ($hook_name == "site_hook_initiation") {
                            call_user_func($function, $hooks);
                        }

                        if ($priority > -1) {
                            while (isset($hooks[$hook_name][$priority])) {
                                $priority++;
                            }
                            $hooks[$hook_name][$priority] = $function;
                        } else {
                            array_push($hooks[$hook_name], $function);
                        }

                        // add to array of hooks
                        $hooks[$hook_name][] = $function;
                    }
                }
            }
        }

        // sort
        foreach ($hooks as $key => $hook) {
            ksort($hook);
            $hooks[$key] = $hook;
        }

        // add hooks to site object
        $this->hooks = $hooks;

        // process cron_tasks
        if (!empty($cron_task)) {

            // set the number of cron tasks to process each time
            $cron_task_limit = defined('CRON_TASK_LIMIT') ? CRON_TASK_LIMIT : 1;

            while ($cron_task_limit > 0 && !empty($cron_task)) {

                // shift off the first array element
                $each_cron_task = array_shift($cron_task);

                // decode json object
                $cron_info = json_decode($each_cron_task['value'], true);

                // get list of activated components
                $activated_components = json_decode($this->activated_components, true);

                // check that the cron_task component is activated
                if (isset($activated_components[$cron_info['component']])) {

                    // path to component
                    $component_path = BASEPATH . $activated_components[$cron_info['component']];

                    if (file_exists($component_path)) {

                        // load this ccomponent class
                        require_once $component_path;

                        $class_to_call = $cron_info['component'];
                        $method_to_call = $cron_info['method'];

                        // call to a static method for the component class
                        $response = $class_to_call::$method_to_call($each_cron_task);

                        // to have cron_tasks be updatable before the site object is complete, a returned value is needed
                        // check that a response was returned
                        if (!empty($response)) {
                            // send the returned array from the class::method called to within the cron_task
                            $vce->manage_cron_task($response);
                        } elseif (defined('VCE_DEBUG') && VCE_DEBUG == true) {
                            // die with an error message
                            die('cron_task error: nothing returned to class.site.php when calling to ' . $cron_info['component'] . '::' . $cron_info['method'] . '(' . print_r($each_cron_task, true) . ')');
                        }

                    } else {

                        // delete this cron_task because component is not installed
                        if (defined('VCE_DEBUG') && VCE_DEBUG == true) {
                            // die with an error message
                            die('cron_task error: component does not exist for ' . $cron_info['component'] . '::' . $cron_info['method'] . '(' . print_r($each_cron_task, true) . ')');
                        } else {
                            // delete this cron_task
                            $vce->manage_cron_task(array('action' => 'delete', 'id' => $each_cron_task['id']));
                        }

                    }

                } else {

                    // delete this cron_task because component is not activated
                    if (defined('VCE_DEBUG') && VCE_DEBUG == true) {
                        // die with an error message
                        die('cron_task error: component has not been activated for ' . $cron_info['component'] . '::' . $cron_info['method'] . '(' . print_r($each_cron_task, true) . ')');
                    } else {
                        // delete this cron_task
                        $vce->manage_cron_task(array('action' => 'delete', 'id' => $each_cron_task['id']));
                    }

                }

                // deduct one from the counter
                $cron_task_limit--;
            }

        }

        // add site_url to vce object
        $vce->site_url = $this->site_url;

        // check if ASSETS_URL has been defined in vce-config, otherwise use site_url
        $site_url = defined('ASSETS_URL') ? ASSETS_URL : $this->site_url;

        // site_object_construct

        // add theme path for templates
        $this->theme_path = $site_url . "/vce-content/themes/" . $this->site_theme;

        // load hooks
        // site_object_construct
        if (isset($this->hooks['site_object_construct'])) {
            foreach ($this->hooks['site_object_construct'] as $hook) {
                call_user_func($hook, $this);
            }
        }

        // list of javascript dependencies
        $this->javascript_dependencies = array(
            // list of javascript dependencies
            'scripts' => array(
                'jquery' => $site_url . '/vce-application/js/jquery/jquery.min.js',
                'jquery-ui' => $site_url . '/vce-application/js/jquery/jquery-ui.min.js',
                'select2' => $site_url . '/vce-application/js/jquery/select2.min.js',
                'tablesorter' => $site_url . '/vce-application/js/jquery/jquery.tablesorter.min.js',
                'tabletocard' => $site_url . '/vce-application/js/jquery/tabletocard.js',
            ),
            // list of css associated with dependencies
            'styles' => array(
                'jquery' => null,
                'jquery-ui' => $site_url . '/vce-application/css/jquery/jquery-ui.min.css',
                'select2' => $site_url . '/vce-application/css/jquery/select2.css',
                'tablesorter' => $site_url . '/vce-application/css/jquery/tablesorter.css',
                'tabletocard' => $site_url . '/vce-application/css/jquery/tabletocard.css',
            ),
        );

        // load hooks
        // site_javascript_dependencies
        if (isset($this->hooks['site_javascript_dependencies'])) {
            foreach ($this->hooks['site_javascript_dependencies'] as $hook) {
                $this->javascript_dependencies = call_user_func($hook, $this, $this->javascript_dependencies);
            }
        }

        // prevent caching
        $ver = time();

        // optional constant in vce-config that allows for another location to be used for javascript
        if (defined('PATH_TO_BASE_JAVASCRIPT')) {
            // add vce javascript
            $this->add_script($site_url . '/' . PATH_TO_BASE_JAVASCRIPT . '/vce.js?ver=' . $ver, 'jquery');
        } else {
            // add vce javascript
            $this->add_script($site_url . '/vce-application/js/vce.js?ver=' . $ver, 'jquery');
        }

        // optional constant in vce-config that allows for another location to be used for stylesheet
        if (defined('PATH_TO_BASE_STYLESHEET')) {
            // add vce javascript
            $this->add_style($site_url . '/' . PATH_TO_BASE_STYLESHEET . '/vce.css?ver=' . $ver, 'vce');
        } else {
            // add vce stylesheet
            $this->add_style($site_url . '/vce-application/css/vce.css?ver=' . $ver, 'vce');
        }

        // push out attributes into vce object that have been saved into session
        $this->obtrude_attributes($vce);

    }

    /**
     * Adds theme.php for selected theme.
     * @return include
     */
    public function add_theme_functions() {

        // allow for user site objects to be referenced
        // global $user;
        // global $site;

        // load theme functions
        include_once BASEPATH . 'vce-content/themes/' . $this->site_theme . '/theme.php';

    }

    /**
     * Creates an array of template names with file paths.
     * @return array
     */
    public function get_template_names() {

        $templates = array();

        // http://php.net/manual/en/class.directoryiterator.php
        foreach (new DirectoryIterator(BASEPATH . "vce-content/themes/" . $this->site_theme) as $key => $fileInfo) {

            // check for dot files and directories
            if ($fileInfo->isDot() || $fileInfo->isDir()) {
                continue;
            }

            // find .php files but exclude theme.php file
            if (preg_match('/^.*\.php$/i', $fileInfo->getFilename()) && $fileInfo->getFilename() != 'theme.php') {

                // full path
                $full_path = BASEPATH . "vce-content/themes/" . $this->site_theme . "/" . $fileInfo->getFilename();

                // search for template name in first 100 chars
                preg_match('/Template Name:(.*)$/mi', file_get_contents($full_path, NULL, NULL, 0, 100), $header);

                // if theme name is set
                if (isset($header['1'])) {
                    $templates[trim($header['1'])] = $fileInfo->getFilename();
                } else {
                    // otherwise file names
                    $templates[$fileInfo->getFilename()] = $fileInfo->getFilename();
                }

            }
        }

        return $templates;
    }

    /**
     * Adds javascript property to site object.
     * @param string $path
     * @param string $dependencies
     * @param bool $footer
     * @global object $content
     * @return adds script to object $content
     */
    public function add_script($path, $dependencies = null, $footer = false) {

        global $vce;

        // if this class is being called without other classes being loaded first
        if (!isset($vce->content)) {
            return;
        }

        // prevent caching
        $ver = time();

        // check if $path starts with http
        if (substr($path, 0, 4) != 'http') {

            // check if ASSETS_URL has been defined in vce-config, otherwise use site_url
            $site_url = defined('ASSETS_URL') ? ASSETS_URL : $this->site_url;

            // get the base path by using getcwd, or if that returns false, use BASEPATH
            $base = (getcwd() !== false && !defined('ASSETS_URL')) ? getcwd() : BASEPATH;

            // create the URI path to the document
            $path = $site_url . '/' . ltrim(str_replace('\\', '/', str_replace($base, '', $path)), '/');

        }

        // list of javascript dependencies
        $scripts = $this->javascript_dependencies['scripts'];

        // list of css associated with dependencies
        $styles = $this->javascript_dependencies['styles'];

        if (isset($dependencies)) {
            $dependent = preg_split("/[\s,]+/", trim($dependencies));
        }

        if (!empty($dependent)) {

            foreach ($dependent as $each_dependent) {

                if (isset($scripts[$each_dependent]) && strpos($vce->content->javascript, $scripts[$each_dependent]) === false) {

                    $vce->content->javascript .= '<script type="text/javascript" src="' . $scripts[$each_dependent] . '?ver=' . $ver . '"></script>' . PHP_EOL;

                    if (isset($styles[$each_dependent])) {
                        self::add_style($styles[$each_dependent], $each_dependent . '-style');
                    }

                }

            }
        }

        // check that javascript for component has not already been added
        if (strpos($vce->content->javascript, $path) === false && strpos($vce->content->javascript_footer, $path) === false) {

            if ($footer) {
                $vce->content->javascript_footer .= '<script type="text/javascript" src="' . $path . '?ver=' . $ver . '"></script>' . PHP_EOL;
            } else {
                $vce->content->javascript .= '<script type="text/javascript" src="' . $path . '?ver=' . $ver . '"></script>' . PHP_EOL;
            }

        }

    }

    /**
     * Adds stylesheet property to contents object.
     * @param string $path
     * @param string $name
     * @param string $media
     * @global object $content
     * @return adds CSS to object $content
     */
    public function add_style($path, $name = null, $media = 'all') {

        global $vce;

        // if this class is being called without other classes being loaded first
        if (!isset($vce->content)) {
            return;
        }

        // prevent caching
        $ver = time();

        // check if $path starts with http
        if (substr($path, 0, 4) != 'http') {

            // check if ASSETS_URL has been defined in vce-config, otherwise use site_url
            $site_url = defined('ASSETS_URL') ? ASSETS_URL : $this->site_url;

            // get the base path by using getcwd, or if that returns false, use BASEPATH
            $base = (getcwd() !== false && !defined('ASSETS_URL')) ? getcwd() : BASEPATH;

            // create the URI path to the document
            $path = $site_url . '/' . ltrim(str_replace('\\', '/', str_replace($base, '', $path)), '/');

        }

        // check that stylesheet for component has not already been added
        if (strpos($vce->content->stylesheet, $path) === false) {
            $vce->content->stylesheet .= '<link rel="stylesheet" type="text/css" id="' . $name . '" href="' . $path . '?ver=' . $ver . '" type="text/css" media="' . $media . '">' . PHP_EOL;
        }

    }

    /**
     * Generates the media link for file
     * @param array $fileinfo
     * @return string of media URL for link
     */
    public function media_link($fileinfo) {

        // check if ASSETS_URL has been defined in vce-config, otherwise use site_url
        $site_url = defined('ASSETS_URL') ? ASSETS_URL : $this->site_url;

        $path_to_uploads = defined('PATH_TO_UPLOADS') ? PATH_TO_UPLOADS : 'vce-content/uploads';

        // by default media_link points to upload location
        $media_link = $site_url . '/' . $path_to_uploads . '/' . $fileinfo['path'];

        // a hook to modify
        if (isset($this->hooks['site_media_link'])) {
            foreach ($this->hooks['site_media_link'] as $hook) {
                $media_link = call_user_func($hook, $fileinfo, $this);
            }
        }

        return $media_link;

    }

    /**
     * push out attributes that have been saved into the $vce object
     */
    public function obtrude_attributes($vce) {

        // site_obtrude_attributes hook
        if (isset($vce->site->hooks['site_obtrude_attributes'])) {
            foreach ($vce->site->hooks['site_obtrude_attributes'] as $hook) {
                call_user_func($hook, $vce);
            }
        }

        if (isset($_SESSION)) {
            // check for session attributes saved previously
            if (isset($_SESSION['add_attributes'])) {
                foreach (json_decode($_SESSION['add_attributes'], true) as $key => $value) {
                    // if there is a persistent value set
                    if ($key == 'persistent') {
                        $persistent = $value;
                        foreach ($persistent as $persistent_key => $persistent_value) {
                            $vce->$persistent_key = $persistent_value;
                        }
                    } else {
                        // normal value
                        $vce->$key = $value;
                    }
                }
                // clear it
                unset($_SESSION['add_attributes']);
                // rewrite if persistent value had been set
                if (isset($persistent)) {
                    $_SESSION['add_attributes'] = json_encode(array('persistent' => $persistent));
                }
            }
        }

    }

    /**
     * Adds attributes that will be added to the page object on next page load.
     * If persistent, then attribute will stay until deleted or session has ended.
     * @param string $key
     * @param string $value
     * @param bool $persistent
     * @return adds JSON object of attributes to add
     */
    public function add_attributes($key, $value, $persistent = false) {

        global $vce;

        // site_add_attributes hook
        if (isset($vce->site->hooks['site_add_attributes'])) {
            foreach ($vce->site->hooks['site_add_attributes'] as $hook) {
                call_user_func($hook, $key, $value, $persistent);
            }
        }

        if (isset($_SESSION)) {
            // get current value of 'add_attributes'
            if (isset($_SESSION['add_attributes'])) {
                $add_attributes = json_decode($_SESSION['add_attributes'], true);
            } else {
                $add_attributes = array();
            }
            if ($persistent) {
                // add to persistent sub array
                $add_attributes['persistent'][$key] = $value;
            } else {
                // add as normal
                $add_attributes[$key] = $value;
            }
            $_SESSION['add_attributes'] = json_encode($add_attributes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
        }
    }

    /**
     * retrieve attributes by key
     * @param string $key
     * @return value for key
     */
    public function retrieve_attributes($key) {

        global $vce;

        // site_add_attributes hook
        if (isset($vce->site->hooks['site_retrieve_attributes'])) {
            foreach ($vce->site->hooks['site_retrieve_attributes'] as $hook) {
                call_user_func($hook, $key);
            }
        }

        if (isset($_SESSION)) {
            // check that add_attributes is in session
            if (isset($_SESSION['add_attributes'])) {
                // get array of keys
                $attributes = json_decode($_SESSION['add_attributes'], true);
                // if the key exists return it
                if (isset($attributes[$key])) {
                    return $attributes[$key];
                }
                // if the persistent key exists return it
                if (isset($attributes['persistent'][$key])) {
                    return $attributes['persistent'][$key];
                }
            }

        }
        return null;
    }

    /**
     * removes attributes from next page load.
     * @param string $key
     * @return JSON object of attributes
     */
    public function remove_attributes($key) {

        global $vce;

        // site_add_attributes hook
        if (isset($vce->site->hooks['site_remove_attributes'])) {
            foreach ($vce->site->hooks['site_remove_attributes'] as $hook) {
                call_user_func($hook, $key);
            }
        }

        if (isset($_SESSION)) {
            if (isset($_SESSION['add_attributes'])) {
                $attributes = json_decode($_SESSION['add_attributes'], true);
                unset($attributes[$key], $attributes['persistent'][$key]);
                $_SESSION['add_attributes'] = json_encode($attributes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            }
        }
    }

    /**
     * Checks if a url has already been assigned to another component
     * @param string $url
     * @return string of $clean_url
     */
    public function url_checker($url) {

        global $vce;

        // clean the url using preg_replace. This can also be done in the javascript by using:
        // .replace(/[^\w\d\/]+/gi,'-').toLowerCase();
        $clean_url = trim(strtolower(preg_replace("/[^\w\d\/]+/i", "-", $url)), '-/');

        // get component that has been assigned this url
        $query = "SELECT * FROM " . TABLE_PREFIX . "components WHERE url='" . $clean_url . "'";
        $existing_url = $vce->db->get_data_object($query);

        if (empty($existing_url)) {
            return $clean_url;
        }
        // recursive call back to self to check variation
        return self::url_checker($clean_url . '-2');
    }

    /**
     * Add new roles to the site
     * @param array $attributes
     * @global object $db
     * @global object $site
     *
     * $attributes = array (
     * array (
     * 'role_name' => '*new_role_name*',
     * 'role_hierarchy' => '*new_role_hierarchy*',
     * 'permissions' => '*component_specific_permisions*'
     * ),
     * array (
     * 'role_name' => '*new_role_name*'
     * )
     * );
     */
    public function add_site_roles($attributes) {

        global $vce;

        // find out which class is calling to this method
        $trace = debug_backtrace();
        // our calling class is:
        $calling_class = $trace[1]['class'];
        // cycle through current to create an array to check against for existing role_names
        $site_roles = json_decode($vce->site->roles, true);
        foreach ($site_roles as $each_current) {
            $current_roles[strtolower($each_current['role_name'])] = true;
        }
        // cycle through new
        foreach ($attributes as $each_addition) {
            if (isset($each_addition['role_name'])) {
                // check if role_name already exists
                if (!isset($current_roles[strtolower($each_addition['role_name'])])) {
                    // new array each time though
                    $new_role = array();
                    $new_role['role_name'] = $each_addition['role_name'];
                    // check if permissions and then add
                    $new_role['role_hierarchy'] = isset($each_addition['role_hierarchy']) ? $each_addition['role_hierarchy'] : 0;
                    if (isset($each_addition['permissions'])) {
                        $new_role['permissions'] = array(
                            $calling_class => $each_addition['permissions'],
                        );
                    }
                    // add new role to site_roles
                    $site_roles[] = $new_role;
                }
            }
        }
        // update site_roles in site_meta table
        $update = array('meta_value' => json_encode($site_roles));
        $update_where = array('meta_key' => 'roles');
        $vce->db->update('site_meta', $update, $update_where);

    }

    /**
     * Creates a URN string from input.
     * @param string $input
     * @return string
     * @toDo actually this should be clean_path or something like that.
     */
    public function create_path($input) {
        return preg_replace('/[\W\s]+/mi', '-', str_replace('/', '-', strtolower($input)));
    }

    /**
     * Gets the url path to the component
     * @param string $filepath
     * @global object $site
     * @return string $path
     */
    public static function path_to_url($filepath) {

        global $vce;

        // check if ASSETS_URL has been defined in vce-config, otherwise use site_url
        $site_url = defined('ASSETS_URL') ? ASSETS_URL : $vce->site->site_url;

        $path = str_replace(BASEPATH, $site_url . '/', $filepath);
        return $path;
    }

    /**
     * call to method in class.user.php
     */
    public static function create_vector() {
        global $user;
        return $user->create_vector();
    }

    /**
     * call to method in class.user.php
     */
    public static function encryption($encode_text, $vector) {
        global $user;
        return $user->encryption($encode_text, $vector);
    }

    /**
     * call to method in class.user.php
     */
    public static function decryption($decode_text, $vector) {
        global $user;
        return $user->decryption($decode_text, $vector);
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