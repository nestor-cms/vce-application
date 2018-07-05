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

// error reporting
if (defined('VCE_DEBUG') && VCE_DEBUG === false) {
	ini_set('error_reporting', 0);
}

// require vce
require_once(BASEPATH . 'vce-application/class.vce.php');
$vce = new VCE();

// require database class
require_once(BASEPATH . 'vce-application/class.db.php');
$db = new DB();

// create contents object
require_once(BASEPATH . 'vce-application/class.content.php');
$content = new Content();

// class.component.php loaded with __construct method of class.site.php

// create site object
require_once(BASEPATH . 'vce-application/class.site.php');
$site = new Site();

// add theme.php
$site->add_theme_functions();

// create user object
require_once(BASEPATH . 'vce-application/class.user.php');
$user = new User();

// create page object
require_once(BASEPATH . 'vce-application/class.page.php');
$page = new Page();

// unset($page->site,$page->user,$page->content);
// $vce->dump($vce->user, 'efefef');

// output theme page
if (file_exists(BASEPATH .'vce-content/themes/' . $site->site_theme . '/' . $page->template)) {
	require_once(BASEPATH .'vce-content/themes/' . $site->site_theme . '/' . $page->template);
} else {
	echo "template file not found: " . $site->site_theme . '/' . $page->template;
}