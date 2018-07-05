<?php

class Pagination extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Pagination',
			'description' => 'When sub components is inside this component, sub components are paginated',
			'category' => 'site'
		);
	}

	/**
	 * things to do when this component is preloaded
	 */
	public function preload_component() {
		
		$content_hook = array (
		'page_build_content_callback' => 'Pagination::paginate_components'
		);

		return $content_hook;

	}

	/**
	 * build_sub_components
	 */
	public static function paginate_components($sub_components, $each_component, $vce) {
	
		if (isset($each_component->pagination_length) || isset($each_component->recipe['pagination_length'])) {
		
			$pagination_length = isset($each_component->pagination_length) ? $each_component->pagination_length : $each_component->recipe['pagination_length'];
		
			$vce->pagination_total = count($sub_components);
			$vce->pagination_pages = ceil(count($sub_components) / $pagination_length);
			$vce->pagination_current = isset($vce->pagination_current) ? $vce->pagination_current : 1;
					
			$pagination_offset = ($vce->pagination_current != 1) ? ($pagination_length * ($vce->pagination_current - 1)) : 0;
					
			// use array_slice to limit sub components recursively passed back to build_content
			$sub_components = array_slice($sub_components, $pagination_offset, $pagination_length);
		}
		
		return $sub_components;
		
	}

		
	/**
	 * book end of as_content
	 */
	public function as_content_finish($each_component, $vce) {

		if ($vce->pagination_pages > 1) {
		
			// set pagination_current value so that we arrive there again
			$vce->site->add_attributes('pagination_current',$vce->pagination_current);
		
			// create a special dossier
			$dossier_for_show = $vce->generate_dossier(array('type' => 'Pagination','procedure' => 'show'));		

			$content = '<div class="pagination">';
		
			$content .= '<div>' . $vce->pagination_current . ' of ' . $vce->pagination_pages . '</div>';

			for ($x = 1;$x <= $vce->pagination_pages; $x++) {

				$class = ($x == $vce->pagination_current) ? 'class="highlighted"': '';

		
$content .= <<<EOF
<form class="inline-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_show">
<input type="hidden" name="pagination_current" value="$x">
<input $class type="submit" value="$x">
</form>
EOF;
		
			}
		
			$content .= '</div>';

			$vce->content->add('main',$content);
		
		}
	
	}
	
	
	/**
	 * show
	 */
	protected function show($input) {
	
		global $site;
		
		$site->add_attributes('pagination_current',$input['pagination_current']);
	
		echo json_encode(array('response' => 'success','action' => 'reload','delay'=>'0'));
		return;
	
	}
	
	/**
	 * fields for ManageRecipe
	 */
	public function recipe_fields($recipe) {
	
		global $site;
	
		$title = isset($recipe['title']) ? $recipe['title'] : self::component_info()['name'];
		$template = isset($recipe['template']) ? $recipe['template'] : null;
		$pagination_length = isset($recipe['pagination_length']) ? $recipe['pagination_length'] : 1;

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
<select name="pagination_length">
EOF;

		for ($x=1;$x<21;$x++) {
			$elements .= '<option value="' . $x . '"';
			if ($x == $pagination_length) {
				$elements .= ' selected';
			}
			$elements .= '>' . $x . '</option>';
		}

$elements .= <<<EOF
</select>
<div class="label-text">
<div class="label-message">Sub-Components Paginatate</div>
<div class="label-error">Enter a Number</div>
</div>
</label>
EOF;

		return $elements;
		
	}

}