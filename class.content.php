<?php
/**
 * Object in which HTML produced by Components is stored and for output to browser.
 * 
 * This object can contain properties such as $premain, $main and $postmain
 * These properties are then used to send content to the browser.
 */
 
class Content {

	/**
	 * Add content to vce object
	 *
	 */
    public function __construct() {
    
    	// add vce object
    	global $vce;
    	$vce->content = $this;
    	
    }

	/**
	 * Add content to object by property name
	 *
	 * @param string $property_name
	 * @param string $new_content
	 * @param string $prepend
	 */
	public function add($property_name = 'main', $new_content = null, $prepend = false) {
		// check if prepend is set to true, or anything
		if (!$prepend) {
			$this->$property_name .= $new_content;
		} else {
			$this->$property_name = $new_content . $this->$property_name;
		}
	}

	/**
	 * Combines parts and echos the whole of body contents.
	 * Echos rather than returns the output
	 *
	 * @param string $items
	 */
	public function output($items = null) {
		if (isset($items)) {
			$content = "";
			foreach ($items as $each_item) {
				$content .= $this->$each_item;
			}
			echo $content;
		} else {
			echo $this->premain . $this->main . $this->postmain;
		}
	}


	/**
	 * Creates menu when called.
	 * Reads stored menu from the $site object, then builds a menu into the $link property
	 *
	 * @param string $title
	 * @param array $args
	 * @property object $site  Gets the site menue from the $site object
	 * @return echo string $link
	 */
	public function menu($title, $args = array()) {
		global $vce;
		$site_menus = json_decode($vce->site->site_menus, true);
		if (isset($site_menus[$title])) {
			$link = '<ul class="menu-' .  $title;
			if (isset($args['class'])) {
				$link .=  ' ' . $args['class'];
			}
			$link .= '">';
			foreach($site_menus[$title] as $menu_item) {
				$link .= self::create_menu_items($menu_item, $args);	
			}
			// remove the last separator before closing the list
			if (isset($args['separator'])) {
				$link = preg_replace('/' . preg_quote($args['separator'], '/') . '$/', '', $link);
			}
			$link .= '</ul>' . PHP_EOL;
			// echo menu
			echo $link;
		}
	}
	

	/**
	 * Adds each menu item as a list item.
	 * Is called by the menu method.
	 * 
	 * @param object or array $menu_item
	 * @param string $separator
	 * @property $site	Gets $site URL
	 * @property $user  Controls user access
	 * @return string $link
	 */
	private function create_menu_items($menu_item, $args) {
		
		global $vce;
		
		// check if ASSETS_URL has been defined in vce-config, otherwise use site_url
		$site_url = defined('ASSETS_URL') ? ASSETS_URL : $vce->site->site_url;
		
		// type cast array as object
		$menu = (object) $menu_item;
		// check if role has access
		if (in_array($vce->user->role_id,explode('|',$menu->role_access))) {
			// check for full url
			$link = '<li';
			// check if current page
			if ($menu->url == $vce->requested_url) {
				$link .= ' class="current-menu-item"';
			}
			// check if the current page contains part of the url from another menu item, making it a parent of the item
			if ($menu->url != "" && strpos($vce->requested_url,$menu->url . '/') !== false) {
				$link .= ' class="current-menu-ancestor"';
			}
			$link .= '>';
			if ($menu->url != "") {
			
				if (strtolower(substr($menu->url,0,4)) != "http") {
					// if the url is / then this is the homepage
					if ($menu->url == "/" || !isset($menu->url)) {
						$url = $vce->site->site_url;
					} else {
						$url = $vce->site->site_url . '/' . $menu->url;
					}
				} else {
					$url = $menu->url;
				}
			
				$link .= '<a href="' . $url . '"';
				// open in new window
				if (isset($menu->target))  {
					$link .= ' target="' . $menu->target . '"';
				}
				$link .= ' class="menu-item">' . $menu->title . '</a>';
		
			} else {
		
				$link .= '<div class="menu-item">' . $menu->title . '</div>';
			}
			if (isset($menu->components)) {
				$link .= '<ul class="sub-menu">';
					foreach($menu->components as $menu_sub) {
						$link .= self::create_menu_items($menu_sub, $args);
					}
				
				// remove the last separator before closing the list
				if (isset($args['separator'])) {
					$link = preg_replace('/' . preg_quote($args['separator'], '/') . '$/', '', $link);
				}
				$link .= '</ul>';
			}
			$link .= '</li>';
			if (isset($args['separator'])) {
				$link .= $separator;
			}
			
			return $link;
		}
	}



	/**
	 * Allows for calling object properties from template pages in theme and then return or print them.
	 *
	 * @param $name
	 * @param $args
	 * @return string OR echo string
	 */
	public function __call($name, $args) {
		if (isset($this->$name)) {
			if ($args) {
				// return object property
				return $this->$name;
			} else {
				// print object property
				echo $this->$name;
			}
		} else {
			return false;
		}
	}
	
	
	/**
	 * Handles errors 
	 * Reading data from inaccessible properties will return false instead of a Notice: Undefined property error.
	 *
	 * @param mixed $var  Can accept a parameter, but always returns false
	 */
	public function __get($var) {
		return false;
	}
	
}