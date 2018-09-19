<?php
/**
 * Gets components information and builds page content
 */
class Page {
	
	/**
	 * Builds component tree from recipe
	 * Takes the URL from the $site object, finds all components in the recipe related to that URL
	 * First working backwards to the base of the recipe, and then forward to get sub components
	 * and calls them to add their data to the $content object
	 * @global object $db
	 * @global object $site
	 */
	function __construct($vce) {
	
		// okay we're going to try this out
		// global $vce;
		$vce->page = $this;

		// check that http_host and PHP_URL_HOST match
		if ($_SERVER['HTTP_HOST'] != parse_url($vce->site->site_url, PHP_URL_HOST) && !parse_url($vce->site->site_url, PHP_URL_PORT)) {
		 	header('location: ' . parse_url($vce->site->site_url, PHP_URL_SCHEME) . '://' . parse_url($vce->site->site_url, PHP_URL_HOST) . $_SERVER['REQUEST_URI']);
		}
		
		// remove extra / at end of url
		if (preg_match('/\/{2,}$/',$_SERVER['REQUEST_URI'])) {
		 	header('location: ' . parse_url($vce->site->site_url, PHP_URL_SCHEME) . '://' . $_SERVER['HTTP_HOST'] . rtrim($_SERVER['REQUEST_URI'],'/') . '/');
		}

		// check for https within site_url only when it is set to https
		if (parse_url($vce->site->site_url, PHP_URL_SCHEME) == "https") {
			// HTTPS server variables for both Apache and Nginx
			if (isset($_SERVER['HTTPS']) == 'on' || !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) == 'on') {
				// empty slot for when https is working.
			} else {
				// force https8
				header('location: ' . 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
			}
		}

		// the url that has been requested
		$full_requested_url = explode('?', $_SERVER['REQUEST_URI']);

		// url path without query string
		$requested_url = trim($full_requested_url[0], '/');

		// if a query string has been added to the requested URL, sanitize valuse and store in $page->query_string 
		if (defined('QUERY_STRING_INPUT')) {
			// check if a query string is included within URL
			if (isset($full_requested_url[1])) {
				$query_string = array();
				foreach ($_GET as $key=>$value) {
					$query_string[filter_var($key, FILTER_SANITIZE_STRING)] = filter_var($value, FILTER_SANITIZE_STRING);
				}
				$this->query_string = $vce->query_string = json_encode($query_string);
			}
		}
		
		// get the trimmed site url path
		$site_url = trim(parse_url($vce->site->site_url, PHP_URL_PATH ), '/');
		
		// clean up the requested url by triming $requested_url slashes before and after, removing $site_url from $requested_url. 
		// # is used instead of / to prevent unknown modifier error
		$requested_url = trim(preg_replace("#^$site_url#i", '', $requested_url), '/');
		// $requested_url is now consistant with $site->site_url as base url for site

		// hook to work with the requested_url before page object get or build happens
		if (isset($vce->site->hooks['page_requested_url'])) {
			foreach($vce->site->hooks['page_requested_url'] as $hook) {
				call_user_func($hook, $requested_url, $vce);
			}
		}

		// we still need to update this for the actual url check, now with query string
		if (!defined('QUERY_STRING_INPUT')) {
			if (isset($full_requested_url[1])) {
				header('location: ' . $vce->site->site_url . '/' . $requested_url);
			}
		}

		// check for session attributes saved previously
		if (isset($_SESSION['add_attributes'])) {
			foreach (json_decode($_SESSION['add_attributes'],true) as $key=>$value) {
				// if there is a persistent value set
				if ($key == 'persistent') {
					$persistent = $value;
					foreach ($persistent as $persistent_key=>$persistent_value) {
						$vce->$persistent_key = $vce->db->clean($persistent_value);
					}
				} else {
					// normal value
					$vce->$key = $vce->db->clean($value);
				}
			}
			// clear it
			unset($_SESSION['add_attributes']);
			// rewrite if persistent value had been set
			if (isset($persistent)) {
				$_SESSION['add_attributes'] = json_encode(array('persistent' => $persistent));
			}
		}
		
		// check to see if there is a requested url
		if (!empty($requested_url)) {
		
			// check to see if a component_id has been requested, which is done by using the following syntax
			// tilde and component_id
			// ~123
			if (preg_match('/~(\d+)/',$requested_url,$requested_id)) {
				
				// fetch requested component by component_id
				$query = "SELECT " . TABLE_PREFIX . "components.*, " . TABLE_PREFIX . "components_meta.meta_value AS 'type' FROM " . TABLE_PREFIX . "components INNER JOIN " . TABLE_PREFIX . "components_meta ON " . TABLE_PREFIX . "components.component_id =  " . TABLE_PREFIX . "components_meta.component_id  WHERE " . TABLE_PREFIX . "components.component_id='" . $requested_id[1] . "' AND " . TABLE_PREFIX . "components_meta.meta_key='Type' LIMIT 1";


			// otherwise fetch by url
			} else {
			
				// fetch requested component by url
				$query = "SELECT " . TABLE_PREFIX . "components.*, " . TABLE_PREFIX . "components_meta.meta_value AS 'type', " . TABLE_PREFIX . "components_meta.minutia AS 'cache' FROM " . TABLE_PREFIX . "components INNER JOIN " . TABLE_PREFIX . "components_meta ON " . TABLE_PREFIX . "components.component_id =  " . TABLE_PREFIX . "components_meta.component_id  WHERE " . TABLE_PREFIX . "components.url='" . $requested_url . "' AND " . TABLE_PREFIX . "components_meta.meta_key='Type' LIMIT 1";
								
			}

			// call to database, grab first array item because there should only be one
			$requested_location = $vce->db->get_data_object($query);
		
		}

		// if url is not found, return / for homepage
		if (empty($requested_location)) {
		
			// get homepage
			$query = "SELECT " . TABLE_PREFIX . "components.*, " . TABLE_PREFIX . "components_meta.meta_value AS 'type' FROM " . TABLE_PREFIX . "components INNER JOIN " . TABLE_PREFIX . "components_meta ON " . TABLE_PREFIX . "components.component_id =  " . TABLE_PREFIX . "components_meta.component_id  WHERE " . TABLE_PREFIX . "components.url='/' AND " . TABLE_PREFIX . "components_meta.meta_key='Type' LIMIT 1";
			$requested_location = $vce->db->get_data_object($query);
			
			// if no homepage has been set, then direct to message
			if (empty($requested_location)) {
				require_once(BASEPATH . 'vce-application/html/index.html');
				exit();
			}

		}

		
		// the requested component: component_id, parent_id, sequence, url
		$requested_component = $requested_location[0];
		
		// load hooks
		// page_construct_object
		// to redirect to another component_id, set both component_id and parent_id
		if (isset($vce->site->hooks['page_construct_object'])) {
			foreach($vce->site->hooks['page_construct_object'] as $hook) {
				call_user_func($hook, $requested_component, $vce);
			}
		}
		
		// add basics to object
		$vce->requested_id = $requested_component->component_id;
		$vce->requested_url = $requested_component->url;
	
		// start building page object components
		// $page_id, $requested_id, array('requested_location' => $requested_component)
		// requested_location is used to so that the recursive get_components method knows this is the first time
		self::get_components($requested_component->component_id, $requested_component->component_id, array('requested_location' => $requested_component));
		
		// read recipe
		$recipe = (isset($this->recipe)) ? $this->recipe : array();
	
		// prevent any errors if no template has been assigned
		if (!isset($this->template)) {
			$this->template = "index.php";
		}

		// build page content from components
		self::build_content($this->components, $recipe, $requested_component->component_id);

	}

	
	/**
	 * Gets list of components and associated meta data
	 * this is done from the component which is being accessed by url backwards to the start of the recipe
	 * Called by __construct(), takes the $requested_id and returns all associated components
	 * @global object $db
	 * @param int $page_id
	 * @param int $requested_id
	 * @param array $components
	 * @param boolean $build_sub_componets
	 * @return adds components to class-wide array of components
	 */
	private function get_components($page_id, $requested_id, $components) {
	
		global $vce;
		
		// not first time
		if (!isset($components['requested_location'])) {
		
			// get level one components
			$query = "SELECT * FROM  " . TABLE_PREFIX . "components WHERE component_id='" . $page_id . "' LIMIT 1";
			$requested_component = $vce->db->get_data_object($query)[0];
			
			// load hooks
			if (isset($this->site->hooks['page_requested_components'])) {
				foreach($this->site->hooks['page_requested_components'] as $hook) {
					$requested_component = call_user_func($hook, $requested_component, func_get_args());
				}
			}
		
		// first time
		} else {
		
			// add value from previous function
			$requested_component = $components['requested_location'];
			// clean-up
			unset($components['requested_location']);
		
		}

		// clean up the object if no url has been set
		// if no url was returned within $requested_page, delete property in object
		if (empty($requested_component->url)) {
			unset($requested_component->url);
		}
		
		// get level one components meta data
		$query = "SELECT meta_key, meta_value, minutia FROM  " . TABLE_PREFIX . "components_meta WHERE component_id='" . $requested_component->component_id . "' ORDER BY meta_key";
		$components_meta = $vce->db->get_data_object($query);
		
		// add meta data to page object
		foreach ($components_meta as $each_meta) {
		
			// add title from requested id to page object base
			if ($requested_component->component_id == $requested_id && $each_meta->meta_key == "title") {
				$this->title = $each_meta->meta_value;
			}

			// if a template has been assigned to this component, add it to object
			
			// moving backwards though the componenets, if template has not been set, add it to page object
			if (!isset($this->template) && $each_meta->meta_key == "template") {
				// check that template file exists
				if (is_file(BASEPATH .'vce-content/themes/' . $vce->site->site_theme . '/' . $each_meta->meta_value)) {
					$this->template = $each_meta->meta_value;
				}
			}

			// get recipe and add to base of object
			if ($each_meta->meta_key == "recipe") {
			
				// decode json object of recipe
				$recipe = json_decode($each_meta->meta_value, true)['recipe'];
				
				// load hooks
				if (isset($vce->site->hooks['page_add_recipe'])) {
					foreach($vce->site->hooks['page_add_recipe'] as $hook) {
						$recipe = call_user_func($hook, $this->recipe, $recipe);
					}
				}
				
				// set recipe property of page object
				$this->recipe = $recipe;
				continue;
			
			}
			
			// get meta keys name to add to this component
			$key = $each_meta->meta_key;
			
			// create associative array with meta key and meta value
			$requested_component->$key = $vce->db->clean($each_meta->meta_value);

			// create minutia array element - this is not being used much
			if (!empty($each_meta->minutia)) {
				$key .= "_minutia";
				$requested_component->$key = $each_meta->minutia;
			}
		
		}
		
		// load hooks
		if (isset($vce->site->hooks['page_get_components'])) {
			foreach($vce->site->hooks['page_get_components'] as $hook) {
				call_user_func($hook,$requested_component,$components,$vce);
			}
		}
		
		// check that component has not been disabled
		$activated_components = json_decode($vce->site->activated_components, true);
		
		if (!VCE_DEBUG) {
			// prevent an error when there is an orphaned component without any meta_data
			$requested_component->type = isset($requested_component->type) ? $requested_component->type : 'Components';
		}
		
		// prepend to begining of array to make parents first
		array_unshift($components, $requested_component);

		// if component has parent id, recursive call to this function
		if (isset($requested_component->parent_id) && $requested_component->parent_id != 0) {

			// recursive call
			self::get_components($requested_component->parent_id, $requested_id, $components);

		// check that access is allowed for sub components
		} else {
		
			// to check find_sub_components returned value, get end component
			$end_component = end($components);
		
			// check that this component has been activated
			if (isset($activated_components[$end_component->type])) {
				require_once(BASEPATH . $activated_components[$end_component->type]);
				// create a new instance of the component
				$check = new $end_component->type();
			} else {
				// if deactivated use the parent class
				$check = new Component();
			}
		
			// get returned value from component for find_sub_components method
			// by default returns true from method in components.class
			// true from components continues getting sub components
			$find_sub_components = $check->find_sub_components($requested_component, $vce, $components, $sub_components = array());

			// check if find_sub_components is true
			if ($find_sub_components) {
				// get sub-components
				$nested_components = self::get_sub_components($requested_id, $requested_id, $components);
						
				// add sub_components to components list
				$components[(count($components)-1)]->components = $nested_components;
			}
			
			// add components to object
			$this->components = $components;

		}
	
	}
	
	
	/**
	 * Gets list of sub-components and associated meta data
	 * Takes the id of the component being process and queries for all components ordered under it
	 * @global object $db
	 * @param int $current_id
	 * @param int $parent_id
	 * @param array $sub_components
	 * @param string $sub_url
	 * @return array of subcomponents
	 */
	private function get_sub_components($current_id, $parent_id, $components, $sub_components = array(), $sub_url = false, $full_object = false) {
	
		// allow access to the global vce object
		global $vce;

		// get children of current_id
		$query = "SELECT * FROM  " . TABLE_PREFIX . "components WHERE parent_id='" . $current_id . "' ORDER BY sequence ASC";
		$requested_components = $vce->db->get_data_object($query);
	
		// load hooks
		if (isset($this->site->hooks['requested_sub_components'])) {
			foreach($this->site->hooks['requested_sub_components'] as $hook) {
				$requested_components = call_user_func($hook, $requested_components, func_get_args());
			}
		}

		if (!empty($requested_components)) {

			// get meta data for each component and add it to object
			foreach ($requested_components as $each_key=>$each_component) {
			
				// found a url so make sub_url = true
				if (!empty($each_component->url)) {
					$sub_url = true;
				} else {
					// otherwise, unset url
					unset($each_component->url);
				}
			
				// add array element keyed by component_id
				$requested[$each_component->component_id] = $each_component;
			
			}
			
			// get meta data for component
			$query = "SELECT component_id, meta_key, meta_value, minutia FROM  " . TABLE_PREFIX . "components_meta WHERE component_id IN (" . implode(',',array_keys($requested)) . ") ORDER BY meta_key";
			$components_meta = $vce->db->get_data_object($query);
			
			// rekey the meta data
			foreach ($components_meta as $each_meta) {

					// this is not currently used
					if ($each_meta->meta_key == "recipe") {
			
						// decode json object of recipe
						$recipe = json_decode($each_meta->meta_value, true)['recipe'];
				
						// load hooks
						if (isset($vce->site->hooks['page_add_recipe'])) {
							foreach($vce->site->hooks['page_add_recipe'] as $hook) {
								$recipe = call_user_func($hook, $this->recipe, $recipe);
							}
						}
				
						// set recipe property of page object
						$this->recipe = $recipe;
						continue;
			
					}

					// create a var from meta_key
					$key = $each_meta->meta_key;
	
					$requested[$each_meta->component_id]->$key = $vce->db->clean($each_meta->meta_value);

					// adding minutia if it exists within database table
					if (!empty($each_meta->minutia)) {
						$key .= "_minutia";
						$requested[$each_meta->component_id]->$key = $each_meta->minutia;
					}
					
			}
			
			// rekey requested
			$requested_components = array_values($requested);

			// load hooks
			// page_get_sub_components
			if (isset($vce->site->hooks['page_get_sub_components'])) {
				foreach($vce->site->hooks['page_get_sub_components'] as $hook) {
					$requested_components = call_user_func($hook,$requested_components,$sub_components,$vce);
				}
			}

			// anonymous function to place requested components into a multidimensional array of sub components
			$build_components_tree = function($sub_components,$requested_components) use (&$build_components_tree) {
			
				// take requested_components and associate parent_id with sub_component id
				// get parent_id associated with this level of requested page
				$parent_id = $requested_components[0]->parent_id;
			
				foreach ($sub_components as $key=>$each_sub_component) {
				
					// current matches parent
					if ($each_sub_component->component_id == $parent_id) {
					
						// found parent and returning value
						$sub_components[$key]->components = $requested_components;

						// break out of foreach and then use the return at the end of this fuction
						break;
			
					}

					if (isset($each_sub_component->components)) {
						// up to next level, recursive call back to anonymous function
						$sub_components[$key]->components = $build_components_tree($each_sub_component->components,$requested_components);
					}
				
				}
				
				// one and only return out of this funtion
				return $sub_components;
				
			};

			if (!empty($sub_components)) {
				// subsequent times, call to anonymous recursive function
				$sub_components = $build_components_tree($sub_components,$requested_components);
			} else {
				// first time through
				$sub_components = $requested_components;
			}
			

			// check for sub components
			foreach ($requested_components as $each_key=>$each_component) {
						
				// load components class
				
				// check that component has not been disabled
				// global $site;

				$activated_components = json_decode($vce->site->activated_components, true);
				
				// check that the component type has been activated
				if (isset($each_component->type) && isset($activated_components[$each_component->type])) {
		
					// require the component
					require_once(BASEPATH . $activated_components[$each_component->type]);
		
					// fire check_access funtion for each component
					// this effects children of component
					$access = new $each_component->type();
				
				} else {
				
					// fall back if component has been disabled, though why would someone do that?!
					$access = new Component();
				
				}
								
				// if $recursive is true then recursive call back to get_sub_components for next component
				$recursive = false;
					
				// anonymous function for checking if there are nested components with the same type.
				$nested = function($sub_components, $type, $history = array(), $level = 0, $tracker = array()) use (&$nested) {
					
					if (isset($sub_components)) {
					
						// cycle through current sub_component looking for a type match
						foreach ($sub_components as $counter=>$each_component) {
								
							// types match so return true
							if ($each_component->type == $type) {
								return true;
							}
							
							// check if a url has been set, if so add to $tracker so we can skip it
							if (isset($each_component->url)) {
							
								// create a reference for this position within sub_components
								$position = $level . 'x' . $counter;
							
								// add position info to tracker
								$tracker[$position] = true;
							
							}
									
						}
					
						
						// going up a level if nothing was found on this one
						foreach ($sub_components as $counter=>$each_component) {
							
							// create a reference for this position within sub_components
							$position = $level . 'x' . $counter;
							
							// check that components exist for this level and that a reference wasn't already created for this position.
							if (isset($each_component->components) && !array_key_exists($position,$tracker)) {
								
								// record this current level so we can come back
								$history[] = $sub_components;
								
								// next level 
								$level++;
								
								// add position info to tracker
								$tracker[$position] = true;
								
								// recursive call for next level
								return $nested($each_component->components, $type, $history, $level, $tracker);

							}
	
						}
						
						// made it though the foreach without a recursive send, so check for history and recursively
							
						if (isset($history[(count($history)-1)])) {
							
							// get previous value
							$previous_sub_component = $history[(count($history)-1)];
							// then unset previous value
							unset($history[(count($history)-1)]);
							// then delete one from $level
							$level--;
							
							// recursive call
							return $nested($previous_sub_component, $type, $history, $level, $tracker);
							
						}
						
					}
						
				};
				
				// check that component allows sub_components to be built in page object
				$recursive = $access->find_sub_components($each_component, $vce, $components, $sub_components);

				// if find_sub_components returned true for current component, then check for the following
				if ($recursive) {

					// full object option has been checked, build entire page object
					if ($full_object) {
						$recursive = true;
					} elseif (isset($this->recipe[0]['full_object'])) {
						$recursive = true;
					// check for nested components with the same type
					// the purpose of this is if you have several branches of differnet depths
					// where sub_url (the next url) might be at a deeper level
					} elseif ($sub_url && !$nested($sub_components,$each_component->type)) {
						$recursive = false;
					}
				
				}
				
				// if full_object is true, then overide recursive value and call back to get_sub_components
				if ($full_object) {
					$recursive = true;
				}
	
	
				// send call back to this function
				if ($recursive) {
					// our recursive call
					self::get_sub_components($each_component->component_id, $each_component->parent_id, $components, $sub_components, $sub_url, $full_object);
				}	

			}
			
			// return nested components
			return $sub_components;
		
		}
		
	}
	
	
	/**
	 * Builds content from components
	 * Called from __construct(), is given a list of components and the recipe, and then orders the components and
	 * recursively calls on them to add their content to the $content object. 
	 * @global object $site
	 * @global object $user
	 * @param array $components
	 * @param array $recipe
	 * @param int $requested_id
	 * @param bool $linked
	 * @param array $recipe_tracker
	 * @return components have added their content to the $content object
	 */
	private function build_content($components, $recipe, $requested_id, $linked = false, $recipe_tracker = array()) {

		// allow access to the global vce object
		global $vce;

		// loop through components
		foreach ($components as $each_component_key=>$each_component) {

			// load hooks
			// page_build_content
			if (isset($vce->site->hooks['page_build_content'])) {
				foreach($vce->site->hooks['page_build_content'] as $hook) {
					call_user_func($hook, $each_component, $linked);
				}
			}

			// anonymous function to find the piece of the recipe associated with current component
			$find_recipe_item = function($recipe, $component, $recipe_level, $recipe_tracker, $cascade_attributes, $rewind = array(), $rewind_tracker = array()) use (&$find_recipe_item) {
	
				// current level counter
				$recipe_level++;

				// the list of attributes that cascade forward within recipes
				// we can create a hook at some point if needed
				$attributes = array('content_create','content_edit','content_delete');

				// in case there are multiple components at the same recipe level
				foreach ($recipe as $recipe_item_key=>$recipe_item) {

					foreach ($attributes as $each_attribute) {
						// check each attribute
						if (isset($recipe_item[$each_attribute])) {
							// set value for next time though
							$cascade_attributes[$each_attribute] = $recipe_item[$each_attribute];
						} else {
							// if this level of recipe has none, set it to previous
							if (isset($cascade_attributes[$each_attribute])) {
								$recipe_item[$each_attribute] = $cascade_attributes[$each_attribute];
							}
						}
					}
				
					// component type matches recipe type
					if ($recipe_item['type'] == $component->type) {
					
						// set to false, make true if we have a recipe match
						$match = false;
						
						// if recipe_key has been set, then check the location for a match
						if (isset($component->recipe_key)) {
							// recipe_key has been set, check if this is the correct recipe item
							if ($component->recipe_key == $recipe_item_key) {
								$match = true;
							} else {
								// move to next foreach item
								continue;
							}
						} else {

							// check that this recipe item hasn't been encounterd before and isn't contained within $recipe_tracker
							if (!isset($recipe_tracker[$recipe_level . 'x' . $recipe_item_key])) {
								$match = true;
							} else {
								// check what the current level is
								if ($recipe_level == end($recipe_tracker)) {		
									// there's another component on this level with the same type
									$level_count = count($recipe) - 1;
									if ($level_count > 0) {
										for ($x=$level_count;$x>=0;$x--) {
											// looking for another recipe item that is the same type
											if ($x > $recipe_item_key && $recipe[$x]['type'] == $component->type) {
												// break out if one is found
												continue;
											} elseif ($recipe[$x]['type'] == $component->type) {
												// otherwise, if the current one is the same type, then add this recipe to the current component
												$match = true;
											}
										}
									} else {
										// check if there is only one recipe item at this level
										if (count($recipe) == 1) {
											$match = true;
										}
									}
								}
							}
						}

						if ($match) {

							// save the current recipe item
							$this_recipe = $recipe_item;
														
							// clean it up
							unset($this_recipe['components']);
							
							// there is a sub_recipe to pass
							if (isset($recipe_item['components'])) {
							
								// sort though and add content_create value if it does not exist on this level
								foreach ($recipe_item['components'] as $key=>$each_item) {
									// cycle through attibutes to cascade
									foreach ($attributes as $each_attribute) {
										if (!isset($recipe_item['components'][$key][$each_attribute])) {
											if (isset($cascade_attributes[$each_attribute])) {
												$recipe_item['components'][$key][$each_attribute] = $cascade_attributes[$each_attribute];
											}
										}
									}
								}

								// return sub recipe and location marker
								return array('sub_recipe' => $recipe_item['components'],'recipe_level' => $recipe_level, 'location' => $recipe_item_key, 'this_recipe' => $this_recipe);
								
							// or when there is no sub recipe
							} else {
								
								// return sub recipe and location marker as placeholder
								return array('sub_recipe' => null,'recipe_level' => $recipe_level, 'location' => $recipe_item_key, 'this_recipe' => $this_recipe);
							}
							
						}
						
					}

				}

				// add the current recipe level to rewind
				$rewind[] = $recipe;

				// run it all again to recursively call
				foreach ($recipe as $key=>$recipe_item) {
				
					// add current recipe location to tracker
					$rewind_tracker[$recipe_level . 'x' . $key] = true;

					// check if we have a match with the a value within $recipe_tracker, which would have been set last time 
					if (isset($recipe_tracker[$recipe_level . 'x' . $key]) && isset($recipe_item['components'])) {

						return $find_recipe_item($recipe_item['components'], $component, $recipe_level, $recipe_tracker, $cascade_attributes, $rewind, $rewind_tracker);
						
					}
					
				}

				// made it though the foreach without a recursive send, so check for rewind and recursively call back to this function
				// nothing found in the last array element, so remove current recipe level from rewind
				array_pop($rewind);

				// check that current level of rewind is set
				if (isset($rewind[(count($rewind)-1)])) {

					// get the previous recipe from rewind
					$previous_recipe_level = $rewind[(count($rewind)-1)];

					// remove last array elements and backout recipe level for recursive call back to function
					array_pop($rewind);
					array_pop($rewind_tracker);
					$recipe_level--;
					
					// cycle through recipe elements
					foreach ($previous_recipe_level as $key=>$value) {
						
						// if this element has already been checked, continue
						if (isset($rewind_tracker[$recipe_level . 'x' . $key])) {
							continue;
						}

						// check for value first
						if (isset($value['components'])) {
							// recursive call for sub-components
							// note: start debugging here if you cannot see a way to add a component in a recipe
							return $find_recipe_item($value['components'], $component, $recipe_level, $recipe_tracker, $cascade_attributes, $rewind, $rewind_tracker);
						}
						
					}

				}

			};

			// safety check that recipe is there.
			if (isset($recipe)) {
				
				if (!VCE_DEBUG) {
					// prevent an error when there is an orphaned component without any meta_data
					$each_component->type = isset($each_component->type) ? $each_component->type : 'Components';
				}
				
				// if this is the requested component or the last time though was
				if (($each_component->component_id == $requested_id) || $linked === true) {
					// get sub_recipe for each_component, to send to class.component.php->recipe_components

					// call to anonymous function
					$sub_recipe = $find_recipe_item($recipe, $each_component, 0, $recipe_tracker, array());

					// add sub_recipe
					if (isset($sub_recipe['sub_recipe'])) {
						$each_component->sub_recipe = $sub_recipe['sub_recipe'];
					}

					if (isset($sub_recipe['this_recipe'])) {
						$each_component->recipe = $sub_recipe['this_recipe'];
					}

					// add location to recipe tracker
					
					$recipe_tracker[$sub_recipe['recipe_level'] . 'x' . $sub_recipe['location']] = $sub_recipe['recipe_level'];

				} else {

					// call to anonymous function
					$sub_recipe = $find_recipe_item($recipe, $each_component, 0, $recipe_tracker, null);

					// set recipe as the returned sub_recipe
					$recipe = $sub_recipe['sub_recipe'];

				}
				
			}

			// set sub components
			$sub_components = isset($each_component->components) ? $each_component->components : null;

			// get name of component class
			$class_name = $each_component->type;

			// get list of activated components
			$activated_components = json_decode($vce->site->activated_components, true);
			
			// check that this component hasn't been disabled
			if (isset($activated_components[$class_name])) {
				
				// check that class has been loaded
				if (!class_exists($class_name)) {
					// require the component
					require_once(BASEPATH . $activated_components[$class_name]);
				}
				
				// call to the component class for Type
				$this_component = new $class_name((object) $each_component);

			} else {
			
				// default to base class for components
				$this_component = new Component((object) $each_component);
			
			}
			
			// does component have a url assigned?
			if (isset($each_component->url)) {
			
				// check if last component was the requested id
				if ($linked === false) {
			
					// should move the recipe thing in here?
			
					// check to see if this component is the requested id
					if ($each_component->component_id == $requested_id) {
						// change value to true 
						$linked = true;
					}
				} else {

					// last component was the requested id, so generate links for this component
					$this_component->as_link($each_component, $vce);
					continue;
				}
			}

			// check_access calls
			// saving previous comments
			// check_access was false within get_components(), or content_edit equals roles, so access_denied was set for this component
			// if ((!isset($each_component->access_denied) || (isset($each_component->recipe['content_edit']) && $each_component->recipe['content_edit'] == 'roles')) && $this_component->check_access((object) $each_component, $this)) {
			if ($this_component->check_access($each_component, $vce) || (isset($each_component->recipe['content_edit']) && $each_component->recipe['content_edit'] == 'roles')) {
				
				// normal component layout
				// as_content can be used to stop the build if false is returned by the component method
				$as_content = $this_component->as_content($each_component, $vce);
				
				// this currently does not carry forward to sub components
				if ($this_component->allow_sub_components($each_component, $vce)) {
				
					// check if prevent_sub_components has been set by previous allow_sub_components call
					if (!isset($this->prevent_sub_components)) {
								
						// user can create sub component?
						// send sub_recipe only to recipe_components function in class.component.php
						// which in turn sends to the add_component function in each component
						$this_component->recipe_components($each_component, $vce);
					
					}
					
				}
		
				// prevent_editing can be used to skip the call to the component's edit_component method
				if (!isset($each_component->prevent_editing) || $each_component->prevent_editing === false) {
					// revise_component calls to edit_component function within components.class
					// added for consistancy, so that it matches how recipe_component behaves when it passes the dossier
					// $this_component->revise_component($each_component, $vce, $this_component);
					$this_component->edit_component($each_component, $vce);
				}

				// does this component have sub components and as_content was not returned as false by the component method
				if (isset($sub_components) && $as_content !== false) {

					// load hooks
					// page_build_content_callback
					if (isset($vce->site->hooks['page_build_content_callback'])) {
						foreach($vce->site->hooks['page_build_content_callback'] as $hook) {
							$sub_components = call_user_func($hook, $sub_components, $each_component, $vce);
						}
					}

					// check if build_sub_components is true
					if ($this_component->build_sub_components($each_component, $vce)) {
	
						// recursive call for sub component
						self::build_content($sub_components, $recipe, $requested_id, $linked, $recipe_tracker);
				
					}
					
				} 
			
				// prevent top component from closing immediately after call
				if (isset($each_component->parent_id)) {
					
					// look for sub recipes to fire off close
					if (isset($each_component->sub_recipe)) {
						
						// cycle though any sub_recipes
						foreach ($each_component->sub_recipe as $each_sub_recipe) {
						
							// get name of component class
							$class_name = $each_sub_recipe['type'];

							// get list of activated components
							$activated_components = json_decode($vce->site->activated_components, true);
						
							// check that this component hasn't been disabled
							if (isset($activated_components[$class_name])) {

								// load if it hasn't been yet
								require_once(BASEPATH . $activated_components[$class_name]);

								// call to the component class for Type
								$previous_component = new $class_name((object) $each_component);

							} else {
				
								// default to base class for components
								$previous_component = new Component((object) $each_component);
			
							}
							
							// check to see if allow_sub_components does not return false
							if ($this_component->allow_sub_components($each_component, $vce)) {
					
								// call book end for recipe_components, similar to as_content_finish
								$previous_component->add_component_finish($each_component, $vce);
								
							}
					
						}
					
					}
					
					// as content finish
					$this_component->as_content_finish($each_component, $vce);
					
				} else {
					// save top component values and then execute after foreach
					$top_instance = $this_component;
					$top_object = $each_component;
				}
				
			} else {
			
				// work in progress
			
				// access denied, so search for a repudiated_url within components meta_data
				for ($key = 0;$key < count($this->components);$key++) {
					// repudiated_url found
					if ($this->components[$key]->component_id == $each_component->component_id && isset($this->components[$key]->repudiated_url)) {
						// forward location to repudiated_url		
						header('location: ' . $vce->site->site_url . '/' . ltrim($this->components[$key]->repudiated_url, '/'));
					}
				}
				
				// reset title in page object to level of failed access
				$this->title = isset($each_component->title) ? $each_component->title : 'Page';
			
				// reset template in page object to level of failed access
				$template_default = isset($this->template) ? $this->template : 'index.php';
				$this->template = isset($each_component->template) ? $each_component->template : $template_default;
				
				// end our foreach loop
				// if we have not found the url yet, break
				if (!$linked) {
					break;
				}
				
			}
			
		}
	
		// execute top component finish
		if (!empty($top_instance)) {
			$top_instance->as_content_finish((object) $top_object, $vce);
		}

	}
	
	
	
	/**
	 * Gets parents of a component_id
	 * @param int $component_id
	 * @return object 
	 */
	public static function get_parents($component_id) {
	
		global $vce;
		
		// annonymous function for database calls
		$get_component = function($component_id) use ($vce) {
		
			$each_component = new stdClass();

			$query = "SELECT * FROM  " . TABLE_PREFIX . "components WHERE component_id='" . $component_id . "' LIMIT 1";
			$requested_component = $vce->db->get_data_object($query);
			
			foreach ($requested_component[0] as $each_key=>$each_value) {
				// check if value is empty
				if ($each_value != "") {
					$each_component->$each_key = $each_value;
				}
			}
			
			// get all meta data
			$query = "SELECT meta_key, meta_value, minutia FROM  " . TABLE_PREFIX . "components_meta WHERE component_id='" . $component_id . "'";
			$requested_met_data = $vce->db->get_data_object($query);
		
			// add meta_kay => meta_value data
			foreach ($requested_met_data as $meta_data) {
				$meta_key = $meta_data->meta_key;
				$each_component->$meta_key = $meta_data->meta_value;
				if ($meta_data->minutia != NULL) {
					$meta_key .= '_minutia';
					$each_component->$meta_key = $meta_data->minutia;
				}
			}

		
			return $each_component;
		
		};
	
		// annonymous function to get parents
		$get_parents = function ($component_id, $parent_id = null, $components = array()) use (&$get_parents, $get_component) {

			
			// if no parent id provided, start our search
			if (!isset($parent_id)) {

				$each_component = $get_component($component_id);
				
				// add this component to the start of the components array
				array_unshift($components, $each_component);
				
				// get our parent_id for the first time through
				$parent_id = $each_component->parent_id;
				
			}
			
			// There are no parents for this component
			if ($parent_id == 0) {
				return $components;
			}
			
			$each_component = $get_component($parent_id);
			
			// add this component to the start of the components array
			array_unshift($components, $each_component);
			
			if ($each_component->parent_id != 0) {
				// recursive call to get next depth componetn
				return $get_parents($component_id, $each_component->parent_id, $components);
			} else {
				// we're reached the end
				return $components;
			}
			
		};
		
		return $get_parents($component_id);
			
	}
	
	/**
	 * Gets children of a parent_id
	 * @param int $parent_id
	 * @return call self::get_sub_components()
	 */
	public function get_children($current_id, $parent_id = null, $component = array(), $sub_components = array(), $sub_url = false, $full_object = false) {
		return self::get_sub_components($current_id, $parent_id, $component, $sub_components, $sub_url, $full_object);
	}
	
	
	/**
	 * Display Components
	 * This method allows for a component object to be sent to build_content
	 * Note: if you want to have recipe_components / add_component work for components sent to this method, you need to send along a requested_id with the recipe
	 * $requested_id should be the component_id from where you want the sub_recipe to allow add_content.
	 * The recipe should include the parent component of the requested_id
	 * Note: To display content in a layout, you should output to content beforehand: $vce->content->add('main', $content);
	 *
	 * @param int $components
	 * @return true
	 */
	public function display_components($components, $recipe = null, $requested_id = null, $linked = false, $recipe_tracker = array()) {
		// $components needs to be an array
		$components_array = !is_array($components) ? array($components) : $components;
		// call to build_content
		self::build_content($components_array, $recipe, $requested_id, $linked, $recipe_tracker);
		return;
	}
	
	
	/**
	 * Gets url that is associated with component, which is the first one encountered working backwards through parents
	 * @global object $db
	 * @global object $site
	 * @param int $component_id
	 */
	public function find_url($component_id) {
	
		global $vce;
	
		// get current_id
		$query = "SELECT * FROM  " . TABLE_PREFIX . "components WHERE component_id='" . $component_id . "'";
		$requested_component = $vce->db->get_data_object($query);
		
		if (isset($requested_component[0]->url) && strlen($requested_component[0]->url) > 0) {
			return $vce->site->site_url . '/' . $requested_component[0]->url;
	
		}
		
		// if there is a parent_id and that it's not equal to 0
		if (isset($requested_component[0]->parent_id) && $requested_component[0]->parent_id != 0) {
			// recursive call back to parent component searching for a url
			return self::find_url($requested_component[0]->parent_id);
		}
		
	}
	
	/**
	 * Checks to see if user can add a component to the page
	 * @param object $each_recipe_component
	 * @return bool
	 */
	public function can_add($each_component) {
	
		global $vce;
	
		// user is a site admin
		if ($vce->user->role_id == "1") {
			return true;
		}
	
		// user role_id is contained within content_create
		if (isset($each_component->content_create)) {
			if (in_array($vce->user->role_id,explode('|',$each_component->content_create))) {
				return true;
			}
		} else {
			// content_create not set, so allow add for any user, including public
			return true;
		}
		
		// in recipe, user role_id is contained within content_create, but this time it's contained within recipe
		if (isset($each_component->recipe['content_create'])) {
			if (in_array($vce->user->role_id,explode('|',$each_component->recipe['content_create']))) {
				return true;
			}
		}
		
		return false;
	
	}
	
	/**
	 * Checks to see if user can edit a component to the page
	 * @param object $each_component
	 * @return bool
	 */
	public function can_edit($each_component) {
	
		global $vce;

		// user is a site admin
		if ($vce->user->role_id == "1") {
			return true;
		}
		
		// prevent_editing is set in component
		if (isset($each_component->prevent_editing) && $each_component->prevent_editing === true ) {
			return false;
		}
		
		// user created this component
		if (isset($each_component->created_by) && $each_component->created_by == $vce->user->user_id) {
			return true;
		}
		
		// in recipe, content_edit = roles and user->role_id is in content_create
		if (isset($each_component->recipe['content_edit']) && isset($each_component->recipe['content_create'])) {
        	if ($each_component->recipe['content_edit'] == "roles" && in_array($vce->user->role_id,explode('|',$each_component->recipe['content_create']))) {
				return true;
			}
		}
		
		// in component meta_data, content_edit = roles and user->role_id is in content_create
		if (isset($each_component->content_edit) && isset($each_component->content_create)) {
        	if ($each_component->content_edit == "roles" && in_array($vce->user->role_id,explode('|',$each_component->content_create))) {
				return true;
			}
		}
	
		return false;
	}


	/**
	 * Checks to see if user can delete a component to the page
	 * @param object $each_component
	 * @return bool
	 */
	public function can_delete($each_component) {

		global $vce;

		// user is a site admin
		if ($vce->user->role_id == "1") {
			return true;
		}
		
		// if prevent_delete is true, then return false
		if (isset($each_component->prevent_delete)) {
			// if roles have not be specified, then no one can delete, except for admins
			if (!isset($each_component->prevent_delete_roles)) {
				return false;
			// specific roles
			} else if (in_array($vce->user->role_id,explode('|',$each_component->prevent_delete_roles))) {
				return false;
			}
		}
		
		// user created this component
		if (isset($each_component->created_by) && $each_component->created_by == $vce->user->user_id) {
			return true;
		}
		
		// in recipe, content_edit = roles and user->role_id is in content_create
        if (isset($each_component->recipe['content_delete']) && isset($each_component->recipe['content_create'])) {
        	if ($each_component->recipe['content_delete'] == "roles" && in_array($vce->user->role_id,explode('|',$each_component->recipe['content_create']))) {
				return true;
			}
		}

		// in component meta_data, content_edit = roles and user->role_id is in content_create
        if (isset($each_component->content_delete) && isset($each_component->content_create)) {
        	if ($each_component->content_delete == "roles" && in_array($vce->user->role_id,explode('|',$each_component->content_create))) {
				return true;
			}
		}
	
		return false;
	
	}
	
	/**
	 * Create encrypted dossier and return it as a string
	 *
	 * @param object $dossier_elements
	 * @return string
	 */
	public function generate_dossier($dossier_elements_input) {
	
		global $vce;
		
		// cast to array
		$dossier_elements = (array) $dossier_elements_input;
	
		// clean-up nulls and any empty array
		foreach ($dossier_elements as $dossier_name=>$dossier_value) {
			if (is_null($dossier_value) || count($dossier_value) < 1) {
				unset($dossier_elements[$dossier_name]);
			}
		}
		
		// encrypt dossier with session_vector for user
		return $vce->user->encryption(json_encode($dossier_elements),$vce->user->session_vector);
	
	}
	
	/**
	 * A method to check component specific permissions
	 *
	 * @param string $permission_name
	 * @param string $component_name
	 * @return bool
	 */
	public function check_permissions($permission_name, $component_name = null) {
		global $user;
		// find the calling class by using debug_backtrace
		if (!$component_name) {
			$backtrace = debug_backtrace(false, 2);
			$component_name = $backtrace[1]['class'];
		}
		// add permissions onto the component name
		$component_permissions = $component_name . '_permissions';
		if (in_array($permission_name, explode(',', $user->$component_permissions))) {
			return true;
		}
		return false;
	}

	/**
	 * ~sort an object or array by associative key
	 *
	 * @param object/array $data
	 * @param string $key
	 * @param string $order
	 * @param string $type
	 * @return object/array
	 */
	public static function sorter($data, $key='title', $order='asc', $type='string') {
	
		usort($data, function($a, $b) use ($key, $order, $type) {
			// check if this is an object or an array
			$a_sort = is_object($a) ? $a->$key : $a[$key];
			$b_sort = is_object($b) ? $b->$key : $b[$key];
			if (isset($a_sort) && isset($b_sort)) {
				// sort as string
				if ($type == 'string') {
					if ($order == "asc") {
						return (strcmp($a_sort, $b_sort) > 0) ? 1 : -1;
					} else {
						return (strcmp($a_sort, $b_sort) > 0) ? -1 : 1;
					}
				} else if ($type == 'time') {
					// sort as time
					if ($order == "asc") {
						return strtotime($a_sort) > strtotime($b_sort) ? 1 : -1;
					} else {
						return strtotime($a_sort) > strtotime($b_sort) ? -1 : 1;
					}
				} else {
					return 1;
				}
			} else {
				return 1;
			}
		});
		// return the sorted object/array
		return $data;
	}

	/**
	 * Allows for calling object properties from template pages in theme and then return or print them.
	 *
	 * @param string $name
	 * @param array $args
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
			if (!VCE_DEBUG) {
				return false;
			} else {
				// print name of none existant component
				echo 'Call to non-existant property ' . '$' . strtolower(get_class()) . '->' . $name . '()'  . ' in ' . debug_backtrace()[0]['file'] . ' on line ' . debug_backtrace()[0]['line'];
			}
		}
	}

	/**
	 * Returns false instead of "Notice: Undefined property error" when reading data from inaccessible properties
	 */
	public function __get($var) {
		return false;
	}

}