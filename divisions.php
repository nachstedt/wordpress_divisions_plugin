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
	require_once(sprintf("%s/includes/dvs_constants.php", dirname(__FILE__)));

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
			add_action(
					'init',
					array(&$this, 'init_hook'));
			add_action(
					'wp_update_nav_menu_item',
					array( &$this, 'wp_update_nav_menu_item_hook' ), 10, 3 );

			// register filters
			add_filter(
				'post_link',
				array(&$this, 'post_link_filter'), 1, 2);
			add_filter(
				'plugin_action_links_' . plugin_basename(__FILE__),
				array(&$this, 'plugin_action_links_filter'));
			add_filter(
					'wp_edit_nav_menu_walker',
					array( &$this, 'wp_edit_nav_menu_walker_filter' ) );
			add_filter(
				'wp_setup_nav_menu_item',
				array(&$this, 'setup_nav_menu_item_filter'));
			add_filter(
				'theme_mod_nav_menu_locations',
				array(&$this, 'theme_mod_nav_menu_locations_filter'));
			add_filter(
				'theme_mod_header_image',
					array(&$this, 'theme_mod_header_image_filter'));
			add_filter(
				'wp_nav_menu_objects',
				array($this, "nav_menu_objects_filter"));
			add_filter(
				'term_link',
				array($this, 'term_link_filter'));
			add_filter(
				'sidebars_widgets',
				array($this, 'sidebars_widgets_filter'));
		}

		/**
		 * hook into WP's init hook
		 */
		public function init_hook()
		{
			$this->register_sidebars();
			if (is_admin())
			{
				$this->register_nav_menu_locations();
			}
			else
			{
				$this->load_current_division();
			}
		}

		/**
		 * Filter frontend output of navigtation menu items
		 *
		 * This filter adds the divisions query argument to the URL the
		 * navigation menu items link to.
		 *
		 * @param object $menu_item The menu item to modify
		 * @return object The modified menu item
		 */
		public function setup_nav_menu_item_filter($menu_item)
		{
			if (is_admin()) return $menu_item;
			$division_enabled = esc_attr( get_post_meta(
				$menu_item->ID,
				dvs_Constants::NAV_MENU_DIVSION_ENABLED_OPTION,
				TRUE ) );
			$chosen_division = esc_attr( get_post_meta(
				$menu_item->ID,
				dvs_Constants::NAV_MENU_DIVISION_OPTION,
				TRUE ) );
			if ($division_enabled)
			{
				// chosen_division <0 means "no division"
				if ($chosen_division <0 ) return $menu_item;
				$division = $chosen_division;
			}
			else
			{
				$division = $this->get_current_division();
			}
			$menu_item->url = add_query_arg(
				dvs_Constants::QUERY_ARG_NAME_DIVISION,
				$division,
				$menu_item->url);
			if ($division_enabled && $division==$this->get_current_division())
				$menu_item->classes[] =
					dvs_Constants::CSS_CLASS_MENU_ITEM_CURRENT_DIVISION;
			return $menu_item;
		}

		/**
		 * Filter the URL of the header image
		 *
		 * This filter replaces the url of the header image if the current division
		 * is configured accordingly.
		 *
		 * @param string $url original header image url
		 * @return string modified header image url
		 */
		public function theme_mod_header_image_filter($url)
		{
			$option = get_post_meta(
				$this->get_current_division(),
				dvs_Constants::HEADER_IMAGE_MODE_OPTION,
				TRUE);
			if ($option==dvs_Constants::HEADER_IMAGE_MODE_NO_IMAGE) return "";
			if ($option==dvs_Constants::HEADER_IMAGE_MODE_REPLACE)
				return get_post_meta(
					$this->get_current_division (),
					dvs_Constants::HEADER_IMAGE_URL_OPTION,
					TRUE);
			return $url;
		}

		/**
		 * Filter the mapping from nav menu location to menu
		 *
		 * For frontend users, this replaces the original navigation menu for a
		 * given location with the individual one of the current division.
		 *
		 * @param array $menus Array with elements [location]=>menu_id
		 * @return array modified menu location array
		 */
		public function theme_mod_nav_menu_locations_filter($menus)
		{
			if (is_admin()) return $menus;
			$replaced = get_post_meta(
				$this->get_current_division(),
				dvs_Constants::DIVISION_REPLACED_NAV_MENUS_OPTION,
				TRUE);
			if ($replaced=="") $replaced=array();
			foreach ($replaced as $name) {
				$menu_id = $menus[$name . '_division_' . $this->get_current_division()];
				$menus[$name] = $menu_id;
			}
			return $menus;
		}

		/**
		 * Filter permalinks for taxonomy archives
		 *
		 * This filter adds a querz arg to the generated link to maintain the
		 * current division.
		 *
		 * @param string $url original link url
		 * @return string modified link url
		 */
		public function term_link_filter($url)
		{
			return add_query_arg(
				dvs_Constants::QUERY_ARG_NAME_DIVISION,
				$this->get_current_division(),
				$url);
		}

		/**
		 * Load the current division
		 *
		 * This method determines the current division based on the submitted query
		 * url and stores the division id into the current_division property.
		 *
		 */
		public function load_current_division() {
			if (array_key_exists(dvs_Constants::QUERY_ARG_NAME_DIVISION, $_GET))
				$id =  $_GET[dvs_Constants::QUERY_ARG_NAME_DIVISION];
			else
				$id = "0";
			if (get_post_type($id) == dvs_Constants::DIVISION_POST_TYPE
					&& get_post_status($id) == 'publish') {
				$this->current_division = get_post($id);
			} else {
				$divisions = get_posts(array(
					'post_type'      => dvs_Constants::DIVISION_POST_TYPE,
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'paged'          => 0,
					'orderby'        => 'ID',
					'order'          => 'ASC',
				));
				$this->current_division = $divisions[0];
			};
		}

		/**
		 * Return an array of all available divisions
		 *
		 * This method obtains all divisions from the database and buffers this
		 * list for future requests.
		 *
		 * @return array Array containing all divisions
		 */
		public function get_divisions()
		{
			if ( !isset($this->divisions) )
			{
				$this->divisions = get_posts(array(
					'post_type'      => dvs_Constants::DIVISION_POST_TYPE,
					'post_status'    => 'publish',
					'orderby'        => 'post_title',
					'order'          => 'ASC',
				));
			}
			return $this->divisions;
		}

		/**
		 * Filter links to posts
		 *
		 * This method filters links to posts in page and adds the current division
		 * as query argument
		 *
		 * @param string $permalink_url original post link url
		 * @param array $post_data meta data of the linke post
		 * @return string modified link url
		 */
		public function post_link_filter($permalink_url, $post_data)  {
			return add_query_arg(
				dvs_Constants::QUERY_ARG_NAME_DIVISION,
				$this->get_current_division(),
				$permalink_url);
		}

		/**
		 * Filters frontend output of navigantion menu items
		 *
		 * This filter removes the css classes indicating the currently chosen
		 * menu item in case the menu item is attached to a specific division which
		 * is different from the current one.
		 *
		 * @param array $items Array of menu items and its properties
		 * @return array Modified array of menu items
		 */
		public function nav_menu_objects_filter($items)
		{
			foreach ($items as $item) {
				if (in_array("current-menu-item", $item->classes)) {
					$division_enabled = esc_attr(
						get_post_meta(
							$item->ID,
							dvs_Constants::NAV_MENU_DIVSION_ENABLED_OPTION,
							TRUE));
					$chosen_division = esc_attr(
						get_post_meta(
							$item->ID,
							dvs_Constants::NAV_MENU_DIVISION_OPTION,
							TRUE ) );
					if ($division_enabled
							&& $chosen_division != $this->get_current_division())
					{
						$item->classes = array_diff(
							$item->classes,
							array(
								"current-menu-item",
								'page_item',
								'page_item-' . $item->object_id,
								'current_page_item'));
					}
				}
			}
			return $items;
		}

		/**
		 * Registers additional navigation menu locations
		 *
		 * This method registers the additional navigation menu locations based
		 * on the selection of navigation menus to replace for all the divisions.
		 * This method only needs to be called for the admin (backend) interface.
		 *
		 */
		public function register_nav_menu_locations()
		{
			$this->original_nav_menu_locations = get_registered_nav_menus();

			$originals = $this->original_nav_menu_locations;
			$divisions = $this->get_divisions();
			foreach ($divisions as $division)
			{
				$replaced = get_post_meta(
					$division->ID,
					dvs_Constants::DIVISION_REPLACED_NAV_MENUS_OPTION,
					True);
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

		/**
		 * Registers additional sidebars
		 *
		 * Based on the settings for all the divisions, this method registers the
		 * additional sidebars used to replaced the original one.
		 *
		 * @global array $wp_registered_sidebars Array containing all registered
		 *                                       sidebars
		 *
		 */
		public function register_sidebars()
		{
			global $wp_registered_sidebars;
			$this->original_sidebars = $wp_registered_sidebars;
			$divisions = $this->get_divisions();
			foreach ($divisions as $division)
			{
				$replaced = get_post_meta(
					$division->ID,
					dvs_Constants::DIVISION_REPLACED_SIDEBARS_OPTION,
					True);
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

		/**
		 * Adds settings link to the plugin information shown on the plugin site
		 *
		 * @param array $links Original list of links to display
		 * @return array Extended list of links to display
		 */
		public function plugin_action_links_filter($links) {
			$settings_link =
				'<a href="'
				. get_bloginfo('wpurl')
				. '/wp-admin/admin.php?page=tn_divisions_plugin">Settings</a>';
			array_unshift($links, $settings_link);
			return $links;
		}

		/**
		 * Return the id of the currently active division
		 *
		 * @return int index of current division or -1 if no division
		 */
		public function get_current_division() {
			if (array_key_exists(dvs_Constants::QUERY_ARG_NAME_DIVISION, $_GET))
			{
				return $_GET[dvs_Constants::QUERY_ARG_NAME_DIVISION];
			}
			else
			{
				return -1;
			}
		}

		/**
		 * Filter the used navigation menu walker
		 *
		 * This method modifies the walker used to display the navigation menu
		 * in the admin interface (Menu Editor). It replaces the original walker
		 * with a custom one that includes selections for a specific division for
		 * every menu item.
		 *
		 * @param string $walker Class name of the original walker
		 * @return string Class name of the modified walker
		 */
		function wp_edit_nav_menu_walker_filter( $walker ) {
			// swap the menu walker class only if it's the default wp class (just in
			// case)
			if ( $walker === 'Walker_Nav_Menu_Edit' ) {
				require_once
					WP_PLUGIN_DIR
					. '/divisions/includes/divisions_walker_nav_menu_edit.php';
				$walker = 'Divisions_Walker_Nav_Menu_Edit';
			}
			return $walker;
		}


		/**
		 * Save additional meta data of menu items
		 *
		 * This method hooks into the saving of menu items in the admin interface
		 * and implements the logic to store the division specific options for every
		 * mentu item
		 *
		 * @param int $menu_id Id of the menu the item is located in
		 * @param int $menu_item_id Id of the menu item
		 * @param array $args Additional data for the menu_item
		 */
		function wp_update_nav_menu_item_hook($menu_id, $menu_item_id, $args) {

			$division_enabled = isset(
				$_POST[dvs_Constants::NAV_MENU_DIVSION_ENABLED_OPTION ][$menu_item_id]);
			update_post_meta(
				$menu_item_id,
				dvs_Constants::NAV_MENU_DIVSION_ENABLED_OPTION,
				$division_enabled );

			if ( isset(
					$_POST[dvs_Constants::NAV_MENU_DIVISION_EDIT_NAME ][$menu_item_id] ) )
			{
				update_post_meta(
					$menu_item_id,
					dvs_Constants::NAV_MENU_DIVISION_OPTION,
					$_POST[dvs_Constants::NAV_MENU_DIVISION_EDIT_NAME ][$menu_item_id] );
			}
			else
			{
				delete_post_meta(
					$menu_item_id,
					dvs_Constants::NAV_MENU_DIVISION_OPTION );
			}
		}

		/**
		 * Modify the widgets displayed in the sidebar.
		 *
		 * This method replaces the widgets attached to the sidebars by the
		 * counterparts attached to the correspondig replacement. THis makes only
		 * sense for the frontend interface
		 *
		 * @param array $sidebar_widgets Original array of sidebar widgets
		 * @return array Modified array of sidebar widgets
		 *
		 */
		public function sidebars_widgets_filter($sidebar_widgets)
		{
			if (is_admin() || $this->current_division==NULL)
					return $sidebar_widgets;
			$replaced = get_post_meta(
				$this->get_current_division(),
				dvs_Constants::DIVISION_REPLACED_SIDEBARS_OPTION, TRUE);
			if (empty($replaced)) $replaced = array();
			foreach ($replaced as $sidebar)
				$sidebar_widgets[$sidebar] =
					$sidebar_widgets[$sidebar . "_" . $this->get_current_division()];
			return $sidebar_widgets;
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
