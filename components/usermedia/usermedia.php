<?php

class UserMedia extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'User Media',
			'description' => 'Allows a user to manage their media',
			'category' => 'user'
		);
	}
	
	
	/**
	 * Prevent sub-components from being retrieved
	 */
	public function find_sub_components($requested_component, $vce, $components, $sub_components) {
		return false;
	}

	/**
	 * Display this component
	 */
	public function check_access($each_component, $vce) {
		return true;
	}

	/**
	 * Prevent sub-components from being displayed
	 */
	public function build_sub_components($each_component, $vce) {
		return false;
	}

	/**
	 * Allow sub-components to be created
	 */
	public function allow_sub_components($each_component, $vce) {
		return true;
	}


	public function as_content($each_component, $vce) {

		$content = "";
		
		if (isset($vce->media_id)) {
		
			$subquery = "SELECT * FROM  " . TABLE_PREFIX . "components WHERE component_id='" . $vce->media_id . "'";
			$component_info = $vce->db->get_data_object($subquery);
			
			$query = "SELECT meta_key, meta_value FROM  " . TABLE_PREFIX . "components_meta WHERE component_id='" . $vce->media_id . "'";
			$meta_data = $vce->db->get_data_object($query,0);
			
			$media_component = new stdClass();
			
			$media_component->component_id = $vce->media_id;
			$media_component->sequence = $component_info[0]->sequence;
			
			foreach ($meta_data as $key=>$value) {
			
				$meta_key = $value['meta_key'];
				$media_component->$meta_key = $value['meta_value'];
			
			}
			
			
			if ($media_component->created_by == $vce->user->user_id || $vce->user->role_id == 1) {
			
				$class_name = $media_component->type;
			
				// get list of activated components
				$activated_components = json_decode($vce->site->activated_components, true);
			
				if (!class_exists($class_name)) {

					// load class
					require_once($activated_components[$class_name]);

				}
			
				$this_component = new $class_name((object) $media_component);
			
				$this_component->as_content((object) $media_component, $vce);
			
				$this_component->as_content_finish((object) $media_component, $vce);
				
				$query = "SELECT component_id FROM  " . TABLE_PREFIX . "components_meta WHERE meta_key='alias_id' and meta_value='" . $vce->media_id . "'";
				$alias_ids = $vce->db->get_data_object($query);
				
				foreach ($alias_ids as $key=>$value) {
				
					$query = "SELECT meta_key, meta_value FROM  " . TABLE_PREFIX . "components_meta WHERE component_id='" . $value->component_id . "'";
					$meta_data = $vce->db->get_data_object($query);
				
					$each_component = new stdClass();
			
					$each_component->component_id = $value->component_id;

					// cycle through results and add meta_key / meta_value pairs to component object
					foreach ($meta_data as $each_meta) {
			
						$key = $each_meta->meta_key;
	
						$each_component->$key = $each_meta->meta_value;
	
						// adding minutia if it exists within database table
						if (!empty($each_meta->minutia)) {
							$key .= "_minutia";
							$each_component->$key = $each_meta->minutia;
						}
			
					}
			
					$user_media[] = $each_component;
				
				}
				
				if (!empty($user_media)) {
				
					$content .= '<h3>Aliases Of This Media Item</h3>';
				
					foreach ($user_media as $key=>$each_media_item) {
				
						$link_url = $vce->find_url($each_media_item->component_id);

						$dossier = array(
						'type' => 'UserMedia',
						'procedure' => 'delete',
						'component_id' => $each_media_item->component_id,
						'created_at' =>  $each_media_item->created_at,
						'parent_url' => $page->requested_url
						);

						$dossier_for_delete = $vce->generate_dossier($dossier);
		
$content .= <<<EOF
$link_url

<a href="$link_url" class="link-button">View In Location</a>

<form id="delete_$each_media_item->component_id" class="delete-form inline-form asynchronous-form" method="post" action="$page->input_path">
<input type="hidden" name="dossier" value="$dossier_for_delete">
<input type="submit" value="Delete">
</form>
<hr>
EOF;

				
					}
				}
				
			
			} else {
			
				$content .= "You do not have permission to access this component";
			
				return false;
			
			}
		
		
		} else {

			$user_media = array();
		
			// $user->user_id

			// get media
			$subquery = "SELECT component_id FROM " . TABLE_PREFIX . "components_meta WHERE meta_key='type' AND meta_value='Media'";
			$query = "SELECT component_id FROM " . TABLE_PREFIX . "components_meta WHERE component_id in (" . $subquery . ") AND meta_key='created_by' AND meta_value='" . $vce->user->user_id . "'";
			$component_ids = $vce->db->get_data_object($query);

			foreach ($component_ids as $each_id) {

				// get alias components meta data
				$query = "SELECT meta_key, meta_value, minutia FROM " . TABLE_PREFIX . "components_meta WHERE component_id='" . $each_id->component_id . "' ORDER BY meta_key";
				$components_meta = $vce->db->get_data_object($query);

				$each_component = new stdClass();
			
				$each_component->component_id = $each_id->component_id;

				// cycle through results and add meta_key / meta_value pairs to component object
				foreach ($components_meta as $each_meta) {
			
					$key = $each_meta->meta_key;
	
					$each_component->$key = $each_meta->meta_value;
	
					// adding minutia if it exists within database table
					if (!empty($each_meta->minutia)) {
						$key .= "_minutia";
						$each_component->$key = $each_meta->minutia;
					}
			
				}
			
				$user_media[] = $each_component;

			}
		

			$content .= '<h1>My Media</h1>';
		
			foreach($user_media as $each_media_item) {
			
			if ($each_media_item->media_type == "Alias") {

				continue;
			
			}
						
			$link_url = $vce->page->find_url($each_media_item->component_id);

			$dossier_for_view = $vce->generate_dossier(array('type' => 'UserMedia','procedure' => 'view','component_id' => $each_media_item->component_id));
		

$content .= <<<EOF
$each_media_item->media_type
/
$each_media_item->title

<form id="view_$each_media_item->component_id" class="inline-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_view">
<input type="submit" value="View">
</form>

<hr>
EOF;


			}
		
		}
		
		$vce->content->add('main', $content);
	
	}
	
		
	/**
	 * edit recipe
	 */
	protected function view($input) {
	
		// add attributes to page object for next page load using session
		global $site;
		
		$site->add_attributes('media_id',$input['component_id']);
	
		echo json_encode(array('response' => 'success','action' => 'reload', 'delay' => '0'));
		return;
		
	}


	/**
	 * fields for ManageRecipe
	 */
	public function recipe_fields($recipe) {
	
		global $site;
	
		$title = isset($recipe['title']) ? $recipe['title'] : self::component_info()['name'];

$elements = <<<EOF
<input type="hidden" name="auto_create" value="forward">
<label>
<input type="text" name="title" value="$title" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Title</div>
<div class="label-error">Enter a Title</div>
</div>
</label>
EOF;

		return $elements;
		
	}

}