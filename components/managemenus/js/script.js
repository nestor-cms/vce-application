$(document).ready(function() {

	$("#existing-menus").tablesorter({
		headers: { 
            0: { sorter: false }, 3: { sorter: false }
        } 
	}); 


    // activate Nestable for list 1
    $('#nestable, #nestable2').nestable({
        group: 1
    });
    
    $('.recipe-form').on('submit', function(e) {
    e.preventDefault();
    
   	var formsubmitted = $(this);
   	var list = $('.right-block');
    
    submittable = true;
    
    	var typetest = $(list).find('input[type=hidden],input[type=text],input[type=email],input[type=password]');
		typetest.each(function(index) {
			if ($(this).val() || $(this).attr('name') === 'url') {
				$(this).closest('.dd-item').attr('data-' + $(this).attr('name'), $(this).val());
			}
			if ($(this).val() == "" && $(this).attr('tag') == 'required') {
				$(this).parent('label').addClass('highlight-alert');
				$(this).closest('.dd-content-extended').show();
				submittable = false;
			}
		});
		
		
		var checkboxtest = $(list).find('input[type=checkbox]');

		checkboxtest.each(function(index) {
			boxname = 'data-' + $(this).attr('name');
			boxvalue = $(this).val();	
			boxcheck = $(this).prop('checked');
			if (boxcheck === true) {
				var item = $(this).closest('.dd-item');
				var referrer = item.attr('referrer');
				if (!item.attr('found')) {
					item.attr(boxname,null);
					item.attr('found','true');
				}
				current = item.attr(boxname);
				if (current) {
					item.attr(boxname,current + '|' + boxvalue);
				} else {
					item.attr(boxname,boxvalue);
				}
			}
		});

		hierarchy = JSON.stringify($('#nestable2').nestable('serialize'));

		if (submittable) {
		postdata = $(this).serializeArray();
		postdata.push({name: 'json', value: hierarchy});
		
		$.post( formsubmitted.attr('action'), postdata, function(data) {
			if (data.response == "error") {
				$(formsubmitted).children('.recipe-info').prepend('<div class="form-message form-error">' + data.message + '</div>');
				$('.form-message').delay(2000).fadeOut('slow');
			} else if (data.response == "success") {
				$(formsubmitted).children('.recipe-info').prepend('<div class="form-message form-success">' + data.message + '</div>');
				$('.form-message').delay(2000).fadeOut('slow');
				setTimeout( function() {
	   				window.location.reload(true);
				}, 3000);
			} else if (data.response == "updated") {
				$(formsubmitted).children('.recipe-info').prepend('<div class="form-message form-success">' + data.message + '</div>');
				$('.form-message').delay(2000).fadeOut('slow');
				setTimeout( function() {
	   				window.location.reload(true);
				}, 3000);
			} else {
				$(formsubmitted).children('.recipe-info').prepend('<div class="form-message form-error">An error occured</div>');
				$('.form-message').delay(2000).fadeOut('slow');
			}
			
			//console.log(data);	
		}, "json")
		.fail(function(response) {
			console.log('Error: Response was not a json object');
			$(formsubmitted).prepend('<div class="form-message form-error">' + response.responseText + '</div>');
		});
		
		}
		    
    });
    

    $('.delete-form').on('submit', function(e) {
    	e.preventDefault();
    	
		if (!confirm("Are you sure you want to delete?")) {
			return false;
		}
    
    	var formsubmitted = $(this);
    	
    	postdata = $(this).serializeArray();
		
		$.post( formsubmitted.attr('action'), postdata, function(data) {
			if (data.response == "success") {
    			window.location.reload(true);
    		}
		}, "json")
		.fail(function(response) {
			console.log('Error: Response was not a json object');
			$(formsubmitted).prepend('<div class="form-message form-error">' + response.responseText + '</div>');
		});;
    
    });
    
    
    $('.depth-display').on('click', function() {
    	var category_type = '.depth_' + $(this).attr('category');
    	$('.depth_all').hide();
    	$('.depth-display').removeClass('highlight');
    	$(this).addClass('highlight');
    	$(category_type).show();
    });

});