$(document).ready(function() {

	$('.users_select').select2();
	
	$('.users_select').on('change', function() {
		var users = $(this).select2("data");
		var ids = new Array();
		$.each(users, function (index, value) {
			$.each(value, function (index, value) {
				if (index == "id") {
					ids.push(value);
				}
			});	
		});
		$(this).parent().find('.user_ids').val(ids.join('|'));
	});


});