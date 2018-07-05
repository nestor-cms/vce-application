<?php

class Image extends MediaType {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Image (Media Type)',
			'description' => 'Adds Image to Media',
			'category' => 'media'
		);
	}

	/**
	 * 
	 */
	public function display($each_component, $vce) {
    	
    	// expires = how many seconds from now?
    	// path = $each_component->created_by . '/' . $each_component->path
    	// name = the name given to the media item
    	// user_id = $user->user_id check the user id of the current user. 
    	// disposition  = attachment/inline
    	// here's a list of content disposition values
		// http://www.iana.org/assignments/cont-disp/cont-disp.xhtml
    	$fileinfo = array(
    	'expires' => 30,
    	'path' => $each_component->created_by . '/' . $each_component->path
    	);
        		
    	$vce->content->add('main','<img class="vce-image" src="' . $vce->site->media_link($fileinfo) . '">');

    }
    
    /**
     * file uploader needed
     */
   	public static function file_upload() {
	 	return true;
	}


	/**
	 * a way to pass file extensions to the plupload to limit file selection
	 */
	 public static function file_extensions() {
	 	//{title:'Image files',extensions:'gif,png,jpg,jpeg'};
	 	return array('title' => 'Image files','extensions' => 'gif,png,jpg,jpeg');
	 }
	 
	 /**
	  * a way to pass the mimetype and mimename to vce-upload.php
	  * the minename is the class name of the mediaplayer.
	  * mimetype can have a wildcard for subtype, included after slash by adding .*
	  * https://www.sitepoint.com/mime-types-complete-list/
	  */
		public static function mime_info() {
	 	return array(
	 	'image/.*' => get_class()
	 	);
	 }


}