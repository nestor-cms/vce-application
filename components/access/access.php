<?php

class Access extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Access',
			'description' => 'Access and creation restriction by user role for sub-components',
			'category' => 'site'
		);
	}	

	
	/**
	 * check if get_sub_components should be called.
	 * @return bool
	 */
	public function find_sub_components($requested_component, $vce, $components, $sub_components) {
	
		// if user role is in role_access, return tue
		if (in_array($vce->user->role_id,explode('|',$requested_component->role_access))) {
			return true;
		}
		
		return false;
	}

	/**
	 *
	 */
	public function check_access($each_component, $vce) {

		if (isset($vce->user->role_id)) {
		
			// check if user_id is in role_access
			
			if (!in_array($vce->user->role_id,explode('|',$each_component->role_access))) {
			
				return false;
				
			}
		
		} else {
		// no user role.
			if (count($each_component->role_access)) {
				return false;
			}
		}
		
		return true;
	}

	/**
	 * 
	 */
	public function as_content($each_component, $vce) {

		if ($each_component->component_id == $vce->requested_id) {
	
			$vce->title = $each_component->title;
	
		}
	}

	/**
	 *  fields for ManageRecipe
	 */
	public function recipe_fields($recipe) {
	
		global $site;
		
		$site->get_template_names();
	
		$roles = json_decode($site->roles, true);
		
		// add public to the roles list
		$roles['x'] = "Public";
	
		$title = isset($recipe['title']) ? $recipe['title'] : self::component_info()['name'];
		$template = isset($recipe['template']) ? $recipe['template'] : null;
		$repudiated_url = isset($recipe['repudiated_url']) ? $recipe['repudiated_url'] : null;
		$role_access = isset($recipe['role_access']) ? $recipe['role_access'] : null;
		$content_create = isset($recipe['content_create']) ? $recipe['content_create'] : null;
		$content_edit = isset($recipe['content_edit']) ? $recipe['content_edit'] : null;
		$content_delete = isset($recipe['content_delete']) ? $recipe['content_delete'] : null;

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
<select name="template">
<option value=""></option>
EOF;

	foreach($site->get_template_names() as $key=>$value) {
	
		$elements .= '<option value="' . $value . '"';
		if ($value == $template) {
			$elements .= ' selected';
		}
		$elements .= '>' . $key . '</option>';
	
	}

$elements .= <<<EOF
</select>
<div class="label-text">
<div class="label-message">Template (optional)</div>
<div class="label-error">Enter a Template</div>
</div>
</label>
<label>
<input type="text" name="repudiated_url" value="$repudiated_url" autocomplete="off">
<div class="label-text">
<div class="label-message">Access Denied URL (Optional)</div>
<div class="label-error">Enter a url</div>
</div>
</label>
<label>
<div class="input-padding">
EOF;

		foreach ($roles as $key=>$each_role) {
		
			// allow both simple and complex role definitions
			$role_name = is_array($each_role) ? $each_role['role_name'] : $each_role;
		
			$elements .= '<label class="ignore"><input type="checkbox" name="role_access" value="' . $key . '"';
			// is this the admin role?
			if ($key == 1) {
				$elements .= ' disabled="disabled" checked="checked"';
			} else if (in_array($key,explode('|',$role_access))) {
				$elements .= ' checked="checked"';
			}
			$elements .= '>  ' . $role_name . '</label> ';
		}

$elements .= <<<EOF
</div>
<div class="label-text">
<div class="label-message">Who Can View Content?</div>
<div class="label-error">Must have roles</div>
</div>
</label>
<label>
<div class="input-padding">
EOF;

		foreach ($roles as $key=>$each_role) {
			
			// allow both simple and complex role definitions
			$role_name = is_array($each_role) ? $each_role['role_name'] : $each_role;
			
			$elements .= '<label class="ignore"><input type="checkbox" name="content_create" value="' . $key . '"';
			// is this the admin role?
			if ($key == 1) {
				$elements .= ' disabled="disabled" checked="checked"';
			} else if (in_array($key,explode('|',$content_create))) {
				$elements .= ' checked="checked"';
			}
			$elements .= '>  ' . $role_name . '</label> ';
		}

$elements .= <<<EOF
</div>
<div class="label-text">
<div class="label-message">Who Can Create Content?</div>
<div class="label-error">Must have roles</div>
</div>
</label>
<div class="clickbar-container">
<div class="clickbar-content">
<label for="">
<select name="content_edit">
EOF;

		$editors = array(
		'User' => 'user',
		'Roles' => 'roles'
		);

		foreach($editors as $key=>$value) {
	
			$elements .= '<option value="' . $value . '"';
			if ($value == $content_edit) {
				$elements .= ' selected';
			}
			$elements .= '>' . $key . '</option>';
	
		}

$elements .= <<<EOF
</select>
<div class="label-text">
<div class="label-message">Who Can Edit Created Content?</div>
<div class="label-error">Who can edit created content</div>
</div>
</label>
<label for="">
<select name="content_delete">
EOF;

		$deleters = array(
		'User' => 'user',
		'Roles' => 'roles'
		);

		foreach($deleters as $key=>$value) {
	
			$elements .= '<option value="' . $value . '"';
			if ($value == $content_delete) {
				$elements .= ' selected';
			}
			$elements .= '>' . $key . '</option>';
	
		}

$elements .= <<<EOF
</select>
<div class="label-text">
<div class="label-message">Who Can Delete Created Content?</div>
<div class="label-error">Who can delete created content</div>
</div>
</label>
</div>
<div class="clickbar-title clickbar-closed"><span>Advanced Options</span></div>
</div>
<br>
EOF;

		return $elements;
		
	}

}