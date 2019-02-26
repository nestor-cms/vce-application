<?php

class Accessibility {
    
    public function __construct($vce) {
    
    	// add vce object
    	// $vce->content->accessibility = $this;

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


}