$(document).ready(function() {

	// tool tips
	$('.tooltip-icon').mouseover(function() {
			$(this).children().show();
		}).mouseout(function() {
			$(this).children().hide();
	});
	
});
