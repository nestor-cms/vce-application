$(document).ready(function() {

	$('.tooltip').on('mouseover', function(e) {
			$(this).children().show();
		}).mouseout(function() {
			$(this).children().hide();
	});

});