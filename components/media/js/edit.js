$(document).ready(function() {

	$(document).on('click','.media-edit-open', function(e) {
		var current = $(this);
		var mediaForm = $(this).siblings('.media-edit-form').first();
		current.toggleClass('media-edit-current');
		mediaForm.toggleClass('display-form');
		$('.media-edit-open').not(current).removeClass('media-edit-current');
		$('.media-edit-form').not(mediaForm).removeClass('display-form');
	});

	$(document).on('click','.media-edit-cancel', function(e) {
		$(this).closest('.media-item-container').find('.media-edit-open').toggleClass('media-edit-current');
		$(this).closest('.media-edit-form').removeClass('display-form');
	});
	
	
});