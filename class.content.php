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
    public function __construct($vce) {
    
    	// add vce object
    	// global $vce;
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
	 * Reads stored menu from the $site object, then builds a menu into $requested_menu
	 *
	 * @param string $title
	 * @param array $args
	 * @property object $vce
	 * @return echo string $requested_menu
	 */
	public function menu($title, $args = array()) {
	
		global $vce;
		
		$site_menus = json_decode($vce->site->site_menus, true);
		if (isset($site_menus[$title])) {
		
			$requested_menu = '<ul';
			if (isset($args['id'])) {
				$requested_menu .=  ' id="' . $args['id'] .'"';
			}
			$requested_menu .= ' class="menu-' .  $title;
			if (isset($args['class'])) {
				$requested_menu .=  ' ' . $args['class'];
			}
			$requested_menu .= '" role="menubar" aria-label="' . ucwords($title) . ' Menu">'  . PHP_EOL;

			// anonymous function to create the menu structure and save the rewind values used to insert links in the insert_menu_links function
			$create_menu_structure = function($menu_item, $args, &$menu, &$rewind) use (&$create_menu_structure, $vce) {

				// check that user role_id can view this page
				if (in_array($vce->user->role_id,explode('|',$menu_item['role_access']))) {
	
					// keep track of current level
					$args['level'] = isset($args['level']) ? $args['level'] + 1  : 1;

					$menu_item['level'] = isset($menu_item['level']) ? $menu_item['level'] : 1;
					
					$menu_item['key'] = isset($menu_item['key']) ? $menu_item['key'] : 1;
					
					// a zero value is added when at the end of the tree
					$menu_item['children'] = isset($menu_item['components']) ? count($menu_item['components']) : 0;
	
					// add this current level to the rewind array
					$rewind[] = $menu_item;
					
					// start with an li tag
					$menu .= '<li role="none">' . PHP_EOL;
					
					// add code that will be replaced with the link in the insert_menu_links function
					$menu .= '[' . $args['level'] . '|' . $menu_item['key'] . ']' . PHP_EOL;
	
					if (isset($menu_item['components'])) {
						foreach($menu_item['components'] as $menu_key=>$menu_sub) {
							// add for menu-item-first
							if ($menu_key == 0) {
								$menu_sub['position'] = 'first';
							}
							// add for menu-item-last or if only one, menu-item-single
							if ($menu_key == (count($menu_item['components']) - 1)) {
								if (!isset($menu_sub['position'])) {
									$menu_sub['position'] = 'last';
								} else {
									$menu_sub['position'] = 'single';
								}
							}

							// add a ul 
							$menu .= '<ul class="sub-menu" role="menu" aria-label="[aria|' . ($args['level'] + 1) . ']">' . PHP_EOL;
							
							// one level up
							$menu_sub['level'] = ($args['level'] + 1);
							$menu_sub['key'] = ($menu_key + 1);
		
							// recursive call back to this function
							$create_menu_structure($menu_sub, $args, $menu, $rewind);
							
							//close ul
							$menu .= '</ul>' . PHP_EOL;
						}
					}
					
					//close li
					$menu .= '</li>' . PHP_EOL;
		
				} 
			
			};
			
			// anonymous function to insert menu link
			$insert_menu_links = function($menu_item, $args, &$menu, $rewind) use (&$insert_menu_links, $vce) {

				// create classes
				if (isset($menu_item['level'])) {
					$classes[] = 'menu-level-' . $menu_item['level'];
				} else {
					$classes[] = 'menu-level-0';
				}
				
				$classes[] = 'menu-item';
				$classes[] = 'menu-item-id-' . $menu_item['id'];

				if (isset($menu_item['position'])) {
					$classes[] = 'menu-item-' . $menu_item['position'];
				}

				// check for children
				if ($menu_item['children'] > 0) {
					$classes[] = 'menu-item-has-children';
				} else {
					$classes[] = 'menu-item-childless';
				}
			
				if (!empty($args['child']['classes'])) {
					// add parent
					if (in_array('current-menu-item',$args['child']['classes'])) {
						$classes[] = 'current-menu-parent';
						$classes[] = 'current-menu-ancestor';
					}
					// add ancestor
					if (in_array('current-menu-ancestor',$args['child']['classes'])) {
						$classes[] = 'current-menu-ancestor';
					}
				}
				
				// the menu item is contantained in the requested url
				if (preg_match('#\/' . $menu_item['url'] . '\/#', '/' . $vce->requested_url)) {
					$classes[] = 'requested-url-ancestor';
				}
				
				// this menu item is the current page
				if ($menu_item['url'] == $vce->requested_url) {
					$classes[] = 'current-menu-item';
				}
				
				// a way to not have the aria tags added for dropdown menus
				$aria = null;
				if (!isset($args['dropdown']) || $args['dropdown'] != false) {
					if ($menu_item['children'] != 0) {
						$aria = ' aria-expanded="false" aria-haspopup="true"';
					}
				}
				
				// the filling for either a link or div
				$scf = ' class="' . implode(' ' ,$classes) . '" role="menuitem"' .  $aria . '>' . $menu_item['title'];
								
				if (!empty($menu_item['url'])) {
					// check if target is set to open this link in a new window
					if (isset($menu_item['target']))  {
						$scf = ' target="_blank"' . $scf ;
					}
					// check for external urls links
					if (!preg_match("/^(http|mailto)/i", $menu_item['url'])) {
						if ($menu_item['url'] == "/") {
							$menu_item['url'] = $vce->site->site_url;
						} else {
							$menu_item['url'] = $vce->site->site_url . '/' . $menu_item['url'];
						}
					}
					$link = '<a href="' . $menu_item['url'] . '"' . $scf . '</a>';
				} else {
					// if no url create as a div
					$link = '<div'  . $scf . '</div>';
				}

				$search = '[' . $menu_item['level'] . '|' . $menu_item['key'] . ']';

				$menu = str_replace($search, $link, $menu);
				
				$search = '[aria|' . $menu_item['level'] . ']';
				
				$aria_label = $menu_item['title'];

				$menu = str_replace($search, $aria_label, $menu);

				// add this link to args as child so that parent component can know
				$args['child'] = array(		
					'menu_item' => $menu_item,
					'classes' => $classes
				);

				// $vce->dump(htmlentities($link));
			
				// get last array of rewind and remove it
				$last_rewind = array_pop($rewind);
			
				// unset sub components so that it will end up down here
				unset($last_rewind['components']);
			
				if (!empty($last_rewind)) {
					// recursive callback
					$insert_menu_links($last_rewind, $args, $menu, $rewind);
				}
			
			};	
		
			// loop though first level menu items and their children
			foreach($site_menus[$title] as $menu_key=>$menu_item) {
			
				// adds menu-item-first to first menu item
				if ($menu_key == 0) {
					$menu_item['position'] = 'first';
				}
				// adds menu-item-last to last menu item
				if ($menu_key == (count($site_menus[$title]) - 1)) {
					if (!isset($menu_item['position'])) {
						$menu_item['position'] = 'last';
					} else {
						$menu_item['position'] = 'single';
					}
				}
				
				// using scope by setting these values here and then passing by reference in anonymous function to add to them
		
				$sub_menu = null;
				$rewind = array();

				// call to anonymous function
				$create_menu_structure($menu_item, $args, $sub_menu, $rewind);

				// get the last rewind value to pass to anonymous function
				$last_rewind = array_pop($rewind);
				
				// call to anonymous function
				$insert_menu_links($last_rewind, $args, $sub_menu, $rewind);
	
				$requested_menu .= $sub_menu;

			}		
			
			$requested_menu .= '</ul>' . PHP_EOL;
			
			// echo menu
			echo $requested_menu;

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
	public function _menu($title, $args = array()) {
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
	private function _create_menu_items($menu_item, $args) {
		
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
			if ($menu->url != "") {
				if (preg_match('#\/' . $menu->url . '\/#', '/' . $vce->requested_url)) {
					$link .= ' class="current-menu-ancestor"';
				}
			}
			$link .= '>';
			if ($menu->url != "") {
				if (preg_match("/^(http|mailto)/i", $menu->url)) {
					$url = $menu->url;
				} else {
					// if the url is / then this is the homepage
					if ($menu->url == "/" || !isset($menu->url)) {
						$url = $vce->site->site_url;
					} else {
						$url = $vce->site->site_url . '/' . $menu->url;
					}
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
     * Allows components to add functions to the $content object dynamically.
     *
	 * @param $name
	 * @param $args
	 * @return string OR echo string
	 */
	public function __call($name, $args) {
	
		if (isset($this->$name)) {
			if (is_string($this->$name)) {
				echo $this->$name;
				return;
			} else {
                if ($args) {
                    return call_user_func_array($this->$name, $args);
                } else {
                    return call_user_func($this->$name);
                }
			}
		}
	
		global $vce;
	
        if (isset($vce->site->hooks['content_call_add_functions'])) {
            foreach ($vce->site->hooks['content_call_add_functions'] as $hook) {
                call_user_func($hook, $vce);
            }
        }
        
        if (isset($this->$name)) {
			return self::__call($name, $args);
        } else {
			if (!VCE_DEBUG) {
				return false;
			} else {
				// print name of none existant component
				echo '<div class="vce-error-message">Call to non-existant method/property ' . '$' . strtolower(get_class()) . '->' . $name . '()'  . ' in ' . debug_backtrace()[0]['file'] . ' on line ' . debug_backtrace()[0]['line'] .'</div>';
			}
		}
        
	}


    /**
     * Magic function to convert static function calls to non-static and use __call functionality above
     *
     * @param [type] $name
     * @param [type] $args
     * @return void
     */
    public static function __callStatic($name, $args) {

        global $vce;
        return $vce->__call($name, $args);
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