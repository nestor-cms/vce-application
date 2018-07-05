$(document).ready(function() {

var file_size_limit = $('#browse').attr('file_size_limit');
var max_allowed_size  = $('#browse').attr('max_allowed_size');
var set_chunk_size = $('#browse').attr('chunk_size');
var url_to_upload = $('#browse').attr('path');
var upload_error = false;

var mimelist = [];
$('.file-extensions').each(function() {
	var mime = {title:$(this).attr('title'),extensions:$(this).attr('extensions')};
	mimelist.push(mime);
});

var typelist = [];
$('.media-types').each(function() {
	var eachmime = {};
	eachmime.mimetype = $(this).attr('mimetype');
	eachmime.mimename = $(this).attr('mimename');
	typelist.push(eachmime);
});
$('#mediatypes').val(JSON.stringify(typelist));

var params = {};
params['mimetype'] = "none";
params['postnames'] = "";
$('.vce-input').each(function() {
	params[$(this).attr('name')] = "";
});


//  chunk_size: '512kb / 10mb',
// 
var uploader = new plupload.Uploader({
  browse_button: 'browse',
  url: url_to_upload,
  max_file_size: file_size_limit,
  chunk_size: set_chunk_size,
  max_retries: 4,
  multi_selection: false,
  filters : mimelist,
	multipart_params : {
        params   
	}
});

uploader.init();

uploader.bind('FilesAdded', function(up, files) {
	plupload.each(files, function(file) {
		var fileName = file.name;
		fileName = fileName.replace(/\..*$/, '');
		fileName = fileName.replace(/[_-]/g, ' '); 
		$('#upload-browse').hide();
		$('#text-block-container').remove();
		$('#upload-form').show();
		$('#resource-name').val(fileName).focus();
	});
});


uploader.bind('BeforeUpload', function(up, file) {

	$('#upload-form').hide();
	$('#progressbar-container').show();
	$('#progressbar').progressbar({
		value: false
	});
	
	var inputs = {};
	inputs['mimetype'] = file.type;
	inputs['timestamp'] = Date.now();
	$('.vce-input').each(function() {
		inputs[$(this).attr('name')] = $(this).val();
	});
	
	var filename = file.name;
	var extention = filename.slice((filename.lastIndexOf('.') - 1 >>> 0) + 2);
	
	if (extention) {
		inputs['name'] = 'file.' + extention;
	}
	
	inputs['postnames'] = JSON.stringify(inputs);

	up.settings.multipart_params = inputs;
	
	setInterval(function() {
		if ((Date.now() - $('#progress-label').attr('timestamp')) > 300000) {
			uploader.stop();
			$('#progressbar-container').hide();
			$('#progressbar-error').html('File Uploader Error: Process Timed Out');
			$('#progressbar-error').show();
		}
	}, 300000);

});

uploader.bind('UploadProgress', function(up, file) {
	$('#progressbar').progressbar("value",file.percent);
	$('#progress-label').text(file.percent + "%" );
	$('#progress-label').attr('timestamp', Date.now());
});

uploader.bind('ChunkUploaded', function(up, file, result) {
	data = JSON.parse(result.response);
	if (data.status === "error") {
		uploader.stop();
		$('#progressbar-container').hide();
		$('#progressbar-error').html(data.message);
		$('#progressbar-error').show();
	}
});

uploader.bind('Error', function(up, err) {
	upload_error = true;
	uploader.stop();
	$('#progressbar-container').hide();
	if (err.message) {
		if (err.message == "File size error.") {
			$('#progressbar-error').html('This file is too large to upload. File must be under ' + max_allowed_size + ' in size.' );
		} else {
			$('#progressbar-error').html('File Uploader Error: ' + err.message);
		}
	} else {
		$('#progressbar-error').html('File Uploader Error: Server Not Responding');
	}
	$('#progressbar-error').show();
});


uploader.bind('FileUploaded', function(up, err, result) {
	//console.log(result);
	formsubmitted = $('#action').val();
	postdata = JSON.parse(result.response);
	if (postdata.status === "error") {
		$('#progressbar-container').hide();
		$('#progressbar-error').html(postdata.message);
		$('#progressbar-error').show();
	} else {
		$.post(formsubmitted, postdata, function(data) {
		if (data.response == "success") {
			window.location.reload(true);
		}
		console.log(data);
		}, "json");
	}
});

uploader.bind("UploadComplete", function(up, files) {
	if (upload_error) {
		$('#progressbar-container').hide();
		$('#progressbar-error').html('Error Uploading File');
		$('#progressbar-error').show();
	} else {
		console.log('File Uploaded');
	}
});

$('#start-upload').on('click', function(e) {
	e.preventDefault();
	var submittable = true;
	
	var resourceName = $('#resource-name');
	if (resourceName.val() == "") {
		resourceName.parent("label").addClass('highlight-alert');
		submittable = false;
	}
	
	if (submittable === true) {
		uploader.start();
	}
});

$('#progressbar-error').on('click', function(e) {
	window.location.reload(true);
});

});