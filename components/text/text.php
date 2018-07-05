<?php

class Text extends MediaType {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Text (Media Type)',
			'description' => 'Adds Text to Media',
			'category' => 'media'
		);
	}    
	/**
	 * 
	 */
	public function display($each_component, $vce) {
    	
    	// this can be deleted after testing is over
    	$text = isset($each_component->text) ? nl2br($each_component->text) : nl2br($each_component->title);
    		
    	$vce->content->add('main','<div class="media-text-block">' . $text . '</div>');
    
    }
    
	/**
	 * 
	 */    
    public static function add($recipe_component, $vce) {

$content_mediatype = <<<EOF
<div id="text-block-container add-container">
<div class="clickbar-container">
<div class="clickbar-content">
<form id="create_media" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$recipe_component->dossier_for_create">
<input type="hidden" name="media_type" value="Text">
<label> 
<input type="text" name="title" value="Text Block" class="" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Text Block Title</div>
<div class="label-error">Enter Text Block Title</div>
</div>
</label>
<label>
<textarea name="text" class="textarea-input" tag="required"></textarea>
<div class="label-text">
<div class="label-message">Text Block Content</div>
<div class="label-error">Enter Text Block Content</div>
</div>
</label>
<input type="hidden" name="sequence" value="$recipe_component->sequence">
<input type="submit" value="Create">
</form>
</div>
<div class="clickbar-title clickbar-closed"><span>Add A Text Block</span></div>
</div>
</div>
EOF;

		return $content_mediatype;
    
    }
    
    
	/**
	 * 
	 */    
    public static function edit($each_component, $vce) {

$content_mediatype = <<<EOF
<div class="media-edit-container">
<div class="media-edit-open" title="edit">&#9998;</div>
<div class="media-edit-form">
<form id="update_$each_component->component_id" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$each_component->dossier_for_edit">
<label>
<input type="text" name="title" value="$each_component->title" tag="required">
<div class="label-text">
<div class="label-message">Text Block Title</div>
<div class="label-error">Enter Text Block Title</div>
</div>
</label>
<label>
<textarea name="text" class="textarea-input" tag="required">$each_component->text</textarea>
<div class="label-text">
<div class="label-message">Text Block Content</div>
<div class="label-error">Enter Text Block Content</div>
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
<div class="link-button media-edit-cancel">Cancel</div>
</form>
<form id="delete_$each_component->component_id" class="float-right-form delete-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$each_component->dossier_for_delete">
<input type="submit" value="Delete">
</form>
</div>
</div>
EOF;

		return $content_mediatype;
        
    }

}