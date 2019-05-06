$(document).ready(function() {

	// click-bar
	$('.clickbar-title').on('click', function(e) {
		if ($(this).hasClass('disabled') !== true) {
			$(this).toggleClass('clickbar-closed');
			$(this).parent('.clickbar-container').children('.clickbar-content').slideToggle();
		}
	});
	
	
	$('.clickbar-group').on('click', function(e) {
		if ($(this).hasClass('clickbar-closed')) {
			$('.clickbar-group').not($(this)).show();
		} else {
			$('.clickbar-group').not($(this)).hide();
		}
	});


	$('.clickbar-group-close').on('click', function(e) {
		$(this).closest('.clickbar-container').find('.clickbar-group').click();
	});


});