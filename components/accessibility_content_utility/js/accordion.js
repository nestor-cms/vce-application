$(document).ready(function() {

	// click-bar
	$('.accordion-title').on('click', function(e) {
		if ($(this).hasClass('disabled') !== true) {
			$(this).attr("aria-expanded",($(this).attr("aria-expanded") != "true"));
			if ($(this).closest('.accordion-container').hasClass('accordion-open')) {	
				$(this).closest('.accordion-container').find('.accordion-content').first().slideUp('slow', function() {
					$(this).closest('.accordion-container').removeClass('accordion-open').addClass('accordion-closed');
				});
			} else {
				$(this).closest('.accordion-container').find('.accordion-content').first().slideDown('slow');
				$(this).closest('.accordion-container').addClass('accordion-open').removeClass('accordion-closed');
			}
		}
	});

});