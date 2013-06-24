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
			echo 'HALLLO';
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
			if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			{
				return;
			}

			if( $_POST['post_type'] == self::POST_TYPE
					&& current_user_can('edit_post', $post_id))
			{
				foreach(array('meta a', 'meta b', 'meta c') as $field_name)
				{
					update_post_meta(
						$post_id,
						$field_name,
						$_POST[$field_name]);
				}
			}
			else
			{
				return;
			}
		}

		public static function admin_init()
		{
			add_action('add_meta_boxes', array(__CLASS__, 'add_meta_boxes'));
		}

		public static function add_meta_boxes()
		{
			// Add this metabox to every selected post
			add_meta_box(
				sprintf('wp_plugin_template_%s_section', self::POST_TYPE),
				sprintf('%s Information', self::POST_NAME),
				array(__CLASS__, 'add_inner_meta_boxes'),
				self::POST_TYPE
			);
		}

		public static function add_inner_meta_boxes($post)
		{
			include(sprintf(
				"%s/../templates/%s_metabox.php",
				dirname(__FILE__),
				self::POST_TYPE));
		}

		public static function meta_box_callback()
		{
			echo '<style>#edit-slug-box{display:none;}</style>';
			#remove_meta_box('submitdiv', self::POST_TYPE, 'side');
			remove_meta_box('slugdiv', self::POST_TYPE, 'normal');
		}
	};
}

?>