<div class="wrap">
	<h2>Divisions Plugin</h2>
	<form method="post" action="options.php">
		<?php settings_fields('tn_divisions_plugin-settings'); ?>
		<?php do_settings_sections( 'tn_divisions_plugin'); ?>
		<?php submit_button(); ?>
	</form>
</div>
