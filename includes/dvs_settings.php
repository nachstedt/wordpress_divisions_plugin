<?php
if(!class_exists('dvs_Settings'))
{

	require_once(TN_DIVISIONS_INCLUDE_DIR . 'dvs_division.php');

	class dvs_Settings
	{
		const OPTION_GROUP = 'tn_divisions_plugin-settings';
		const MENU_SLUG = "tn_division_plugin_settings";
		const SECTION_LINKS_SLUG = "section_links";
		const SECTION_LINKS_TITLE = "General Settings";
		const SECTION_LINKS_DESCRIPTION = 'Define how the Divisions plugin
			manipulates page links to determine which division to load when
			clicking on a link';
		const SETTING_LINK_MODIFICATION_TITLE = 'Link Modification';
		const SETTING_LINK_MODIFICATION_SLUG = 'dvs_link_modification';
		const OPTION_QUERY_ARG_VALUE = 'query_arg';
		const OPTION_QUERY_ARG_LABEL = 'Add query argument';
		const OPTION_PERMALINK_VALUE = 'permalink';
		const OPTION_PERMALINK_LABEL = 'Modify permalink (Global permalinks
			must be activated)';
		const SETTING_LINK_MODIFICATION_OPTION_PERMALINK = 'permalinks';

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
				'edit.php?post_type='.  dvs_Division::POST_TYPE,
				'Divisions Plugin Settings',                # title in browser bar
				'Settings',                                 # menu title
				'manage_options',                           # required capability
				self::MENU_SLUG,                   # menu slug
				array(__CLASS__, 'settings_menu_callback')  # callback
			);
		}

		public static function get_use_permalinks()
		{
			return (
				get_option(self::SETTING_LINK_MODIFICATION_SLUG)
				== self::OPTION_PERMALINK_VALUE);
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
			register_setting(
				self::OPTION_GROUP,
				self::SETTING_LINK_MODIFICATION_SLUG,
				array(__CLASS__, 'setting_link_modification_sanitize')
			);
			add_settings_section(
				self::SECTION_LINKS_SLUG,                     # id
				_(self::SECTION_LINKS_TITLE),                 # title
				array(__CLASS__, 'section_links_callback'),   # callback
				self::MENU_SLUG                               # menu slug
			);
			add_settings_field(
				self::SETTING_LINK_MODIFICATION_SLUG,          # field id
				self::SETTING_LINK_MODIFICATION_TITLE,         # display title
				array(__CLASS__, 'permalink_option_callback'), # callback
				self::MENU_SLUG,                               # menu slug
				self::SECTION_LINKS_SLUG                       # section id
			);
		}

		public static function section_links_callback()
		{
			echo _(self::SECTION_LINKS_DESCRIPTION);
		}

		public static function setting_link_modification_sanitize($input)
		{
			dvs_LinkModification::schedule_rewrite_rules_flush();
			return $input;
		}

		public static function permalink_option_callback()
		{
			$value = get_option(self::SETTING_LINK_MODIFICATION_SLUG);
			$checked_permalink = ($value==self::OPTION_PERMALINK_VALUE);
			$checked_query_arg = !$checked_permalink;
			echo
				"<label>"
					. "<input type='radio' "
						. "name='" . self::SETTING_LINK_MODIFICATION_SLUG . "' "
						. "value='". self::OPTION_QUERY_ARG_VALUE . "' "
						. ($checked_query_arg ? "checked" : "")
						. " /> "
					. "<span>". self::OPTION_QUERY_ARG_LABEL . "</span>"
				. "</label>"
				. "<br>"
				."<label>"
					. "<input type='radio' "
						. "name='" . self::SETTING_LINK_MODIFICATION_SLUG . "' "
						. "value='" . self::OPTION_PERMALINK_VALUE . "' "
						. ($checked_permalink ? "checked" : "")
						." /> "
					. "<span>" . self::OPTION_PERMALINK_LABEL . "</span>"
				. "</label>";
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
				. dvs_Division::POST_TYPE
				. '&page='
				. self::MENU_SLUG
				. '">Settings</a>';
			array_unshift($links, $settings_link);
			return $links;
		}


	}
}
?>
