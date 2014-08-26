<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of link_modification
 *
 * @author timo
 */
class dvs_LinkModification {

	const TRANSIENT_REWRITE_FLUSH_SCHEDULED = "dvs_rewrite_flush_scheduled";

	private static $during_deactivation = False;

	public static function register_hooks()
	{
		if (is_admin())
		{
			# register actions
			add_action('admin_init', array(__CLASS__, 'admin_init_hook'));
			add_action('wp_trash_post', array(__CLASS__, 'wp_trash_post_hook'));
			add_action('wp_untrash_post', array(__CLASS__, 'wp_untrash_post_hook'));

			add_filter(
				'rewrite_rules_array',
				array(__CLASS__, 'rewrite_rules_array_filter'));
			add_filter(
				'wp_insert_post_data',
				array(__CLASS__, 'wp_insert_post_data_filter'),
				'99', 2);

			register_activation_hook(
				TN_DIVISIONS_PLUGIN_FILE,
				array(__CLASS__, 'activation_hook'));
			register_deactivation_hook(
				TN_DIVISIONS_PLUGIN_FILE,
				array(__CLASS__, 'deactivation_hook'));
		}
		else
		{
			add_filter(
				'home_url',
				array(__CLASS__, 'home_url_filter'));
			add_filter(
				'page_link',
				array(__CLASS__, 'page_link_filter'));
			add_filter(
				'post_link',
				array(__CLASS__, 'post_link_filter'), 1, 2);			
		}
	}

	public static function activation_hook()
	{
		if (dvs_Settings::get_use_permalinks())
		{
			flush_rewrite_rules();
		}
	}


	/**
	 * hook into WP's admin_init action hook
	 */
	public static function admin_init_hook()
	{
	    if (delete_transient(self::TRANSIENT_REWRITE_FLUSH_SCHEDULED))
		{
			flush_rewrite_rules();
		}
	}

	public static function add_division_to_url($url, $division_id)
	{
		if (dvs_Settings::get_use_permalinks()
			&& get_option('permalink_structure'))
		{
			$site_url = get_site_url();
			$post_data = get_post($division_id, ARRAY_A);
			$slug = $post_data['post_name'];
			return $site_url
				. '/' . $slug
				. substr($url, strlen($site_url));
		}
		else
		{
			return add_query_arg(
				dvs_Constants::QUERY_ARG_NAME_DIVISION,
				$division_id,
				$url);
		}
	}

	public static function deactivation_hook()
	{
		if (dvs_Settings::get_use_permalinks())
		{
			self::$during_deactivation = TRUE;
			flush_rewrite_rules();
		}
	}
	
	public static function home_url_filter($permalink_url)
	{
		global $tn_divisions_plugin;
		$current_division = $tn_divisions_plugin->get_current_division();
		if ($current_division==NULL) 
		{
			return $permalink_url;
		}
		return self::add_division_to_url(
			$permalink_url,
			$current_division->get_id());
	}
  
  public static function page_link_filter($permalink_url)
	{
		global $tn_divisions_plugin;
		$current_division = $tn_divisions_plugin->get_current_division();
		if ($current_division==NULL) 
		{
			return $permalink_url;
		}
		return self::add_division_to_url(
			$permalink_url,
			$current_division->get_id());
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
	public static function post_link_filter($permalink_url, $post_data)  {
		global $tn_divisions_plugin;
		$current_division = $tn_divisions_plugin->get_current_division();
		if ($current_division==NULL) 
		{
			return $permalink_url;
		}
		return self::add_division_to_url(
			$permalink_url,
			$current_division->get_id());
	}		
	
	public static function remove_division_from_url($url)
	{
		if (dvs_Settings::get_use_permalinks() 
				&& get_option('permalink_structure'))
		{
			$site_url = get_site_url();
			$relative_url = substr($url, strlen($site_url)+1);
			$first_slash = strpos($relative_url, '/');
			if ($first_slash)
			{
				return $site_url . substr($relative_url, $first_slash);
			}
			else 
			{
				return $url;
			}
		}
		else
		{
			return add_query_arg(
				dvs_Constants::QUERY_ARG_NAME_DIVISION,
				FALSE,
				$url);
		}

		return $url;
	}
	
	public static function replace_division_in_url($url, $division_id)
	{
		return self::add_division_to_url(
				self::remove_division_from_url($url), $division_id);
	}

	public static function rewrite_rules_array_filter($rules)
	{
		if (self::$during_deactivation) {return $rules;}

		$filtered_rules = array();

		// remove dvs_division/* rules
		foreach($rules as $key => $rule)
		{
			if (strpos($key, dvs_Division::POST_TYPE)!==0)
			{
				$filtered_rules[$key] = $rule;
			}
		}

		if (!dvs_Settings::get_use_permalinks()) {return $filtered_rules;}

		$newrules = array();
		$divisions = dvs_Division::get_all();

		// add homepage permalinks
		foreach  ($divisions as $division)
		{
			$url = $division->get_permalink_slug() . "$";
			$rewrite = '/index.php?division=' . $division->get_id();
			$newrules[$url] = $rewrite;
		}

		// add division permalinks for all existing rules
		foreach($filtered_rules as $key => $rule)
		{
			foreach($divisions as $division)
			{
				$url = $division->get_permalink_slug() . '/' . $key;
				$rewrite = $rule . '&division=' . $division->get_id();
				$newrules[$url] = $rewrite;
			}
		}

		return $newrules + $filtered_rules;
	}

	public static function schedule_rewrite_rules_flush()
	{
		set_transient(self::TRANSIENT_REWRITE_FLUSH_SCHEDULED, TRUE);
	}

	public static function wp_insert_post_data_filter($data, $postarr)
	{
		if (($data['post_type'] != dvs_Division::POST_TYPE)
			or ($data['post_status'] != 'publish')
			or (!dvs_Settings::get_use_permalinks()))
		{
			return $data;
		}

		$new_post_name = $data['post_name'];
		$division = new dvs_Division($postarr['ID']);
		$old_post_name = $division->get_permalink_slug();
		if (($new_post_name != $old_post_name))
		{
			self::schedule_rewrite_rules_flush();
		}
		return $data;
	}

	public static function wp_trash_post_hook($id)
	{
		if ((get_post_type($id) != dvs_Division::POST_TYPE)
			or (!dvs_Settings::get_use_permalinks()))
		{
			return;
		}
		self::schedule_rewrite_rules_flush();
	}

	public static function wp_untrash_post_hook($id)
	{
		if ((get_post_type($id) != dvs_Division::POST_TYPE)
			or (!dvs_Settings::get_use_permalinks()))
		{
			return;
		}
		self::schedule_rewrite_rules_flush();
	}
}
