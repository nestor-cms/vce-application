<?php

class UserSettings extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'User Settings',
			'description' => 'Allows users to update their account',
			'category' => 'user'
		);
	}
	
	
	/**
	 *
	 */
	public function as_content($each_component, $vce) {
	
		//global $site;
		$user = $vce->user;
		
		// add javascript to page
		$vce->site->add_script(dirname(__FILE__) . '/js/script.js');
		
		$first_name = isset($user->first_name) ? $user->first_name : null;
		$last_name = isset($user->last_name) ? $user->last_name : null;
		
		$site_roles = json_decode($vce->site->roles, true);
		
		// get site user attributes
		$user_attributes = json_decode($vce->site->user_attributes, true);
		
		// allow both simple and complex role definitions
		$user_role = is_array($site_roles[$user->role_id]) ? $site_roles[$user->role_id]['role_name'] : $site_roles[$user->role_id];
		
		// create a special dossier
		$dossier_for_password = $vce->user->encryption(json_encode(array('type' => 'UserSettings','procedure' => 'password')),$vce->user->session_vector);		
		$dossier_for_update = $vce->user->encryption(json_encode(array('type' => 'UserSettings','procedure' => 'update')),$vce->user->session_vector);		

$content = null;

		if (!isset($user_attributes['password']) || !isset($user_attributes['password']['type']) || $user_attributes['password']['type'] != 'conceal') {
		
$content .= <<<EOF
<p>
<div class="clickbar-container">
<div class="clickbar-content">
<form id="password" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_password">
<label>
<input type="password" name="password" value="" tag="required">
<div class="label-text">
<div class="label-message">Enter New Password</div>
<div class="label-error">Enter Password</div>
</div>
</label>
<label>
<input type="password" name="password2" value="" tag="required">
<div class="label-text">
<div class="label-message">Repeat New Password</div>
<div class="label-error">Repeat Password</div>
</div>
</label>
<input type="submit" value="Update">
</form>
</div>
<div class="clickbar-title clickbar-closed"><span>Update Password</span></div>
</div>
</p>
EOF;


		}

$content .= <<<EOF
<p>
<div class="clickbar-container">
<div class="clickbar-content clickbar-open">
<form id="update" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_update">
EOF;


		if (!isset($user_attributes['password']) || !isset($user_attributes['password']['type']) || $user_attributes['password']['type'] != 'conceal') {

$content .= <<<EOF
<label>
<input type="text" name="email" value="$user->email" current="$user->email" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Email</div>
<div class="label-error">Enter your Email</div>
</div>
</label>
<label id="password-required" style="display:none;">
<input id="password-input" type="text" name="password">
<div class="label-text">
<div class="label-message">Enter Current Password</div>
<div class="label-error">Enter your Current Password</div>
</div>
</label>
EOF;

		} else {


$content .= <<<EOF
<label>
<div class="input-padding">
$user->email
</div>
<div class="label-text">
<div class="label-message">Email</div>
<div class="label-error">Enter your Email</div>
</div>
</label>
EOF;
		
		}
		
		
		/* attributes */
		
		foreach ($user_attributes as $user_attributes_key=>$user_attributes_value) {
		
			// nice title for this user attribute
			$title = isset($user_attributes_value['title']) ? $user_attributes_value['title'] : ucwords(str_replace('_', ' ', $user_attributes_key));
			
			// check if required
			$tag = (isset($user_attributes_value['required']) && $user_attributes_value['required'] == '1') ? 'required' : null;

			// attribute value
			$attribute_value = isset($user->$user_attributes_key) ? $user->$user_attributes_key : null;

			// if a datalist has been assigned
			if (isset($user_attributes_value['datalist'])) {

				if (!is_array($user_attributes_value['datalist'])) {
					$datalist_field = 'datalist';
					$datalist_value = $user_attributes_value['datalist'];
				} else {
					$datalist_field = array_keys($user_attributes_value['datalist'])[0];
					$datalist_value = $user_attributes_value['datalist'][$datalist_field];
				}

				$options_data = $vce->get_datalist_items(array($datalist_field => $datalist_value));

				$options = array();

				if (!empty($options_data)) {

					foreach ($options_data['items'] as $option_key=>$option_value) {
				
						$options[$option_key] = $option_value['name'];
				
					}
				
				}

			}
			
			
			// if options is set
			if (isset($user_attributes_value['options'])) {
				$options = $user_attributes_value['options'];
			}
					

			if (isset($user_attributes_value['type'])) {
			
				// skip if conceal
				if ($user_attributes_value['type'] == 'conceal') {
					continue;
				}
				
$content .= <<<EOF
<label>
EOF;

				// check to see if this attribute is not editable
				if (!isset($user_attributes_value['editable']) || $user_attributes_value['editable'] == 0) {

$content .= <<<EOF
<div class="input-padding">
$attribute_value
</div>
EOF;

				} else {
				// normal attributes


					// if this is text
					if ($user_attributes_value['type'] == 'text') {
				
$content .= <<<EOF
<input type="text" name="$user_attributes_key" value="$attribute_value" tag="$tag" autocomplete="off">
EOF;

						}
					
					
					// if this is a radio button
					if ($user_attributes_value['type'] == 'radio' || $user_attributes_value['type'] == 'checkbox') {

$content .= <<<EOF
<div class="input-padding">
EOF;

						$type = $user_attributes_value['type'];

						$option_counter = 0;
					
						foreach ($options as $option_key=>$option_value) {
					
							$option_counter++;
							$input_name = $user_attributes_key;
						
							// if this is a checkbox, then append with _1, _2
							if ($user_attributes_value['type'] == 'checkbox') {
								$input_name .= '_' . $option_counter;
															
								// check if checkbox selected
								if (in_array($option_key,json_decode($attribute_value))) {
									$checked = 'checked';
								} else {
									$checked = '';
								}

							} else {
					
								// check if radio selected
								if ($option_key == $attribute_value) {
									$checked = 'checked';
								} else {
									$checked = '';
								}
						
							}

$content .= <<<EOF
<label class="ignore"><input type="$type" name="$input_name" value="$option_key" $checked> $option_value </label>
EOF;

						}
						
$content .= <<<EOF
</div>
EOF;
			
					}
				
					// if this is text
					if ($user_attributes_value['type'] == 'select') {
					
$content .= <<<EOF
<select name="$user_attributes_key" tag="$tag" autocomplete="off">
<option value=""></option>
EOF;

						if (isset($options)) {
							foreach ($options as $option_key=>$option_value) {
								$content .= '<option value="' . $option_key . '"';
								if ($option_key == $attribute_value) {
									$content .= ' selected';
								}
								$content .= '>' . $option_value . '</option>';
							}
						}
						
$content .= <<<EOF
</select>
EOF;

					}
				
				}
					
					
$content .= <<<EOF
<div class="label-text">
<div class="label-message">$title</div>
<div class="label-error">Enter your $title</div>
</div>
</label>
EOF;

			}

		}

		// add hooks
		if (isset($page->site->hooks['manage_users_attributes'])) {
			foreach($page->site->hooks['manage_users_attributes'] as $hook) {
				$content .= call_user_func($hook, $page->user);
			}
		}

$content .= <<<EOF
<label>
<div class="input-padding">
$user_role
</div>
<div class="label-text">
<div class="label-message">Role</div>
<div class="label-error">Enter your Role</div>
</div>
</label>
<input type="submit" value="Update">
<input type="reset" value="Reset">
</form>
</div>
<div class="clickbar-title disabled"><span>Update User Settings</span></div>
</div>
</p>
EOF;

		$vce->content->add('main', $content);
	
	}


	
	/**
	 *
	 */
	public function check_access($each_component, $vce) {

		if (isset($vce->user->user_id)) {
			return true;
		}
		
		// in the event that a user is not logged in, redirect to top of site

		// to front of site
		header('location: ' . $vce->site->site_url);

	}
	
	
	/**
	 *
	 */
	protected function password($input) {
	
		global $db;
		global $user;
	
		if ($input['password'] != $input['password2']) {
			echo json_encode(array('response' => 'error','message' => 'Passwords do not match','action' => ''));
			return;
		}
		
		// call to user class to create_hash function
		$hash = user::create_hash($user->email, $input['password']);
			
		// update hash
		$update = array('hash' => $hash);
		$update_where = array( 'user_id' => $user->user_id);
		$db->update('users', $update, $update_where);

		echo json_encode(array('response' => 'success','message' => 'Password Updated','action' => ''));
		return;
		
	}
	
	/**
	 *
	 */
	protected function update($input) {
	
		/* hide for now until user management are centralized into class.user.php
		global $user;
		echo User::update_user($input);
		return;
		*/
	
		global $db;
		global $site;
		global $user;
		
		// loop through to look for checkbox type input
		foreach ($input as $input_key=>$input_value) {
			// for checkbox inputs
			if (preg_match('/_\d+$/',$input_key,$matches)) {
				// strip _1 off to find input value for checkbox
				$new_input = str_replace($matches[0],'', $input_key);
				// decode previous json object value for input variable
				$new_value = isset($input[$new_input]) ? json_decode($input[$new_input], true) : array();
				// add new value to array
				$new_value[] = $input_value;
				// remove the _1
				unset($input[$input_key]);
				// reset the input with json object
				$input[$new_input] = json_encode($new_value);
			}
		}

		// get user attributes
		$user_attributes = json_decode($site->user_attributes, true);

		// start with default
		$attributes = array('email' => 'text');
		
		// assign values into attributes for order preserving hash in minutia column
		if (isset($user_attributes)) {
			foreach ($user_attributes as $user_attributes_key=>$user_attributes_value) {
				if (isset($user_attributes_value['sortable']) && $user_attributes_value['sortable']) {
					$value = isset($user_attributes_value['type']) ? $user_attributes_value['type'] : null;
					$attributes[$user_attributes_key] = $value;
				}
			}
		}
		
		$user_id = $user->user_id;
		
		// we don't need to store the type, so unset this
		unset($input['type']);
		
		// get user vector
		$query = "SELECT vector FROM " . TABLE_PREFIX . "users WHERE user_id='" . $user_id . "' LIMIT 1";
		$user_vector = $db->get_data_object($query);
		
		// set
		$vector = $user_vector[0]->vector;
		
		// check if email has been changed
		if (isset($input['email']) && $input['email'] != $user->email) {
			$input['email'] = filter_var(strtolower($input['email']), FILTER_SANITIZE_EMAIL);
			if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
				echo json_encode(array('response' => 'error','message' => 'Not a valid email address','form' => 'create', 'action' => ''));
				return;
			}
			
			// create lookup
			$lookup = user::lookup($input['email']);
			
			// get user vector
			$query = "SELECT id FROM " . TABLE_PREFIX . "users_meta WHERE meta_key='lookup' and meta_value='" . $lookup . "' LIMIT 1";
			$lookup_check = $db->get_data_object($query);
			
			if (!empty($lookup_check)) {
				echo json_encode(array('response' => 'error','message' => 'Email is already in use','form' => 'create', 'action' => ''));
				return;
			}
				
			// call to user class to create_hash function
			$hash = user::create_hash($user->email, $input['password']);
			
			$query = "SELECT user_id FROM  " . TABLE_PREFIX . "users WHERE hash='" . $hash . "' LIMIT 1";
			$password_check = $db->get_data_object($query);
			
			// check that password is correct
			if (empty($password_check)) {
				echo json_encode(array('response' => 'error','message' => 'Password is not correct','form' => 'create', 'action' => ''));
				return;
			}
			
			// call to user class to create_hash function
			$hash = user::create_hash($input['email'], $input['password']);
			
			// update hash
			$update = array( 'hash' => $hash);
			$update_where = array( 'user_id' => $user->user_id);
			$db->update( 'users', $update, $update_where );
			
			// update hash
			$update = array( 'meta_value' => $lookup);
			$update_where = array('user_id' => $user->user_id, 'meta_key' => 'lookup');
			$db->update( 'users_meta', $update, $update_where );
			
		}
		
		unset($input['password']);
		
		// delete old meta data
		
		foreach ($input as $key => $value) {
				
			// delete user meta from database
			$where = array( 'user_id' => $user_id, 'meta_key' => $key);
			$db->delete('users_meta', $where);
		
		}
		
		// now add meta data
		
		$records = array();
		
		foreach ($input as $key => $value) {

			// encode user data
			$encrypted = user::encryption($value, $vector);
			
			$minutia = null;
			
			// if this is a sortable text attribute
			if (isset($attributes[$key])) {
				// check if this is a text field
				if ($attributes[$key] == 'text') {
					$minutia = user::order_preserving_hash($value);
				}
				// other option will go here
			}
			
			$records[] = array(
			'user_id' => $user_id,
			'meta_key' => $key,
			'meta_value' => $encrypted,
			'minutia' => $minutia
			);
			
		}
		
		$db->insert('users_meta', $records);
		
		// reload user object
		$user->make_user_object($user_id);
		
		echo json_encode(array('response' => 'success','procedure' => 'create','action' => 'reload','message' => 'User Settings Updated'));
		return;

	}
	

	/**
	 * fileds to display when this is created
	 */
	public function recipe_fields($recipe) {
	
		global $site;
		
		$site->get_template_names();
	
		$title = isset($recipe['title']) ? $recipe['title'] : self::component_info()['name'];
		$url = isset($recipe['url']) ? $recipe['url'] : null;
		$template = isset($recipe['template']) ? $recipe['template'] : null;

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
<div class="label-message">URL (optional)</div>
<div class="label-error">Enter a URL</div>
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
EOF;

		return $elements;
		
	}

}