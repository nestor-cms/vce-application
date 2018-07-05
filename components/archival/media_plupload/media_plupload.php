<?php

class MediaPlupload extends Component {

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
		'site_hook_initiation' => 'MediaPlupload::require_once_mediatype'
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
	public function as_content($each_component, $page) {
	
		$page->content->add('main','<div class="media-item">');
	
		$each_type = $each_component->media_type;
		
		$media_players = json_decode($page->site->enabled_mediatype, true);
		
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
		
		// load parent class to prevent errors
		$this_component->display($each_component, $page);
		
		
		
		if ($page->can_edit($each_component)) {

			// get list of enabled_mediatype
			$media_players = json_decode($page->site->enabled_mediatype, true);

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
			'type' => 'MediaPlupload',
			'procedure' => 'update',
			'component_id' => $each_component->component_id,
			'created_at' => $each_component->created_at,
			'media_type' => $each_component->media_type
			);

			// generate dossier
			$each_component->dossier_for_edit = $page->generate_dossier($dossier);
		
		
			// the instructions to pass through the form
			$dossier = array(
			'type' => 'MediaPlupload',
			'procedure' => 'delete',
			'component_id' => $each_component->component_id,
			'created_at' => $each_component->created_at,
			'media_type' => $each_component->media_type,
			'parent_url' => $page->requested_url
			);

			// generate dossier
			$each_component->dossier_for_delete = $page->generate_dossier($dossier);
			
			// call to edit() in MediaType
			$add_content = $this_type->edit($each_component, $page);

			// add edit form
			$page->content->add('main',$add_content);
		
		}
		
	}



	public function as_content_finish($each_component, $page) {
	
		$page->content->add('main', '</div>');
	
	}
	
	/**
	 *
	 */
	public function add_component($recipe_component, $page) {

		$recipe_component->dossier_for_create = $page->generate_dossier($recipe_component->dossier);

$content = <<<EOF
<div class="clickbar-container admin-container add-container ignore-admin-toggle">
<div class="clickbar-content">
EOF;

		// get list of activated media_players
		$media_players = json_decode($page->site->enabled_mediatype, true);

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
					if (!isset($page->file_uploader)) {
						$content .= self::add_file_uploader($recipe_component, $page);
					}
			
					// a way to pass file extensions to the plupload to limit file selection
					$file_extensions = $this_type->file_extensions();
			
					// write a div with atttributes for plupload to use
					$content .=  '<div class="file-extensions" title="' . $file_extensions['title'] . '" extensions="' . $file_extensions['extensions'] . '"></div>';
			
					// a way to get content type for class and pass it to vce-upload.php
					// https://en.wikipedia.org/wiki/Media_type
					$media_type = $this_type->mime_info();
					
					// add div for each media type
					foreach ($media_type as $type=>$name) {
					
						$content .=  '<div class="media-types" mimetype="' . $type . '" mimename="' . $name . '"></div>';
					
					}
			
				} else {
		
					// add form
					$content .= $this_type->add($recipe_component, $page);
				}
			
			}
			
		} else {
		
			$content .= "No Media Types Selected";
		
		}

$content .= <<<EOF
</div>
<div class="clickbar-title clickbar-closed"><span>Add New $recipe_component->title</span></div>
</div>
EOF;
		// add to content object
		$page->content->add('main',$content);

	}
	
	/**
	 *
	 */
	public static function add_file_uploader($recipe_component, $page) {
	
		// path to image
		$path = $page->site->path_to_url(dirname(__FILE__));
	
		// add path to media uploading
		$page->media_upload_path = $path . '/upload.php';

		// add a property to page to indicate the the uploader has been added
		$page->file_uploader = true;
		
		// add plupload
		$page->site->add_script(dirname(__FILE__) . '/js/plupload/plupload.full.min.js', 'jquery-ui');
		
		// add javascript to page
		$page->site->add_script(dirname(__FILE__) . '/js/script.js', 'jquery-ui');
		
		// this may change to owner_id
		$user_id = $page->user->user_id;
	
		if (defined('UPLOAD_SIZE_LIMIT')) {
			$chunk_size = $page->site->convert_to_bytes(UPLOAD_SIZE_LIMIT);
		} else {
			// dividing this value by half to try and prevent errors.
			$chunk_size = min($page->site->convert_to_bytes(ini_get('upload_max_filesize')),$page->site->convert_to_bytes(ini_get('post_max_size'))) / 2;
		}
	
		if (defined('MAX_FILE_LIMIT')) {
			$file_size_limit = $page->site->convert_to_bytes(MAX_FILE_LIMIT);
			$max_allowed_size = MAX_FILE_LIMIT;
		} else {
			$file_size_limit = '0';
			$max_allowed_size = 'none';
		}

		
$content_media = <<<EOF
<div id="progressbar-container">
<div id="prgress-title">Upload In Progress</div>
<div id="progressbar-block">
<div id="progressbar-block-left"><div id="progressbar"></div></div>
<div id="progressbar-block-right"><a id="cancel-upload" class="link-button" href="">Cancel</a></div>
</div>
<div id="progress-label" timestamp=0>0%</div>
</div>
<div id="progressbar-error"></div>
<div id="upload-browse" style="margin-bottom:-17px;">
<label>
<div class="form-margin">
<a id="browse" class="link-button" href="javascript:;" path="$page->media_upload_path" file_size_limit="$file_size_limit" max_allowed_size="$max_allowed_size" chunk_size="$chunk_size">Browse</a> Choose File To Upload
</div>
<div class="label-text">
<div class="label-message">Select A File To Upload</div>
</div>
</label>
</div>
<div id="upload-form" style="display:none;">
<div class="clickbar-container">
<div class="clickbar-content clickbar-open">
<input id="action" type="hidden" value="$page->input_path">
<input id="dossier" class="vce-input" type="hidden" name="dossier" value="$recipe_component->dossier_for_create">
<input id="created_by" class="vce-input" type="hidden" name="created_by" value="$user_id">
<input id="mediatypes" class="vce-input" type="hidden" name="mediatypes" value="">
<label> 
<input type="text" name="title" id="resource-name" class="vce-input" autocomplete="off">
<div class="label-text">
<div class="label-message">Title</div>
<div class="label-error">Enter A Title</div>
</div>
</label>
<a id="start-upload" class="link-button" href="javascript:;">Upload</a> <a id="cancel-upload" class="link-button" href="">Cancel</a>
</div>
<div class="clickbar-title"><span>Upload File</span></div>
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

		if (!isset($input['parent_id'])) {
			$input['parent_id'] = 0;
		}
		
		if (!isset($input['sequence'])) {
			$input['sequence'] = 0;
		}
		
		// load hooks
		// media_create_component
		if (isset($site->hooks['media_create_component'])) {
			foreach($site->hooks['media_create_component'] as $hook) {
				$input = call_user_func($hook, $input);
			}
		}
		
		// place input into an array so that create_component can be triggered multiple times
		$input_array[] = $input;

		// load hooks
		// media_create_component_input
		if (isset($site->hooks['media_create_component_input'])) {
			foreach($site->hooks['media_create_component_input'] as $hook) {
				$input_array = call_user_func($hook, $input_array);
			}
		}
		
		$component_id = null;;
		foreach ($input_array as $key=>$input) {
		
			if (isset($site->hooks['media_create_component_loop'])) {
				foreach($site->hooks['media_create_component_loop'] as $hook) {
					$input = call_user_func($hook, $input, $component_id);
				}
			}	
		
			$component_id = self::create_component($input);
		
		}
		
		echo json_encode(array('response' => 'success','procedure' => 'create','action' => 'reload','message' => 'New Component Was Created'));
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