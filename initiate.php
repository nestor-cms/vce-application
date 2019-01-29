<?php

if (file_exists(BASEPATH . 'vce-config.php')) {
	// check if vce-config already was loaded
	if (!defined('SITE_KEY')) {
		// configuration file 
		require_once(BASEPATH . 'vce-config.php');
	}
} else {
	// run installer
	if (file_exists(BASEPATH . 'vce-installer.php')) {
		header('Location: vce-installer.php');
	} else {
		echo "vce-installer.php not found";
	}
	exit();
}

/* autoloaders **/

/**
 * auto load an activated component
 *
 * @param [string] $className
 * @return void
 */
function autoload_components($class_name) {

	global $vce;

	if (isset($vce) && isset($vce->site)) {
		$activated_components = json_decode($vce->site->activated_components, true);
		if (isset($activated_components[$class_name])) {
			require_once BASEPATH . $activated_components[$class_name];
		}
	}
}

/**
 * auto load a vce-application class with name like class.foo.php
 *
 * @param [string] $className
 * @return void
 */
function autoload_classes($class_name) {

	$file = BASEPATH . 'vce-application/class.' . strtolower($class_name) . '.php';
	if (file_exists($file))
		require_once $file;
}


// register autoload functions
spl_autoload_register('autoload_components');
spl_autoload_register('autoload_classes');

// error reporting
if (defined('VCE_DEBUG') && VCE_DEBUG === false) {
	ini_set('error_reporting', 0);
}

// require vce
$vce = new VCE();

// require database class
$db = new DB($vce);

// create contents object
$content = new Content($vce);

// create site object
$site = new Site($vce);

// add theme.php
$site->add_theme_functions();

// create user object
$user = new User($vce);

// create page object
$page = new Page($vce);

// unset($page->site,$page->user,$page->content);
// $vce->dump($vce->user, 'efefef');

// output theme page
if (file_exists(BASEPATH .'vce-content/themes/' . $site->site_theme . '/' . $page->template)) {
	require_once(BASEPATH .'vce-content/themes/' . $site->site_theme . '/' . $page->template);
} else {
	echo "template file not found: " . $site->site_theme . '/' . $page->template;
}