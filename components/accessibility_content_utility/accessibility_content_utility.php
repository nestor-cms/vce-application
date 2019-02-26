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
		$vce->site->add_script(dirname(__FILE__) . '/js/script.js', 'jquery');
		$vce->site->add_style(dirname(__FILE__) . '/css/style.css', 'accessibility-style');
    
    }
    
    
    
    public static function content_call_add_functions($vce) {
    
		$vce->content->accordion = function ($accordion_title, $accordion_content) {
			
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
        
			$content = <<<EOF
<div class="accordion-container">
	<div role="heading" aria-level="2">
		<button class="accordion-title accordion-closed" role="button" aria-expanded="false" aria-controls="accordion-content-$aria_integer" id="accordion-title-$aria_integer">
			<span>$accordion_title / $aria_integer</span>
		</button>
	</div>
	<div class="accordion-content" id="accordion-content-$aria_integer" role="region" aria-labelledby="accordion-title-$aria_integer">
		$accordion_content
	</div>
</div>
EOF;
			return $content;
			
        };
    
    }
    
    

    /**
     * add utility functions to Content
     *
     * @param [VCE] $vce
     */
    public static function vce_call_add_functions($vce) {
    
		// require accessibility class
		// require_once(dirname(__FILE__) . '/class.accessibility.php');
		// $accessibility = new Accessibility($vce);
		// $vce->content->accessibility = new Accessibility($vce);
		
		// $vce->content->accordion = self::accordion('test','test');

    }
    

	public static function accordion($accordion_title, $accordion_content) {
			
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
        
			$content = <<<EOF
<div class="accordion-container">
	<div role="heading" aria-level="2">
		<button class="accordion-title accordion-closed" role="button" aria-expanded="false" aria-controls="accordion-content-$aria_integer" id="accordion-title-$aria_integer">
			<span>$accordion_title / $aria_integer</span>
		</button>
	</div>
	<div class="accordion-content" id="accordion-content-$aria_integer" role="region" aria-labelledby="accordion-title-$aria_integer">
		$accordion_content
	</div>
</div>
EOF;
			return $content;
			
    }


    /**
     * hide this component from being added to a recipe
     */
    public function recipe_fields($recipe) {
        return false;
    }

}