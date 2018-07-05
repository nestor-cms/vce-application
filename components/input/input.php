<?php

class Input extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Input',
			'description' => 'Asynchronous form input portal',
			'category' => 'admin'
		);
	}

	/**
	 * things to do when this component is preloaded
	 */
	public function preload_component() {
		
		$content_hook = array (
		'page_requested_url' => 'Input::page_requested_url'
		);

		return $content_hook;

	}

	/**
	 * method for page_requested_url hook
	 */
	public static function page_requested_url($requested_url, $vce) {

		// add the path to input for form action value
		$vce->input_path = defined('INPUT_PATH') ? $vce->site->site_url . '/' . INPUT_PATH : $vce->site->site_url . '/input';

		if ((!defined('INPUT_PATH') && strpos($requested_url, 'input') !== false && strlen($requested_url) == 5) || (defined('INPUT_PATH') && strpos($requested_url, INPUT_PATH) !== false) && strlen($requested_url) == strlen(INPUT_PATH)) {

			// if no dossier is set, forward to homepage
			if (!isset($_POST['dossier'])) {
				header("Location: " . $vce->site->site_url);
				exit();
			}
			
			// for sanitizing
			global $db;

			header("Access-Control-Allow-Origin: *");

			// decryption of dossier and cast json_decode as an array, mostly to keep the $_POST array concept alive
			// continues through to procedures where $input is worked with as an array
			$dossier = json_decode($vce->user->decryption($_POST['dossier'], $vce->user->session_vector), true);

			// check that component type is a property of $dossier, json object test
			if (!isset($dossier['type']) || !isset($dossier['procedure'])) {
				echo json_encode(array('response' => 'error','message' => 'Dossier is not valid','action' => ''));
				exit();
			}

			// which component type to send this input data to
			$type = preg_replace("/[^A-Za-z0-9_]+/", '', trim($dossier['type']));

			// list of input types as json object
			// we could use this to sanitize different input types
			// $_POST['inputtypes'];
			// json can be set as the input type when using the asynchronous-form path by adding schema="json" within the input element
			$inputtypes = array();
			if (isset($_POST['inputtypes'])) {
				foreach (json_decode($_POST['inputtypes']) as $each_input) {
					if (isset($each_input->name)) {
						$inputtypes[$each_input->name] = $each_input->type;
					}
				}
			}	

			// unset what is not needed and prevent component type and component procedure from being changed
			unset($_POST['type'],$_POST['procedure'], $_POST['dossier'], $_POST['inputtypes']);

			// create array to pass on
			$input = array();

			// add dossier values first
			foreach ($dossier as $key=>$value) {
				$input[$key] = $value;
			}

			// sanitize and rekey
			foreach ($_POST as $key=>$value) {
				$value = trim($value);
				if (isset($inputtypes[$key])) {
					if ($inputtypes[$key] == 'json') {
						// make sure that the json object is valid
						// value will be passed as a json object into the procedure
						json_decode($value);
						if (json_last_error() == JSON_ERROR_NONE) {
							$input[$key] = $value;
						} else {
							// json error reporting here
							$input[$key] = 'json object error';
						}
					} elseif ($inputtypes[$key] == 'textarea') {
						// load hooks
						if (isset($vce->site->hooks['input_sanitize_textarea'])) {
							foreach($vce->site->hooks['input_sanitize_textarea'] as $hook) {
								$value = call_user_func($hook, $value);
							}
						} else {
							// textarea default is FILTER_SANITIZE_STRING
							$value = filter_var($value, FILTER_SANITIZE_STRING);
						}
						// remove line returns if input contains html
						if ($value != strip_tags($value)) {
							$value = str_replace(array("\r", "\n"), '', $value);
						}
						// add to input
						$input[$key] = $db->sanitize($value);
					} else {
						// default filtering
						$input[$key] = $db->sanitize($value);	
					}
				} else {
					// this will be updated when manange recipes and menus is updated
					if ($key == 'json') {
						// make sure that the json object is valid
						// value will be passed as a json object into the procedure
						json_decode($value);
						if (json_last_error() == JSON_ERROR_NONE) {
							$input[$key] = $value;
						} else {
							// json error reporting here
							$input[$key] = 'json object error';
						}
					} else {
						// default filtering
						$input[$key] = $db->sanitize($value);
					}
				}
			}

			// load base components class
			require_once(BASEPATH .'vce-application/class.component.php');

			// create array of installed components
			$activated_components = json_decode($vce->site->activated_components, true);
			// check that component type exists
			if (isset($activated_components[$type])) {
				// require component type class
				require_once(BASEPATH . $activated_components[$type]);
				// initialize component type object
				$this_component = new $type($input);
				// call to method for form input in component type class
				// basically, check that class extends Component
				if (method_exists($this_component,'form_input')) {
					$this_component->form_input($input);
				} else {
					// default to Component in the case the component is, for example, a mediatype
					// in this case the method should be "public static" instead of "protected"
					$this_component = new Component;
					$this_component->form_input($input);
				}
				exit();
			} elseif (isset($type)) {
				// component type called out in form input does not exist
				if (VCE_DEBUG) {
					echo json_encode(array('response' => 'error','message' => 'Component not found'));
					exit();
				}
			}

		}
		
	}

	
	/**
	 * hide this component from being added to a recipe
	 */
	public function recipe_fields($recipe) {
		return false;
	}

}