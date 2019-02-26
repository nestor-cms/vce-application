$(document).ready(function() {

	// click-bar
	$('.accordion-title').on('click', function(e) {
		if ($(this).hasClass('disabled') !== true) {
			$(this).toggleClass('accordion-closed');
			$(this).parent('.accordion-container').children('.accordion-content').slideToggle();
		}
	});
	
	
	$('.accordion-group').on('click', function(e) {
		if ($(this).hasClass('accordion-closed')) {
			$('.accordion-group').not($(this)).show();
		} else {
			$('.accordion-group').not($(this)).hide();
		}
	});


	$('.accordion-group-close').on('click', function(e) {
		$(this).closest('.accordion-container').find('.accordion-group').click();
	});

});