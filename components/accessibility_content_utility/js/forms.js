$(document).ready(function() {

	$('.input-label-style').on('focus', 'textarea, input[type=text],input[type=email], input[type=password], select', function(e) {		
		$(this).closest('.input-label-style').removeClass('highlight-alert').addClass('highlight')
	});

	$('.input-label-style').on('blur', 'textarea, input[type=text], input[type=email], input[type=password], select', function() {
		$(this).closest('.input-label-style').removeClass('highlight')
	});

});