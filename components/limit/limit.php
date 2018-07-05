<?php

class Limit extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Limit',
			'description' => 'Limit the number of sub components that can be created within this component',
			'category' => 'site'
		);
	}
	
	/**
	 *  limit
	 */
	public function allow_sub_components($each_component, $vce) {
	
		if (isset($each_component->components) && isset($each_component->components_limit)) {
			if (count($each_component->components) >= $each_component->components_limit) {
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * fields for ManageRecipe
	 */
	public function recipe_fields($recipe) {
	
		global $site;
	
		$title = isset($recipe['title']) ? $recipe['title'] : self::component_info()['name'];
		$template = isset($recipe['template']) ? $recipe['template'] : null;
		$components_limit = isset($recipe['components_limit']) ? $recipe['components_limit'] : 1;

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
<select name="components_limit">
EOF;

		for ($x=1;$x<21;$x++) {
			$elements .= '<option value="' . $x . '"';
			if ($x == $components_limit ) {
				$elements .= ' selected';
			}
			$elements .= '>' . $x . '</option>';
		}

$elements .= <<<EOF
</select>
<div class="label-text">
<div class="label-message">Sub-Components Limit</div>
<div class="label-error">Enter a Number</div>
</div>
</label>
EOF;

		return $elements;
		
	}

}