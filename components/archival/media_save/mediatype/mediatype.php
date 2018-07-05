<?php

class MediaType {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Media Type',
			'description' => 'Base class for all media types, such as Image.',
			'category' => 'media'
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
	public function display($each_component, $page) {
	
		if (!isset(json_decode($page->site->enabled_mediatypes, true)[$each_component->media_type])) {
	
			$page->content->add('main','<div class="warning">' . $each_component->media_type . ' media player has been disabled</div>');

		}
		
	}
	
	/**
	 * add media
	 */
	public static function add($each_component, $page) {
	}
	
	/**
	 * edit media, called from as_content_finish() in media component
	 */
	public static function edit($each_component, $page) {

$content_mediatype = <<<EOF
<div class="clickbar-container edit-container">
<div class="clickbar-content">
<form id="update_$each_component->component_id" class="asynchronous-form" method="post" action="$page->input_path" autocomplete="off">
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
</form>
<form id="delete_$each_component->component_id" class="float-right-form delete-form asynchronous-form" method="post" action="$page->input_path">
<input type="hidden" name="dossier" value="$each_component->dossier_for_delete">
<input type="submit" value="Delete">
</form>
</div>
<div class="clickbar-title clickbar-closed"><span>Edit $each_component->title</span></div>
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