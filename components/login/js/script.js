function onformerror(formsubmitted,data) {
	$(formsubmitted).prepend('<div class="form-message form-error">' + data.message + '</div>');
	if (data.action == "clear") {
		$(formsubmitted).trigger('reset');
	}
	if (data.action == "reload") {
		window.location.reload();
	}
}

function onformsuccess(formsubmitted,data) {
	$(formsubmitted).prepend('<div class="form-message form-success">' + data.message + '</div>');
	if (data.url) {
			window.location.assign(data.url);
	} else {
		window.location.reload();
	}
}