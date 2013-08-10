<input type="radio" id="dvs_header_image_option_use_default"
		name="dvs_header_image_option" value="use_default"
		<?php if ($header_image_option == "use_default") echo "checked" ?>/>
<label for="dvs_header_image_option_use_default">
	use default header image
</label>
<br />
<input type="radio" id="dvs_header_image_option_replace"
		name="dvs_header_image_option" value="replace"
		<?php if ($header_image_option == "replace") echo "checked" ?>/>
<label for="dvs_header_image_option_replace">
	replace header image
</label>
<br />
<input type="radio" id="dvs_header_image_option_none"
		name="dvs_header_image_option" value="none"
		<?php if ($header_image_option == "none") echo "checked" ?>/>
<label for="dvs_header_image_option_none">
	no header image
</label>
<br />
<input id="dvs_upload_image_button" class="button" type="button" value="Choose Image" />
<input id="dvs_discard_image_button" class="button" type="button" value="Discard Image" />
<br />
<img id="dvs_header_image" src="<?php echo $header_image_url; ?>" />
<input id="dvs_header_image_url" type="hidden"
		name="dvs_header_image_url" value="<?php echo $header_image_url; ?>" />
