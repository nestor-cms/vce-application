<?php

class Logout extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Logout',
			'description' => 'Component for creating a logout link',
			'category' => 'site'
		);
	}

	/**
	 * calls to logout function and then forwards to site url
	 */
	public function as_content($each_component, $vce) {

		// call to logout function
		$vce->user->logout();
		
		// to front of site
		header('location: ' . $vce->site->site_url);
		
	}

	/**
	 * fileds to display when this is created
	 */
	function recipe_fields($recipe) {
	
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