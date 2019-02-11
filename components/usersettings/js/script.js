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

	$("input[type='text'][name='email']").on('keydown', function(e) {
		if ($(this).val() !== $(this).attr('current')) {
			$('#password-required-input').attr('tag','required');
			$('#password-required').slideDown();
		} else {
			$('#password-required-input').val('');
			$('#password-required-input').removeAttr('tag');
			$('#password-required').slideUp();
		}
	});

	$('.show-password-input').change(function() {
		if ($(this).is(':checked')) {

			$('.password-input').attr('type', 'text');
		} else {

			$('.password-input').attr('type', 'password');
		}
	});

});