<?php

// php script for jQuery-File-Upload

// upload_max_filesize = 30M
// post_max_size = 30M
// max_execution_time = 260
// max_input_time = -1
// memory_limit = 256M
// max_file_uploads = 100

header('Vary: Accept');
if (isset($_SERVER['HTTP_ACCEPT']) &&
    (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
    header('Content-type: application/json');
} else {
    header('Content-type: text/plain');
}

// No cache
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

header("Access-Control-Allow-Headers: Content-Type,Content-Range,Content-Disposition");

// Define BASEPATH as this file's directory
// define('BASEPATH', dirname(__FILE__) . '/');

// Define BASEPATH as this file's directory
define('BASEPATH', str_replace('/vce-application/components/media','', dirname(__FILE__)) . '/');

// configuration file
include_once(BASEPATH . 'vce-config.php');
        
$chunks = isset($_SERVER['HTTP_CONTENT_RANGE']) ? true : false;

if ($chunks) {
	// Parse the Content-Range header, which has the following form:
	// Content-Range: bytes 0-524287/2000000
	$content_range = preg_split('/[^0-9]+/', $_SERVER['HTTP_CONTENT_RANGE']);
    
	$start_range =  $content_range ? $content_range[1] : null;
	$end_range =  $content_range ? $content_range[2] : null;
	$size_range =  $content_range ? $content_range[3] : null;
		
	// is this the first chunk?
	$first_chunk = ($start_range == 0) ? true : false;
		
	// is this the last chunk?
	$last_chunk = (($end_range + 1) == $size_range) ? true : false;
}
	
// first time through upload
if (!$chunks || $first_chunk) {

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
		echo json_encode(array('response' => 'error','message' => 'File Uploader Error: Dossier does not exist <div class="link-button cancel-button">Try Again</div>','action' => ''));
		exit();
	}

	// decryption of dossier
	$dossier = json_decode($user->decryption($_REQUEST['dossier'], $user->session_vector));

	// check that component is a property of $dossier, json obeject test
	if (!isset($dossier->type) || !isset($dossier->procedure)) {
		echo json_encode(array('response' => 'error','message' => 'File Uploader Error: Dossier is not valid <div class="link-button cancel-button">Try Again</div>','action' => ''));
		exit();
	}
	
}

// 15 minutes execution time
@set_time_limit(15 * 60);

ini_set('memory_limit','256M');

// Settings
if (defined('PATH_TO_UPLOADS')) {
	// this is the full server path to uploads and does not automatically add BASEPATH
	$upload_path = BASEPATH . PATH_TO_UPLOADS;
} else {
	// default location for uploads
	$upload_path = BASEPATH . 'vce-content/uploads';
}

// If the directory doesn't exist, create it
if (!is_dir($upload_path)) {
	if (!mkdir($upload_path, 0775, TRUE)) {
		die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: Failed to create uploads directory. <div class="link-button cancel-button">Try Again</div>')));
	}
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST) && empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0) {
	// error can mean that the UPLOAD_SIZE_LIMIT is set too high, or that upload_max_filesize and post_max_size are too high
	die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: File size exceeds upload_max_filesize / post_max_size in php.ini  <div class="link-button cancel-button">Try Again</div>')));
}

// Get a file name
if (isset($_REQUEST["name"])) {
	$file_name = $_REQUEST["created_by"] . '_' . $_REQUEST["timestamp"] . '_' . strtolower($_REQUEST["name"]);
} else {
	die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: File name not set.  <div class="link-button cancel-button">Try Again</div>')));
}

// the path to the file
$file_path = $upload_path . DIRECTORY_SEPARATOR . $file_name;

// This is here in case you need to write out to the log.txt file for debugging purposes
// file_put_contents(BASEPATH . 'log.txt', $chunk . PHP_EOL, FILE_APPEND);

// This error message should never be thrown, but is here to cover any and all possibilities,
// opendir($upload_path)
if (!$dir = opendir($upload_path)) {
	die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: Failed to open uploads directory.  <div class="link-button cancel-button">Try Again</div>')));
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
	die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: Failed to open output stream.  <div class="link-button cancel-button">Try Again</div>')));
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
		die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: ' . $message[$_FILES["file"]["error"]] . ' <div class="link-button cancel-button">Try Again</div>')));
	}
	
	// Tells whether the file was uploaded via HTTP POST
	if (!is_uploaded_file($_FILES["file"]["tmp_name"])) {
		die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: Failed to move uploaded file. <div class="link-button cancel-button">Try Again</div>')));
	}

	// Read binary input stream and append it to temp file
	if (!$in = @fopen($_FILES["file"]["tmp_name"], "rb")) {
		die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: Failed to open output stream. <div class="link-button cancel-button">Try Again</div>')));
	}
	
} else {	
	if (!$in = @fopen("php://input", "rb")) {
		die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: Failed to open input stream. <div class="link-button cancel-button">Try Again</div>')));
	}
}

while ($buff = fread($in, 4096)) {
	fwrite($out, $buff);
}

@fclose($out);
@fclose($in);

// Check if file has been uploaded
if (!$chunks || $last_chunk) {

	// If no post data was sent, delete file part and return error message
	if (!isset($_REQUEST['mimetype'])) {
		
		$temporary_file_path = "{$file_path}.part";
	
		// delete the temporary file
		@unlink($temporary_file_path);
	
		// Return an error
		die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: Upload Size Limit Too Large. <div class="link-button cancel-button">Try Again</div>')));
		
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
//  check that mimeTypes are the same, if not delete and throw error
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
	// $mimename = "MediaTypes";
	
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
	
	// no mimename name match was found.
	if (!$mimename) {
		// should delete file, but for now leave it for error detection
		unlink("{$file_path}.part");
		die(json_encode(array('status' => 'error', 'message' => 'File Uploader Error: File type not allowed / Mimename not found. <div class="link-button cancel-button">Try Again</div>')));
	}
	
// This is here in case you need to write out to the log.txt file for debugging purposes
// file_put_contents(BASEPATH . 'log.txt', json_encode($_POST) . PHP_EOL, FILE_APPEND);
// file_put_contents(BASEPATH . 'log.txt','- - - - -' . PHP_EOL, FILE_APPEND);
// file_put_contents(BASEPATH . 'log.txt', $_REQUEST['postnames'] . PHP_EOL, FILE_APPEND);
	
	// post variables to pass to create
	$post = [];
	
	// get list of post variables from Media class
	// foreach (json_decode($_REQUEST['postnames']) as $each_post_variable=>$each_post_value) {
		// save each value
		// $post[$each_post_variable] = $each_post_value;
	// }
	
	// clean-up
	// unset($post['mediatypes'], $post['mimetype'],$post['timestamp']);
	
	$post['name'] = $_POST['name'];
	// $post['extention'] = $_POST['extention'];
	$post['dossier'] = $_POST['dossier'];
	$post['created_by'] = $_POST['created_by'];
	$post['title'] = $_POST['title'];
	
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




