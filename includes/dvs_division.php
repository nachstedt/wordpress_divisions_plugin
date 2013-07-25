<?php
if(!class_exists('dvs_Division'))
{

	class dvs_Division
	{
		const POST_TYPE ="dvs_division";
		const POST_NAME ="Division";

		public static function register_hooks()
		{
			add_action('init', array(__CLASS__, 'init'));
			add_action('admin_init', array(__CLASS__, 'admin_init'));
		}

		public static function init()
		{
			self::create_post_type();
			add_action('save_post', array(__CLASS__, 'save_post'));
		}

		public static function create_post_type()
		{
			register_post_type(
				self::POST_TYPE,
				array(
					'labels'              => array(
						'name' => __( self::POST_NAME .'s' ),
						'singular_name' => __( self::POST_NAME )),
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
			if(!$_POST['post_type'] == self::POST_TYPE) return;
			if(!current_user_can('edit_post', $post_id)) return;

			update_post_meta(
				$post_id,
				'replaced_nav_menus',
				$_POST['replaced_nav_menus']);
			update_post_meta(
				$post_id,
				'replaced_sidebars',
				$_POST['replaced_sidebars']);
		}

		public static function admin_init()
		{
			add_action('add_meta_boxes', array(__CLASS__, 'add_meta_boxes'));
		}

		public static function add_meta_boxes()
		{
			add_meta_box(
				self::POST_NAME ."_navigation_menus",
				'Individual Navigation Menu Locations',
				array(__CLASS__, 'render_nav_menus_metabox'),
				self::POST_TYPE
			);
			add_meta_box(
				self::POST_NAME . '_sidebars',
				'Individual Sidebars',
				array(__CLASS__, 'render_sidebars_metabox'),
				self::POST_TYPE
			);
		}

		public static function render_nav_menus_metabox($post)
		{
			global $tn_divisions_plugin;
			$locations = $tn_divisions_plugin->original_nav_menu_locations;
			$replaced_nav_menus = get_post_meta(
					$post->ID, 'replaced_nav_menus',true);
			if ($replaced_nav_menus=='') $replaced_nav_menus=array();
                        include(dirname(__FILE__) . "/../templates/nav_menus_metabox.php");
		}

		public static function render_sidebars_metabox($post)
		{
			global $tn_divisions_plugin;
			$sidebars = $tn_divisions_plugin->original_sidebars;
			$replaced_sidebars = get_post_meta(
					$post->ID, 'replaced_sidebars', true);
			if ($replaced_sidebars=='') $replaced_sidebars=array();
                        include(dirname(__FILE__) . "/../templates/sidebars_metabox.php");
		}

		public static function meta_box_callback()
		{
			#echo '<style>#edit-slug-box{display:none;}</style>';
			#remove_meta_box('submitdiv', self::POST_TYPE, 'side');
			remove_meta_box('slugdiv', self::POST_TYPE, 'normal');
		}
	};
}
