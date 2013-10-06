<div class="wrap">
	<h2>Divisions Plugin</h2>
	<form method="post" action="options.php">
		<?php settings_fields(self::OPTION_GROUP); ?>
		<?php do_settings_sections( self::MENU_SLUG); ?>
		<?php submit_button(); ?>
	</form>
</div>
