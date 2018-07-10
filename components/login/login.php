<?php

class Login extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Login',
			'description' => 'Simple login form using email and password. A User must login to view anything contained with this component.',
			'category' => 'site'
		);
	}

	/**
	 * check if get_sub_components should be called.
	 * @return bool
	 */
	public function find_sub_componets($requested_component, $vce) {
	
		// if user has not logged in, return false
		if (!isset($vce->user->user_id)) {
		
			return false;
		
		}
	
		// return true if user has logged in
		return true;
	}
		
	/**
	 *
	 */
	public function check_access($each_component, $vce) {

		if (!isset($vce->user->user_id)) {
			
			//add javascript
			$vce->site->add_script(dirname(__FILE__) . '/js/script.js', 'jquery');
			
			// the instructions to pass through the form with specifics
			$dossier = array(
			'type' => 'Login',
			'procedure' => 'form_input',
			'requested_url' => rtrim($vce->requested_url,'/')
			);

			// add dossier, which is an encrypted json object of details uses in the form
			$each_component->dossier = $vce->user->encryption(json_encode($dossier),$vce->user->session_vector);


$content = <<<EOF
<div class="clickbar-container">
<div class="clickbar-content clickbar-open">
<form id="login_form" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$each_component->dossier">
<label>
<input type="text" name="email" tag="required" autocapitalize="none" placeholder="Enter Your Email Address">
<div class="label-text">
<div class="label-message">Email</div>
<div class="label-error">Enter Your Email</div>
</div>
</label>
<label>
<input type="password" name="password" tag="required" placeholder="Enter Your Password">
<div class="label-text">
<div class="label-message">Password</div>
<div class="label-error">Enter your Password</div>
</div>
</label>
<input type="submit" value="Click here to Login">
</form>
</div>
<div class="clickbar-title disabled"><span>Login</span></div>
</div>
EOF;
			// add content
			$vce->content->add('main', $content);
	
			return false;
	
		} else {
		
			// login_check_access_true
			// method should return true of false
			if (isset($vce->site->hooks['login_check_access_true'])) {
				foreach($vce->site->hooks['login_check_access_true'] as $hook) {
					return call_user_func($hook, $each_component, $vce);
				}
			}
		
			return true;
		
		}
	}
	
	/**
	 * Instead of going all the way through form_input in class.component.php, we just do everything here in the child.
	 */
	public function form_input($input) {
		global $user;
		global $site;
		$input['email'] = filter_var($input['email'], FILTER_SANITIZE_EMAIL);
		if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
			echo json_encode(array('response' => 'error','message' => 'Not a valid email address','action' => 'clear'));
			return;
		}
			
		// send array to user login
		$success = $user->login($input);
		
		if ($success) {
			echo json_encode(array('response' => 'success','message' => 'Welcome Back!','action' => 'reload','url' => $site->site_url . '/' . $input['requested_url']));
			return;
		}
		// return error
		echo json_encode(array('response' => 'error','message' => 'Invalid Username/Password','action' => 'clear'));
		return;
	}
	
	
	/**
	 *
	 */
	public function recipe_fields($recipe) {
		
		global $site;
	
		$title = isset($recipe['title']) ? $recipe['title'] : self::component_info()['name'];
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