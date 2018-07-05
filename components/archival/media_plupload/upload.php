<?php

// Based on the Moxiecode Systems AB upload.php script
// Mod_evasive apache module can be a problem, since it li

// No cache
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");



// Define BASEPATH as this file's directory
define('BASEPATH', str_replace('/vce-application/components/media','', dirname(__FILE__)) . '/');

// file_put_contents(BASEPATH . 'log.txt', BASEPATH . PHP_EOL, FILE_APPEND);

/*
/Applications/MAMP/htdocs/vce/
*/

// configuration file
include_once(BASEPATH . 'vce-config.php');
	
// first time through upload
if (isset($_REQUEST["chunk"]) && intval($_REQUEST["chunk"]) < 1) {

	// require database class
	require_once(BASEPATH .'vce-application/class.db.php');
	$db = new DB();

	// create contents object
	require_once(BASEPATH . 'vce-application/class.content.php');
	$content = new Content();

	// site object
	require_once(BASEPATH .'vce-application/class.site.php');
	$site = new Site();
	
	// add theme.php 
	$site->add_theme_functions();

	// user class
	require_once(BASEPATH .'vce-application/class.user.php');
	$user = new User();

	// if no dossier is set, forward to homepage
	if (!isset($_REQUEST['dossier'])) {
		echo json_encode(array('response' => 'error','message' => 'Dossier does not exist','action' => ''));
		exit();
	}

	// decryption of dossier
	$dossier = json_decode($user->decryption($_REQUEST['dossier'], $user->session_vector));

	// check that component is a property of $dossier, json obeject test
	if (!isset($dossier->type) || !isset($dossier->procedure)) {
		echo json_encode(array('response' => 'error','message' => 'Dossier is not valid','action' => ''));
		exit();
	}
	
}

// file_put_contents(BASEPATH . 'log.txt', json_encode($_FILES) . PHP_EOL, FILE_APPEND);

// 15 minutes execution time
@set_time_limit(15 * 60);

ini_set('memory_limit','250M');

// Settings
if (defined(PATH_TO_UPLOADS)) {
	$upload_path = PATH_TO_UPLOADS;
} else {
	// default location for uploads
	$upload_path = BASEPATH . 'vce-content/uploads';
}


// If the directory doesn't exist, create it
if (!is_dir($upload_path)) {
	if (!mkdir($upload_path, 0775, TRUE)) {
		die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: Failed to create uploads directory.')));
	}
}

// Get a file name
if (isset($_REQUEST["name"])) {
	$file_name = $_REQUEST["created_by"] . '_' . $_REQUEST["timestamp"] . '_' . $_REQUEST["name"];
} else {
	// this error can also mean that the UPLOAD_SIZE_LIMIT is set too high, or that upload_max_filesize and post_max_size are too high
	die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: File name not set.')));
}

// the path to the file
$file_path = $upload_path . DIRECTORY_SEPARATOR . $file_name;

// Chunking might be enabled
$chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
$chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;

// This error message should never be thrown, but is here to cover any and all possibilities,
// opendir($upload_path)
if (!$dir = opendir($upload_path)) {
	die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: Failed to open uploads directory.')));
}

while (($file = readdir($dir)) !== false) {
	$temporary_file_path = $upload_path . DIRECTORY_SEPARATOR . $file;

	// If temp file is current file proceed to the next
	if ($temporary_file_path == "{$file_path}.part") {
		continue;
	}

	// Remove temp file if older than the max age and is not the current file
	if (preg_match('/\.part$/', $file) && (filemtime($temporary_file_path) < (time() - 3600))) {
		@unlink($temporary_file_path);
	}
}
	
closedir($dir);


// Open temp file
if (!$out = @fopen("{$file_path}.part", $chunks ? "ab" : "wb")) {
	die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: Failed to open output stream.')));
}

if (!empty($_FILES)) {

	// error thrown by php
	if ($_FILES["file"]["error"]) {
		$message = array(
		0 => 'There is no error, the file uploaded with success',
		1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
		2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
		3 => 'The uploaded file was only partially uploaded',
		4 => 'No file was uploaded',
		6 => 'Missing a temporary folder',
		7 => 'Failed to write file to disk.',
		8 => 'A PHP extension stopped the file upload.',
		);
		die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: ' . $message[$_FILES["file"]["error"]])));
	}
	
	// Tells whether the file was uploaded via HTTP POST
	if (!is_uploaded_file($_FILES["file"]["tmp_name"])) {
		die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: Failed to move uploaded file.')));
	}

	// Read binary input stream and append it to temp file
	if (!$in = @fopen($_FILES["file"]["tmp_name"], "rb")) {
		die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: Failed to open output stream.')));
	}
	
} else {	
	if (!$in = @fopen("php://input", "rb")) {
		die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: Failed to open input stream.')));
	}
}

while ($buff = fread($in, 4096)) {
	fwrite($out, $buff);
}

@fclose($out);
@fclose($in);

// Check if file has been uploaded
if (!$chunks || $chunk == $chunks - 1) {

	// If no post data was sent, delete file part and return error message
	if (!isset($_REQUEST['mimetype'])) {
		
		$temporary_file_path = "{$file_path}.part";
	
		// delete the temporary file
		@unlink($temporary_file_path);
	
		// Return an error
		die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: Upload Size Limit Too Large.')));
		
	}


	if (!defined('BASEPATH')) {
		// Define BASEPATH as this file's directory
		define('BASEPATH', str_replace('/vce-application/components/media','', dirname(__FILE__)) . '/');
	}
	
	// get mimetype supplied by plupload
	$mimetype = $_REQUEST['mimetype'];

//	get filesize of uploaded file
// 	$filesize = filesize("{$file_path}.part");
// 	This is causing issues when the file is huge becasue of max memory limit
// 	verify mimeType
// 	$finfo = new finfo(FILEINFO_MIME_TYPE);
// 	$finfo_contents = file_get_contents("{$file_path}.part");
// 	$finfo_mimeType = $finfo->buffer($finfo_contents);
// check that mimeTypes are the same, if not delete and throw error
// 	if ($mimetype != $finfo_mimeType) {
// 	
// 		$temporary_file_path = "{$file_path}.part";
// 	
// 		// delete the temporary file
// 		@unlink($temporary_file_path);
// 		
// 		// die with message
// 		die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: mimeType error.')));
// 
// 	}
	
	// default value for mimename
	$mimename = "MediaTypes";
	
	// cycle through mediatypes that were passed through from functions media_type()
	foreach (json_decode($_REQUEST['mediatypes']) as $each_mediatype) {

		// check for subtype wildcard
		if (preg_match('/\.\*$/', $each_mediatype->mimetype)) {
	
			// match primaray type
			if (explode('/', $each_mediatype->mimetype)[0] == explode('/', $mimetype)[0]) {
	
				// class name of media player
				$mimename = $each_mediatype->mimename;
				
				break;
		
			}
	
		} else {

			// match full
			if ($each_mediatype->mimetype == $mimetype) {
	
				$mimename = $each_mediatype->mimename;
				
				break;
		
			}
		
		}
	
	}
	
	// post variables to pass to create
	$post = [];
	
	// get list of post variables from Media class
	foreach (json_decode($_REQUEST['postnames']) as $each_post_variable=>$each_post_value) {
		// save each value
		$post[$each_post_variable] = $each_post_value;
	}
	
	// clean-up
	unset($post['mediatypes'], $post['mimetype'],$post['timestamp']);
	
	// create user directory if it does not exist
	if (!file_exists($upload_path .  DIRECTORY_SEPARATOR  . $post['created_by'])) {
		mkdir($upload_path .  DIRECTORY_SEPARATOR  . $post['created_by'], 0775, TRUE);
	}
	
	$source_file_name = "{$file_path}.part";

	// create the new file name
	$path = $post['created_by'] . '_' . time() . '.' . pathinfo($file_path)['extension'];	

	$destination_file_name = $upload_path .  DIRECTORY_SEPARATOR  . $post['created_by'] . DIRECTORY_SEPARATOR  . $path;

	rename($source_file_name, $destination_file_name);

	// keeping this in case we decide it's needed at some point
	// $post['mime_type'] = $mimetype;
	$post['media_type'] = $mimename;
	$post['path'] = $path;

}

if (isset($post)) {
	die(json_encode($post));
}

// Return Success JSON-RPC response
die(json_encode(array('status' => 'success', 'message' => 'File has uploaded.')));




