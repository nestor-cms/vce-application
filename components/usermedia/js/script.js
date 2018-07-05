$(document).ready(function() {

	$('#create-title').change(function() {
	 	var name = $(this).val();
	 	var url = name.replace(/[\W\s]+/gi,"-").toLowerCase();
	 	$('#create-url').val($('#create-url').attr('parent_url') + url);
	});

});