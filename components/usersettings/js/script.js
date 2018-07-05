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
	
			$('#password-input').attr('tag','required');
			$('#password-required').slideDown();
	
		} else {
		
			$('#password-input').val('');
			$('#password-input').removeAttr('tag');
			$('#password-required').slideUp();
	
		}
	
	});

});