<?php

class Item extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Item',
			'description' => 'Allows users to create a URL specific component',
			'category' => 'site'
		);
	}

	/**
	 *
	 */
	public function as_content($each_component, $vce) {
	
		// content
		if ($each_component->component_id == $vce->requested_id) {
	
			$vce->title = $each_component->title;
	
		} else {
		
			$vce->content->add('main','<div class="item">');
		
		}
	
	}

	/**
	 *
	 */
	public function as_content_finish($each_component, $vce) {
	
		if ($each_component->component_id != $vce->requested_id) {

			$vce->content->add('main','</div>');
		
		}
	
	}
	
	/**
	 *
	 */
	public function edit_component($each_component, $vce) {
	
		if ($vce->page->can_edit($each_component)) {

			// the instructions to pass through the form
			$dossier = array(
			'type' => $each_component->type,
			'procedure' => 'update',
			'component_id' => $each_component->component_id,
			'created_at' => $each_component->created_at
			);

			// generate dossier
			$dossier_for_update = $vce->generate_dossier($dossier);
			
			// create dossier for checkurl functionality
			$dossier = array(
			'type' => $each_component->type,
			'procedure' => 'checkurl',
			'current_url' => $each_component->url
			);

			// add dossier, which is an encrypted json object of details uses in the form
			$dossier_for_checkurl = $vce->generate_dossier($dossier);


$content = <<<EOF
<div class="clickbar-container admin-container edit-container">
<div class="clickbar-content">
<form id="update_$each_component->component_id" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_update">
<label>
<input type="text" name="title" value="$each_component->title" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Title</div>
<div class="label-error">Enter a Title</div>
</div>
</label>
<label>
<input type="text" name="url" value="$each_component->url" dossier="$dossier_for_checkurl" autocomplete="off">
<div class="label-text">
<div class="label-message">URL</div>
<div class="label-error">Enter a URL</div>
</div>
</label>
<label>
<input type="text" name="sequence" value="$each_component->sequence">
<div class="label-text">
<div class="label-message">Order Number</div>
<div class="label-error">Enter an Order Number</div>
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
<div class="clickbar-title clickbar-closed"><span>Edit $each_component->title</span></div>
</div>
EOF;

			$vce->content->add('admin', $content);
		
		}
	
	}
	
	
	/**
	 *
	 */
	public function add_component($recipe_component, $vce) {
	
		// create dossier
		$dossier_for_create = $vce->generate_dossier($recipe_component->dossier);
	
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
<form id="create_items" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_create">
<label>
<input id="create-title" type="text" name="title" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Name of $recipe_component->title</div>
<div class="label-error">Enter a Title</div>
</div>
</label>
<label>
<input class="check-url" type="text" name="url" value="" parent_url="$recipe_component->parent_url/"  dossier="$dossier_for_checkurl" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">URL</div>
<div class="label-error">Enter a URL</div>
</div>
</label>
<input type="submit" value="Create">
</form>
</div>
<div class="clickbar-title clickbar-closed"><span>Add A New $recipe_component->title</span></div>
</div>
EOF;
		$vce->content->add('main', $content);

	}

	
	
	/**
	 * fields for ManageRecipe
	 */
	public function recipe_fields($recipe) {
	
		global $site;
	
		$title = isset($recipe['title']) ? $recipe['title'] : self::component_info()['name'];
		$template = isset($recipe['template']) ? $recipe['template'] : null;

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
EOF;

		return $elements;
		
	}

}