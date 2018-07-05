<?php

class Layout extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Layout',
			'description' => 'Allows for blocks to be created for layout with CSS',
			'category' => 'site'
		);
	}
	
	/**
	 *
	 */
	function as_content($each_component, $vce) {

		$vce->content->add('main','<div class="layout-block ' . $each_component->class . '">');
	}
	
	/**
	 *
	 */
	function as_content_finish($each_component, $vce) {
	
		$vce->content->add('main', '</div>');

	}

	/**
	 * Edit existing
	 */
	public function edit_component($each_component, $vce) {
	
		if ($vce->can_edit($each_component)) {
	
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
		

$content = <<<EOF
<div class="clickbar-container admin-container edit-container">
<div class="clickbar-content">
<form id="update_$each_component->component_id" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_update"><label>
<input id="title" type="text" name="title" value="$each_component->title" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Title of $each_component->type</div>
<div class="label-error">Enter a Title</div>
</div>
</label>

<label>
<input id="class" type="text" name="class" value="$each_component->class" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">CSS Class Name</div>
<div class="label-error">Enter CSS Class Name</div>
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

			if ($vce->can_delete($each_component)) {
				
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
<form id="delete_$each_component->component_id" class="delete-form float-right-form asynchronous-form" method="post" action="$page->input_path">
<input type="hidden" name="dossier" value="$each_component->dossier_for_delete">
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
	 *
	 */
	public function add_component($recipe_component, $vce) {

		global $site;
		
		// create dossier
		$dossier_for_create = $vce->generate_dossier($recipe_component->dossier);

$content = <<<EOF
<div class="clickbar-container admin-container add-container">
<div class="clickbar-content">
<form id="update" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_create">

<label>
<input id="create-title" type="text" name="title" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Name of $recipe_component->title</div>
<div class="label-error">Enter a Title</div>
</div>
</label>

<label>
<input id="class" type="text" name="class" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">CSS Class Name</div>
<div class="label-error">Enter CSS Class Name</div>
</div>
</label>

<input type="hidden" name="sequence" value="$recipe_component->sequence">

<input type="submit" value="Create">
</form>
</div>
<div class="clickbar-title clickbar-closed"><span>Add A New $recipe_component->title</span></div>
</div>
EOF;

		$vce->content->add('admin', $content);


	}
	
	
	/**
	 *  fields for ManageRecipe
	 */
	public function recipe_fields($recipe) {
	
		$title = isset($recipe['title']) ? $recipe['title'] : self::component_info()['name'];
	
$elements = <<<EOF
<label>
<input type="text" name="title" value="$title" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Title</div>
<div class="label-error">Enter a Title</div>
</div>
</label>
EOF;

		return $elements;
		
	}

}