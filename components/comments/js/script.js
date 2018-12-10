$(document).ready(function() {

	function findplayer(clickbar) {
		var clickbarParent = $(clickbar).parent();
		var mediaItem = $(clickbarParent).find('.media-item');
		if (mediaItem.length !== 0) {
			return $(mediaItem).find('.vidbox');
		}
		return findplayer($(clickbarParent));
	}

	$('.comments-input').on('change keyup keydown paste cut', function () {
		$(this).height(0).height(this.scrollHeight);
	}).change();

	$('.comments-clickbar').on('click', function(e) {

		if ($(this).hasClass('clickbar-closed')) {
			if (typeof videoPlayer === 'object') {	
				var player = findplayer($(this)).attr('player');
				if (typeof videoPlayer[player] !== 'undefined') {
					if (typeof videoPlayer[player].startVideoPlayer === 'function') {
						videoPlayer[player].startVideoPlayer();
					}
				}
				
			}
		} else {
			if (typeof videoPlayer === 'object') {
				var player = findplayer($(this)).attr('player');
				if (typeof videoPlayer[player] !== 'undefined') {
					if (typeof videoPlayer[player].pauseVideoPlayer === 'function') {
						videoPlayer[player].pauseVideoPlayer();
					}
				}
			}
		}
		
	});

	$(document).on('click','.comment-timestamp', function(e) {
		var timestamp = $(this).attr('timestamp');
		if (typeof videoPlayer === 'object') {
			var vidbox = findplayer($(this));
			var player = vidbox.attr('player');
			videoPlayer[player].shuttleVideoPlayer(timestamp);
			var vidPosition = $('#' + player).offset();
			$("html, body").animate({ scrollTop: (vidPosition.top - 50) }, "slow");
		}
	});

	$(document).on('submit','.asynchronous-comment-form', function(e) {
		e.preventDefault();
	
		var formsubmitted = $(this);
		var layout_container = $(this).attr('combar');
		var submittable = true;

		var textareatest = $(this).find('textarea');
		textareatest.each(function(index) {
			if ($(this).val() == "" && $(this).attr('tag') == 'required') {
				$(this).parent('label').addClass('highlight-alert');
				submittable = false;
			}
		});

		if (submittable) {
		
			var submitbutton = $(this).find('input[type=submit]');
			
			$(submitbutton).css('cursor','wait');
	
			var comment_text = $(this).find('textarea').val();

			var postdata = [];
			postdata.push(
				{name: 'dossier', value: $(this).find('input[name=dossier]').val()},
				{name: 'text', value: comment_text}
			);
		
			if (typeof videoPlayer === 'object') {
				var player = findplayer($(this)).attr('player');
				if (typeof videoPlayer[player] !== 'undefined') {
					if (typeof videoPlayer[player].getVideoPlayerTimestamp !== 'undefined') {
						var timestamp = videoPlayer[player].getVideoPlayerTimestamp();
						if (timestamp > 0) {
							postdata.push(
								{name: 'timestamp', value: timestamp}
							);
						}
					}
				}
			}
		
			$.post($(this).attr('action'), postdata, function(data) {
			
				if (data.response === "success") {
	
					if (typeof videoPlayer !== 'object') {
						$('#comments-asynchronous-content').find('.comment-timestamp').remove();
					}

					var asynchronousContent = $('#comments-asynchronous-content').html();
				
					comment_text = comment_text.replace(/(?:\r\n|\r|\n)/g, '<br>');

					asynchronousContent = asynchronousContent.replace("{text}", comment_text);
			
					var date = new Date();
					var created_at = date.toLocaleDateString('en-US') + ', ' + date.toLocaleTimeString('en-US');

					asynchronousContent = asynchronousContent.replace("{created-at}", created_at);

					if (typeof videoPlayer === 'object') {
						var timestamp = 0;
						var nicetimestamp = '0:00:00';
						if (typeof videoPlayer[player] !== 'undefined') {
							if (typeof videoPlayer[player].getVideoPlayerTimestamp !== 'undefined') {
								var timestamp = videoPlayer[player].getVideoPlayerTimestamp();
								var nicetimestamp = videoPlayer[player].getVideoPlayerNiceTime();
							}
						}
					
						asynchronousContent = asynchronousContent.replace("{timestamp}", timestamp);
						asynchronousContent = asynchronousContent.replace("{nice-timestamp}", nicetimestamp);
					}
				
					var newComment = $.parseHTML(asynchronousContent);
				
					if (timestamp === 0) {
						$(newComment).find('.comment-timestamp').remove();
					}
				
					$('#' + layout_container).before(newComment);

					if (typeof videoPlayer === 'object') {
						$(formsubmitted).closest('.vidbox-content').fadeOut('slow');
						if (typeof videoPlayer[player] !== 'undefined') {
							if (typeof videoPlayer[player].startVideoPlayer !== 'undefined') {
								videoPlayer[player].startVideoPlayer();
							}
						}
					
					}

					$(formsubmitted).find('textarea').val('');
					if (!$('#' + layout_container).find('.clickbar-title').hasClass('clickbar-closed')) {
						$('#' + layout_container).find('.clickbar-title').trigger( "click" );
					}
					
					$(submitbutton).css('cursor','pointer');
				
				} else {
				
					alert('Error: Your comment did not save. Please contact support and report this error: ' + JSON.stringify(data));
				
				}

			}, "json")
			.fail(function(response) {
				console.log('Error: Response was not a json object');
				$(formsubmitted).prepend('<div class="form-message form-error">Error: Your comment did not save. Please contact corvin@uw.edu and report this error: Not a json object.</div>');
			});
		}

	});
	
	$('.delete-comment').click(function() {
		if (confirm("Are you sure you want to delete?")) {
			var comment_id = '#comment-' + $(this).attr('comment');
			var postdata = [];
			postdata.push(
				{name: 'dossier', value: $(this).attr('dossier')}
			);
			$.post($(this).attr('action'), postdata, function(data) {
			
				if (data.response === "success") {
					$(comment_id).remove();
				}
			}, "json");
		}
	});

	$('.reply-form-link').click(function() {
		$(this).closest('.comment-row-content').find('.reply-form').slideDown();
	});

	$('.reply-form-cancel').click(function(e) {
		e.preventDefault();
		$(this).closest('.reply-form').slideUp();
	});

	$('.edit-form-link').click(function() {
		$(this).closest('.comment-row-content').find('.update-form').show();
		$(this).closest('.comment-row-content').find('.comment-text').hide();
	});

	$('.update-form-cancel').click(function(e) {
		e.preventDefault();
		$(this).closest('.update-form').hide();
		$(this).closest('.comment-row-content').find('.comment-text').show();
	});
	
	$(document).on('click','.comment-reload', function(e) {
		window.location.reload(true);
	});

});