<?php

class Media extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Media',
			'description' => 'Allows for media to be uploaded and displayed.',
			'category' => 'media'
		);
	}

	/**
	 * add a hook that fires at initiation of site hooks
	 */
	public function preload_component() {
		$content_hook = array (
		'site_hook_initiation' => 'Media::require_once_mediatype'
		);
		return $content_hook;
	}

	/**
	 * loads the MediaType parent class before the children classes are loaded
	 */
	public static function require_once_mediatype($site) {
		// path to mediatype.php
		require_once(dirname(__FILE__) . '/mediatype/mediatype.php');
	}
	
	/**
	 *
	 */
	public function as_content($each_component, $vce) {
	
		$vce->content->add('main','<div class="media-item-container">');
	
		$each_type = $each_component->media_type;
		
		$media_players = json_decode($vce->site->enabled_mediatype, true);
		
		// check that player hasn't been disabled
		if (isset($media_players[$each_type])) {
		
			// load media player class by Type
			require_once(BASEPATH . $media_players[$each_type]);
			
			// call to the component class for Type
			$this_component = new $each_type();
			
		} else {
		
			// load parent class as backup
			$this_component = new MediaType();
		
		}
		
		$vce->content->add('main','<div class="media-item">');
		
		// load hooks
		// media_before_display
		if (isset($vce->site->hooks['media_before_display'])) {
			foreach($vce->site->hooks['media_before_display'] as $hook) {
				call_user_func($hook, $each_component, $vce);
			}
		}
		
		// load parent class to prevent errors
		$this_component->display($each_component, $vce);
		
		
		self::edit_media_component($each_component, $vce);
		
		// load hooks
		// media_after_display
		if (isset($vce->site->hooks['media_after_display'])) {
			foreach($vce->site->hooks['media_after_display'] as $hook) {
				call_user_func($hook, $each_component, $vce);
			}
		}
		
		$vce->content->add('main','</div>');
		
	}
	
	/**
	 *
	 */
	public function edit_component($each_component, $vce) {
	}
	
	/**
	 *
	 */
	public function edit_media_component($each_component, $vce) {

		if ($vce->page->can_edit($each_component)) {
		
			// add javascript to page
			$vce->site->add_script(dirname(__FILE__) . '/js/edit.js');
		
			// add style
			$vce->site->add_style(dirname(__FILE__) . '/css/edit.css', 'media-edit-style');

			// get list of enabled_mediatype
			$media_players = json_decode($vce->site->enabled_mediatype, true);

			if (isset($media_players[$each_component->media_type])) {
	
				// require each
				require_once(BASEPATH . $media_players[$each_component->media_type]);
	
				// inst it
				$this_type = new $each_component->media_type();
			
			} else {
			
				// load parent class to prevent errors
				$this_type = new MediaType();
		
			}
			
			// the instructions to pass through the form
			$dossier = array(
			'type' => 'Media',
			'procedure' => 'update',
			'component_id' => $each_component->component_id,
			'created_at' => $each_component->created_at,
			'media_type' => $each_component->media_type
			);

			// generate dossier
			$each_component->dossier_for_edit = $vce->generate_dossier($dossier);
		
		
			// the instructions to pass through the form
			$dossier = array(
			'type' => 'Media',
			'procedure' => 'delete',
			'component_id' => $each_component->component_id,
			'created_at' => $each_component->created_at,
			'media_type' => $each_component->media_type,
			'parent_url' => $vce->requested_url
			);

			// generate dossier
			$each_component->dossier_for_delete = $vce->generate_dossier($dossier);
			
			// call to edit() in MediaType
			$add_content = $this_type->edit($each_component, $vce);

			// add edit form
			$vce->content->add('main',$add_content);
		
		}
		
	}

	/**
	 * add a closing div
	 */
	public function as_content_finish($each_component, $vce) {
	
		$vce->content->add('main', '</div>');
	
	}
	
	/**
	 *
	 */
	public function add_component($recipe_component, $vce) {

		$recipe_component->dossier_for_create = $vce->generate_dossier($recipe_component->dossier);

$content = <<<EOF
<div class="clickbar-container admin-container add-container ignore-admin-toggle">
<div class="clickbar-content">
EOF;

		// get list of activated media_players
		$media_players = json_decode($vce->site->enabled_mediatype, true);

		if (isset($recipe_component->media_types)) {
		
			// cycle through list of media_types
			foreach(explode('|',$recipe_component->media_types) as $each_type) {
	
				// check that player hasn't been disabled
				if (isset($media_players[$each_type])) {
	
					// require each
					require_once(BASEPATH . $media_players[$each_type]);
	
					// inst it
					$this_type = new $each_type();
		
				} else {
		
					$this_type = new MediaType();
		
				}
			
				// test if the file uploader is needed to add this media type
				if ($this_type->file_upload()) {
			
					// check if the the file uploader has already been loaded
					if (!isset($vce->file_uploader)) {
						$content .= self::add_file_uploader($recipe_component, $vce);
					}
			
					// a way to pass file extensions to the plupload to limit file selection
					$file_extensions = $this_type->file_extensions();

					// a way to get content type for class and pass it to vce-upload.php
					// https://en.wikipedia.org/wiki/Media_type
					$media_type = $this_type->mime_info();

					// create list for accept attribute of input type file
					$accept = '.' . str_replace(',',',.', $file_extensions['extensions']);
					foreach ($media_type as $mime_type=>$class_name) {
						// if a wildcard is found in mime_type, then use it to extend accept for safari
						if (preg_match('/([a-z]*\/{1})\.\*$/',$mime_type, $match)) {
							foreach (explode(',', $file_extensions['extensions']) as $each_extention) {
								$accept .= ',' . $match[1] . $each_extention;
							}							
						} else {
							$accept .= ',' . $mime_type;
						}
					}
			
					// write a div with atttributes for plupload to use
					$content .=  '<div class="file-extensions" title="' . $file_extensions['title'] . '" extensions="' . $accept . '"></div>';
					
					// add div for each media type
					foreach ($media_type as $type=>$name) {
					
						$content .=  '<div class="media-types" mimetype="' . $type . '" mimename="' . $name . '"></div>';
					
					}
			
				} else {
		
					// add form
					$content .= $this_type->add($recipe_component, $vce);
				}
			
			}
			
		} else {
		
			$content .= "No Media Types Selected";
		
		}
		
		$clickbar_title = isset($recipe_component->description) ? $recipe_component->description : 'Add New ' . $recipe_component->title;

$content .= <<<EOF
</div>
<div class="clickbar-title clickbar-closed"><span>$clickbar_title</span></div>
</div>
EOF;
		// add to content object
		$vce->content->add('main',$content);
		
		// clear file_uploader
		unset($vce->file_uploader);

	}
	
	/**
	 *
	 */
	public static function add_file_uploader($recipe_component, $vce) {

		// path to image
		$path = $vce->site->path_to_url(dirname(__FILE__));

		// add a property to page to indicate the the uploader has been added
		$vce->file_uploader = true;
		
		// add javascript for fileupload
		$vce->site->add_script(dirname(__FILE__) . '/js/jquery.fileupload.js', 'jquery jquery-ui');
	
		// add javascript to page
		$vce->site->add_script(dirname(__FILE__) . '/js/script.js');
		
		// add style
		$vce->site->add_style(dirname(__FILE__) . '/css/style.css', 'media-style');

		// this may change to owner_id
		$user_id = $vce->user->user_id;
	
		if (defined('UPLOAD_SIZE_LIMIT')) {
			$chunk_size = $vce->convert_to_bytes(UPLOAD_SIZE_LIMIT);
		} else {
			// dividing this value by half to try and prevent errors.
			$chunk_size = min($vce->convert_to_bytes(ini_get('upload_max_filesize')),$vce->convert_to_bytes(ini_get('post_max_size'))) / 2;
		}
	
		if (defined('MAX_FILE_LIMIT')) {
			$file_size_limit = $vce->convert_to_bytes(MAX_FILE_LIMIT);
		} else {
			$file_size_limit = $vce->convert_to_bytes('4G');
		}
		
		// path to image
		// $path = $page->site->path_to_url(dirname(__FILE__));

$content_media = <<<EOF
<div class="uploader-container">
<div class="progressbar-container">
<div class="progressbar-title">Upload In Progress</div>
<div class="progressbar-block">
<div class="progressbar-block-left"><div class="progressbar"><div class="progress-chunks" style="position:absolute;padding-left:5px;"></div></div></div>
<div class="progressbar-block-right"><a class="cancel-upload link-button" href="">Cancel</a></div>
</div>
<div class="progress-label" timestamp=0>0%</div>
</div>
<div class="verifybar-container">
<div class="verifybar-title">Upload Completed</div>
<div class="verifybar"><div id="verify-chunks" style="position:absolute;padding-left:5px;"></div></div>
<div class="verifybar-label">Verifying File</div>
</div>
<div class="progressbar-error"></div>
<div class="progressbar-success">Upload Complete!</div>
<div class="progressbar-queued">Queued To Upload, Please Wait</div>
<div class="upload-browse" style="margin-bottom:-17px;">

<label>
<input class="fileupload" type="file" name="file" path="$vce->media_upload_path" accept="" file_size_limit="$file_size_limit" chunk_size="$chunk_size" style="padding-top:25px;cursor:pointer;">
<div class="file-upload-cancel cancel-button link-button">Cancel</div>
<div class="label-text">
<div class="label-message">Select A File To Upload</div>
</div>
</label>

</div>
<div class="upload-form" style="display:none;">
<div class="clickbar-container">
<div class="clickbar-content clickbar-open">
<input class="action" type="hidden" value="$vce->input_path">
<input class="dossier" type="hidden" name="dossier" value="$recipe_component->dossier_for_create">
<input class="created_by" type="hidden" name="created_by" value="$user_id">
<input class="mediatypes" type="hidden" name="mediatypes" value="">
<label> 
<input type="text" name="title" class="resource-name" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Title</div>
<div class="label-error">Enter A Title</div>
</div>
</label>
EOF;

		// load hooks
		// media_file_uploader
		if (isset($vce->site->hooks['media_file_uploader'])) {
			foreach($vce->site->hooks['media_file_uploader'] as $hook) {
				$content_media .= call_user_func($hook, $recipe_component, $vce);
			}
		}

$content_media .= <<<EOF
<div class="start-upload link-button" href="javascript:;">Upload</div> <div class="cancel-upload link-button cancel-button">Cancel</div>
</div>
<div class="clickbar-title"><span>Upload File</span></div>
</div>
</div>
</div>
EOF;

		return $content_media;
	
	}

	
	/**
	 * Create a new Media
	 * these $input fields come from media/js/upload.js
	 * passing through vce-upload.php
	 */
	protected function create($input) {
	
		global $site;
		
		// load hooks
		// media_create_component
		if (isset($site->hooks['media_create_component'])) {
			foreach($site->hooks['media_create_component'] as $hook) {
				$input_returned = call_user_func($hook, $input);
				$input = isset($input_returned) ? $input_returned : $input;
			}
		}
		
		$input['component_id'] = self::create_component($input);
		
		$response = array(
		'response' => 'success',
		'procedure' => 'create',
		'message' => 'New Component Was Created'
		);
		
		// load hooks
		// media_component_created
		// was media_create_component_after
		if (isset($site->hooks['media_component_created'])) {
			foreach($site->hooks['media_component_created'] as $hook) {
				$response_returned = call_user_func($hook, $input, $response);
				$response = isset($response_returned) ? $response_returned : $response;
			}
		}
		
		echo json_encode($response);
		return;

	}

	/**
	 * update
	 */
	protected function update($input) {
	
		global $site;
		
		// load hooks
		// media_update_component
		if (isset($site->hooks['media_update_component'])) {
			foreach($site->hooks['media_update_component'] as $hook) {
				$input = call_user_func($hook, $input);
			}
		}
		
		if (self::update_component($input)) {
		
			echo json_encode(array('response' => 'success','procedure' => 'update','action' => 'reload','message' => "Updated"));
			return;
		}
		
		echo json_encode(array('response' => 'error','procedure' => 'update','message' => "Error"));
		return;
	
	}


	/**
	 * delete 
	 */
	protected function delete($input) {
	
		global $site;
		
		// load hooks
		// media_delete_component
		if (isset($site->hooks['media_delete_component'])) {
			foreach($site->hooks['media_delete_component'] as $hook) {
				$input = call_user_func($hook, $input);
			}
		}

		$parent_url = self::delete_component($input);

		if (isset($parent_url)) {

			echo json_encode(array('response' => 'success','procedure' => 'delete','action' => 'reload','url' => $parent_url, 'message' => "Deleted"));
			return;
		}

		echo json_encode(array('response' => 'error','procedure' => 'update','message' => "Error"));
		return;
	
	}


	/**
	 * for ManageRecipes class
	 */
	public function recipe_fields($recipe) {
	
		global $site;
	
		$title = isset($recipe['title']) ? $recipe['title'] : self::component_info()['name'];
		$description = isset($recipe['description']) ? $recipe['description'] : null;
		$media_types = isset($recipe['media_types']) ? $recipe['media_types'] : null;

		
$elements = <<<EOF
<label>
<input type="text" name="title" value="$title" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Title</div>
<div class="label-error">Enter a Title</div>
</div>
</label>
<label>
<input type="text" name="description" value="$description"  autocomplete="off">
<div class="label-text">
<div class="label-message">Clickbar Description</div>
<div class="label-error">Enter a Description</div>
</div>
</label>
<label for="">
<div class="input-padding">
EOF;

		if (isset($site->enabled_mediatype)) {
			foreach (json_decode($site->enabled_mediatype, true) as $key=>$each_media) {
				$elements .= '<label class="ignore"><input type="checkbox" name="media_types" value="' . $key . '"';
				if (in_array($key,explode('|',$media_types))) {
					$elements .= ' checked="checked"';
				}
				$elements .= '>  ' . $key . '</label> ';
			}
		}

$elements .= <<<EOF
</div>
<div class="label-text">
<div class="label-message">Media Types</div>
<div class="label-error">Must have a Media Type</div>
</div>
</label>
EOF;
		return $elements;
		
	}

}