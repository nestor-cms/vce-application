$(document).ready(function() {

	var uploader = uploader || { };
	var active = false;
	var which = {};

	uploader.binder = function(eachUploader) {
	
		var typelist = [];
		eachUploader.siblings('.media-types').each(function() {
			var eachmime = {};
			eachmime.mimetype = $(this).attr('mimetype');
			eachmime.mimename = $(this).attr('mimename');
			typelist.push(eachmime);
		});
		eachUploader.find('.mediatypes').val(JSON.stringify(typelist));
	
		var uploadform = $(eachUploader).find('.fileupload');

		var accept = [];
		eachUploader.siblings('.file-extensions').each(function() {
			var mime = $(this).attr('extensions');
			accept.push(mime);
		});
		uploadform.attr('accept',accept.join(','));

		var file_size_limit = uploadform.attr('file_size_limit');
		var set_chunk_size = uploadform.attr('chunk_size');
		var url_to_upload = uploadform.attr('path');
		var upload_error = false;
		var submittable = true;

		var chunkCount = 1;

		uploadform.fileupload({
			url: url_to_upload,
			maxChunkSize: parseInt(set_chunk_size),
			chunksTotal: 0,
			dataType: 'json',
			add: function (e, data) {
				data.chunksTotal = Math.ceil(data.files[0].size / set_chunk_size);
				eachUploader.find('.upload-browse').hide();
				if (data.files[0].size > file_size_limit) {
					eachUploader.find('.progressbar-error').html('File Uploader Error: Selected file size of ' +  bytesToSize(data.files[0].size) + ' is larger than upload limit of ' + bytesToSize(file_size_limit) + ' <div class="link-button cancel-button" href="">Try Again</div>').show();
					return false;
				}
				eachUploader.find('.upload-form').show();
				var fileName = data.files[0].name;
				fileName = fileName.replace(/\..*$/, '').replace(/[_-]/g, ' '); 
				eachUploader.find('.resource-name').val(fileName).focus();
 				eachUploader.find('.start-upload').click(function () {
 					data.submit();
 				});
			},
			progressall: function (e, data) {
				var progress = parseInt(data.loaded / data.total * 100, 10);
				eachUploader.find('.progressbar').progressbar("value",progress);
				eachUploader.find('.progress-label').text(progress + "%");
				if (progress === 100) {
					eachUploader.find('.progressbar-container').hide();
					eachUploader.find('.verifybar-container').show();
					eachUploader.find('.verifybar').progressbar({
						value: false
					});
					if (chunkCount > 1) {
						var verifyCount = 1;
						setInterval(function() {
							var progress = (verifyCount / chunkCount) * 100;
							eachUploader.find('.verifybar').progressbar("value",progress);
							verifyCount = (verifyCount > chunkCount) ? 1 : (verifyCount + 1);
						}, 3000);
					}
				}
			},
			done: function (e, data) {
				if (data.result.response === "error" || data.result.status === "error") {
					eachUploader.find('.progressbar-container').hide();
					eachUploader.find('.verifybar-container').hide();
					eachUploader.find('.progressbar-error').html(data.result.message).show();
					return;
				}
				var formaction = $('.action').val();
				$.post(formaction, data.result, function(data) {
					if (data.response == "success") {
					
						eachUploader.find('.verifybar-container').hide();
						eachUploader.find('.progressbar-success').show();
						
						if ($.isEmptyObject(which) === false) {
							$.each(which, function(key, value) {
								active = false;
								delete which[key];
								value.submit();
								return false;
							});
						} else {
							if (data.url) {
								window.location.href = data.url;
							} else {
								window.location.reload(true);		
							}
						}
					}
				}, "json");
			},
			fail: function (e, data) {
				eachUploader.find('.progressbar-container').hide();
				if (typeof data.errorThrown.message != 'undefined') {
					var message = data.errorThrown.message;
				} else {
					var message = 'Unsupported file type';
				}
				eachUploader.find('.progressbar-error').html('File Uploader Error: ' +  message + ' <div class="link-button cancel-button" href="">Try Again</div>').show();
				return;
			},
			chunksend: function (e, data) {
				eachUploader.find('.progress-chunks').html('<sup>' + chunkCount + '</sup>/<sub>' + data.chunksTotal + ' parts</sub>').show();
				chunkCount++;
			},
			chunkfail: function (e, data) {
				eachUploader.find('.progressbar-container').hide();
				eachUploader.find('.progressbar-error').html('File Uploader Error: Chunk failed to upload' + ' <div class="link-button cancel-button" href="">Try Again</div>').show();
			},
			submit: function (e, data) {
				if (submittable) {
					if (active === false) {
						eachUploader.find('.upload-form').hide();
						eachUploader.find('.progressbar-queued').hide();
						eachUploader.find('.progressbar-container').show();
						eachUploader.find('.progressbar').progressbar({
						value: false
						});
						active = true;
						return true;
						// data.submit();
					} else {
						eachUploader.find('.upload-form').hide();
						eachUploader.find('.progressbar-queued').show();
						which[Date.now()] = data;
						return false;
					}
				} else {
					return false;
				}
			}
		});


		uploadform.bind('fileuploadsubmit', function (e, data) {
			submittable = true;
			var inputs = {};			
			var filename = data.files[0].name;		
			inputs['name'] = filename.toLowerCase();
			inputs['extention'] = filename.slice((filename.lastIndexOf('.') - 1 >>> 0) + 2).toLowerCase();
			inputs['mimetype'] = data.files[0].type;
			inputs['timestamp'] = Date.now();
			eachUploader.find('.upload-form input, .upload-form textarea, .upload-form select').each(function() {
				if ($(this).attr('tag') == 'required') {
					if ($(this).val() == "") {
						$(this).closest('label').addClass('highlight-alert');
						submittable = false;
					}
					if ($(this).attr('type') === "checkbox" && !$(this).prop('checked')) {
						$(this).closest('label').addClass('highlight-alert');
						submittable = false;
					}
					if ($(this).find('option:selected').val() == "" && $(this).attr('tag') == 'required') {
						$(this).closest('label').addClass('highlight-alert');
						submittable = false;
					}
				}
				if ($(this).attr('name') && !$(this).hasClass('ignore-input')) {
					if ($(this).is(':checkbox')) {
						if ($(this).is(':checked')) {
							inputs[$(this).attr('name')] = $(this).val();
						}
					} else {
						inputs[$(this).attr('name')] = $(this).val();
					}
				}
			});
			data.formData = inputs;
		});
		
		function bytesToSize(bytes) {
			var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
			if (bytes == 0) return '0 Byte';
			var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
			return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
		};

		$(document).on('click','.cancel-button', function(e) {
			window.location.reload(true);
		});
	
	}	
	
	$('.uploader-container').each(function() {
		uploader.binder($(this));
	});
	
	
	$(document).on('click','.fileupload', function(e) {
		$(this).parent('label').addClass('highlight');
	});
	
});