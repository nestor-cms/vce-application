<?php

class Sort extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Sort',
			'description' => 'Sort sub components by specific meta key values.',
			'category' => 'site'
		);
	}

	/**
	 * things to do when this component is preloaded
	 */
	public function preload_component() {
		
		$content_hook = array (
		'page_get_sub_components' => 'Sort::sort_sub_components'
		);

		return $content_hook;

	}


	/**
	 * sort assignments by open_data
	 */
	public static function sort_sub_components($requested_components,$sub_components,$vce) {
	
		foreach ($sub_components as $component_key=>$component_info) {
		
			if ($component_info->type == "Sort") {
			
				foreach ($requested_components as $requested_key=>$requested_info) {
					
					if ($requested_info->parent_id == $component_info->component_id) {
					
						$meta_key = $component_info->sort_by;
						$order = $component_info->sort_order;
						
						usort($requested_components, function($a, $b) use ($meta_key, $order) {
							if (isset($a->$meta_key) && isset($b->$meta_key)) {
								if ($order == "desc") {
									return $a->$meta_key > $b->$meta_key ? -1 : 1;
								} else {
									return $a->$meta_key > $b->$meta_key ? 1 : -1;
								}
							} else {
								return 1;
							}
						});
							
						return $requested_components;
							
					}

				}
		
			}
		
		}
	
		return $requested_components;
		
	}

	
	/**
	 * hide this component from being added to a recipe
	 */
	public function recipe_fields($recipe) {
		global $site;
		
		$title = isset($recipe['title']) ? $recipe['title'] : self::component_info()['name'];
		$sort_by = isset($recipe['sort_by']) ? $recipe['sort_by'] : 'title';
		$sort_order = isset($recipe['sort_order']) ? $recipe['sort_order'] : 'asc';
		
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
<input type="text" name="sort_by" value="$sort_by" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Sort By (Meta Key)</div>
<div class="label-error">Enter a meta key to sort by</div>
</div>
</label>
<label>
<select name="sort_order">
EOF;

		$orders = array(
		'Asc' => 'asc',
		'Desc' => 'desc'
		);

		foreach($orders as $key=>$value) {
	
			$elements .= '<option value="' . $value . '"';
			if ($value == $sort_order) {
				$elements .= ' selected';
			}
			$elements .= '>' . $key . '</option>';
	
		}

$elements .= <<<EOF
</select>
<div class="label-text">
<div class="label-message">Sort Order</div>
<div class="label-error">Enter a sort order</div>
</div>
</label>
EOF;

		return $elements;
		
	}

}