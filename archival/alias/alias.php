<?php

class Alias extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Alias',
			'description' => 'Alias of another component',
			'category' => 'site'
		);
	}
	
	
	/**
	 * add hook - this has been disabled
	 */
	public function disabled_preload_component() {
		
		$content_hook = array (
		'delete_extirpate_component' => 'Alias::delete_extirpate_component'
		);

		return $content_hook;

	}
	
	/**
	 * delete anything that has an alias_id associated with the component
	 */
	public static function delete_extirpate_component($component_id, $components) {
	
		global $db;
	
		// find all aliases of this component
		$query = "SELECT component_id FROM " . TABLE_PREFIX . "components_meta WHERE meta_key='alias_id' and meta_value='" . $component_id . "'";
		$alias_components = $db->get_data_object($query);
		
		foreach ($alias_components as $key=>$value) {
		
			$query = "SELECT * FROM " . TABLE_PREFIX . "components WHERE component_id='" . $value->component_id. "'";
			$additional_components = $db->get_data_object($query);
		
			// add to sub component list
			$components[] = $additional_components[0];
		
		}
		
		return $components;
		
	}

	
	public function find_sub_components($requested_component, $vce, $components, $sub_components) {
	
		// prevent errors
		if (!empty($sub_components)) {

			$find_aliases = function($components) use (&$find_aliases,$vce) {
		
				foreach ($components as $component_key=>$component_value) {
			
					if ($component_value->type == "Alias" && isset($component_value->alias_id)) {

						// get alias components meta data
						$query = "SELECT * FROM  " . TABLE_PREFIX . "components_meta WHERE component_id='" . $component_value->alias_id . "' ORDER BY meta_key";
						$component_meta = $vce->db->get_data_object($query);
		
						if (!empty($component_meta)) {
				
							$ignore = array('component_id','parent_id','sequence','created_at');
							foreach ($component_meta as $meta_values) {
								// set component editing if current user created this alias
								if ($meta_values->meta_key == 'created_by' && $component_value->created_by == $vce->user->user_id) {
									// set component editing
									$components[$component_key]->content_edit = 'roles';
									$components[$component_key]->content_delete = 'roles';
									$components[$component_key]->content_create = '|' . $vce->user->role_id . '|';
								}

							 	if (!in_array($meta_values->meta_key, $ignore)) {
									$meta_key = $meta_values->meta_key;
									$components[$component_key]->$meta_key = $meta_values->meta_value;
								}
							}

						} else {
			
$content = <<<EOF
<div>This alias points to a component that cannot be found.</div>
EOF;

							if ($vce->page->can_delete($component_value)) {
				
								// the instructions to pass through the form
								$dossier = array(
								'type' => $component_value->type,
								'procedure' => 'delete',
								'component_id' => $component_value->component_id,
								'created_at' => $component_value->created_at
								);

								// generate dossier
								$dossier_for_delete = $vce->generate_dossier($dossier);


$content .= <<<EOF
<form id="delete_$component_value->component_id" class="delete-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_delete">
<input type="submit" value="Delete">
</form>
EOF;

							}

							$vce->content->add('postmain',$content);
			
						}

					}
			
					if (isset($component_value->components)) {
				
						$find_aliases($component_value->components);
				
					}
		
		
				}
	
			};
		
			$find_aliases($sub_components);
		
		}
		
		return true;
	}


	/**
	 * custom create component
	 */
	protected function create($input) {
	
		global $site;
	
		// load hooks
		// alias_create_component
		if (isset($site->hooks['alias_create_component'])) {
			foreach($site->hooks['alias_create_component'] as $hook) {
				$input_returned = call_user_func($hook, $input);
				$input = isset($input_returned) ? $input_returned : $input;
			}
		}
	
		// call to create_component, which returns the newly created component_id
		$component_id = self::create_component($input);

		if ($component_id) {
		
			$input['component_id'] = $component_id;
			
			$response = array(
			'response' => 'success',
			'procedure' => 'create',
			'message' => 'New Component Was Created'
			);

			// load hooks
			// alias_component_created
			if (isset($site->hooks['alias_component_created'])) {
				foreach($site->hooks['alias_component_created'] as $hook) {
					$response_returned = call_user_func($hook, $input, $response);
					$response = isset($response_returned) ? $response_returned : $response;
				}
			}
			
			$site->add_attributes('message','Alias Created');
	
			echo json_encode($response);
			return;
		
		}
		
		echo json_encode(array('response' => 'error','procedure' => 'update','message' => "Error"));
		return;

	}
	    
    /**
	 * hide this component from being added to a recipe
	 */
	public function recipe_fields($recipe) {
		return false;
	}


}