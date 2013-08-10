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

	require_once(sprintf("%s/includes/dvs_division.php", dirname(__FILE__)));
    require_once(sprintf("%s/includes/dvs_settings.php", dirname(__FILE__)));

	class TN_Divisions_Plugin
	{

		private $current_division = NULL;

		/**
		 * Construct the plugin object
		 */
		public function __construct()
		{
			// register installation and uninstallation hooks
			register_activation_hook(__FILE__, array(&$this, 'activate'));
			register_deactivation_hook(__FILE__, array(&$this, 'deactivate'));

			// register hooks for plugin classes
			dvs_Division::register_hooks();
            dvs_Settings::register_hooks();

			// register actions
			add_action('init', array(&$this, 'init'));

			add_action( 'wp_edit_nav_menu_walker', array( $this, 'edit_nav_menu_walker' ) );
			add_action( 'wp_update_nav_menu_item', array( $this, 'update_nav_menu_item' ), 10, 3 );

			// register filters
			add_filter(
				'post_link',
				array(&$this, 'post_link_filter'), 1, 2);
			add_filter(
				'plugin_action_links_' . plugin_basename(__FILE__),
				array(&$this, 'plugin_action_links_filter'));
			add_filter(
				'wp_setup_nav_menu_item',
				array(&$this, 'setup_nav_menu_item_filter'));
			add_filter(
				'wp_nav_menu_args',
				array(&$this, 'nav_menu_args_filter'));
			add_filter(
				'theme_mod_nav_menu_locations',
				array(&$this, 'theme_mod_nav_menu_locations_filter'));
            add_filter(
                'theme_mod_header_image',
                 array(&$this, 'theme_mod_header_image_filter'));
		}

		/**
		 * hook into WP's init hook
		 */
		public function init()
		{
			$this->register_nav_menu_locations();
			$this->register_sidebars();
			if (! is_admin())
			{
				$this->load_current_division();
			}
		}

		public function setup_nav_menu_item_filter($menu_item)
		{
			$division_enabled = esc_attr( get_post_meta( $menu_item->ID, 'dvs_division_enabled', TRUE ) );
			$chosen_division = esc_attr( get_post_meta( $menu_item->ID, 'dvs_division', TRUE ) );
			$division = $division_enabled ? $chosen_division : $this->get_current_division();
			$menu_item->url = add_query_arg('division', $division, $menu_item->url);
			return $menu_item;
		}

		public function nav_menu_args_filter($args)
		{
			$replaced = get_post_meta($this->get_current_division(), "replaced_nav_menus", TRUE);
			if ($replaced=="") $replaced=array();
                        $name = $args["theme_location"];
			if (in_array($name, $replaced))
			{
				$this->load_current_division();
				$args["theme_location"] = $name . '_division_' . $this->current_division->ID;
			}
			return $args;
		}

		public function theme_mod_header_image_filter($args)
		{
			$option = get_post_meta($this->get_current_division(), "dvs_header_image_option", TRUE);
			if ($option=="none") return "";
			if ($option=="replace") return get_post_meta($this->get_current_division (), "dvs_header_image_url", TRUE);
			return $args;
		}

		public function theme_mod_nav_menu_locations_filter($args)
		{
			if (is_admin()) return $args;

			$replaced = get_post_meta($this->get_current_division(), "replaced_nav_menus", TRUE);
			if ($replaced=="") $replaced=array();
                        foreach ($replaced as $name) {
				if (! array_key_exists($name, $args))
					$args[$name] = -1;
			}
			return $args;
		}

		public function load_current_division() {
			$id = array_key_exists('division', $_GET) ? $_GET['division'] : "0";
			if (get_post_type($id) == 'dvs_division' && get_post_status($id) == 'publish') {
				$this->current_division = get_post($id);
			} else {
				$this->current_division = get_posts(array(
					'post_type'      => 'dvs_division',
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'paged'          => 0,
					'orderby'        => 'ID',
					'order'          => 'ASC',
				))[0];
			};
		}

		public function get_divisions()
		{
			if ( !isset($this->divisions) )
			{
				$this->divisions = get_posts(array(
					'post_type'      => 'dvs_division',
					'post_status'    => 'publish',
					'orderby'        => 'post_title',
					'order'          => 'ASC',
				));
			}
			return $this->divisions;
		}

		public function post_link_filter($permalink_url, $post_data)  {
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
				$replaced = get_post_meta($division->ID, 'replaced_nav_menus', True);
				if ($replaced=="") $replaced=array();
                                foreach ($originals as $name => $description)
				{
					if (in_array($name, $replaced)) {
						register_nav_menu(
							$name . '_division_' . $division->ID,
							$description . __(" for ") . $division->post_title );
					}
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
				$replaced = get_post_meta($division->ID, 'replaced_sidebars', True);
				if ($replaced=="") $replaced=array();
                                foreach ($this->original_sidebars as $sidebar)
				{
					if (in_array($sidebar['id'], $replaced)){
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
		}

		public function plugin_action_links_filter($links) {
			$settings_link =
				'<a href="'
				. get_bloginfo('wpurl')
				. '/wp-admin/admin.php?page=tn_divisions_plugin">Settings</a>';
			array_unshift($links, $settings_link);
			return $links;
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

		function edit_nav_menu_walker( $walker ) {
			// swap the menu walker class only if it's the default wp class (just in case)
			if ( $walker === 'Walker_Nav_Menu_Edit' ) {
				require_once WP_PLUGIN_DIR . '/divisions/includes/divisions_walker_nav_menu_edit.php';
				$walker = 'Divisions_Walker_Nav_Menu_Edit';
			}
			return $walker;
		}


		/**
		 * Save post meta. Menu items are just posts of type "menu_item".
		 *
		 *
		 * @param type $menu_id
		 * @param type $menu_item_id
		 * @param type $args
		 */
		function update_nav_menu_item($menu_id, $menu_item_id, $args) {
			if ( isset( $_POST[ "menu-item-division-enabled" ][$menu_item_id] ) ) {
				update_post_meta( $menu_item_id, 'dvs_division_enabled', 1 );
			} else {
				update_post_meta( $menu_item_id, 'dvs_division_enabled', 0 );
							}
			if ( isset( $_POST[ "edit-menu-item-division" ][$menu_item_id] ) ) {
				update_post_meta( $menu_item_id, 'dvs_division', $_POST[ "edit-menu-item-division" ][$menu_item_id] );
			} else {
				delete_post_meta( $menu_item_id, 'dvs_division' );
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
	};
}


if(class_exists('TN_Divisions_Plugin'))
{
	// instantiate the plugin class
	$tn_divisions_plugin = new TN_Divisions_Plugin();
}
