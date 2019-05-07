$(document).ready(function() {

	// click-bar
	$('.clickbar-title').on('click', function(e) {
		if ($(this).hasClass('disabled') !== true) {
			$(this).toggleClass('clickbar-closed');
			$(this).parent('.clickbar-container').children('.clickbar-content').slideToggle();
		}
	});
	
	
	$('.clickbar-group').on('click', function(e) {
		if ($(this).hasClass('clickbar-closed')) {
			$('.clickbar-group').not($(this)).show();
		} else {
			$('.clickbar-group').not($(this)).hide();
		}
	});


	$('.clickbar-group-close').on('click', function(e) {
		$(this).closest('.clickbar-container').find('.clickbar-group').click();
	});


	// tool tips
	$('.tooltip-icon').mouseover(function() {
			$(this).children().show();
		}).mouseout(function() {
			$(this).children().hide();
	});


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