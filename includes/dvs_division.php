<?php
if(!class_exists('dvs_Division'))
{

	require_once(sprintf("%s/dvs_constants.php", dirname(__FILE__)));


	class dvs_Division
	{

		public static function register_hooks()
		{
			add_action(
				'init', 
				array(__CLASS__, 'init'));
			add_action(
				'admin_init', 
				array(__CLASS__, 'admin_init'));
			add_action(
				'admin_enqueue_scripts', 
				array(__CLASS__,'admin_enqueue_scripts'));

		}

		public static function init()
		{
			self::create_post_type();
			add_action('save_post', array(__CLASS__, 'save_post'));
		}

		public static function create_post_type()
		{
			register_post_type(
				dvs_Constants::DIVISION_POST_TYPE,
				array(
					'labels'              => array(
						'name' => dvs_Constants::DIVISION_POST_NAME_PLURAL,
						'singular_name' => dvs_Constants::DIVISION_POST_NAME),
					'public'               => true,
					'exclude_from_search'  => true,
					'publicly_queryable'   => false,
					'show_ui'              => true,
					'show_in_nav_menus'    => false,
					'show_in_menu'         => true,
					'has_archive'          => false,
					'supports'             => array(
						'title',),
					'rewrite'              => false,
					'register_meta_box_cb' => array(__CLASS__, 'meta_box_callback')
				)
			);
		}

		public static function save_post($post_id)
		{
			if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
			if(!$_POST['post_type'] == dvs_Constants::DIVISION_POST_TYPE) return;
			if(!current_user_can('edit_post', $post_id)) return;

			update_post_meta(
				$post_id,
				dvs_Constants::DIVISION_REPLACED_NAV_MENUS_OPTION,
				$_POST[dvs_Constants::DIVISION_REPLACED_NAV_MENUS_OPTION]);
			update_post_meta(
				$post_id,
				dvs_Constants::DIVISION_REPLACED_SIDEBARS_OPTION,
				$_POST[dvs_Constants::DIVISION_REPLACED_NAV_MENUS_OPTION]);
			update_post_meta(
				$post_id,
				dvs_Constants::HEADER_IMAGE_MODE_OPTION,
				$_POST[dvs_Constants::HEADER_IMAGE_MODE_OPTION]);
			update_post_meta(
				$post_id,
				dvs_Constants::HEADER_IMAGE_URL_OPTION,
				array_key_exists(dvs_Constants::HEADER_IMAGE_URL_OPTION, $_POST)
							? $_POST[dvs_Constants::HEADER_IMAGE_URL_OPTION]
							: "");
		}

		public static function admin_init()
		{
			add_action('add_meta_boxes', array(__CLASS__, 'add_meta_boxes'));
		}

		public static function admin_enqueue_scripts()
		{
			$screen = get_current_screen();
			if ($screen->base=="post" 
					&& $screen->id == dvs_Constants::DIVISION_POST_TYPE)
			{
				wp_enqueue_media();
				wp_register_script(
					'custom_header_image_upload.js',
					WP_PLUGIN_URL.'/divisions/scripts/custom_header_image_upload.js',
					array('jquery'));
				wp_enqueue_script('custom_header_image_upload.js');
			}
		}

		public static function add_meta_boxes()
		{
			add_meta_box(
				dvs_Constants::REPLACED_NAV_MENUS_METABOX_SLUG,
				dvs_Constants::REPLACED_NAV_MENUS_METABOX_TITLE,
				array(__CLASS__, 'render_nav_menus_metabox'),
				dvs_Constants::DIVISION_POST_TYPE
			);
			add_meta_box(
				dvs_Constants::REPLACED_SIDEBARS_METABOX_SLUG,
				dvs_Constants::REPLACED_SIDEBARS_METABOX_TITLE,
				array(__CLASS__, 'render_sidebars_metabox'),
				dvs_Constants::DIVISION_POST_TYPE
			);
			add_meta_box(
			  dvs_Constants::HEADER_IMAGE_METABOX_SLUG,
				dvs_Constants::HEADER_IMAGE_METABOX_TITLE,
				array(__CLASS__, 'render_header_image_metabox'),
				dvs_Constants::DIVISION_POST_TYPE
			);
		}


		public static function render_nav_menus_metabox($post)
		{
			global $tn_divisions_plugin;
			$locations = $tn_divisions_plugin->original_nav_menu_locations;
			$replaced_nav_menus = get_post_meta(
				$post->ID, 
				dvs_Constants::DIVISION_REPLACED_NAV_MENUS_OPTION,
				true);
			if ($replaced_nav_menus=='') $replaced_nav_menus=array();
			include(dirname(__FILE__) . "/../templates/nav_menus_metabox.php");
		}

		public static function render_sidebars_metabox($post)
		{
			global $tn_divisions_plugin;
			$sidebars = $tn_divisions_plugin->original_sidebars;
			$replaced_sidebars = get_post_meta(
				$post->ID, 
				dvs_Constants::DIVISION_REPLACED_SIDEBARS_OPTION,
				true);
			if ($replaced_sidebars=='') $replaced_sidebars=array();
			include(dirname(__FILE__) . "/../templates/sidebars_metabox.php");
		}

		public static function render_header_image_metabox($post)
		{
			$header_image_option = get_post_meta(
				$post->ID, 
				dvs_Constants::HEADER_IMAGE_MODE_OPTION,
				true);
			$header_image_url = get_post_meta(
				$post->ID, 
				dvs_Constants::HEADER_IMAGE_URL_OPTION, 
				true);
			if (empty($header_image_option)) 
				$header_image_option = dvs_Constants::HEADER_IMAGE_MODE_USE_DEFAULT;
			include(dirname(__FILE__) . "/../templates/header_image_metabox.php");
		}

		public static function meta_box_callback()
		{
			#echo '<style>#edit-slug-box{display:none;}</style>';
			#remove_meta_box('submitdiv', self::POST_TYPE, 'side');
			remove_meta_box('slugdiv', dvs_Constants::DIVISION_POST_TYPE, 'normal');
		}
	};
}
