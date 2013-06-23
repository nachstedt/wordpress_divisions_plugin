<?php
/*
Plugin Name: Divisions
Plugin URI: http://not_yet_available
Description: Allows exactly what we need
Version: 0.0.1
Author: Timo Nachstedt
Author URI: http://not_yet_available
License: GPL2
*/

/*  Copyright YEAR  PLUGIN_AUTHOR_NAME  (email : PLUGIN AUTHOR EMAIL)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if(!class_exists('TN_Divisions_Plugin'))
{
	class TN_Divisions_Plugin
	{
		/**
		 * Construct the plugin object
		 */
		public function __construct()
		{
			// register actions
			add_action('admin_init', array(&$this, 'admin_init'));
			add_action('admin_menu', array(&$this, 'add_menu'));
			add_action('init', array(&$this, 'create_post_type'));
		}

		/**
		 * add a menu
		 */
		public function add_menu()
		{
			add_menu_page(
				'Divisions Plugin Settings',          # title in browser title bar
				'Division Plugin',                    # menu title
				'manage_options',                     # required capability
				'tn_divisions_plugin',                # menu slug
				array(&$this, 'plugin_settings_page') # callback
			);

			add_submenu_page(
				'tn_divisions_plugin',                # parent_slug
				'Division Plugin Settings',           # page_title
				'Division Plugin',                    # menu_title
				'manage_options',                     # required capability
				'tn_divisions_plugin',                # menu_slug (same as parent)
				array(&$this, 'plugin_settings_page') # callback
			);

			/*
			add_submenu_page(
				'tn_divisions_plugin',            # parent_slug
				'Manage your Divisions',          # page_title
				'Divisions',                      # menu_title
				'manage_options',                 # required capability
				'tn_divisions_manage',            # menu_slug (same as parent)
			array(&$this, 'manage_divisions')     # callback
			);
			*/

		}

		/**
		 * hook into WP's admin_init action hook
		 */
		public function admin_init()
		{
			// Set up the settings for this plugin
			$this->init_settings();
			// Possibly do additional admin_init tasks
		}

		public function create_post_type()
		{
			register_post_type(
				'dvs_divisions',
				array(
					'labels'              => array(
						'name' => __( 'Divisions' ),
						'singular_name' => __( 'Division' )
					),
					'public'               => true,
					'exclude_from_search'  => true,
					'publicly_queryable'   => false,
					'show_ui'              => true,
					'show_in_nav_menus'    => false,
					'show_in_menu'         => true,
					'has_archive'          => false,
					'supports'             => array(
						'title',
					),
					'rewrite'              => false,
					'register_meta_box_cb' => array(&$this, 'meta_box_callback')
			)
			);
		}

		public function meta_box_callback()
		{
			echo '<style>#edit-slug-box{display:none;}</style>';
			#remove_meta_box('submitdiv', 'dvs_divisions', 'side');
			remove_meta_box('slugdiv', 'dvs_divisions', 'side');
		}

		/**
		 * Initialize some custom settings
		 */
		public function init_settings()
		{
			// register the settings for this plugin
			register_setting('tn_divisions_plugin-settings', 'setting_a');
			register_setting('tn_divisions_plugin-settings', 'setting_b');
			add_settings_section(
				'section-one',                         # id
				'Section One',                         # title
				array(&$this, 'section_one_callback'), # callback
				'tn_divisions_plugin'                  # menu slug
			);
			add_settings_field(
				'setting_a',                         # field id
				'Setting A',                         # display title
				array(&$this, 'setting_callback'), # callback
				'tn_divisions_plugin',               # menu slug
				'section-one',                       # section id
				array('name' => 'setting_a')        # callback args
			);
			add_settings_field(
				'setting_b',                         # field id
				'Setting B',                         # display title
				array(&$this, 'setting_callback'), # callback
				'tn_divisions_plugin',               # menu slug
				'section-one',                       # section id
				array('name' => 'setting_b')         # callback args
			);
		}

		/**
		 * Menu Callback
		 */
		public function plugin_settings_page()
		{
			if(!current_user_can('manage_options'))
			{
				wp_die(__(
					'You do not have sufficient permissions to access this '
					.'page.'));
			}

			// Render the settings template
			include(sprintf("%s/templates/settings.php", dirname(__FILE__)));
		}

		public function section_one_callback()
		{
			echo 'Some help text goes here.';
		}

		function setting_callback( $args ) {
			$name = esc_attr( $args['name'] );
			$value = esc_attr( get_option( $name ) );
			echo "<input type='text' name=$name value='$value' />";
		}

		/**
		 * Activate the plugin
		 */
		public static function activate()
		{
			// do nothing
		}

		/**
		 * Deactivate the plugin
		 */
		public static function deactivate()
		{
			// Do nothing
		}

	}
}


if(class_exists('TN_Divisions_Plugin'))
{
	// Installation and uninstallation hooks
	register_activation_hook(
		__FILE__, array('TN_Divisions_Plugin', 'activate'));
	register_deactivation_hook(
		__FILE__, array('TN_Divisions_Plugin', 'deactivate'));

	// instantiate the plugin class
	$tn_divisions_plugin = new TN_Divisions_Plugin();
	if(isset($tn_divisions_plugin)) {
		// Add the settings link to the plugins page
		function plugin_settings_link($links) {
			$settings_link =
				'<a href="'
				. get_bloginfo('wpurl')
				. '/wp-admin/admin.php?page=tn_divisions_plugin">Settings</a>';
			array_unshift($links, $settings_link);
			return $links;
		}
		$plugin = plugin_basename(__FILE__);
		add_filter("plugin_action_links_$plugin", 'plugin_settings_link');


	}
}


/*
function modify_link($permalink_url, $post_data)  {
	return add_query_arg('timo', 'super', $permalink_url);
}


add_filter('post_link', 'modify_link', 1, 2);

function modify_menu($args) {
	$args['menu']=3;
	return $args;
}

add_action('wp_nav_menu_args', 'modify_menu', 1, 1);

function modify_locations($args) {
	return $args;
}

add_filter('theme_mod_nav_menu_locations', 'modify_locations', 1, 1);

register_nav_menu("timo", __('Timos Menu'));

*/

?>

