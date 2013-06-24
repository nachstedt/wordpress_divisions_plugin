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

	require_once(sprintf("%s/includes/dvs_division.php",dirname(__FILE__)));

	class TN_Divisions_Plugin
	{
		/**
		 * Construct the plugin object
		 */
		public function __construct()
		{
			dvs_Division::register_hooks();

			// register actions
			add_action('admin_init', array(&$this, 'admin_init'));
			add_action('admin_menu', array(&$this, 'add_menu'));
			add_action('init', array(&$this, 'load_current_division'));
			add_action('init', array(&$this, 'register_nav_menu_locations'));
			add_action('init', array(&$this, 'register_sidebars'));

			// register filters
			add_filter('post_link', array(&$this, 'modify_link'), 1, 2);

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

		public function load_current_division() {
			$id = array_key_exists('division', $_GET) ? $_GET['division'] : "0";
			if (get_post_type($id) == 'dvs_divisions' && get_post_status($id) == 'publish') {
				$this->current_division = get_post($id);
				echo get_post_status($id);
			} else {
				$this->current_division  =get_posts(array(
					'post_type'      => 'dvs_divisions',
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'paged'          => 0,
					'orderby'        => 'ID',
					'order'          => 'ASC',
				))[0];
			};
			echo 'current division is ' . $this->current_division->post_title;
		}

		public function get_divisions() {
			$divisions = get_posts(array(
				'post_type'      => 'dvs_division',
				'post_status'    => 'publish',
				'orderby'        => 'post_title',
				'order'          => 'ASC',
			));
			return $divisions;
		}

		public function modify_link($permalink_url, $post_data)  {
			return add_query_arg(
					'division',
					$this->get_current_division(),
					$permalink_url);
		}

		public function register_nav_menu_locations()
		{

			$this->original_nav_menu_locations = get_registered_nav_menus();

			$originals = $this->original_nav_menu_locations;
			$divisions = $this->get_divisions();
			foreach ($divisions as $division)
			{
				foreach ($originals as $name => $description)
				{
					register_nav_menu(
						$name . '_division_' . $division->ID,
						$description . __(" for ") . $division->post_title );
				}
			}
		}

		public function register_sidebars()
		{
			global $wp_registered_sidebars;
			$this->original_sidebars = $wp_registered_sidebars;
			$divisions = $this->get_divisions();
			foreach ($divisions as $division)
			{
				foreach ($original_sidebars as $sidebar)
				{
					register_sidebar(array(
						'name' => "{$sidebar['name']} {$division->post_title}",
						'id' => "{$sidebar['id']}_{$division->ID}",
						'description' => "{$sidebar['description']} "
							. __( "Only displayed when division "
							."{$division->post_title} is active"),
						'class' => $sidebar['class'],
						'before_widget' => $sidebar['before_widget'],
						'after_widget' => $sidebar['after_widget'],
						'before_title' => $sidebar['before_title'],
						'after_title' => $sidebar['after_title'],
					));
				}
			}
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

		public function setting_callback( $args ) {
			$name = esc_attr( $args['name'] );
			$value = esc_attr( get_option( $name ) );
			echo "<input type='text' name=$name value='$value' />";
		}

		public function get_current_division() {
			if (array_key_exists('division', $_GET))
			{
				return $_GET['division'];
			}
			else
			{
				return 0;
			}
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

