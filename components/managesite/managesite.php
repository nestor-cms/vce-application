<?php

class ManageSite extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Manage Site',
			'description' => 'Site info and user roles',
			'category' => 'admin'
		);
	}

	/**
	 * display content for ManageStie
	 */
	public function as_content($each_component, $vce) {

		// done for ease
 		$site = $vce->site;
		
		$themes_list = self::get_themes();
			
		$dossier_for_update = $vce->generate_dossier(array('type' => 'ManageSite','procedure' => 'update'));

$content = <<<EOF
<p>
<div class="clickbar-container">
<div class="clickbar-content clickbar-open">
<form id="update" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_update">
<label>
<input type="text" name="site_title" value="$site->site_title" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Site Title</div>
<div class="label-error">Enter a Title</div>
</div>
</label>
<label>
<input type="text" name="site_description" value="$site->site_description" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Site Description</div>
<div class="label-error">Enter a Site Description</div>
</div>
</label>
<label>
<input type="text" name="site_url" value="$site->site_url" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Site URL</div>
<div class="label-error">Enter a Site URL</div>
</div>
</label>
<label>
<input type="text" name="site_email" value="$site->site_email" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Site Email</div>
<div class="label-error">Enter a Site Email</div>
</div>
</label>
<label>
<select name="site_theme">
EOF;

		foreach ($themes_list as $key=>$meta_name) {

			$content .= '<option value="' . $themes_list[$key]['path'] . '"';
			if ($themes_list[$key]['path'] == $site->site_theme) {
				$content .= ' selected';
			}
			$content .= '>' . $themes_list[$key]['name'] . '</option>';

		}

$content .= <<<EOF
</select>
<div class="label-text">
<div class="label-message">Site Theme</div>
<div class="label-error">Select a Site Theme</div>
</div>
</label>
<input type="submit" value="Update">
</form>
</div>
<div class="clickbar-title disabled"><span>Site Settings</span></div>
</div>
</p>
EOF;

		// role edit / delete / update

		// fetch user count
		$query = "SELECT role_id, count(role_id) as count FROM " . TABLE_PREFIX . "users GROUP BY role_id";
		$role_count = $vce->db->get_data_object($query);
		
		$count = array();
		foreach($role_count as $each_role_count) {
			
			$count[$each_role_count->role_id] = $each_role_count->count;
		
		}

$content .= <<<EOF
<p>
<div class="clickbar-container">
<div class="clickbar-content clickbar-open clickbar-parent">
EOF;

		$site_roles = json_decode($site->roles, true);
	
		foreach ($site_roles as $key=>$value) {
		
			$key_count = isset($count[$key]) ? $count[$key] : 'No';
		
			$dossier_for_updaterole = $vce->generate_dossier(array('type' => 'ManageSite','procedure' => 'updaterole','role_id' => $key));
			
			$dossier_for_deleterole = $vce->generate_dossier(array('type' => 'ManageSite','procedure' => 'deleterole','role_id' => $key));

			$role_name = is_array($value) ? $value['role_name'] : $value;
			$role_hierarchy = (is_array($value) && isset($value['role_hierarchy'])) ? $value['role_hierarchy'] : 0;
		
 
$content .= <<<EOF
<p>
<div class="clickbar-container">
<div class="clickbar-content">
<form id="update_$key" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_updaterole">
<label>
<input type="text" name="role_name" value="$role_name" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Site Role Name</div>
<div class="label-error">Enter a Site Role Name</div>
</div>
</label>
<label>
<select name="role_hierarchy">
EOF;

			// create options
			for ($x = 0;$x <= count($site_roles);$x++) {
				// $role_hierarchy
				$content .= '<option value="' . $x . '"';
				if ($x == $role_hierarchy) {
					$content .= ' selected';
				}
				$content .= '>' . $x . '</option>';
			}

$content .= <<<EOF
</select>
<div class="label-text">
<div class="label-message">Role Hierarchy</div>
<div class="label-error">Enter Role Hierarchy</div>
</div>
</label>
<input type="submit" value="Update">
<input type="reset" value="Reset">
</form>
<form id="delete_$key" class="delete-form float-right-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_deleterole">
<input type="submit" value="Delete">
</form>
</div>
<div class="clickbar-title clickbar-closed"><span>$role_name / $key_count Users</span></div>
</div>
</p>
EOF;

		}

		$dossier_for_addrole = $vce->generate_dossier(array('type' => 'ManageSite','procedure' => 'addrole'));

$content .= <<<EOF
<p>
<div class="clickbar-container">
<div class="clickbar-content">
<form id="update" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_addrole">
<label>
<input type="text" name="role_name" value="" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Site Role Name</div>
<div class="label-error">Enter a Site Role Name</div>
</div>
</label>
<label>
<select name="role_hierarchy">
EOF;

		// create options
		for ($x = 0;$x <= count($site_roles);$x++) {
			// $role_hierarchy
			$content .= '<option value="' . $x . '">' . $x . '</option>';
		}

$content .= <<<EOF
</select>
<div class="label-text">
<div class="label-message">Role Hierarchy</div>
<div class="label-error">Enter Role Hierarchy</div>
</div>
</label>
<input type="submit" value="Add New Site Role">
</form>
</div>
<div class="clickbar-title clickbar-closed"><span>Add Site Role</span></div>
</div>
</p>
</div>
<div class="clickbar-title disabled"><span>Site Roles</span></div>
</div>
</p>
EOF;


/* start of user attributes */


$content .= <<<EOF
<p>
<div class="clickbar-container">
<div class="clickbar-content clickbar-open clickbar-parent">
EOF;

		$user_attributes = json_decode($vce->site->user_attributes, true);

		// adding to allow sorting
		$attributes_count = count($user_attributes);
		$place_counter = 0;

		foreach ($user_attributes as $user_attribute_key=>$user_attribute_value) {
		
			$place_counter++;

			// create the dossier
			$dossier_for_attribute_update = $vce->generate_dossier(array('type' => 'ManageSite','procedure' => 'update_attribute'));

$content .= <<<EOF
<p>
<div class="clickbar-container">
<div class="clickbar-content clickbar-parent">
<form id="$user_attribute_key" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_attribute_update">
<input type="hidden" name="attribute" value="$user_attribute_key">
EOF;



$content .= <<<EOF
<label>
<select name="order">
EOF;

		// create options
		for ($x = 1;$x <= $attributes_count;$x++) {
			// $role_hierarchy
			$content .= '<option value="' . $x . '"';
			if ($x == $place_counter) {
				$content .= ' selected';
			}
			$content .= '>' . $x . '</option>';
		}

$content .= <<<EOF
</select>
<div class="label-text">
<div class="label-message">Order</div>
</div>
</label>
EOF;


			/* start title */
			$title = isset($user_attribute_value['title']) ? $user_attribute_value['title'] : $user_attribute_key;

$content .= <<<EOF
<label>
<input type="text" name="title" value="$title" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Title</div>
<div class="label-error">Enter Title</div>
</div>
</label>
EOF;
/* end title */


/* start types */

			// list of types
			$types = array('text','select','radio','checkbox','conceal');

$content .= <<<EOF
<label for="null">
<div class="input-padding">
/&nbsp;
EOF;

			foreach ($types as $each_type) {

				$selected = ($each_type == $user_attribute_value['type']) ? ' checked' : null;

$content .= <<<EOF
<label for="$user_attribute_key-type-$each_type" class="ignore"><input id="$user_attribute_key-type-$each_type" type="radio" name="attribute_type" value="$each_type" $selected> $each_type</label> /&nbsp;
EOF;

			}

$content .= <<<EOF
</div>
<div class="label-text">
<div class="label-message">Type</div>
<div class="label-error">Enter Type</div>
</div>
</label>
EOF;

/* end types */

/* start datalist */

			$checked = isset($user_attribute_value['datalist']) ? ' checked' : null;

$content .= <<<EOF
<label>
<div class="input-padding">
<input type="checkbox" name="datalist_checkbox" value="1" disabled $checked> Datalist
EOF;

			if ($checked) {
				$content .= '<input type="hidden" name="datalist" value="' . $user_attribute_value['datalist']['datalist'] . '">';
			}

$content .= <<<EOF
</div>
<div class="label-text">
<div class="label-message">Datalist</div>
<div class="label-error">Enter Datalist</div>
</div>
</label>
EOF;

/* end datalist */

/* start required */

			$checked = (isset($user_attribute_value['required']) && $user_attribute_value['required'] == 1) ? ' checked' : null;

$content .= <<<EOF
<label>
<div class="input-padding">
<input type="checkbox" name="required" value="1"$checked> User Attribute Required
</div>
<div class="label-text">
<div class="label-message">Required</div>
<div class="label-error">Enter Title</div>
</div>
</label>
EOF;

/* end required */

/* start sortable */

			$checked = (isset($user_attribute_value['sortable']) && $user_attribute_value['sortable'] == 1) ? ' checked' : null;

$content .= <<<EOF
<label>
<div class="input-padding">
<input type="checkbox" name="sortable" value="1"$checked> User Attribute Sortable
</div>
<div class="label-text">
<div class="label-message">Sortable</div>
<div class="label-error">Enter Sortable</div>
</div>
</label>
EOF;

/* end sortable */

/* start editable */

			$checked = (isset($user_attribute_value['editable']) && $user_attribute_value['editable'] == 1) ? ' checked' : null;

$content .= <<<EOF
<label>
<div class="input-padding">
<input type="checkbox" name="editable" value="1"$checked> Attribute Editable By User
</div>
<div class="label-text">
<div class="label-message">Editable</div>
<div class="label-error">Enter Editable</div>
</div>
</label>
EOF;

/* end editable */

$content .= <<<EOF
<input type="submit" value="Update">
<div class="link-button cancel-button">Cancel</div>
</form>
EOF;

			// create the dossier
			$dossier_for_attribute_delete = $vce->generate_dossier(array('type' => 'ManageSite','procedure' => 'delete_attribute','attribute'  => $user_attribute_key));

$content .= <<<EOF
<form class="delete-form float-right-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_attribute_delete">
<input type="submit" value="Delete">
</form>
EOF;

$content .= <<<EOF
</div>
<div class="clickbar-title clickbar-closed"><span>$title ($user_attribute_key)</span></div>
</div>
</p>
EOF;

}

			// create the dossier
			$dossier_for_attribute_create = $vce->generate_dossier(array('type' => 'ManageSite','procedure' => 'create_attribute'));

$content .= <<<EOF
<div class="clickbar-container">
<div class="clickbar-content">
<form id="create_attribute" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_attribute_create">

<label>
<input type="text" name="title" value="" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Title</div>
<div class="label-error">Enter Title</div>
</div>
</label>

<label for="null">
<div class="input-padding">
/ <label for="type-text" class="ignore"><input id="type-text" type="radio" name="attribute_type" value="text" checked> text</label> /
<label for="type-select" class="ignore"><input id="type-select" type="radio" name="attribute_type" value="select"> select</label> /
<label for="type-radio" class="ignore"><input id="type-radio" type="radio" name="attribute_type" value="radio"> radio</label> /
<label for="type-checkbox" class="ignore"><input id="type-checkbox" type="radio" name="attribute_type" value="checkbox"> checkbox</label> /
<label for="type-conceal" class="ignore"><input id="type-conceal" type="radio" name="attribute_type" value="conceal"> conceal</label> /

</div>
<div class="label-text">
<div class="label-message">Type</div>
<div class="label-error">Enter a Type</div>
</div>
</label>

<label>
<div class="input-padding">
<input type="checkbox" name="datalist" value="1"> Datalist
</div>
<div class="label-text">
<div class="label-message">Datalist</div>
<div class="label-error">Enter Datalist</div>
</div>
</label>

<label>
<div class="input-padding">
<input type="checkbox" name="required" value="1"> User Attribute Required
</div>
<div class="label-text">
<div class="label-message">Required</div>
<div class="label-error">Enter Title</div>
</div>
</label>

<label>
<div class="input-padding">
<input type="checkbox" name="sortable" value="1"> User Attribute Sortable
</div>
<div class="label-text">
<div class="label-message">Sortable</div>
<div class="label-error">Enter Sortable</div>
</div>
</label>

<label>
<div class="input-padding">
<input type="checkbox" name="editable" value="1"> Attribute Editable By User
</div>
<div class="label-text">
<div class="label-message">Editable</div>
<div class="label-error">Enter Editable</div>
</div>
</label>

<input type="submit" value="Create">
<div class="link-button cancel-button">Cancel</div>
</form>
</div>
<div class="clickbar-title clickbar-closed"><span>Add a new User Attribute</span></div>
</div>

</div>
<div class="clickbar-title disabled"><span>User Attributes</span></div>
</div>
</p>
EOF;

		$vce->content->add('main', $content);

	}
	
	
	
	/**
	 * create attribute
	 */
	protected function create_attribute($input) {
	
		global $db;
		global $vce;
		
		// set the type to the attribute_type
		$input['type'] = $input['attribute_type'];
		unset($input['attribute_type']);
		
		$user_attributes = json_decode($vce->site->user_attributes, true);
		
		$attribute = strtolower(preg_replace('/\s+/', '_', $input['title']));
		
		
		// this is a place to add a hook so that datalists can be moved into a utility component
		if (isset($input['datalist'])) {
		
			$attributes = array (
			'datalist' => $attribute . '_datalist',
			'aspects' => array ('name' => $attribute)
			);
			
			$vce->create_datalist($attributes);
			
			$input['datalist'] = array('datalist' => $attribute . '_datalist');
		
		}
		
		// add to existing user_attributes array
		foreach ($input as $key=>$value) {
			$user_attributes[$attribute][$key] = $value;
		}
		
		$update = array('meta_value' => json_encode($user_attributes));
		$update_where = array('meta_key' => 'user_attributes');
		$db->update('site_meta', $update, $update_where);
		
	
		echo json_encode(array('response' => 'success','procedure' =>'create','action' => 'reload','message' => json_encode($user_attributes)));
		return;
	
	}
	
	
	/**
	 * update attribute
	 */
	protected function update_attribute($input) {

		global $db;
		global $site;
		global $vce;
		
		// set the type to the attribute_type
		$input['type'] = $input['attribute_type'];
		$attribute = $input['attribute'];
		$order = $input['order'];
		unset($input['attribute'],$input['attribute_type'],$input['order']);
		
		$user_attributes = json_decode($site->user_attributes, true);
		
		// rekey
		$position = 1;
		foreach ($user_attributes as $key=>$value) {
			if ($key == $attribute) {
				// place before
				if ($order < $position) {
					$attributes[(($order * 2) - 1)][$key] = $value;
					continue;
				}
				// place after
				if ($order > $position) {
					$attributes[(($order * 2) + 1)][$key] = $value;
					continue;	
				}
			}
			// standard place
			$attributes[($position * 2)][$key] = $value;
			// add 
			$position++;
		}
		
		ksort($attributes);
		
		$updated_attributes = array();
		
		foreach ($attributes as $user_attributes) {
		
			foreach ($user_attributes as $key=>$value) {
				if ($key == $attribute) {
					foreach ($input as $input_key=>$input_value) {
						// don't update datalist
						if ($input_key != "datalist") {
							$updated_attributes[$attribute][$input_key] = $input_value;
						} else {
							$updated_attributes[$attribute]['datalist'] = $user_attributes[$attribute]['datalist'];
						}
					}
				} else {
					$updated_attributes[$key] = $value;
				}
			}
		
		}
		
		$update = array('meta_value' => json_encode($updated_attributes));
		$update_where = array('meta_key' => 'user_attributes');
		$db->update('site_meta', $update, $update_where);
	
		echo json_encode(array('response' => 'success','procedure' =>'create','action' => 'reload','message' => 'User Attribute Updated'));
		return;
	
	}


	/**
	 * update attribute
	 */
	protected function delete_attribute($input) {
	
		global $db;
		global $site;
		

		// set the type to the attribute
		$attribute = $input['attribute'];
		unset($input['attribute']);
		
		$user_attributes = json_decode($site->user_attributes, true);
		
		$updated_attributes = array();
		foreach ($user_attributes as $key=>$value) {
			if ($key == $attribute) {
				continue;
			} else {
				$updated_attributes[$key] = $value;
			}
		}
		
		$update = array('meta_value' => json_encode($updated_attributes));
		$update_where = array('meta_key' => 'user_attributes');
		$db->update('site_meta', $update, $update_where);
		
		
		echo json_encode(array('response' => 'success','procedure' =>'create','action' => 'reload','message' => 'Deleted'));
		return;
		
	}
	
	
	/**
	 * Update Site Meta
	 */
	protected function update($input) {
	
		global $db;
		global $site;
		
		unset($input['type']);
		
		foreach ($input as $meta_key=>$meta_value) {
			
			// if the meta_key exists in site_meta table, then update
			if (isset($site->$meta_key)) {
			
				// update hash
				$update = array('meta_value' => $meta_value);
				$update_where = array('meta_key' => $meta_key);
				$db->update('site_meta', $update, $update_where);

			} else {
			

 				$user_data = array(
				'meta_key' => $meta_key, 
				'meta_value' => $meta_value
 				);

				$db->insert('site_meta', $user_data);
			
			}
		
		}
	
		echo json_encode(array('response' => 'success','procedure' => 'update','action' => 'reload','message' => 'Updated'));
		return;
	
	}
	
	
	/**
	 * Add Role
	 */
	protected function addrole($input) {
	
		global $db;
		global $site;
		
		// get current site roles
		$current_roles = json_decode($site->roles, true);
		
		// new role name
		$role_name = trim($input['role_name']);

		// new role_hierarchy
		$role_hierarchy = trim($input['role_hierarchy']);

		// case insensitive check to see if role name is already in use
		foreach ($current_roles as $key=>$value) {
			if (strtolower($role_name) == strtolower($value['role_name'])) {
				echo json_encode(array('response' => 'error','procedure' => 'create','message' => $value['role_name'] . ' is already in use'));
				return;
			}
		}
		
		// set new role
		$current_roles[] = array(
		'role_name' => $role_name,
		'role_hierarchy' => $role_hierarchy
		);
		
		
		// update roles
		$update = array('meta_value' => json_encode($current_roles));
		$update_where = array('meta_key' => 'roles');
		$db->update('site_meta', $update, $update_where);
	
	
		echo json_encode(array('response' => 'success','procedure' =>'create','action' => 'reload','message' => 'New Site Role Created'));
		return;
	
	}	
	

	/**
	 * Update Role
	 */
	protected function updaterole($input) {
	
		global $db;
		global $site;
		
		// get current site roles
		$current_roles = json_decode($site->roles, true);
		
		// current role id
		$role_id = trim($input['role_id']);	
		
		// new role name
		$role_name = trim($input['role_name']);
		
		// new role_hierarchy
		$role_hierarchy = trim($input['role_hierarchy']);

		// case insensitive check to see if role name is already in use
		foreach ($current_roles as $key=>$value) {
			if (isset($value['role_name']) && strtolower($value['role_name']) == strtolower($role_name) && $key != $role_id) {
				echo json_encode(array('response' => 'error','procedure' =>'update','action' => 'reload','message' => $value['role_name'] . ' is already in use'));
				return;
			}
		}
		
		// set new role
		// $current_roles[$role_id] = array('role_name' => $role_name);
		if (is_array($current_roles[$role_id])) {
			$current_roles[$role_id]['role_name'] = $role_name;
			$current_roles[$role_id]['role_hierarchy'] = $role_hierarchy;
		} else {
			// we can remove this once we know every instance has been updated
			$current_roles[$role_id] = array('role_name' => $role_name);
		}
		
		
		// update roles
		$update = array('meta_value' => json_encode($current_roles));
		$update_where = array('meta_key' => 'roles');
		$db->update('site_meta', $update, $update_where);
	
		echo json_encode(array('response' => 'success','procedure' =>'update','action' => 'reload','message' => 'Role Updated'));
		return;
	
	}
	
	/**
	 * Update Role
	 */
	protected function deleterole($input) {
	
		global $db;
		global $site;
		
		// get current site roles
		$current_roles = json_decode($site->roles, true);
		
		// current role id
		$role_id = trim($input['role_id']);
		
		if ($role_id == "1") {
			echo json_encode(array('response' => 'error','procedure' =>'delete','message' => 'This role cannot be deleted'));
			return;
		}
		
		// fetch user count
		$query = "SELECT role_id FROM " . TABLE_PREFIX . "users WHERE role_id='" . $role_id . "'";
		$role_count = $db->get_data_object($query);
		
		if (!empty($role_count)) {
		
			echo json_encode(array('response' => 'error','procedure' =>'delete','message' => 'There are ' . count($role_count) . ' users assigned to this role', 'form' => 'delete'));
			return;	
		
		}
			
		
		// remove role from array
		unset($current_roles[$role_id]);
		
		// update roles
		$update = array('meta_value' => json_encode($current_roles));
		$update_where = array('meta_key' => 'roles');
		$db->update('site_meta', $update, $update_where );
	
		echo json_encode(array('response' => 'success','procedure' =>'delete','action' => 'reload','message' => 'Role Deleted'));
		return;
	
	}	

	/**
	 * create an array of themes names
	 */ 
	private function get_themes() {
	
		$themes = array();
		
		// http://php.net/manual/en/class.directoryiterator.php
		foreach (new DirectoryIterator(BASEPATH . "vce-content/themes/") as $key=>$fileInfo) {
   	 		
   	 		// check for dot files
   	 		if ($fileInfo->isDot()) {
   	 			continue;
   	 		}
   	 		
   	 		if ($fileInfo->isDir()) {
   	 		
   	 			// set theme path
   	 			$themes[$key]['path'] = $fileInfo->getFilename();
   	 		
   	 			// full path
   	 			$full_path = BASEPATH . "vce-content/themes/" . $fileInfo->getFilename() . "/theme.php";
   	 		
   	 			// get theme name
   	 			preg_match('/Theme Name:(.*)$/mi', file_get_contents($full_path), $header);
   	 			
   	 			// set theme name
   	 			if (isset($header['1'])) {
					$themes[$key]['name'] = trim($header['1']);
   	 			} else {
   	 				$themes[$key]['name'] = $fileInfo->getFilename();
   	 			}
			}
		
		}
		
		return $themes;

	}
	
	
	/**
	 * create an array of template names with file paths
	 */ 
	public function get_template_names() {

		global $site;

		$path = BASEPATH . "vce-content/themes/" . $site->site_theme;

		$files = scandir(BASEPATH . "vce-content/themes/" . $site->site_theme);

		for ($x=0,$y=count($files);$x<$y;$x++) {
			if (!preg_match('/\.php/', $files[$x])) {
				unset($files[$x]);
			}
		}

		$template_names = array();

		foreach($files as $each_file) {

			$full_path = BASEPATH . "vce-content/themes/" . $site->site_theme . '/' . $each_file;

			preg_match('/Template Name:(.*)$/mi', file_get_contents($full_path), $header);

			if (empty($header)) {
				$template_names["Default"] = $each_file;
			} else {
				$template_names[trim($header['1'])] = $each_file;
			}
		}

		return $template_names;

	}
	
	
	/**
	 * fileds to display when this is created
	 */
	public function recipe_fields($recipe) {
	
		$title = isset($recipe['title']) ? $recipe['title'] : self::component_info()['name'];
		$url = isset($recipe['url']) ? $recipe['url'] : null;
	
$elements = <<<EOF
<input type="hidden" name="auto_create" value="forward">
<label>
<input type="text" name="title" value="$title" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Title</div>
<div class="label-error">Enter a Title</div>
</div>
</label>
<label>
<input type="text" name="url" value="$url" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">URL</div>
<div class="label-error">Enter a URL</div>
</div>
</label>
EOF;

		return $elements;
		
	}

}