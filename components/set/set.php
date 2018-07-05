<?php

class Set extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Set',
			'description' => 'allows for sets of users to be selected who can view content contained within.',
			'category' => 'site'
		);
	}


	/**
	 * check access
	 */
	public function check_access($each_component, $vce) {
	
		if (isset($vce->user->user_id)) {
			// current user_id is in list of user_access or this user created it
			if (in_array($vce->user->user_id,explode('|', $each_component->user_access)) || $vce->page->can_edit($each_component)) {	
					return true;
			}
		}
		
		return false;
	}


	/**
	 * Show a link to if the user_id is within the user_access, or if user can edit
	 */
	public function as_link($each_component, $vce) {
		
		if (in_array($vce->user->user_id,explode('|', $each_component->user_access)) || $vce->page->can_edit($each_component)) {
			$vce->content->add('main', '<div class="sets-link"><a href="' . $vce->site->site_url . '/' . $each_component->url . '">' . $each_component->title . '</a></div>'  . PHP_EOL);
		}

	}
	
	
	/**
	 * Edit existing set
	 */
	public function edit_component($each_component, $vce) {
	
		if (!isset($each_component->recipe)) {
			return false;
		}
	
		if ($vce->page->can_edit($each_component)) {

			$recipe = (object) $each_component->recipe;

			// the instructions to pass through the form
			$dossier = array(
			'type' => $each_component->type,
			'procedure' => 'update',
			'component_id' => $each_component->component_id,
			'created_at' => $each_component->created_at
			);

			// generate dossier
			$dossier_for_update = $vce->generate_dossier($dossier);

			$recipe = (object) $each_component->recipe;
			
			// add javascript to page
			$vce->site->add_script(dirname(__FILE__) . '/js/script.js', 'select2');
		
			$sequence = isset($each_component->sequence) ? $each_component->sequence : '0';
			$url = isset($each_component->url) ? $each_component->url : '';

			// get the list of current user_access
			$current_user_ids = explode('|', $each_component->user_access);
			$user_ids = $each_component->user_access;

		
$content = <<<EOF
<div class="clickbar-container admin-container edit-container">
<div class="clickbar-content">
<form id="update_$each_component->component_id" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_update">
<label>
<input type="text" name="title" value="$each_component->title" tag="required">
<div class="label-text">
<div class="label-message">Name of $recipe->title</div>
<div class="label-error">Enter a Title</div>
</div>
</label>
<label>
<input type="text" name="url" value="$url">
<div class="label-text">
<div class="label-message">URL</div>
<div class="label-error">Enter a URL</div>
</div>
</label>
<label>
<input type="text" name="sequence" value="$sequence">
<div class="label-text">
<div class="label-message">Order Number</div>
<div class="label-error">Enter an Order Number</div>
</div>
</label>
<label>
<input type="hidden" class="user_ids" name="user_ids" value="$user_ids">
<select class="users_select" name="users" multiple="multiple">
<option value=""></option>
EOF;

			foreach (user::get_users(array('roles' => $recipe->role_select)) as $each_user) {
				$content .= '<label for=""><option value="' . $each_user->user_id . '"';
				if (in_array($each_user->user_id, $current_user_ids)) {
					$content .= ' selected';
				}
				$content .= '>' . $each_user->first_name . ' ' . $each_user->last_name . '</option>';
			}

$content .= <<<EOF
</select>
<div class="label-text">
<div class="label-message">User Acesss</div>
<div class="label-error">Enter Users</div>
</div>
</label>
<input type="submit" value="Update">
</form>
EOF;

			if ($vce->page->can_delete($each_component)) {
				
				// the instructions to pass through the form
				$dossier = array(
				'type' => $each_component->type,
				'procedure' => 'delete',
				'component_id' => $each_component->component_id,
				'created_at' => $each_component->created_at
				);

				// generate dossier
				$dossier_for_delete = $vce->generate_dossier($dossier);

$content .= <<<EOF
<form id="delete_$each_component->component_id" class="delete-form float-right-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_delete">
<input type="submit" value="Delete">
</form>
EOF;

			}

$content .= <<<EOF
</div>
<div class="clickbar-title clickbar-closed"><span>Edit this $recipe->title</span></div>
</div>
EOF;

			$vce->content->add('admin', $content);
		
		}
	
	}


	/**
	 * add a new set
	 */
	public function add_component($recipe_component, $vce) {
	
		// create dossier
		$dossier_for_create = $vce->generate_dossier($recipe_component->dossier);
	
		// add javascript to page
		$vce->site->add_script(dirname(__FILE__) . '/js/script.js', 'select2');
		
		// create dossier for checkurl functionality
		$dossier = array(
		'type' => $recipe_component->type,
		'procedure' => 'checkurl'
		);

		// add dossier, which is an encrypted json object of details uses in the form
		$dossier_for_checkurl = $vce->generate_dossier($dossier);

	
$content = <<<EOF
<div class="clickbar-container admin-container add-container">
<div class="clickbar-content">
<form id="create_sets" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_create">
<label>
<input id="create-title" type="text" name="title" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Name of $recipe_component->title</div>
<div class="label-error">Enter a Title</div>
</div>
</label>
<label>
<input class="check-url" type="text" name="url" parent_url="$recipe_component->parent_url/" dossier="$dossier_for_checkurl" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">URL</div>
<div class="label-error">Enter a URL</div>
</div>
</label>
<label>
<input type="hidden" class="user_ids" name="user_ids" value="">
<select class="users_select" name="users" multiple="multiple">
<option value=""></option>
EOF;

		foreach (user::get_users(array('roles' => $recipe_component->role_select)) as $each_user) {
				$content .= '<option value="' . $each_user->user_id . '">' . $each_user->first_name . ' ' . $each_user->last_name . '</option>';
		}
		
$content .= <<<EOF
</select>
<div class="label-text">
<div class="label-message">User Acesss</div>
<div class="label-error">Enter Users</div>
</div>
</label>
<input type="submit" value="Create">
</form>
</div>
<div class="clickbar-title clickbar-closed"><span>Add a new $recipe_component->title</span></div>
</div>
EOF;

		$vce->content->add('admin', $content);
		
	}

	
	/**
	 * Create a new Set
	 */
	protected function create($input) {
		
		$input['user_access'] = $input['user_ids'];
		
		unset($input['users'], $input['user_ids']);
		
		if (self::create_component($input)) {
	
			echo json_encode(array('response' => 'success','procedure' => 'create','action' => 'reload','message' => 'Created'));
			return;
		
		}
		
		echo json_encode(array('response' => 'error','procedure' => 'update','message' => "Error"));
		return;

	}
	
	
	/**
	 * Update Set
	 */
	protected function update($input) {
	
		$input['user_access'] = $input['user_ids'];
		
		unset($input['users'], $input['user_ids']);
	
		if (self::update_component($input)) {
		
			echo json_encode(array('response' => 'success','procedure' => 'update','action' => 'reload','message' => "Updated"));
			return;
		}
		
		echo json_encode(array('response' => 'error','procedure' => 'update','message' => "Error"));
		return;
	
	}

	
	
	/**
	 * fields for ManageRecipes
	 */
	public function recipe_fields($recipe) {
	
		global $site;
		
		$title = isset($recipe['title']) ? $recipe['title'] : self::component_info()['name'];
		$template = isset($recipe['template']) ? $recipe['template'] : null;
		$repudiated_url = isset($recipe['repudiated_url']) ? $recipe['repudiated_url'] : null;
		$role_select = isset($recipe['role_select']) ? $recipe['role_select'] : null;
		
$elements = <<<EOF
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
<label for="">
<div class="input-padding">
EOF;

		foreach (json_decode($site->roles, true) as $key=>$each_role) {
		
			// allow both simple and complex role definitions
			$role_name = is_array($each_role) ? $each_role['role_name'] : $each_role;
		
			$elements .= '<label class="ignore"><input type="checkbox" name="role_select" value="' . $key . '"';
			if (in_array($key,explode('|',$role_select))) {
				$elements .= ' checked="checked"';
			}
			$elements .= ' tag="required">  ' . $role_name . '</label> ';
		}

$elements .= <<<EOF
</div>
<div class="label-text">
<div class="label-message">Select Users From Which Roles?</div>
<div class="label-error">Must select at least one role</div>
</div>
</label>
EOF;

		return $elements;
		
	}


}