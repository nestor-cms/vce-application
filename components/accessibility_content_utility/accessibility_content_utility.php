<?php

class AccessibilityContentUtility extends Component {

    /**
     * basic info about the component
     */
    public function component_info() {
        return array(
            'name' => 'Accessibility Content Utility',
            'description' => 'Add utility functions to Content for Accessibility',
            'category' => 'accessibility',
        );
    }

    /**
     * things to do when this component is preloaded
     */
    public function preload_component() {

        $content_hook = array(
        	'page_construct_object' => 'AccessibilityContentUtility::page_construct_object',
            'content_call_add_functions' => 'AccessibilityContentUtility::content_call_add_functions'
        );

        return $content_hook;

    }
    
    
    public static function page_construct_object($requested_component, $vce) {
    		
		// add javascript to page
		$vce->site->add_script(dirname(__FILE__) . '/js/accordion.js', 'jquery');
		$vce->site->add_style(dirname(__FILE__) . '/css/accordion.css', 'accessibility-accordion-style');
		$vce->site->add_script(dirname(__FILE__) . '/js/forms.js', 'jquery');
		$vce->site->add_style(dirname(__FILE__) . '/css/forms.css', 'accessibility-forms-style');
		$vce->site->add_script(dirname(__FILE__) . '/js/tooltips.js', 'jquery');
		$vce->site->add_style(dirname(__FILE__) . '/css/tooltips.css', 'accessibility-tooltips-style');
 
 
       	// legacy
		$vce->site->add_script(dirname(__FILE__) . '/legacy/js/script.js', 'jquery');
		$vce->site->add_style(dirname(__FILE__) . '/legacy/css/style.css','legacy-input-style'); 

    }
    
    
    public static function content_call_add_functions($vce) {

		$vce->content->accordion = function ($accordion_title, $accordion_content, $accordion_expanded = false, $accordion_disabled = false, $accordion_class = null, $tooltip = null) {
			
			/*
			<div class="accordion-container">
			<!--clickbar header has role of heading-->
			<div role="heading" aria-level="2">
			<!-- Clickbar itself has role of button so reader knows it's actionable.  Also, aria-expanded is toggled between "true" and "false"-->
			<!-- aria-controls contains id of element that appears when expanded-->
			<!--change type to button-->
			<button class="accordion-title accordion-closed" role="button" aria-expanded="false" aria-controls="accordion-content-$aria_integer" id="accordion-title-$aria_integer">
			<span>accordion title</span></button>
			</div>
			<!-- aria-labelledby contains id of element that controls expansion/contraction-->
			<div class="accordion-content" id="accordion-content-$aria_integer" role="region" aria-labelledby="accordion-title-$aria_integer">
			accordion content
			</div> <!--click bar content-->
			</div> <!--click bar container-->
			*/
        
        	// create a unique id for id and aria tags
			$aria_integer = mt_rand(0,1000);
			
			$container_classes = $accordion_expanded === true ? 'accordion-container accordion-open' : 'accordion-container accordion-closed';
			
			if (isset($accordion_class)) {
				$container_classes .= ' ' . $accordion_class;
			}

			$title_classes = $accordion_disabled === true ? 'accordion-title disabled' : 'accordion-title active';

			$aria_expanded = $accordion_expanded === true ? 'true' : 'false';
        
			$content = <<<EOF
<div class="$container_classes">
<div class="accordion-heading" role="heading" aria-level="2">
<button class="$title_classes" role="button" aria-expanded="$aria_expanded" aria-controls="accordion-content-$aria_integer" id="accordion-title-$aria_integer">
<span>$accordion_title</span>
</button>
</div>
<div class="accordion-content" id="accordion-content-$aria_integer" role="region" aria-labelledby="accordion-title-$aria_integer">
$accordion_content
</div>
</div>
EOF;
			return $content;
			
        };
        
        
        
        /**
         * Samples for different input types
         * - - - - - - - - - - - - - - - - - - - -
         * // text input
         *	$input = array(
		 *		'type' => 'text',
		 *		'name' => 'text_input_name',
		 *		'required' => 'true',
		 *		'placeholder' => 'enter something',
		 *		'data' => array(
		 *			// additional values
		 *			'autocapitalize' => 'none',
		 *			'tag' => 'required',
		 *		)
		 *	);
         *
         *  // select menu
		 *	$input = array(
		 *		'type' => 'select',
		 *		'name' => 'select_menu_name',
		 *		// html5 required tag
		 *		'required' => 'true',
		 *		'data' => array(
		 *			// vce.js required
		 *			'tag' => 'required'
		 *		),
		 *		'options' => array(
		 *			array(
		 *				// empty select menu item
		 *				'name' => '',
		 *				'value' => ''
		 *			),
		 *			array(
		 *				'name' => 'first_name',
		 *				'value' => 'first_value'
		 *			),
		 *			array(
		 *				'name' => 'second_name',
		 *				'value' => 'second_value',
		 *				// select this item
		 *				'selected' => true
		 *			)
		 *		)
		 *	);
		 *
		 *	// textarea
		 *	$input = array(
		 *		'type' => 'textarea',
		 *		'name' => 'notes',
		 *		//'value' => 'current value for this textarea',
		 *		//'required' => 'true',
		 *		'data' => array(
		 *			'rows' => '20',
		 *			'tag' => 'required',
		 *			'placeholder' => 'enter something'
		 *		)
		 *	);
		 *
		 *	// radio button
		 * 	$input = array(
		 *		'type' => 'radio',
		 *		'name' => 'radio_menu_name',
		 *		//'required' => 'true',
		 *		'data' => array(
		 *			'tag' => 'required'
		 *		),
		 *		'options' => array(
		 *			array(
		 *				'name' => 'name',
		 *				'value' => 'first_value',
		 *				'label' => ' label for radio button '
		 *			),
		 *			array(
		 *				'name' => 'name',
		 *				'value' => 'second_value',
		 *				'label' => ' second label radio button ',
		 *				'selected' => true
		 *			)
		 *		)
		 *	);
		 *
		 * 
		 *	// checkbox
		 *	$input = array(
		 *		'type' => 'checkbox',
		 *		'name' => 'checkbox_name',
		 *		//'required' => 'true',
		 *		'data' => array(
		 *			'tag' => 'required'
		 *		),
		 *		'options' => array(
		 *			array(
		 *				'name' => 'name',
		 *				'value' => 'first_value',
		 *				'label' => ' label for checkbox',
		 *				'selected' => true
		 *			),
		 *			array(
		 *				'name' => 'name',
		 *				'value' => 'second_value',
		 *				'label' => ' second label checkbox',
		 *			)
		 *		)
		 *	);
		 *
         */
		$vce->content->form_input = function ($input) use ($vce) {
		
			// default value to prevent errors
			$type = 'text';
			$for = null;
			
			// validate type
			if (isset($input['type']) && in_array($input['type'],array('text','password','search','checkbox','radio','select','textarea'))) {
				$type = $input['type'];
			}
			
			$name = isset($input['name']) ? $input['name'] : 'name';
			$required = isset($input['required']) ? $input['required'] : null;
			$autocomplete = isset($input['autocomplete']) ? $input['autocomplete'] : null;
			
			// normalize options array
			if (!isset($input['options']) || empty($input['options'])) {
				$input['options'] = array();
				$input['options']['value'] = isset($input['value']) ? $input['value'] : null;
				$input['options']['id'] = isset($input['id']) ? $input['id'] : null;
				$input['options']['class'] = isset($input['class']) ? $input['class'] : null;
				$input['options']['selected'] = isset($input['selected']) ? $input['selected'] : null;
				$input['options']['label'] = isset($input['label']) ? $input['label'] : null;
				$input['options']['placeholder'] = isset($input['placeholder']) ? $input['placeholder'] : null;
				if (isset($input['data']) && is_array($input['data'])) {
					$input['options']['data'] = $input['data'];
				}
			}
		
			// normalize as array of array
			if (!is_array(array_values($input['options'])[0])) {
				$options[] = $input['options'];
				// set back again.
				$input['options'] = $options;
			}
			
			$content = null;
		
			foreach ($input['options'] as $key=>$value) {
			
				if ($type == 'select') {
					// select menu
				
					if (empty($content)) {
						$content .= '<select name="' . $name . '"';
						
						if (isset($input['id'])) {
							$input_id = $input['id'];
							$content .= ' id="' . $input_id . '"';
						} else {
							$input_id = $name;
							$content .= ' id="' . $input_id . '"';
						}
						
						$for = $input_id;
				
						// class
						if (isset($item['class'])) {
							$content .= ' class="' . $item['class'] . '"';
						}
						
						// data
						if (isset($input['data'])) {
							foreach ($input['data'] as $data_key=>$data_value) {
								$content .= ' ' . $data_key . '="' . $data_value . '"';
							}
						}
				
						// required
						if (isset($required)) {
							$content .= ' required';
						}
												
						$content .= '>' . PHP_EOL;
					}
					
					$content .= '<option';
					
					if (isset($value['value'])) {
						$content .= ' value="' . $value['value'] . '"';
					}
					
					// id
					if (isset($value['id'])) {
						$input_id = $value['id'];
						$content .= ' id="' . $input_id . '"';
						if (empty($for)) {
							$for = $input_id;
						}
					} else {
						if (isset($value['label'])) {
							$input_id = $name . '-' . $key;
							$content .= ' id="' . $input_id . '"';
						} else {
							$input_id = $name . '_selections';
							$content .= ' id="' . $input_id . '"';
							$for = $input_id;
						}
					}
				
					// class
					if (isset($value['class'])) {
						$content .= ' class="' . $value['class'] . '"';
					}
					
					if (isset($value['selected'])) {
						$content .= ' selected';
					}
					 
					$content .= '>';
					
					if (isset($value['name'])) {
						$content .= $value['name'];
					}
					 
					$content .= '</option>' . PHP_EOL;
					
					if (is_numeric($key) && count($input['options']) == ($key + 1)) {
						$content .= '</select>';
					}
			
				} elseif ($type == 'textarea') {
					// textarea
				
					$content .= '<textarea name="' . $name . '"';

					// id
					if (isset($value['id'])) {
						$content .= ' id="' . $value['id'] . '"';
						$for = $value['id'];
					} else {
						$content .= ' id="' . $name . '_selections"';
						$for = $name . '_selections';
					}
				
					// class
					if (isset($value['class'])) {
						$content .= ' class="' . $value['class'] . '"';
					}
					
					// data
					if (isset($value['data'])) {
						foreach ($value['data'] as $data_key=>$data_value) {
							$content .= ' ' . $data_key . '="' . $data_value . '"';
						}
					}
					
					// required
					if (isset($required)) {
						$content .= ' required';
					}
					
					$content .= '>';
					
					// value
					if (isset($value['value'])) {
						$content .= $value['value'];
					}
					
					$content .= '</textarea>';
				
				} else {
					// checkbox and radio buttons
				
					if (empty($content) && in_array($type,array('checkbox','radio'))) {
						$content .= '<div class="input-padding"';
						//if (count($input['options']) > 1) {
						if (isset($input['id'])) {
							$content .= ' id="' . $input['id'] . '"';
							$for = $input['id'];
						} else {
							$content .= ' id="' . $name . '_selections"';
							$for = $name . '_selections';
						}
						
						$content .= '>' . PHP_EOL;
					}
				
					$content .= '<input type="' . $type . '"';
				
					if (isset($value['name'])) {
						$content .= ' name="' . $value['name'] . '"';
					} else {
						if (count($input['options']) == 1 || $type == 'radio') {
							$content .= ' name="' . $name . '"';
						} else {
							$content .= ' name="' . $name . '_' . $key . '"';

						}
					}
				
				
					// value
					if (isset($value['value'])) {
						$content .= ' value="' . $value['value'] . '"';
					}

					// id
					if (isset($value['id'])) {
						$input_id = $value['id'];
						$content .= ' id="' . $input_id . '"';
						if (empty($for)) {
							$for = $input_id;
						}
					} else {
						if (isset($value['label'])) {
							$input_id = $name . '-' . $key;
							$content .= ' id="' . $input_id . '"';
						} else {
							$input_id = $name . '_selections';
							$content .= ' id="' . $input_id . '"';
							$for = $input_id;
						}
					}
				
					// class
					if (isset($value['class'])) {
						$content .= ' class="' . $value['class'] . '"';
					}
					
					// data
					if (isset($value['data'])) {
						foreach ($value['data'] as $data_key=>$data_value) {
							$content .= ' ' . $data_key . '="' . $data_value . '"';
						}
					}
				
					// required
					if (isset($required)) {
						$content .= ' required';
					}
				
					// autocomplete
					if (isset($autocomplete)) {
						if ($autocomplete == 'on') {
							$content .= ' autocomplete="on"';	
						} else {
							$content .= ' autocomplete="off"';	
						}
					} else {
						if (in_array($type,array('text','password','search'))) {
							$content .= ' autocomplete="off"';
						}
					}
					
					// value
					if (isset($value['placeholder'])) {
						$content .= ' placeholder="' . $value['placeholder'] . '"';
					}
				
					// selected
					if (isset($value['selected'])) {

						$selected = array(
							'checkbox' => 'checked',
							'radio' => 'checked',
							'select' => ' selected'
						);
					
						if (array_key_exists($type,$selected)) {
							$content .= ' ' . $selected[$type];
						}
				
					}
				
					$content .= '>';
				 
					if (isset($value['label'])) {
				
						$content .= '<label class="omit" for="' . $input_id . '">' . $value['label'] . '</label>';

					}
	
					if (count($input['options']) > 1 && count($input['options']) != ($key +1)) {
					$content .= '<span>/</span>';
				
					}
					
					if (count($input['options']) == ($key + 1) && in_array($type,array('checkbox','radio'))) {
						$content .= PHP_EOL . '</div>';
					}
				
				}
			
			}

			return array('input' => $content,'for' => $for);
        
        };
        
        /**
         * 
         */
		$vce->content->create_input = function ($input, $title = null, $error = null, $tooltip = null) use ($vce) {
		
			$title = !empty($title) ? $title : $input['name'];
			
			$error = !empty($error) ? $error : $title . ' is required';
		
			$content = $vce->content->form_input($input);
			
			$input_content = $content['input'];
			
			$for = $content['for'];
			
			/*
			<!--change label to div with label-style class-->
			<div class="label-style"> 
			<!--move label text to top-->
			<div class="label-text">
			<!-- change div with class label-message to an explicit label-->
			<label class="label-message" for="title">Title</label>
			<!--adding role="alert" lets AT watch this element for changes and announce them-->
			<div class="label-error" role="alert">Enter A Title</div>
			</div>
			<input type="text" name="title" id="title" class="resource-name" tag="required" autocomplete="off">
			</div>
			*/
     
			$content = <<<EOF
<div class="input-label-style"> 
<div class="input-label-text">
<label class="input-label-message" for="$for">$title</label>
<div class="input-label-error" role="alert">$error</div>
</div>
$input_content
</div>
EOF;

            
			return $content;
        
        };
        
        
        $vce->content->tool_tip = function ($tooltip_content, $tooltip_id = null) {
        
        	$css_id = !empty($tooltip_id) ? $tooltip_id :  "tooltip_" . mt_rand(0,1000);
        
			$content = <<<EOF
<div id="$css_id" class="tooltip" role="tooltip"><div class="tooltip-text" aria-describedby="$css_id">$tooltip_content</div></div>
EOF;

			return $content;

        };
        

    }


    /**
     * hide this component from being added to a recipe
     */
    public function recipe_fields($recipe) {
        return false;
    }

}