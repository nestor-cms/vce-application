<?php

/**
* Components (the basic building blocks of VCE).
* This is the parent class which is extended by all components.
*/

class Component {
	
	/**
	 * Basic info about the component.
	 */
	public function component_info() {
		return array(
			'name' => ltrim(preg_replace('/[A-Z]/', ' $0', get_class($this))),
			'description' => '&nbsp;',
			'category' => 'site'
		);
	}

	/**
	 * Method to call when Component has been installed.
	 */
	public function installed() {
	}

	/**
	 * Component has been activated.
	 */
	public function activated() {
	}
	
	/**
	 * Component has been disabled.
	 */
	public function disabled() {
	}
	
	/**
	 * Component has been removed, as in deleted.
	 */
	public function removed() {
	}
	
	/**
	 * This method can be used to access application hooks.
	 *
	 * $content_hook = array(
	 * 	'*vce_hook_name*' => '*component_class_name*::*component_method_name*'
	 * );
	 * return $content_hook;
	 *
	 * You can also control the order in which hook events are fired off by using a priority value. A lower or negative priority value goes first, with positive numbers after.
	 *
	 * $content_hook = array(
	 * 	'*vce_hook_name*' => ['function' => '*component_class_name*::*component_method_name*', 'priority' => -100]
	 * );
	 * return $content_hook;
	 *
	 * @return bool
	 */
	public function preload_component() {
		return false;
	}
	
	/**
	 * check if get_sub_components method should be called.
	 * this occures before components structure is added to the page object
	 * and is checked in both get_components and get_sub_components, which is why both variables are available
	 * @param object $requested_component
	 * @param object $page
	 * @return bool
	 */
	public function find_sub_components($requested_component, $vce, $components, $sub_components) {
		return true;
	}

	/**
	 * Checks to see if this component should be displayed.
	 * This is fired within class.page.php in build_content()
	 * also, if you would like to add something to the $page object that can be used
	 * by sub_components, this would be where to add that sort of thing $vce->something = "like this".
	 * @param object $each_component
	 * @param object $page
	 * @return bool
	 */
	public function check_access($each_component, $vce) {
		return true;
	}


	/**
	 * check if page object should build sub_components for this component.
	 * @param object $each_component
	 * @param object $page
	 * @return bool
	 */
	public function build_sub_components($each_component, $vce) {
		return true;
	}

	/**
	 * Checks that sub_components listed in receipe are allowed to be created.
	 * @param object $each_component
	 * @param object $page
	 * @return bool
	 */
	public function allow_sub_components($each_component, $vce) {
		return true;
	}

	/**
	 * Generates links for this component that has a url. The previous component with a URL was the requested id, so generate links for this component
	 * The Last component was the requested id, and this creates the links for it. By default this is a simple html link.
	 * @param object $each_component
	 * @param object $page
	 * @return adds to content variable
	 */
	public function as_link($each_component, $vce) {
		$title = isset($each_component->title) ? $each_component->title : get_class($this);
		$vce->content->main .= '<div class="items-link"><a href="' . $vce->site->site_url . '/' . $each_component->url . '">' . $title . '</a></div>'  . PHP_EOL;
	}

	/**
	 * Defines the content section of the component
	 * @param object $each_component
	 * @param object $page
	 */
	public function as_content($each_component, $vce) {
	}
	
	/**
	 * Book end of as_content.
	 * @param object $each_component
	 * @param object $page
	 */
	public function as_content_finish($each_component, $vce) {
	}

	/**
	 * Creates components.
	 * Called from class.page.php from build_content()
	 * this function then calls add_component()
	 * @param object $each_component
	 * @param object $page
	 * @param bool $auto_create
	 * @return calls other methods
	 */
	public static function recipe_components($each_component, $vce, $auto_create_reverse = null) {
	
		// check that recipe_components exists
		if (isset($each_component->sub_recipe)) {

			// sequence generator: get sequence for next item.
			$next_sequence = isset($each_component->components) ? (count($each_component->components) + 1) : '1';

			foreach ($each_component->sub_recipe as $key=>$each_recipe_component) {
			
				$auto_create = null;
				
				$this_component = Page::instantiate_component($each_recipe_component, $vce);

				$component_type = $this_component->type;
				
				$this_component->recipe_manifestation((object) $each_recipe_component, $vce);
				
				$recipe_manifestation = $component_type;

				// auto_create == reverse
				if (isset($each_recipe_component['auto_create']) && $each_recipe_component['auto_create'] == "reverse") {
					// check if the auto_create == backwards has not been created and no url has been set
					if (isset($each_component->components[0]) && !isset($each_component->components[0]->url) && $each_component->components[0]->type == $each_recipe_component['type']) {	
						// if there is more than one recipe item at this level, then continue
						if (count($each_component->sub_recipe) > 1) {
							continue;
						}
					}
					// add sub_component of auto_create reverse to sub_recipe
					$each_component->sub_recipe = isset($each_recipe_component['components']) ? $each_recipe_component['components'] : null;
					// set auto_create value to each_recipe_component
					$auto_create[] = $each_recipe_component;
					// check to see if there is an auto_create forward after this component
					if (isset($each_recipe_component['components'][0]['components'][0])) {
						// add this value
						$auto_create[] = $each_recipe_component['components'][0]['components'][0];
					}
					// clean up
					unset($auto_create[0]['components'][0]['components']);
					// code that was used to clean-up but that is now being commented out: unset($auto_create[0]['components']);
					// recursive call back to this method with updated sub_components
					self::recipe_components($each_component, $vce, $auto_create);
					// move to next foreach items
					if (count($each_component->sub_recipe) > 1) {
						continue;
					}
				}

				// the component is a parent, so add sibling_components
				if (isset($each_component->components)) {
					$each_recipe_component['sibling_components'] = $each_component->components;
				}

				// auto_create == forward
				// check that $auto_create_reverse doesn't exist
				if (!isset($auto_create_reverse)) {
					if (isset($each_recipe_component['components'])) {
						foreach ($each_recipe_component['components'] as $each_sub_recipe_component) {				
							if (isset($each_sub_recipe_component['auto_create']) && $each_sub_recipe_component['auto_create'] == "forward") {	
								$auto_create = $each_recipe_component['components'];
								// exit this foreach loop
								break;
							}
						}
					}
				} else {
					// 	this is an reverse auto_create	
					$auto_create = $auto_create_reverse;
				}

				// recipe_key is a meta_key that is used when the same component occures multipe time at a recipe level
				// otherwise there is no way of knowing which a compoment belongs to. It's a complicated imperfect world.
				$recipe_key = null;
				if (count($each_component->sub_recipe) > 1) {
				
					// check if array is set
					if (!isset($sub_recipe_components)) {
						$sub_recipe_components = array();
						// create an array of sub_recipe items to check if type occures multiple times
						foreach ($each_component->sub_recipe as $sub_recipe_key=>$sub_recipe_value) {
							// create associate array
							if (isset($sub_recipe_components[$sub_recipe_value['type']])) {
								$sub_recipe_components[$sub_recipe_value['type']]++;
							} else {
								$sub_recipe_components[$sub_recipe_value['type']] = 1;
							}
						}
					}
				
					// if more than one occurance of this component type in recipe at this level, then add recipe_key to help with build
					if ($sub_recipe_components[$each_recipe_component['type']] > 1) {
						// add recipe_helper
						$each_recipe_component['recipe_key'] = $key;
						$recipe_key = $key;
					}					

				}
			
				// $each_component is the current component. $each_recipe_component is the recipe for the next possible component

				// add parent_url
				$each_recipe_component['parent_url'] = isset($each_component->url) ? $each_component->url : null;
			
				// add parent
				$each_recipe_component['parent_id'] = $each_component->component_id;
				
				// parent type
				$each_recipe_component['parent_type'] = $each_component->type;
			
				// sequence generator: create number range for each type and add next_sequence.
				$each_recipe_component['sequence'] = ($key * 100) + $next_sequence;
				
				// add template which is supplied by the 
				$each_recipe_component['template'] = isset($each_recipe_component['template']) ? $each_recipe_component['template'] : null;
				
				// create_component_before hook
				if (isset($vce->site->hooks['page_requested_components'])) {
					foreach($vce->site->hooks['page_requested_components'] as $hook) {
						call_user_func($hook, $each_recipe_component);
					}
				}
				
				// check if user role can create this component
				if ($vce->page->can_add((object) $each_recipe_component)) {
				
					// the instructions to pass through the form with specifics
					// 'parent_url' => $each_recipe_component['parent_url']
					// 'current_url' => $each_recipe_component['current_url']
					$dossier = array(
					'type' => $each_recipe_component['type'],
					'procedure' => 'create',
					'recipe_key' => $recipe_key,
					'parent_id' => $each_recipe_component['parent_id'],
					'sequence' => $each_recipe_component['sequence'],
					'template' => $each_recipe_component['template'],
					'auto_create' => $auto_create
					);

					// add dossier
					$each_recipe_component['dossier'] = $dossier;
					
					// access add_component for current component
					$this_component->add_component((object) $each_recipe_component, $vce);
				}

				// call to recipe_manifestation_finish method of component that is in the recipe but has not been created
				// check if this is an reverse auto_create
				if (isset($auto_create_reverse)) {
					// when it has the back->forward, it's a double, so prevent it
					$recipe_manifestation_finish_type = 'recipe_manifestation_finish_' . $auto_create[0]['type'];
					if (!isset($vce->$recipe_manifestation_finish_type)) {
						// set to prevent the recipe_manifestation_finish method from being fired twice
						$vce->$recipe_manifestation_finish_type = true;
						
						$previous_component = Page::instantiate_component($auto_create[0], $vce);

						$previous_component->recipe_manifestation_finish((object) $each_recipe_component, $vce);
						
					}
				} elseif (isset($recipe_manifestation) && $recipe_manifestation == $component_type) {
					
					// create the specific property to look for
					$recipe_manifestation_finish_type = 'recipe_manifestation_finish_' . $this_component->type;

					// if it doesn't exist then do call to the recipe_manifestation_finish method
					if (!isset($vce->$recipe_manifestation_finish_type)) {
	
						$this_component->recipe_manifestation_finish((object) $each_recipe_component, $vce);
					
					}
					
				}
			
			}
		}	
	}
	
	/**
	 * allows for a user to create this component.
	 * @param object $each_component
	 * @param object $page
	 */
	public function add_component($each_recipe_component, $vce) {
	}
	
	/**
	 * Book end of add_component
	 * @param object $each_component
	 * @param object $page
	 */
	public function add_component_finish($each_component, $vce) {
	}

	/**
	 * Allows for content to be displayed when component is contained within the recipe, regardless if a component was created.
	 * This is a ghostly apparation of a sub recipe item.
	 */
	public function recipe_manifestation($each_recipe_component, $vce) {
	}
	
	/**
	 * Closes the content to be displayed when component is contained within the recipe, regardless if a component was created.
	 * This is a ghostly apparation bookend for recipe_manifestation and occures after sub_component item
	 */
	public function recipe_manifestation_finish($each_recipe_component, $vce) {
	}

	/**
	 * method called from class.page.php which adds dossiers for edit and delete,
	 * then passes updated objects to edit_component
	 * this has been added for consistancy, so that it matches how recipe_component behaves when it passes the dossier.
	 * @param object $each_component
	 * @param object $page
	 */
	public function revise_component($each_component, $vce, $this_component) {
	
		// dossier used to edit component
		$dossier_to_edit = array(
		'type' => $each_component->type,
		'procedure' => 'update',
		'component_id' => $each_component->component_id,
		'created_at' => $each_component->created_at
		);
		
		// add dossier to component object
		$each_component->dossier_to_edit = $dossier_to_edit;
		
		// dossier used to delete component
		$dossier_to_delete = array(
		'type' => $each_component->type,
		'procedure' => 'delete',
		'component_id' => $each_component->component_id,
		'created_at' => $each_component->created_at
		);
	
		// add dossier to component object
		$each_component->dossier_to_delete = $dossier_to_delete;
		
		// call to edit_components with updated component object
		$this_component->edit_component($each_component, $vce);
	}

	/**
	 * called from revise_comonent.
	 * @param object $each_component
	 * @param object $page
	 */
	public function edit_component($each_component, $vce) {
	}


	/**
	 * Get configuration fields for component and add to $vce object
	 *
	 *
	 */
    public function get_component_configuration() {
    }

	
	/**
	 * Configuration fields for a component used in ManageComponents
	 * @param object $configuration
	 * @return false (as a default)
	 */
	public function component_configuration() {
		return false;
	}

	/**
	 * Adds component fields used in ManageRecipes.
	 * @param object $recipe
	 * @return false (will prevent a component from being available to add to a recipe)
	 */
	public function recipe_fields($recipe) {
	
		$title = isset($recipe['title']) ? $recipe['title'] : self::component_info()['name'];
		
$elements = <<<EOF
<label>
<input type="text" name="title" value="$title" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Title</div>
<div class="label-error">Enter a Title</div>
</div>
</label>
EOF;

		return $elements;
		
	}

	/**
	 * Deals with asynchronous form input 
	 * This is called from input portal forward onto class and function of component
	 * @param array $input
	 * @return calls component's procedure or echos an error message
	 */
	public function form_input($input) {

		// save these two, so we can unset to clean up $input before sending it onward
		$type = trim($input['type']);
		$procedure = trim($input['procedure']);
		
		// unset component and procedure
		unset($input['procedure']);
		
		// check that protected function exists
		if (method_exists($type, $procedure)) {
			// call to class and function
			$type::$procedure($input);	
			return;
		}
		
		echo json_encode(array('response' => 'error','message' => 'Unknown Procedure'));
		return;
	}

	/**
	 * Creates component
	 * @param array $input
	 * @return calls component's procedure or echos an error message
	 */
	protected function create($input) {
	
		// call to create_component, which returns the newly created component_id
		$component_id = self::create_component($input);
	
		if ($component_id) {
		
			global $site;
			$site->add_attributes('message',self::component_info()['name'] . ' Created');
	
			echo json_encode(array('response' => 'success','procedure' => 'create','action' => 'reload','message' => 'Created','component_id' => $component_id));
			return;
		
		}
		
		echo json_encode(array('response' => 'error','procedure' => 'update','message' => "Error"));
		return;

	}
	
	/**
	 * Creates component from $input and also auto_create anything based on the recipe.
	 * This function can be updated later to allow for deeper level auto_create.
	 * @param array $input
	 * @return calls component's procedure or echos an error message
	 */
	protected static function create_component($input) {
	
		global $db;
		global $site;
		global $user;
		
		// add created by and created at time_stamp
		$input['created_by'] = $user->user_id;
		$input['created_at'] = time();
		
		// make sure we have default values
		$input['title'] = isset($input['title']) ? $input['title'] : preg_replace('/[A-Z]/', ' $0', $input['type']);
		$input['parent_id'] = isset($input['parent_id']) ? $input['parent_id'] : 0;
		
		// set $auto_create
		$auto_create = isset($input['auto_create']) ? $input['auto_create'] : null;
		unset($input['auto_create']);
		
		// anonymous function to create components
		$create_component = function($input) use (&$create_component, $db, $site, $user) {

		 	// local version of $input, which should not be confused with the $input fed to the create_component method
		
			// create_component_before hook
			if (isset($site->hooks['create_component_before'])) {
				foreach($site->hooks['create_component_before'] as $hook) {
					$input = call_user_func($hook, $input);
				}
			}
			
			// clean up url
			if (isset($input['url'])) {
				$input['url'] = $site->url_checker($input['url']);
			}
			
			// create component data
			$parent_id = isset($input['parent_id']) ? $input['parent_id'] : 0;
			$sequence = isset($input['sequence']) ? $input['sequence'] : 1;
			$url = isset($input['url']) ? stripslashes($input['url']) : '';
			// $current_url = isset($input['current_url']) ? $input['current_url'] : '';
		
			unset($input['parent_id'], $input['sequence'], $input['url'], $input['current_url']);
	
			$data = array(
			'parent_id' => $parent_id, 
			'sequence' => $sequence,
			'url' => $url
			);
		
			// insert into components table, which returns new component id
			$component_id = $db->insert('components', $data);

			// now add meta data
			$records = array();

			// loop through other meta data
			foreach ($input as $key=>$value) {
		
				// title
				$records[] = array(
				'component_id' => $component_id,
				'meta_key' => $key, 
				'meta_value' => $value,
				'minutia' => null
				);
		
			}

			$db->insert('components_meta', $records);
			
			return $component_id;
			
		};
	
		// anonymous function to create auto_create components
		$auto_create_components = function($auto_create, $input, $direction) use (&$auto_create_components, $site, $create_component) {

			if (!empty($auto_create)) {
				// set counter
				$counter = 0;
				foreach ($auto_create as $each_key=>$each_component) {
					
					if (!isset($each_component['auto_create'])) {
						continue;
					}
				
					if (isset($each_component['components'])) {
						$sub_auto_create = $each_component['components'];
					} else {
						$sub_auto_create = null;
					}
		
					if ($direction == "reverse" && $each_component['auto_create'] == "reverse") {
					
						// check that the component type that is being created is in the recipe as a sub-component of this reverse auto_create component
						if (!isset($each_component['components'][0]['type']) || ($each_component['components'][0]['type'] != $input['type'] && $input['type'] != 'Alias')) {
							// if not, then return the parent_id that was supplied within the $input array
							return $input['parent_id'];	
						}
					
						// add to counter
						$counter++;
						
						// unset sub components and auto_create 
						unset($auto_create[$each_key]['components'],$auto_create[$each_key]['auto_create']);
						
						$new_component = array();
						
						// update input from recipe
						foreach ($auto_create[$each_key] as $meta_key=>$meta_value) {
							$new_component[$meta_key] = $meta_value;
						}
						
						// create separate sequence space in case
						$new_component['sequence'] = $counter;
						
						// add required fields
						$new_component['parent_id'] = $input['parent_id'];
						$new_component['created_by'] = $input['created_by'];
						$new_component['created_at'] = $input['created_at'];
						
						// call and then return the component_id
						$new_component_id = $create_component($new_component);
									
						// check that component has not been disabled
						$activated_components = json_decode($site->activated_components, true);

						// check that this component has been activated
						if (isset($activated_components[$new_component['type']])) {
							require_once(BASEPATH . $activated_components[$new_component['type']]);
						} else {
							// default to parent class
							$new_component['type'] = 'Component';
						}
		
						// add component_id to new_component
						$new_component['component_id'] = $new_component_id;
		
						//  add auto_create to new_component
						$new_component['auto_create'] = $each_component['auto_create'];
		
						// call to auto_created
						$new_component['type']::auto_created($new_component);
		
						return $new_component_id;
					
					}
					
					if ($direction == "forward" && $each_component['auto_create'] == "forward") {
						
						// add to counter, for use with sequence
						$counter++;
						
						// clear array and start again
						$new_component = array();
						
						// keep track of how many instances of the same component occur at this level, so that a recipe_key can be added if needed
						if (!isset($recipe_type)) {
							// loop through the first time to find multiples
							foreach ($auto_create as $recipe_component) {
								$recipe_type[$recipe_component['type']] = isset($recipe_type[$recipe_component['type']]) ? ($recipe_type[$recipe_component['type']] + 1) : 0;
							}
						}

						// if multipes have been found, add $recipe_key
						if ($recipe_type[$each_component['type']] > 0) {
							if (!isset($recipe_key[$each_component['type']])) {
								$recipe_key[$each_component['type']] = 0;
							} else {
								$recipe_key[$each_component['type']] = $recipe_key[$each_component['type']] + 1;
							}
							// add meta_key to each_sub_components
							$new_component['recipe_key'] = $recipe_key[$each_component['type']];
						}
						
				
						// create separate sequence space in case
						$new_component['sequence'] = $counter;

						// unset sub components and auto_create 
						unset($auto_create[$each_key]['components'],$auto_create[$each_key]['auto_create']);
						
						// update input from recipe
						foreach ($auto_create[$each_key] as $meta_key=>$meta_value) {
							// prevent overwriting
							if (!isset($new_component[$meta_key])) {
								$new_component[$meta_key] = $meta_value;
							}
						}
						
						// add required fields
						$new_component['parent_id'] = $input['parent_id'];
						$new_component['created_by'] = $input['created_by'];
						$new_component['created_at'] = $input['created_at'];
						
						// create a sub url
						if (isset($each_component['url']) && $each_component['url'] != "") {
							if (isset($input['url'])) {
								$url = $input['url'] . '/' . $each_component['url'];
							} else {
								$url = $each_component['url'];													
							}
							// save new extended url
							$new_component['url'] = $url;
						}

						// call and then return the component_id
						$component_id = $create_component($new_component);
						
						// check that component has not been disabled
						$activated_components = json_decode($site->activated_components, true);

						// check that this component has been activated
						if (isset($activated_components[$new_component['type']])) {
							require_once(BASEPATH . $activated_components[$new_component['type']]);
						} else {
							// default to parent class
							$new_component['type'] = 'Component';
						}
		
						// add component_id to new_component
						$new_component['component_id'] = $component_id;
		
						//  add auto_create to new_component
						$new_component['auto_create'] = $each_component['auto_create'];
		
						// call to auto_created
						$new_component['type']::auto_created($new_component);
						
						// recursive call
						if (isset($sub_auto_create)) {
							// create a copy of input to add parent_id and send recersively 
							$new_input = $input;
							// update parent_id with the newly created component_id
							$new_input['parent_id'] = $component_id;
							// make call
							$auto_create_components($sub_auto_create, $new_input, $direction);
						}
					
					}
				}
	
				// if there is an auto_create == reverse and auto_create == forward at the same level as the component.
				if (isset($auto_create[0]['auto_create']) && $auto_create[0]['auto_create'] == "reverse") {

					// update parent_id with the reverse_parent_id value from before
					$input['parent_id'] = $input['reverse_parent_id'];

					// recursive call
					$auto_create_components($sub_auto_create, $input, $direction);

				}
			}
			
			return $input['parent_id'];
		
		};

		// check for auto_create == reverse
		$input['parent_id'] = $auto_create_components($auto_create, $input, "reverse");
		
		// save the parent_id of the reverse auto_create component
		$reverse_parent_id = $input['parent_id'];
		
		// create component
		$input['parent_id'] = $create_component($input);
		$component_id = $input['parent_id'];
		
		// add this value back
		$input['reverse_parent_id'] = $reverse_parent_id;
		
		// check for auto_create == forward
		$auto_create_components($auto_create, $input, "forward");
		
		// return the current_id for the newly created component
		return $component_id;

	}
	

	/**
	 * This function is called after a component has been auto_created
	 * @param array $new_component
	 */
	public static function auto_created($new_component) {
	}
	

	/**
	 * Updates data
	 * @param array $input
	 * @return calls component's procedure or echos an error message
	 */
	protected function update($input) {
	
		if (self::update_component($input)) {
		
			global $site;
			$site->add_attributes('message',self::component_info()['name'] . " Updated");
		
			echo json_encode(array('response' => 'success','procedure' => 'update','action' => 'reload','message' => "Updated"));
			return;
		}
		
		echo json_encode(array('response' => 'error','procedure' => 'update','message' => "Permission Error"));
		return;
	}

	/**
	 * Updates component
	 * @param array $input
	 * @return calls component's procedure or echos an error message
	 */
	protected static function update_component($input) {

		global $db;
		global $user;
		
		$component_id = $input['component_id'];
		unset($input['users'], $input['component_id']);
		
		$query = "SELECT * FROM " . TABLE_PREFIX . "components WHERE component_id='" . $component_id . "'";
		$components = $db->get_data_object($query);
	
		$query = "SELECT * FROM " . TABLE_PREFIX . "components_meta WHERE component_id='" . $component_id . "'";
		$components_meta = $db->get_data_object($query);
		
		// for components_meta key => values
		$meta_data = array();	
	
		// key components_meta
		foreach ($components_meta as $each_meta) {
			$key = $each_meta->meta_key;
			$meta_data[$key] = $each_meta->meta_value;
		}		

		// check that created_at is the same
		if ($meta_data['created_at'] == $input['created_at']) {

			$sequence = isset($input['sequence']) ? $input['sequence'] : $components[0]->sequence;
			$url = isset($input['url']) ? stripslashes($input['url']) : $components[0]->url;
			
			unset($input['sequence'], $input['url']);
			
			$update = array('sequence' => $sequence, 'url' => $url);
			$update_where = array('component_id' => $component_id);
			$db->update('components', $update, $update_where);
			
			// in case of an alias return true
			if (isset($meta_data['alias_id'])) {
				return true;
			}
			
			foreach ($input as $key=>$value) {
			
				// check if meta_data already exists, then update
				if (isset($meta_data[$key])) {
			
					$update = array('meta_value' => $value);
					$update_where = array('component_id' => $component_id, 'meta_key' => $key);
					$db->update('components_meta', $update, $update_where);
					
				} else {
				// meta_data doesn't exists, so create it
				
					// insert is expecting an associative array
					$records[] = array(
					'component_id' => $component_id,
					'meta_key' => $key, 
					'meta_value' => $value,
					'minutia' => null
					);

				}

			}
			
			// if $records exists, insert meta_data
			if (isset($records)) {
				$db->insert('components_meta', $records);
			}
			
			return true;
		
		}
		
		return false;

	}

	/**
	 * Deletes data
	 * @param array $input
	 * @return calls component's procedure or echos an error message
	 */
	protected function delete($input) {

		$parent_url = self::delete_component($input);

		if (isset($parent_url)) {
		
			// if a url has been passed, then reload that page
			if (isset($input['url'])) {
				$parent_url	= $input['url'];
			}
		
			// add a message that item has been deleted
			global $site;
			$site->add_attributes('message',self::component_info()['name'] . " Deleted");

			echo json_encode(array('response' => 'success','procedure' => 'delete','action' => 'reload','url' => $parent_url, 'message' => "Deleted"));
			return;
		}

		echo json_encode(array('response' => 'error','procedure' => 'update','message' => "Error"));
		return;
	
	}
	
	/**
	 * Deletes component.
	 * Logic works this way: a user can delete a component they have created and then all sub components regardless who created them.
	 * @param array $input
	 * @return calls component's procedure or echos an error message
	 */
	protected static function delete_component($input) {
	
		global $db;
		global $site;
		global $user;
		
		// recursive search for parent_url
		$find_parent_url = function($component_id) use (&$find_parent_url, $db) {
				
			$query = "SELECT parent_id FROM " . TABLE_PREFIX . "components WHERE component_id='" . $component_id . "' AND parent_id != '0'";
			$this_parent = $db->get_data_object($query);
			
			if (!empty($this_parent)) {
			
				$query = "SELECT url FROM " . TABLE_PREFIX . "components WHERE component_id='" . $this_parent[0]->parent_id . "'";
				$this_url = $db->get_data_object($query);

				if (isset($this_url[0]->url)) {
					return $this_url[0]->url;
				}
			
				return $find_parent_url($this_parent->parent_id);
			
			}
			
			return false;

		};
		
		if (!isset($input['component_id']) || $input['component_id'] == "0") {
			echo json_encode(array('response' => 'error','procedure' => 'update','message' => "No component_id"));
			return;
		}
		
		if (!isset($input['parent_url'])) {
			$parent_url = $site->site_url . '/' . $find_parent_url($input['component_id']);
		} else {
			$parent_url = $site->site_url . '/' . $input['parent_url'];
		}
	
		$query = "SELECT meta_value FROM " . TABLE_PREFIX . "components_meta WHERE component_id='" . $input['component_id'] . "' AND meta_key='created_at'";
		$components_meta = $db->get_data_object($query);
		
		// check that the created_at timestamp for the component matches the input value, which is done as an addtional security check
		if ($components_meta[0]->meta_value == $input['created_at']) {

			// call to recursive function to delete components and components_meta data
			self::extirpate_component($input['component_id']);
			
			return $parent_url;
			
		}
		
		return false;

	}
	
	/**
	 * Searches for sub components and deletes them.
	 * This is a recursive function.
	 * @param int $component_id
	 */
	protected static function extirpate_component($component_id) {
	
		global $db;
		global $site;
	
		// find all sub components for a given component
		$query = "SELECT * FROM " . TABLE_PREFIX . "components WHERE parent_id='" . $component_id . "'";
		$components = $db->get_data_object($query);

		// delete_extirpate_component
		if (isset($site->hooks['delete_extirpate_component'])) {
			foreach($site->hooks['delete_extirpate_component'] as $hook) {
				$components = call_user_func($hook, $component_id, $components);
			}
		}
	
		// check to see if a path to media exists
		$query = "SELECT * FROM " . TABLE_PREFIX . "components_meta WHERE component_id='" . $component_id . "' AND meta_key ='path'";
		$file_path = $db->get_data_object($query);
	
		if (count($file_path)) {
	
			// find the user id that created it
			$query = "SELECT * FROM " . TABLE_PREFIX . "components_meta WHERE component_id='" . $component_id . "' AND meta_key ='created_by'";
			$created_by = $db->get_data_object($query);
			
			$basepath = defined('INSTANCE_BASEPATH') ? INSTANCE_BASEPATH . PATH_TO_UPLOADS : BASEPATH . PATH_TO_UPLOADS;
		
			// path of file
			$unlink_path = $basepath .  DIRECTORY_SEPARATOR  . $created_by[0]->meta_value . DIRECTORY_SEPARATOR  . $file_path[0]->meta_value;
			
			// make sure file exists before deleteing/unlinking it
			if (file_exists($unlink_path)) {
				unlink($unlink_path);
			}
	
		}
		
		$query = "SELECT * FROM " . TABLE_PREFIX . "datalists WHERE component_id='" . $component_id . "'";
		$datalists = $db->get_data_object($query);			
			
		foreach ($datalists as $each_datalist) {
			
			$where = array('component_id' => $component_id);
			$db->delete('datalists', $where);
				
			$where = array('datalist_id' => $each_datalist->datalist_id);
			$db->delete('datalists_meta', $where);
			
			$query = "SELECT * FROM  " . TABLE_PREFIX . "datalists_items WHERE datalist_id='" . $each_datalist->datalist_id . "'";
			$items = $db->get_data_object($query);
				
			$where = array('datalist_id' => $each_datalist->datalist_id);
			$db->delete('datalists_items', $where);
				
			foreach ($items as $each_item) {

				$where = array('item_id' => $each_item->item_id);
				$db->delete('datalists_items_meta', $where);
				
			}
			
		}
	
		// delete component
		$where = array('component_id' => $component_id);
		$db->delete('components', $where);
		
		// delete component meta data
		$where = array('component_id' => $component_id);
		$db->delete('components_meta', $where);
	
		// go through sub components
		foreach ($components as $each_component) {
			
			//recursively call this function to delete sub components
			self::extirpate_component($each_component->component_id);
	
		}
	
	}
	
	/**
	 * Checks that url has not already been assigned to another component.
	 * @param array $input
	 */
	protected function checkurl($input) {
	
		global $site;
		$checked = $site->url_checker($input['url']);
		
		echo json_encode(array('response' => 'success','procedure' => 'checkurl','url' => $checked));
		return;
	
	}

	/**
	 * Returns false instead of "Notice: Undefined property error" when reading data from inaccessible properties
	 */
	public function __get($var) {
		return false;
	}
	
}