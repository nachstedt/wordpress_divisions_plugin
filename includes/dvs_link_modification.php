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

	public static function register_hooks()
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

	public static function rewrite_rules_array_filter($rules)
	{
		if (!dvs_Settings::get_use_permalinks()) {return $rules;}

		$newrules = array();
		$divisions = dvs_Division::get_all();

		foreach($rules as $key => $rule)
		{
			foreach($divisions as $division)
			{
				$url = $division->get_permalink_slug() . '/' . $key;
				$rewrite = $rule . '&division=' . $division->get_id();
				$newrules[$url] = $rewrite;
			}
		}

		return $newrules + $rules;
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
