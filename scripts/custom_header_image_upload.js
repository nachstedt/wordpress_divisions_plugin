
jQuery(document).ready(function($){

	var custom_uploader;

	$('#dvs_upload_image_button').click(function(e) {

		e.preventDefault();

		//If the uploader object has already been created, reopen the dialog
		if (custom_uploader) {
			custom_uploader.open();
			return;
		}

		//Extend the wp.media object
		custom_uploader = wp.media.frames.file_frame = wp.media({
			title: 'Choose Image',
			button: {
				text: 'Choose Image'
			},
			multiple: false
		});

		//When a file is selected, grab the URL and set it as the text field's value
		custom_uploader.on('select', function() {
			attachment = custom_uploader.state().get('selection').first().toJSON();
			$('#dvs_header_image_url').val(attachment.url);
			$('#dvs_header_image').attr("src", attachment.url);
			$('#dvs_header_image').show();
		});

		//Open the uploader dialog
		custom_uploader.open();
	});

	$('#dvs_discard_image_button').click(function(e) {
		e.preventDefault();
		$('#dvs_header_image_url').val("");
		$('#dvs_header_image').hide();
	});


});