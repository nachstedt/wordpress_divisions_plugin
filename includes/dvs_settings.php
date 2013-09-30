<?php
if(!class_exists('dvs_Settings'))
{

	require_once(TN_DIVISIONS_INCLUDE_DIR . 'dvs_division.php');

    class dvs_Settings
    {
		const OPTION_GROUP = 'tn_divisions_plugin-settings';
		const USE_PERMALINKS_OPTION = 'use_permalinks';
		const SECTION_GENERAL_SLUG = "section_general";
		const MENU_SLUG = "tn_division_plugin_settings";

        public static function register_hooks()
        {
            # register actions
			add_action('admin_init', array(__CLASS__, 'admin_init'));
			add_action('admin_menu', array(__CLASS__, 'admin_menu'));

			add_filter(
				'plugin_action_links_' . TN_DIVISIONS_PLUGIN_BASENAME,
				array(__CLASS__, 'plugin_action_links_filter'));
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
				'edit.php?post_type='.dvs_Constants::DIVISION_POST_TYPE,
				'Divisions Plugin Settings',                # title in browser bar
				'Settings',                                 # menu title
				'manage_options',                           # required capability
				self::MENU_SLUG,                   # menu slug
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
			include(TN_DIVISIONS_TEMPLATE_DIR . 'settings.php');
		}

		/**
		 * Initialize some custom settings
		 */
		private static function init_settings()
		{
			// register the settings for this plugin
			register_setting(self::OPTION_GROUP, self::USE_PERMALINKS_OPTION);
			add_settings_section(
				self::SECTION_GENERAL_SLUG,                   # id
				_("General Settings"),                        # title
				array(__CLASS__, 'section_general_callback'), # callback
				self::MENU_SLUG                               # menu slug
			);
			add_settings_field(
				self::USE_PERMALINKS_OPTION,                   # field id
				'Setting A',                                   # display title
				array(__CLASS__, 'permalink_option_callback'), # callback
				self::MENU_SLUG,                               # menu slug
				self::SECTION_GENERAL_SLUG                     # section id
			);
		}

		public static function section_general_callback()
		{
			echo 'Some help text goes here.';
		}

        public static function permalink_option_callback()
		{
			echo "<input type='text' name='timo' value='nachstedt' />";
		}

		/**
		 * Adds settings link to the plugin information shown on the plugin site
		 *
		 * @param array $links Original list of links to display
		 * @return array Extended list of links to display
		 */
		public static function plugin_action_links_filter($links) {
			$settings_link =
				'<a href="'
				. get_bloginfo('wpurl')
				. '/wp-admin/edit.php?post_type='
				. dvs_Constants::DIVISION_POST_TYPE
				. '&page='
				. self::MENU_SLUG
				. '">Settings</a>';
			array_unshift($links, $settings_link);
			return $links;
		}


    }
}
?>
