function onformerror(formsubmitted,data) {

	$(formsubmitted).prepend('<div class="form-message form-error">' + data.message + '</div>');

}

function onformsuccess(formsubmitted,data) {

	console.log(data);

	$(formsubmitted).prepend('<div class="form-message form-success">' + data.message + '</div>');
	
	setTimeout( function() {
	window.location.reload(true);
	}, 1000);
	
}

$(document).ready(function() {

	$("input[type='text'][name='email']").on('focus', function(e) {
		$('#password-required').slideDown();
	});
	
	$("input[type='text'][name='email']").on('keydown', function(e) {
		$('#password-required-input').attr('tag','required');
	});

	$('.show-password-input').change(function() {
		if ($(this).is(':checked')) {

			$('.password-input').attr('type', 'text');
		} else {

			$('.password-input').attr('type', 'password');
		}
	});

});