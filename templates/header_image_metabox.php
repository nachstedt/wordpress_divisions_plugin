<label for="upload_image">
	<input type="checkbox" id="dvs_replace_header_image"
		name="dvs_replace_header_image"
		value="True"
		<?php if ($replace_header_image) echo "checked"?>
		/>
	<label for="dvs_replace_header_image">replace header image</label>
	<input id="dvs_upload_image_button" class="button" type="button" value="Choose Image" />
	<input id="dvs_discard_image_button" class="button" type="button" value="Discard Image" />
	<br />
	<img id="dvs_custom_header_image" src="" />
	<input id="dvs_custom_header_image_url" type="hidden" 
		name="dvs_custom_header_image_url" value="http://" />
</label>
