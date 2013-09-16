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
					array(&$this, 'init'));
			add_action(
					'wp_edit_nav_menu_walker',
					array( &$this, 'edit_nav_menu_walker' ) );
			add_action(
					'wp_update_nav_menu_item',
					array( &$this, 'update_nav_menu_item' ), 10, 3 );

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

		public function theme_mod_header_image_filter($args)
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
			return $args;
		}

		public function theme_mod_nav_menu_locations_filter($args)
		{
			if (is_admin()) return $args;
			$replaced = get_post_meta(
				$this->get_current_division(), 
				dvs_Constants::DIVISION_REPLACED_NAV_MENUS_OPTION, 
				TRUE);
			if ($replaced=="") $replaced=array();
			foreach ($replaced as $name) {
				$menu_id = $args[$name . '_division_' . $this->get_current_division()];
				$args[$name] = $menu_id;
			}
			return $args;
		}

		public function term_link_filter($url)
		{
			return add_query_arg(
				dvs_Constants::QUERY_ARG_NAME_DIVISION,
				$this->get_current_division(),
				$url);
		}

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

		public function post_link_filter($permalink_url, $post_data)  {
			return add_query_arg(
				dvs_Constants::QUERY_ARG_NAME_DIVISION,
				$this->get_current_division(),
				$permalink_url);
		}

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

		public function register_sidebars()
		{
			global $wp_registered_sidebars;
			$this->original_sidebars = $wp_registered_sidebars;
			$divisions = $this->get_divisions();
			foreach ($divisions as $division)
			{
				$replaced = get_post_meta(
					$division->ID, 
					dvs_Constants::DIVISION_REPLACED_SIDEBARS_OPTIONS, 
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

		public function plugin_action_links_filter($links) {
			$settings_link =
				'<a href="'
				. get_bloginfo('wpurl')
				. '/wp-admin/admin.php?page=tn_divisions_plugin">Settings</a>';
			array_unshift($links, $settings_link);
			return $links;
		}

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

		function edit_nav_menu_walker( $walker ) {
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
		 * Save post meta. Menu items are just posts of type "menu_item".
		 *
		 *
		 * @param type $menu_id
		 * @param type $menu_item_id
		 * @param type $args
		 */
		function update_nav_menu_item($menu_id, $menu_item_id, $args) {
			
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

		public function sidebars_widgets_filter($sidebar_widgets)
		{
			if (is_admin() || $this->current_division==NULL)
					return $sidebar_widgets;
			$replaced = get_post_meta(
				$this->get_current_division(), 
				dvs_Constants::DIVISION_REPLACED_SIDEBARS_OPTIONS, TRUE);
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
