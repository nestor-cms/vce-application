<?php

class MediaType {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Media Type',
			'description' => 'Base class for all media types, such as Image.',
			'category' => 'media',
			'typename' => null
		);
	}

	/**
	 * component has been installed
	 */
	public function installed() {
	}

	/**
	 * component has been activated
	 */
	public function activated() {
	}
	
	/**
	 * component has been disabled
	 */
	public function disabled() {
	}
	
	/**
	 * component has been removed, as in deleted
	 */
	public function removed() {
	}
	
	/**
	 * this will hopefully prevent an error when a component has been disabled
	 */
	public function preload_component() {
		return false;
	}

	/**
	 * display media
	 */
	public function display($each_component, $vce) {
	
		if (!isset(json_decode($vce->site->enabled_mediatype, true)[$each_component->media_type])) {
		
			$delete_button = null;

			if ($each_component->created_by == $vce->user->user_id ) {

				// the instructions to pass through the form
				$dossier = array(
				'type' => $each_component->type,
				'procedure' => 'delete',
				'component_id' => $each_component->component_id,
				'created_at' => $each_component->created_at
				);

				// generate dossier
				$dossier_for_delete = $vce->generate_dossier($dossier);
			

			
				$delete_button = <<<EOF
<form id="delete_$each_component->component_id" class="delete-form inline-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_delete">
<input type="submit" value="Delete File">
</form>
EOF;

			}
		
			$vce->content->add('main','<div class="form-message form-error">' . $each_component->media_type . ' This unsupported file type cannot be displayed  ' . $delete_button . '</div>');
			
		}
		
	}
	
	/**
	 * add media
	 */
	public static function add($each_component, $vce) {
	}
	
	/**
	 * edit media, called from edit_media_component() in media component
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
<div class="label-message">Title</div>
<div class="label-error">Enter a Title</div>
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

	/**
	 * check if the media will require the file uploader or not
	 */
	 public static function file_upload() {
	 	return false;
	 }
 
	/**
	 * a way to pass file extensions to the plupload to limit file selection
	 */
	 public static function file_extensions() {
		/*
		{title : "Image files", extensions : "gif,png,jpg,jpeg"},
		{title : "PDF files", extensions : "pdf"},
		{title : "Office files", extensions : "doc,docx,ppt,pptx,xls,xlsx"},
		{title : "Audio files", extensions : "mp3"},
		{title : "Video files", extensions : "mpg,mpeg,mov,mp4,m4v,wmv,avi,asx,asf"}
		*/
	 	return array('title' => '','extensions' => '');
	 }

	 /**
	  * a way to pass the mimetype and mimename to vce-upload.php
	  * the minename is the class name of the mediaplayer.
	  * mimetype can have a wildcard for subtype, included after slash by adding .*
	  * http://www.iana.org/assignments/media-types/media-types.xhtml
	  */
	 public static function media_type() {
	 	/*
	 	return array(
	 	'image/.*' => 'Image'
	 	);
	 	*/
	 	return null;
	 }
	 
	/**
	 * hide configuration for component
	 */
	public function component_configuration() {
		return false;
	}

	/**
	 * hide from ManageRecipe
	 */
	public function recipe_fields($recipe) {
		return false;
	}
}