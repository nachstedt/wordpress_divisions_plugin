<?php
/*
Plugin Name: Divisions
Plugin URI: http://www.nachstedt.com/en/divisions-wordpress-plugin-en
Description: Create multiple divisions in your site with individual menus, sidebars and header images. Divisions may easily change share content of all types while maintaining a consistent look.
Version: 0.2.3
Author: Timo Nachstedt
Author URI: http://www.nachstedt.com
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

define('TN_DIVISIONS_PLUGIN_FILE', __FILE__);

if (!defined('TN_DIVISIONS_PLUGIN_BASENAME'))
{
	define('TN_DIVISIONS_PLUGIN_BASENAME', plugin_basename(__FILE__));
}

if (!defined('TN_DIVISIONS_PLUGIN_DIR'))
{
	define('TN_DIVISIONS_PLUGIN_DIR', plugin_dir_path( __FILE__ ));
}

if (!defined('TN_DIVISIONS_INCLUDE_DIR'))
{
	define('TN_DIVISIONS_INCLUDE_DIR', TN_DIVISIONS_PLUGIN_DIR . 'includes/');
}

if (!defined('TN_DIVISIONS_TEMPLATE_DIR'))
{
	define('TN_DIVISIONS_TEMPLATE_DIR', TN_DIVISIONS_PLUGIN_DIR . 'templates/');
}

if (!defined('TN_DIVISIONS_SCRIPT_DIR_URL'))
{
	define('TN_DIVISIONS_SCRIPT_DIR_URL', plugins_url('scripts/', __FILE__));
}


if(!class_exists('TN_Divisions_Plugin'))
{

	require_once(TN_DIVISIONS_INCLUDE_DIR . 'dvs_constants.php');
	require_once(TN_DIVISIONS_INCLUDE_DIR . 'dvs_division.php');
	require_once(TN_DIVISIONS_INCLUDE_DIR . 'dvs_link_modification.php');
	require_once(TN_DIVISIONS_INCLUDE_DIR . 'dvs_settings.php');

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
			dvs_LinkModification::register_hooks();
			dvs_Settings::register_hooks();

			// register actions
			add_action(
					'init',
					array(&$this, 'init_hook'));
			add_action(
					'wp_update_nav_menu_item',
					array( &$this, 'wp_update_nav_menu_item_hook' ), 10, 3 );
			add_action(
					'wp',
					array(&$this, 'wp_hook'));

			// register filters
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
				'sidebars_widgets',
				array($this, 'sidebars_widgets_filter'));
			add_filter(
				'query_vars',
				array($this, 'query_vars_filter'));
		}

		/**
		 * hook into WP's init hook
		 */
		public function init_hook()
		{
			$this->check_for_updates();
			$this->register_sidebars();
			if (is_admin())
			{
				$this->register_nav_menu_locations();
			}
		}

		/**
		 * Check if plugin was updated and migrate plugin data if neccessary
		 *
		 * This method compares the version of the present plugin code with the
		 * version string stored in the database, performs data updates if
		 * neccessary.
		 *
		 */
		public function check_for_updates()
		{
			$my_version = dvs_Constants::VERSION;
			$db_version = get_option(dvs_Constants::DATABASE_VERSION_OPTION, NULL);
			if ($my_version==$db_version)
				return;

			if ($db_version=='0.2.0')
			{
				// in version 0.2.0, when using permalink divisions, there were
				// no rewrite rules added for homepages (e.g.
				// your.site.com/my_division), In case the user has currently
				// enabled permalink link modifications, we need to flush
				// rewrite rules to add the additional rules
				if (dvs_Settings::get_use_permalinks())
				{
					flush_rewrite_rules();
				}
			}
			update_option(dvs_Constants::DATABASE_VERSION_OPTION, $my_version);
		}
		
		public function get_current_division()
		{
			return $this->current_division;
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
			if (is_admin()) {return $menu_item;}

			$division_enabled = esc_attr( get_post_meta(
				$menu_item->ID,
				dvs_Constants::NAV_MENU_DIVSION_ENABLED_OPTION,
				TRUE ) );
			$chosen_division = esc_attr( get_post_meta(
				$menu_item->ID,
				dvs_Constants::NAV_MENU_DIVISION_OPTION,
				TRUE ) );

			$current_division_id = $this->current_division==NULL
				? -1
				: $this->current_division->get_id();

			if ($division_enabled)
			{
				// chosen_division <0 means "no division"
				if ($chosen_division >= 0 && $current_division_id >=0)
				{
					$menu_item->url = 
						dvs_LinkModification::replace_division_in_url(
							$menu_item->url,
							$chosen_division);
				}
				if ($chosen_division >= 0 && $current_division_id < 0)
				{
					$menu_item->url = 
						dvs_LinkModification::add_division_to_url(
							$menu_item->url,
							$chosen_division);
				}
				else
				if ($chosen_division < 0 && $current_division_id >=0)
				{
					$menu_item->url = 
						dvs_LinkModification::remove_division_from_url(
							$menu_item->url);
				}
				if ($chosen_division==$current_division_id)
				{
					$menu_item->classes[] =
						dvs_Constants::CSS_CLASS_MENU_ITEM_CURRENT_DIVISION;
				}
				
			}

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
			if ($this->current_division == NULL) {return $url;}
			$option = $this->current_division->get_header_image_mode();
			if ($option==dvs_Constants::HEADER_IMAGE_MODE_NO_IMAGE) {return "";}
			if ($option==dvs_Constants::HEADER_IMAGE_MODE_REPLACE)
			{
				return $this->current_division->get_header_image_url();
			}
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
			if (is_admin() || $this->current_division==NULL) {return $menus;}
			foreach ($this->current_division->get_replaced_nav_menus() as $name)
			{
				$menu_id = $menus[
					$name . '_division_' . $this->current_division->get_id()];
				$menus[$name] = $menu_id;
			}
			return $menus;
		}

		/**
		 * Load the current division
		 *
		 * This method determines the current division based on the submitted
		 * query and stores the division id into the current_division property.
		 *
		 */
		public function load_current_division() {
			$division_id = get_query_var('division');
			if (!empty($division_id)) {
				$this->current_division = new dvs_Division($division_id);
			}
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
			$current_division_id = $this->current_division==NULL
				? -1
				: $this->current_division->get_id();
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
							&& $chosen_division != $current_division_id)
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
			$divisions = dvs_Division::get_all();
			foreach ($divisions as $division)
			{
				foreach ($originals as $name => $description)
				{
					if (in_array($name, $division->get_replaced_nav_menus())) {
						register_nav_menu(
							$name . '_division_' . $division->get_id(),
							$description . __(" for ") . $division->get_title());
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
			$divisions = dvs_Division::get_all();
			foreach ($divisions as $division)
			{
				$replaced =  $division->get_replaced_sidebars();
				foreach ($this->original_sidebars as $sidebar)
				{
					if (in_array($sidebar['id'], $replaced)){
						register_sidebar(array(
							'name' => "{$sidebar['name']} {$division->get_title()}",
							'id' => "{$sidebar['id']}_{$division->get_id()}",
							'description' => "{$sidebar['description']} "
								. __( "Only displayed when division "
								."{$division->get_title()} is active"),
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
				require_once(
					TN_DIVISIONS_INCLUDE_DIR
					. 'divisions_walker_nav_menu_edit.php');
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
				$_POST
					[dvs_Constants::NAV_MENU_DIVISION_CHECKBOX_NAME]
					[$menu_item_id]);
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
			$replaced = $this->current_division->get_replaced_sidebars();
			foreach ($replaced as $sidebar) {
				$replaced_name = $sidebar . "_" . $this->current_division->get_id();
				if (array_key_exists($replaced_name, $sidebar_widgets))
				{
					$sidebar_widgets[$sidebar] = $sidebar_widgets[$replaced_name];
				}
			}
			return $sidebar_widgets;
		}

		public function query_vars_filter($vars)
		{
			$vars[] = 'division';
			return $vars;
		}

		/**
		 * hook into the point of time when the wp object is set up
		 */
		public function wp_hook()
		{
			if (!is_admin()) {$this->load_current_division();}
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
