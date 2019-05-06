$(document).ready(function() {

	$('input[name=title]').not('.prevent-check-url').change(function() {
		if ($('input[name=url].check-url').length) {
			var url = $(this).val().replace(/\//g,'');
			$('input[name=url]').val($('input[name=url]').attr('parent_url') + url);
			checkurl($('input[name=url]'),$(this).closest('.asynchronous-form'));
		}
	});


	$('input[name=url].check-url').change(function() {
		if ($('input[name=url]').length) {
			checkurl($('input[name=url]'),$(this).closest('.asynchronous-form'));
		}
	});


	checkurl = function(url,thisform) {
		var postdata = [];
		postdata.push(
			{name: 'dossier', value: url.attr('dossier')},
			{name: 'url', value: url.val()}
		);
		if (thisform.length > 0) {
			$.post(thisform.attr('action'), postdata, function(data) {
				$('input[name=url]').val(data.url);
			}, "json");
		}
	}

});