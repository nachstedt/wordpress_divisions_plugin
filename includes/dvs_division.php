<?php
if(!class_exists('dvs_Division'))
{

	require_once(TN_DIVISIONS_INCLUDE_DIR . 'dvs_constants.php');


	class dvs_Division
	{

		const REPLACED_NAV_MENUS_OPTION = 'replaced_nav_menus';
		const REPLACED_SIDEBARS_OPTION = 'replaced_sidebars';
		const POST_TYPE = 'dvs_division';
		const POST_NAME = 'Division';
		const POST_NAME_PLURAL = 'Divisions';


		private $id = NULL;
		private $header_image_mode = NULL;
		private $header_image_url = NULL;
		private $permalink_slug = NULL;
		private $replaced_nav_menus = NULL;
		private $replaced_sidebars = NULL;
		private $title = NULL;

		private static $all_divisions = NULL;

		public function __construct($id) {
			$this->id = $id;
		}

		public function get_id()
		{
			return $this->id;
		}

		public function get_header_image_mode()
		{
			if ($this->header_image_mode == NULL)
			{
				$this->header_image_mode = get_post_meta(
					$this->id,
					dvs_Constants::HEADER_IMAGE_MODE_OPTION,
					TRUE);
				if (empty($this->header_image_mode))
				{
					$this->header_image_mode =
						dvs_Constants::HEADER_IMAGE_MODE_USE_DEFAULT;
				}
			}
			return $this->header_image_mode;
		}

		public function get_header_image_url()
		{
			if ($this->header_image_url ==  NULL)
			{
				$this->header_image_url = get_post_meta(
					$this->id,
					dvs_Constants::HEADER_IMAGE_URL_OPTION,
					TRUE);
			}
			return $this->header_image_url;
		}

		public function get_permalink_slug()
		{
			if ($this->permalink_slug == NULL)
			{
				$this->permalink_slug = get_post($this->id)->post_name;
			}
			return $this->permalink_slug;
		}

		public function get_replaced_nav_menus()
		{
			if ($this->replaced_nav_menus == NULL)
			{
				$this->replaced_nav_menus = get_post_meta(
					$this->id,
					self::REPLACED_NAV_MENUS_OPTION,
					TRUE);
				if ($this->replaced_nav_menus =="")
				{
					$this->replaced_nav_menus=array();
				}
			}
			return $this->replaced_nav_menus;
		}

		public function get_replaced_sidebars()
		{
			if ($this->replaced_sidebars == NULL)
			{
				$this->replaced_sidebars = get_post_meta(
					$this->id,
					self::REPLACED_SIDEBARS_OPTION,
					True);
				if ($this->replaced_sidebars=="") {
					$this->replaced_sidebars = array();
				}
			}
			return $this->replaced_sidebars;
		}

		public function get_title()
		{
			if ($this->title == NULL)
			{
				$this->title = get_the_title($this->id);
			}
			return $this->title;
		}

		public static function get_all()
		{
			if (self::$all_divisions == NULL)
			{
				$posts = get_posts(array(
					'posts_per_page' => -1,
					'post_type'      => self::POST_TYPE,
					'post_status'    => 'publish',
					'orderby'        => 'post_title',
					'order'          => 'ASC',
				));
				self::$all_divisions = array();
				foreach ($posts as $post)
				{
					$division = new dvs_Division($post->ID);
					$division->title = $post->post_title;
					$division->permalink_slug = $post->post_name;
					self::$all_divisions[] = $division;
				}
			}
			return self::$all_divisions;
		}

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
				self::POST_TYPE,
				array(
					'labels'              => array(
						'name'         => __(self::POST_NAME_PLURAL, self::POST_TYPE),
						'singular_name'=> __(self::POST_NAME, self::POST_TYPE),
						'add_new'      => __('Add New', self::POST_TYPE),
						'add_new_item' => __('Add New '. self::POST_NAME, self::POST_TYPE),
						'edit_item'    => __('Edit ' . self::POST_NAME, self::POST_TYPE),
						'new_item'     => __('New ' . self::POST_NAME, self::POST_TYPE),
						'view_item'    => __('View ' . self::POST_NAME, self::POST_TYPE),
						'search_items' => __('Search ' . self::POST_NAME_PLURAL, self::POST_TYPE),
					),
					'public'               => true,
					'exclude_from_search'  => true,
					'publicly_queryable'   => false,
					'show_ui'              => true,
					'show_in_nav_menus'    => false,
					'show_in_menu'         => true,
					'menu_position'        => 100,
					'capabilities'         => array(
						'edit_post'          => 'manage_options',
						'read_post'          => 'manage_options',
						'delete_post'        => 'manage_options',
						'edit_posts'         => 'manage_options',
						'edit_others_posts'  => 'manage_options',
						'publish_posts'      => 'manage_options',
						'read_private_posts' => 'manage_options'
					),
					'has_archive'          => false,
					'supports'             => array(
						'title',),
					'rewrite'              => true,
					'register_meta_box_cb' => array(__CLASS__, 'meta_box_callback')
				)
			);
		}

		public static function save_post($post_id)
		{
			if ((get_post_type($post_id) != self::POST_TYPE)
					or (!current_user_can('edit_post', $post_id))
					or (empty($_POST)))
			{
				return;
			}

			update_post_meta(
				$post_id,
				self::REPLACED_NAV_MENUS_OPTION,
				array_key_exists(
						self::REPLACED_NAV_MENUS_OPTION, $_POST)
					? $_POST[self::REPLACED_NAV_MENUS_OPTION]
					: array());

			update_post_meta(
				$post_id,
				self::REPLACED_SIDEBARS_OPTION,
				array_key_exists(
					self::REPLACED_SIDEBARS_OPTION, $_POST)
					? $_POST[self::REPLACED_SIDEBARS_OPTION]
					: array());

			update_post_meta(
				$post_id,
				dvs_Constants::HEADER_IMAGE_MODE_OPTION,
				$_POST[dvs_Constants::HEADER_IMAGE_MODE_OPTION]);

			update_post_meta(
				$post_id,
				dvs_Constants::HEADER_IMAGE_URL_OPTION,
				$_POST[dvs_Constants::HEADER_IMAGE_URL_OPTION]);
		}

		public static function admin_init()
		{
			add_action('add_meta_boxes', array(__CLASS__, 'add_meta_boxes'));
		}

		public static function admin_enqueue_scripts()
		{
			$screen = get_current_screen();
			if ($screen->base=="post"
					&& $screen->id == self::POST_TYPE)
			{
				wp_enqueue_media();
				wp_register_script(
					'custom_header_image_upload.js',
					TN_DIVISIONS_SCRIPT_DIR_URL
					. 'custom_header_image_upload.js',
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
				self::POST_TYPE
			);
			add_meta_box(
				dvs_Constants::REPLACED_SIDEBARS_METABOX_SLUG,
				dvs_Constants::REPLACED_SIDEBARS_METABOX_TITLE,
				array(__CLASS__, 'render_sidebars_metabox'),
				self::POST_TYPE
			);
			add_meta_box(
			  dvs_Constants::HEADER_IMAGE_METABOX_SLUG,
				dvs_Constants::HEADER_IMAGE_METABOX_TITLE,
				array(__CLASS__, 'render_header_image_metabox'),
				self::POST_TYPE
			);
		}


		public static function render_nav_menus_metabox($post)
		{
			global $tn_divisions_plugin;
			$locations = $tn_divisions_plugin->original_nav_menu_locations;
			$replaced_nav_menus = get_post_meta(
				$post->ID,
				self::REPLACED_NAV_MENUS_OPTION,
				true);
			if ($replaced_nav_menus=='') $replaced_nav_menus=array();
			include(TN_DIVISIONS_TEMPLATE_DIR . 'nav_menus_metabox.php');
		}

		public static function render_sidebars_metabox($post)
		{
			global $tn_divisions_plugin;
			$sidebars = $tn_divisions_plugin->original_sidebars;
			$replaced_sidebars = get_post_meta(
				$post->ID,
				self::REPLACED_SIDEBARS_OPTION,
				true);
			if ($replaced_sidebars=='') $replaced_sidebars=array();
			include(TN_DIVISIONS_TEMPLATE_DIR . 'sidebars_metabox.php');
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
			include(TN_DIVISIONS_TEMPLATE_DIR . 'header_image_metabox.php');
		}

		public static function meta_box_callback()
		{
			#echo '<style>#edit-slug-box{display:none;}</style>';
			#remove_meta_box('submitdiv', self::POST_TYPE, 'side');
			#remove_meta_box('slugdiv', dvs_Constants::DIVISION_POST_TYPE, 'normal');
		}
	};
}
