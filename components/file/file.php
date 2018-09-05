<?php

class File extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'File',
			'description' => 'Component to allow a file to be displayed securely. PATH_TO_FILE can be used in vce-config to change the default location.',
			'category' => 'admin'
		);
	}

	/**
	 * things to do when this component is preloaded
	 */
	public function preload_component() {
		
		$content_hook = array (
		'page_requested_url' => 'File::page_requested_url',
		'site_media_link' => 'File::site_media_link'
		);

		return $content_hook;

	}

	/**
	 * add a user attribute
	 */
	public static function page_requested_url($requested_url, $vce) {
	
		if ((!defined('PATH_TO_FILE') && strpos($requested_url, 'file/') !== false) || (defined('PATH_TO_FILE') && strpos($requested_url, PATH_TO_FILE . '/') !== false)) {
		
			$file_directory = !defined('PATH_TO_FILE') ? 'file/' : PATH_TO_FILE . '/';

			// 1/1_1506638838.png
			$file_sting = str_replace($file_directory,'',$requested_url);
			
			// for file extention
			$path_parts = pathinfo($file_sting);
			
			if (strpos($file_sting, '-') === false) {
				// redirect to 403 Forbidden
				header('HTTP/1.0 403 Forbidden');
				echo '<html><head><title>403 Forbidden</title></head><body><center>resource not available</center></body></html>';
				exit();	
			}

			// clean up query string and explode into values
			list($encrypted_dirty,$vector_dirty) = explode('-', rtrim($file_sting, '.' . $path_parts['extension']));

			if (isset($encrypted_dirty) && isset($vector_dirty)) {
			
				$encrypted = base64_decode(str_pad(strtr($encrypted_dirty, '-_', '+/'), strlen($encrypted_dirty) % 4, '=', STR_PAD_RIGHT)); 
				$vector = base64_decode(str_pad(strtr($vector_dirty, '-_', '+/'), strlen($vector_dirty) % 4, '=', STR_PAD_RIGHT)); 

				// vector length
				// user::vector_length() 
				if (OPENSSL_VERSION_NUMBER) {
					$vector_length = openssl_cipher_iv_length('aes-256-cbc');
				} else {
					$vector_length = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
				}

				// make sure vector length is correct
				if (strlen(base64_decode($vector)) ==  $vector_length) {

					// decrypting, from $site->media_link()
					// user::decryption($decode_text,$vector)
					if (OPENSSL_VERSION_NUMBER) {
						$decrypted = trim(openssl_decrypt(base64_decode($encrypted),'aes-256-cbc',hex2bin(SITE_KEY),OPENSSL_RAW_DATA,base64_decode($vector)));
					} else {
						$decrypted = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, hex2bin(SITE_KEY), base64_decode($encrypted), MCRYPT_MODE_CBC, base64_decode($vector)));
					}
		
					$fileinfo = unserialize($decrypted);
					
					if (!empty($fileinfo)) {
		
						// if user_id is set, then check user
						if (isset($fileinfo['user_id'])) {
							// if they don't match, return a 403 Forbidden
							if ($fileinfo['user_id'] != $vce->user->user_id) {
				
								header('HTTP/1.0 403 Forbidden');
								echo '<html><head><title>403 Forbidden</title></head><body><center>resource not available</center></body></html>';
								exit();
							}
						}
						
						
						// check if access time has expired
						if ($fileinfo['expires'] > time() && isset($fileinfo['path'])) {
				
							// for file extention
							$path_parts = pathinfo($fileinfo['path']);

							// full path to file
							$file_path = defined('PATH_TO_UPLOADS') ? PATH_TO_UPLOADS  . '/' . $fileinfo['path'] : BASEPATH . PATH_TO_UPLOADS . '/' . $fileinfo['path'];

							// check that file exists
							if (file_exists($file_path)) {

								$size = filesize($file_path);
	
								$image_mime = image_type_to_mime_type(exif_imagetype($file_path));
						
								if (isset($fileinfo['name'])) {
									// clean up name
									$file_name = preg_replace('/[^A-Za-z0-9_-]/','-', $fileinfo['name']) . '.' . $path_parts['extension'];
								} else {
									$file_name = "file." . $path_parts['extension'];
								}
						
								header('Content-type: ' . $image_mime);
								header('Content-Length: ' . $size);
								// here's a list of content disposition values
								// http://www.iana.org/assignments/cont-disp/cont-disp.xhtml
								if (isset($fileinfo['disposition']) && $fileinfo['disposition'] == 'attachment') {
									header("Content-Disposition: attachment; filename=\"" . $file_name . "\"");
								} else {
									header("Content-Disposition: inline; filename=\"" . $file_name . "\"");
								}
						
								if (isset($vce->site->hooks['file_readfile_method'])) {
									foreach($vce->site->hooks['file_readfile_method'] as $hook) {
										call_user_func($hook, $requested_url, $file_path, $vce);
									}
								} else {
									readfile($file_path);
								}

								exit();

							}
						}
					
					}
				
				}
				
			}
			
			// redirect to 403 Forbidden
			header('HTTP/1.0 403 Forbidden');
			echo '<html><head><title>403 Forbidden</title></head><body><center>resource not available</center></body></html>';
			exit();
			
		}
	
	}
	
	/**
	 * method that is hooked to site_media_link
	 */
	public static function site_media_link($fileinfo, $site) {

	
    	// expires = how many seconds from now?
    	// path = $each_component->created_by . '/' . $each_component->path
    	// name = the name given to the media item
    	// user_id = $user->user_id check the user id of the current user. 
    	// disposition  = attachment/inline
    	// here's a list of content disposition values
		// http://www.iana.org/assignments/cont-disp/cont-disp.xhtml
	
		 //check if expires has been set
	 	if (isset($fileinfo['expires'])) {
	 		$fileinfo['expires'] = time() + $fileinfo['expires'];
	 	} else {
	 		// if expires has not been set, set it to 60 seconds
	 		$fileinfo['expires'] = time() + 60;
	 	}
	 	
	 	// for file extention
		$path_parts = pathinfo($fileinfo['path']);
		$file_eextension = $path_parts['extension'];
	 	
	 	global $user;

		// iv (initialization vector) for each user
		$vector = $user->create_vector();

		// encrypting
		$encrypted = $user->encryption(serialize($fileinfo),$vector);

		$encrypted_clean = rtrim(strtr(base64_encode($encrypted), '+/', '-_'), '=');
		$vector_clean = rtrim(strtr(base64_encode($vector), '+/', '-_'), '=');
		
		$file_directory = !defined('PATH_TO_FILE') ? '/file/' : '/' . PATH_TO_FILE . '/';

		$path = $site->site_url . $file_directory . $encrypted_clean . '-' . $vector_clean . '.' . $file_eextension;
		
		return $path;
	
	}
	
	
	/**
	 * hide this component from being added to a recipe
	 */
	public function recipe_fields($recipe) {
		return false;
	}

}