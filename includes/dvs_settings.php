<?php
if(!class_exists('dvs_Settings')) 
{

	require_once(sprintf("%s/dvs_division.php", dirname(__FILE__)));

    class dvs_Settings
    {
        
        public static function register_hooks()
        {
            # register actions
			add_action('admin_init', array(__CLASS__, 'admin_init'));
			add_action('admin_menu', array(__CLASS__, 'admin_menu'));
        }
        
		/**
		 * hook into WP's admin_init action hook
		 */
		public static function admin_init()
		{
			self::init_settings();
		}

        
		/**
		 * hook into WP's admin_menu hook
		 */
		public static function admin_menu()
		{
			add_submenu_page(
                'edit.php?post_type='.dvs_Division::POST_TYPE,
				'Divisions Plugin Settings',             # title in browser bar
				'Settings',                              # menu title
				'manage_options',                        # required capability
				'tn_division_plugin_settings',           # menu slug
				array(__CLASS__, 'settings_menu_callback')  # callback
			);
		}
        
        
        /**
		 * Menu Callback
		 */
		public static function settings_menu_callback()
		{
			if(!current_user_can('manage_options'))
			{
				wp_die(__(
				'You do not have sufficient permissions to access this '
					 .'page.'));
			}

			// Render the settings template
			include(sprintf("%s/../templates/settings.php", dirname(__FILE__)));
		}

		/**
		 * Initialize some custom settings
		 */
		private static function init_settings()
		{
			// register the settings for this plugin
			register_setting('tn_divisions_plugin-settings', 'setting_a');
			register_setting('tn_divisions_plugin-settings', 'setting_b');
			add_settings_section(
				'section-one',                         # id
				'Section One',                         # title
				array(__CLASS__, 'section_one_callback'), # callback
				'tn_divisions_plugin'                  # menu slug
			);
			add_settings_field(
				'setting_a',                         # field id
				'Setting A',                         # display title
				array(__CLASS__, 'setting_callback'), # callback
				'tn_divisions_plugin',               # menu slug
				'section-one',                       # section id
				array('name' => 'setting_a')        # callback args
			);
			add_settings_field(
				'setting_b',                         # field id
				'Setting B',                         # display title
				array(__CLASS__, 'setting_callback'), # callback
				'tn_divisions_plugin',               # menu slug
				'section-one',                       # section id
				array('name' => 'setting_b')         # callback args
			);
		}
        
        public static function section_one_callback()
		{
			echo 'Some help text goes here.';
		}

		public function setting_callback( $args ) {
			$name = esc_attr( $args['name'] );
			$value = esc_attr( get_option( $name ) );
			echo "<input type='text' name=$name value='$value' />";
		}

    }
}
?>
