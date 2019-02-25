<?php
/**
 * The class that creates the foundation object within Nestor
 *
 * notes: Nothing from class.db.php, class.user.php needs moving over
 */
class VCE {

    /**
     * Create encrypted dossier and return it as a string
     * This is a shortcut method for the following
     * $user->encryption(json_encode(array('type' => 'ManageSite','procedure' => 'update')),$user->session_vector);
     *
     * @param object $dossier_elements
     * @return string
     */
    public function generate_dossier($dossier_elements_input) {

        // cast to array
        $dossier_elements = (array) $dossier_elements_input;

        // clean-up nulls and any empty array
        foreach ($dossier_elements as $dossier_name => $dossier_value) {
            if (empty($dossier_value) || is_null($dossier_value)) {
                unset($dossier_elements[$dossier_name]);
            }
        }

        // encrypt dossier with session_vector for user
        return $this->user->encryption(json_encode($dossier_elements), $this->user->session_vector);

    }

    /**
     * A method to check component specific permissions
     *
     * @param string $permission_name
     * @param string $component_name
     * @return bool
     */
    public function check_permissions($permission_name, $component_name = null) {

        // find the calling class by using debug_backtrace
        if (!$component_name) {
            $backtrace = debug_backtrace(false, 2);
            $component_name = $backtrace[1]['class'];
        }
        // add permissions onto the component name
        $component_permissions = $component_name . '_permissions';
        if (in_array($permission_name, explode(',', $this->user->$component_permissions))) {
            return true;
        }
        return false;
    }

    /**
     * A method to check component specific configurations
     *
     * @param string $permission_name
     * @param string $component_name
     * @return bool
     */
    public function check_configuration($configuration_name, $component_name = null) {

        // find the calling class by using debug_backtrace
        if (!$component_name) {
            $backtrace = debug_backtrace(false, 2);
            $component_name = $backtrace[1]['class'];
        }

        // get the config file and save it, or check in the saved version for the configuration name
        if (isset($this->site->$component_name) && !is_array($this->site->$component_name)) {
            $value = $this->site->$component_name;
            $minutia = $component_name . '_minutia';
            $vector = $this->site->$minutia;
            $config = json_decode($this->site->decryption($value, $vector), true);
            $this->site->$component_name = $config;
        } else {
            $config = $this->site->$component_name;
        }

        if (isset($config[$configuration_name])) {
            return $config[$configuration_name];
        }

        return false;

    }

    /**
     * Sends mail using PHP mail function or transport agent
     * @param array $attributes
     * SITE_MAIL = false will prevent mail from being sent
     * attributes to send to mail
     *
     * $attributes = array (
     * 'from' => array('*email*', '*name*'),
     * 'to' => array(
     * array('*email*', '*name*'),
     * array('*email*', '*name*')
     * ),
     * 'subject' => '*subject*',
     * 'message' => '*copy*'
     * );
     *
     * @return notice of mail failure or silent success
     */
    public function mail($attributes) {

        if (!defined('SITE_MAIL') || SITE_MAIL == true) {

            global $vce;

            // load hooks
            // site_mail_transport
            if (isset($vce->site->hooks['site_mail_transport'])) {
                foreach ($vce->site->hooks['site_mail_transport'] as $hook) {
                    $status = call_user_func($hook, $vce, $attributes);
                }
            } else {

                // PHP mail function
                // http://php.net/manual/en/function.mail.php

                // create a new object
                $mail = new stdClass();

                foreach ($attributes as $key => $value) {
                    if (is_array($value)) {
                        $each_values = array_values($value);
                        if (is_array($each_values[0])) {
                            foreach ($each_values as $sub_key => $sub_value) {
                                $address = isset($sub_value[0]) ? $sub_value[0] : null;
                                $name = isset($sub_value[1]) ? $sub_value[1] : null;
                                // call
                                $mail->$key .= trim($name) . ' <' . $address . '>,';
                            }
                        } else {
                            $address = isset($each_values[0]) ? $each_values[0] : null;
                            $name = isset($each_values[1]) ? $each_values[1] : null;
                            // call
                            $mail->$key = trim($name) . ' <' . $address . '>';
                        }
                    } else {
                        $mail->$key = trim($value);
                    }
                }

                if (isset($attributes['html'])) {
                    // To send HTML mail, the Content-type header must be set
                    $headers[] = 'MIME-Version: 1.0';
                    $headers[] = 'Content-type: text/html; charset=iso-8859-1';
                    $mail->message = html_entity_decode(stripcslashes($mail->message));
                }

                // array to translate methods from vce to mail function
                $translate = array(
                    'from' => 'From',
                    'to' => 'To',
                    'reply' => 'Reply-To',
                    'cc' => 'Cc',
                    'bcc' => 'Bcc',
                );

                // create header
                foreach ($translate as $input => $output) {
                    if (isset($mail->$input) && $input != 'to') {
                        $headers[] = $output . ': ' . trim($mail->$input, ",");
                    }
                }

                // PHP mail function
                mail(trim($mail->to, ","), $mail->subject, $mail->message, implode("\r\n", $headers));

            }

            return true;

        } else {

            return false;

        }

    }

    /**
     * sort an object or array by associative key
     *
     * @param object/array $data
     * @param string $key
     * @param string $order
     * @param string $type
     * @return object/array
     */
    public function sorter($data, $key = 'title', $order = 'asc', $type = 'string') {

        if (is_array($data)) {
            usort($data, function ($a, $b) use ($key, $order, $type) {

                // check if this is an object or an array
                if (is_object($a)) {
                    $a_sort = isset($a->$key) ? $a->$key : null;
                } else {
                    $a_sort = isset($a[$key]) ? $a[$key] : null;
                }

                if (is_object($b)) {
                    $b_sort = isset($b->$key) ? $b->$key : null;
                } else {
                    $b_sort = isset($b[$key]) ? $b[$key] : null;
                }

                if (isset($a_sort) && isset($b_sort)) {

                    // sort as string
                    if ($type == 'string') {

                        if ($order == "asc") {
                            return (strcmp($a_sort, $b_sort) > 0) ? 1 : -1;
                        } else {
                            return (strcmp($a_sort, $b_sort) > 0) ? -1 : 1;
                        }

                    } elseif ($type == 'time') {

                        // sort as time
                        if ($order == "asc") {
                            return strtotime($a_sort) > strtotime($b_sort) ? 1 : -1;
                        } else {
                            return strtotime($a_sort) > strtotime($b_sort) ? -1 : 1;
                        }

                    } elseif ($type == 'integer') {

                        // sort as time
                        if ($order == "asc") {
                            return (integer) $a_sort > (integer) $b_sort ? 1 : -1;
                        } else {
                            return (integer) $a_sort > (integer) $b_sort ? -1 : 1;
                        }

                    } else {

                        if ($order == "asc") {
                            return -1;
                        } else {
                            return 1;
                        }
                    }

                } else {

                    if ($order == "asc") {
                        return -1;
                    } else {
                        return 1;
                    }
                }
            });

            // return the sorted object/array
            return $data;
        }

        return null;
    }

    /**
     * a function that converts to bytes
     * @param string $size
     * @return int of bytes
     */
    public function convert_to_bytes($size) {
        $size = strtolower($size);
        $bytes = (int) $size;
        preg_match('/\d+([a-z]+)/', $size, $matches);
        $unit = array(
            'k' => 1024,
            'kb' => 1024,
            'm' => 1024 * 1024,
            'mb' => 1024 * 1024,
            'g' => 1024 * 1024 * 1024,
            'gb' => 1024 * 1024 * 1024,
        );

        if (isset($unit[$matches[1]])) {
            $bytes = intval($size) * $unit[$matches[1]];
        }

        return $bytes;
    }

    /**
     * a function that converts from bytes to a nice readable size
     * @param string $size
     * @return int of bytes
     */
    public function convert_from_bytes($size) {
        $sz = 'BKMGTP';
        $decimals = 2;
        $factor = floor((strlen($size) - 1) / 3);
        $nice_size = sprintf("%.{$decimals}f", $size / pow(1024, $factor)) . @$sz[$factor];

        return $nice_size;
    }
    
    /**
     * a function to gather vce error messages
     * @param string $error
     * @param object $vce
     */
    public function add_errors($error, $vce) {
    	if (!isset($vce->errors)) {
    		$vce->errors = array();
    	}
    	// add values to errors array
    	$vce->errors[] = $error;
    }
    
    /**
     * a function to output vce error messages
     * @param object $vce
     */    
     public function display_errors($vce) {
		// check that errors exist
    	if (isset($vce->errors)) {
    		foreach ($vce->errors as $error) {
       			echo '<div class="vce-error-message">' . $error . '</div>';
    		}
    	}
    }

   /**
     * Allows components to add functions to the $vce object dynamically.
     *
     * @param string $name
     * @param array $args
     */
	public function __call($name, $args) {
		
		// if method or propery is found
		if (isset($this->$name)) {
			if (is_string($this->$name)) {
				// print the propery and return
				echo $this->$name;
				return;
			} else {
				// if method then call to it
                if ($args) {
                    return call_user_func_array($this->$name, $args);
                } else {
                    return call_user_func($this->$name);
                }
			}
		}
	
		global $vce;
	
		// hook to add additional methods or properties
        if (isset($vce->site->hooks['vce_call_add_functions'])) {
            foreach ($vce->site->hooks['vce_call_add_functions'] as $hook) {
                call_user_func($hook, $vce);
            }
        }
        
        // if the method or propery now exists after the hook has been called to, then call back
        if (isset($this->$name)) {
			self::__call($name, $args);
        } else {
			if (!VCE_DEBUG) {
				return false;
			} else {
				// print name of none existant component
				$vce->add_errors('Call to non-existant method/property ' . '$' . strtolower(get_class()) . '->' . $name . '()'  . ' in ' . debug_backtrace()[0]['file'] . ' on line ' . debug_backtrace()[0]['line']);
			}
		}
		
	}

    /**
     * Magic function to convert static function calls to non-static and use __call functionality above
     *
     * @param [type] $name
     * @param [type] $args
     * @return void
     */
    public static function __callStatic($name, $args) {

        global $vce;
        return $vce->__call($name, $args);
    }

    /**
     * Returns false instead of "Notice: Undefined property error" when reading data from inaccessible properties
     */
    public function __get($var) {
        return false;
    }

}