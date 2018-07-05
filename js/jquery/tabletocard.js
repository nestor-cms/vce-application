(function($) {

    $.fn.tabletocard = function(options) {
    
		// $.isFunction
		
		// Establish our default settings
        var settings = $.extend({
        	ignore			: [], // comma delineated list of cells to not display in card view
            responsive		: false,
            displayTitle	: true,
            cardWidth 		: 299,
			cardHeight		: 415,
			cardMarginTop	: 5,
			cardMarginRight : 5,
			cardMarginBottom: 5,
			cardMarginLeft	: 5
        }, options);
        

        this.each( function() {
           	var eachTable = $(this);
           	
           	if (settings.ignore) {
           		var ignore = {};
           		$.each(settings.ignore, function (index, value) {
           			ignore[value] = true;
           		});
           	}
           	
           	if (settings.responsive && $(eachTable).width() < settings.responsive) {
           		//settings.responsive = $(eachTable).width();
           	}
           	
			var content = '<div class="card-container">\n';
           	
           	// get thead 
           	var thead = $(eachTable).find('thead');
           	
           	var tabs = [];
           	var tabIcons = [];
           	tabIconsClass = [];
			$(thead).find('th,td').each(function(thKey, thValue) {
           		tabs[thKey] = $(thValue).text();
           		tabIcons[thKey] = $(thValue).attr('icon');
           		tabIconsClass[thKey] = $(thValue).attr('icon-class');
           	});

           	// get tbody
           	var tbody = $(eachTable).find('tbody');

           	// body of each card
           	$(tbody).find('tr').each(function(trkey, trvalue) {
           	
           		// ';margin:' + settings.cardMargins +
           		content += '<div class="card" style="width:' + settings.cardWidth + 'px;min-height:' + settings.cardHeight + 'px;margin: ' + settings.cardMarginTop + 'px ' + settings.cardMarginRight + 'px ' + settings.cardMarginBottom + 'px ' + settings.cardMarginLeft + 'px;">\n<div class="card-content">\n';

           		var title = $(trvalue).attr('name') ? $(trvalue).attr('name') : (trkey + 1);
           	
           		if (settings.displayTitle) {
           			content += '<div class="card-title">' + title + '</div>\n';
           		}

				var tabLink = [];
				var tdCounter = 0;
           		$(this).find('td').each(function(tdKey, tdValue) {

					if (typeof ignore[tdKey] == 'undefined') {

						var classlist = 'card-content-each';
						if (tdCounter === 0) {
							classlist += ' current-content';
						}
					
						//if ($(tdValue).attr('url')) {				
						tabLink[tdKey] = $(tdValue).attr('url');
						var tabValue = tabs[tdKey].toLowerCase().replace(/ /g,"-");
						//} else {
						//tabLink[tdKey] = "none";
						//var tabValue = "";
						//}

						content += '<div class="' + classlist + '" tab="' + tabValue + '">\n';
						content += '<div class="card-content-inner">' + $(tdValue).html() + '</div>';
						content += '\n</div>';
						
						tdCounter++;
					
					}
           			
           		});
           		
           		content += '</div>\n<div class="card-tabs">';
           		
           		var tabCounter = 0;
           		$.each(tabs, function (tabIndex, tabValue) {

					if (typeof ignore[tabIndex] == 'undefined') {

						var classlist = 'card-tabs-each';
						if (tabCounter === 0) {
							classlist += ' current-tab';
						} else if (tabCounter === 1) {
							classlist += ' current-tab-after';
						}
					
						var urlLink = tabLink[tabIndex] ? ' url="' + tabLink[tabIndex] + '"' : '';
					
						var tabModifer = (typeof settings.ignore != 'undefined') ? settings.ignore.length : 0;
						// var tabModifer = 1;
						
						if (tabIcons[tabIndex]) {
							var tabText = '<div class="tab-icon"><img title="' +  tabValue + '" src="' + tabIcons[tabIndex] + '"></div>';
						} else if (tabIconsClass[tabIndex]) {
							var tabText = '<div class="tab-icon ' +  tabIconsClass[tabIndex] + '">&nbsp;</div>';
						} else {
							var tabText = tabValue;
						}
						
						content += '<div class="' + classlist + '" style="width:' + (100 / (tabs.length - tabModifer)) + '%" title="' +  tabValue + '" tab="' + tabValue.toLowerCase().replace(/ /g,"-") + '"' + urlLink + '>' + tabText + '</div>';

						tabCounter++;

					}
				
				});

           		content += '</div>\n</div>\n';

           	});
           	
           	content += '</div>\n';
           	
           	$(eachTable).after($.parseHTML(content));
           	
           	if (!settings.responsive) {
           		$(eachTable).remove();
           	} else {
           		respond();
           	
           		$(window).bind('resize', function (event) {
           			respond();
           		});
        
				function respond() {
					if ($(eachTable).width() < settings.responsive) {
						$('.card-container').show();
						$(eachTable).hide();
					} else {
					
						$('.card-container').hide();
						$(eachTable).show();
					}
				}
           	}
          	 	
          	$('.card-tabs-each').on('click', function(e) {
          	
				var card = $(this).closest('.card');
				var cardTitleHeight = card.find('.card-title').height();
				$(this).removeClass('current-tab current-tab-before current-tab-after').siblings().removeClass('current-tab current-tab-before current-tab-after');
				
				var currentContent = $(card).find('.card-content-each[tab="' + $(this).attr('tab') + '"]');
				var tileBaseHeight = parseInt(card.css('min-height'), 10);

				var tilePreviousHeight = parseInt(card.css('height'), 10);

				var tileCurrentHeight = parseInt(currentContent.css('height'), 10) + (parseInt(currentContent.find('.card-content-inner').css('paddingBottom'), 10) / 2) + (parseInt(cardTitleHeight, 10) - 20);
				
				if (tilePreviousHeight < tileBaseHeight) {
					tilePreviousHeight = tileBaseHeight
				}

				if (tileCurrentHeight < tileBaseHeight) {
					tileCurrentHeight = tileBaseHeight;
				}

				if (tileCurrentHeight != tilePreviousHeight) {
					$(card).find('.card-content').css('height', tilePreviousHeight).animate({
						height: tileCurrentHeight
					}, 400, function() {
						$(card).find('.card-content-each').removeClass('current-content');
						currentContent.addClass('current-content');
					});
				} else {
					$(card).find('.card-content-each').removeClass('current-content');
					currentContent.addClass('current-content');
				}

				$(this).addClass('current-tab');
				$(this).prev().addClass('current-tab-before');
				$(this).next().addClass('current-tab-after');
				
				if ($(this).attr('url')) {
          			window.location.href = $(this).attr('url');
          		}

			});
        	
        });

    }

}(jQuery));