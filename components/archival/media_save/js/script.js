$(document).ready(function() {

	var typelist = [];
	$('.media-types').each(function() {
		var eachmime = {};
		eachmime.mimetype = $(this).attr('mimetype');
		eachmime.mimename = $(this).attr('mimename');
		typelist.push(eachmime);
	});
	$('#mediatypes').val(JSON.stringify(typelist));

	var accept = [];
	$('.file-extensions').each(function() {
		var mime = $(this).attr('extensions');
		accept.push(mime);
	});
	$('#fileupload').attr('accept',accept.join(','));

	var file_size_limit = $('#fileupload').attr('file_size_limit');
	var set_chunk_size = $('#fileupload').attr('chunk_size');
	var url_to_upload = $('#fileupload').attr('path');
	var upload_error = false;

	var chunkCount = 1;

	$('#fileupload').fileupload({
		url: url_to_upload,
		maxChunkSize: parseInt(set_chunk_size),
		chunksTotal: 0,
		dataType: 'json',
		add: function (e, data) {
			data.chunksTotal = Math.ceil(data.files[0].size / set_chunk_size);
			$('#upload-browse').hide();
			if (data.files[0].size > file_size_limit) {
				$('#progressbar-error').html('File Uploader Error: Selected file size of ' +  bytesToSize(data.files[0].size) + ' is larger than upload limit of ' + bytesToSize(file_size_limit) + ' <div class="link-button cancel-button" href="">Try Again</div>').show();
				return false;
			}
			$('#upload-form').show();
			var fileName = data.files[0].name;
			fileName = fileName.replace(/\..*$/, '').replace(/[_-]/g, ' '); 
			$('#resource-name').val(fileName).focus();
			$('#start-upload').click(function () {
				$('#upload-form').hide();
				$('#progressbar-container').show();
				$('#progressbar').progressbar({
					value: false
				});
        		data.submit();
       		});	
    	},
		progressall: function (e, data) {
			var progress = parseInt(data.loaded / data.total * 100, 10);
    	   	$('#progressbar').progressbar("value",progress);
			$('#progress-label').text(progress + "%");
			if (progress === 100) {
				$('#progressbar-container').hide();
				$('#verifybar-container').show();
				$('#verifybar').progressbar({
					value: false
				});
				if (chunkCount > 1) {
					var verifyCount = 1;
					setInterval(function() {
						var progress = (verifyCount / chunkCount) * 100;
						$('#verifybar').progressbar("value",progress);
						verifyCount = (verifyCount > chunkCount) ? 1 : (verifyCount + 1);
					}, 3000);
				}
			}
		},
		done: function (e, data) {
    		// console.log(data);
    		if (data.result.status === "error") {
    			$('#progressbar-container').hide();
    			$('#progressbar-error').html(data.result.message).show();
    			return;
    		}
			var formaction = $('#action').val();
			$.post(formaction, data.result, function(data) {
				if (data.response == "success") {
					window.location.reload(true);
				}
				// console.log(data);
				}, "json");
    	},
    	fail: function (e, data) {
    		$('#progressbar-container').hide();
    		$('#progressbar-error').html('File Uploader Error: ' +  data.errorThrown.message + ' <div class="link-button cancel-button" href="">Try Again</div>').show();
    		return;
		},
		chunksend: function (e, data) {
    		$('#progress-chunks').html('<sup>' + chunkCount + '</sup>/<sub>' + data.chunksTotal + '</sub>').show();
   	 		chunkCount++;
		},
		chunkfail: function (e, data) {
			$('#progressbar-container').hide();
			$('#progressbar-error').html('File Uploader Error: Chunk failed to upload' + ' <div class="link-button cancel-button" href="">Try Again</div>').show();
		}

	});


	$('#fileupload').bind('fileuploadsubmit', function (e, data) {
		var inputs = {};			
		var filename = data.files[0].name;		
		inputs['name'] = filename.toLowerCase();
		inputs['extention'] = filename.slice((filename.lastIndexOf('.') - 1 >>> 0) + 2).toLowerCase();
		inputs['mimetype'] = data.files[0].type;
		inputs['timestamp'] = Date.now();
		$('.vce-input').each(function() {
			inputs[$(this).attr('name')] = $(this).val();
		});
                   	
		data.formData = inputs;

	});


	function bytesToSize(bytes) {
		var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
		if (bytes == 0) return '0 Byte';
		var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
		return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
	};

	$(document).on('click touchend','.cancel-button', function(e) {
		window.location.reload(true);
	});

});