$(document).ready(function() {

	$(document).on('focus', 'textarea, input[type=text],input[type=email], input[type=password], select', function() {
		$('.form-error').fadeOut(10000, function(){ 
    		$(this).remove();
		});
		$(this).closest('label').removeClass('highlight-alert').addClass('highlight');
		$(this).parents().eq(1).children(':submit').addClass('active-button');
	});

	$(document).on('blur', 'textarea, input[type=text], input[type=email], input[type=password], select', function() {
		$(this).closest('label').removeClass('highlight');
		if ($(this).val() === "") {
			$(this).parents().eq(1).children(':submit').removeClass('active-button');
		}
	});
	
	$(document).on('click', 'input[type=checkbox]', function() {
		$(this).closest('label').removeClass('highlight-alert').addClass('highlight');
	});

	$(document).on('submit', '.asynchronous-form', function(e) {
		e.preventDefault();

		var formsubmitted = $(this);
	
		var submittable = true;
	
		if ($(this).hasClass('delete-form')) {
			if (confirm("Are you sure you want to delete?")) {
				submittable = true;
			} else {
				submittable = false;
				return false;
			}
		}

		var inputtypes = [];
	
		var hiddentest = $(this).find('input[type=hidden]');
		hiddentest.each(function(index) {
			var eachinput = {};
			eachinput.name = $(this).attr('name');
			eachinput.type = $(this).attr('type');
			if ($(this).attr('schema')) {
				eachinput.type = $(this).attr('schema');
			}
			inputtypes.push(eachinput);
			submittable = true;
		});
	
		var textareatest = $(this).find('textarea');
		textareatest.each(function(index) {
			var eachinput = {};
			eachinput.name = $(this).attr('name');
			eachinput.type = 'textarea';
			if ($(this).attr('schema')) {
				eachinput.type = $(this).attr('schema');
			}
			inputtypes.push(eachinput);
			if ($(this).val() == "" && $(this).attr('tag') == 'required') {
				$(this).closest('label').addClass('highlight-alert');
				submittable = false;
			}
		});
						
		var typetest = $(this).find('input[type=text],input[type=email],input[type=password]');
		typetest.each(function(index) {
			var eachinput = {};
			eachinput.name = $(this).attr('name');
			eachinput.type = $(this).attr('type');
			inputtypes.push(eachinput);
			if ($(this).val() == "" && $(this).attr('tag') == 'required') {
				$(this).closest('label').addClass('highlight-alert');
				submittable = false;
			}
		});
		
		var selecttest = $(this).find('select');
		selecttest.each(function(index) {
			var eachinput = {};
			eachinput.name = $(this).attr('name');
			eachinput.type = 'select';
			inputtypes.push(eachinput);
			if ($(this).find('option:selected').val() == "" && $(this).attr('tag') == 'required') {
				$(this).closest('label').addClass('highlight-alert');
				submittable = false;
			}
		});
	
		var checkboxtest = $(this).find('input[type=checkbox]');
		var box = {};
		var required = {};
		checkboxtest.each(function(index) {
			var boxname = $(this).attr('name');			
			var boxcheck = $(this).prop('checked');
			if ($(this).attr('tag') == 'required') {
				required[boxname] = true;
			}
			if (typeof box[boxname] !== 'undefined') {
				if (box[boxname] === false) {
					box[boxname] = boxcheck;
				}
			} else {
				var eachinput = {};
				eachinput.name = boxname;
				eachinput.type = $(this).attr('type');
				inputtypes.push(eachinput);
				box[boxname] = boxcheck;	
			}
			if (box[boxname] === false && required[boxname] === true) {
				var verify = $(this).closest('label').parent('label').addClass('highlight-alert');
				if (typeof verify !== 'undefined') {
					$(this).closest('label').addClass('highlight-alert');
				}
				submittable = false;
			}
		});
	
		if (submittable) {
			postdata = $(this).serializeArray();
			console.log(formsubmitted.attr('id'));
			postdata.push({name: 'inputtypes', value: JSON.stringify(inputtypes)});
			$.post(formsubmitted.attr('action'), postdata, function(data) {
				if (data.response == "error") {
					if (typeof onformerror === 'function') {
						onformerror(formsubmitted,data);
					} else {
						formerror(formsubmitted,data);
					}
				} else if (data.response === "success") {
					if (typeof onformsuccess == 'function') {
						onformsuccess(formsubmitted,data);
					} else {
						formsuccess(formsubmitted,data);
					}
				} else {
					if (typeof onformerror === 'function') {
						onformerror(formsubmitted,data);
					} else {
						formerror(formsubmitted,data);
					}
				}
				console.log(data);	
			}, "json")
			.fail(function(response) {
				console.log('Error: Response was not a json object');
				$(formsubmitted).prepend('<div class="form-message form-error">' + response.responseText + '</div>');
			});
		}
	});


	function formerror(formsubmitted,data) {
		if (data.procedure === "create") {
			$(formsubmitted).prepend('<div class="form-message form-error">' + data.message + '</div>');
		}
		if (data.procedure === "update") {
			$(formsubmitted).prepend('<div class="form-message form-error">' + data.message + '</div>');
		}
		if (data.procedure === "delete") {
			$(formsubmitted).parent().prepend('<div class="form-message form-error">' + data.message + '</div>');
		}
	}


	function formsuccess(formsubmitted,data) {
		if (data.url) {
			window.location.href = data.url;
		} else {
			window.location.reload(true);		
		}
	}


	// click-bar
	$('.clickbar-title').on('click touchend', function(e) {
		if ($(this).hasClass('disabled') !== true) {
			$(this).toggleClass('clickbar-closed');
			$(this).parent('.clickbar-container').children('.clickbar-content').slideToggle();
		}
	});
	
	
	$('.clickbar-group').on('click touchend', function(e) {
		if ($(this).hasClass('clickbar-closed')) {
			$('.clickbar-group').not($(this)).show();
		} else {
			$('.clickbar-group').not($(this)).hide();
		}
	});


	$('.clickbar-group-close').on('click touchend', function(e) {
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
	
	$('.cancel-button').on('click touchend', function(e) {
		e.preventDefault();
		e.stopPropagation();
		window.location.reload(true);
	});

});