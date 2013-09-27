<?php

# code taken from: http://changeset.hr/blog/code/wordpress-menu-item-meta-fields

if ( !class_exists( "Divisions_Walker_Nav_Menu_Edit" )
		&& class_exists( 'Walker_Nav_Menu_Edit' ) )
{
	require_once(TN_DIVISIONS_INCLUDE_DIR . 'dvs_constants.php');

	class Divisions_Walker_Nav_Menu_Edit extends Walker_Nav_Menu_Edit {

		function start_el(&$output, $item, $depth=0, $args=array(),
				$current_object_id=0)
		{

			// append next menu element to $output
			parent::start_el($output, $item, $depth, $args);


			// now let's add a custom form field

			if ( ! class_exists( 'phpQuery') ) {
				// load phpQuery at the last moment, to minimise chance of conflicts (ok, it's probably a bit too defensive)
				require_once(TN_DIVISIONS_INCLUDE_DIR . 'phpQuery-onefile.php');
			}

			$_doc = phpQuery::newDocumentHTML( $output );
			$_li = phpQuery::pq( 'li.menu-item:last' ); // ":last" is important, because $output will contain all the menu elements before current element

			// if the last <li>'s id attribute doesn't match $item->ID something is very wrong, don't do anything
			// just a safety, should never happen...
			$menu_item_id = str_replace( 'menu-item-', '', $_li->attr( 'id' ) );
			if( $menu_item_id != $item->ID ) {
				return;
			}

			// fetch previously saved meta for the post (menu_item is just a post type)
			$division_enabled = esc_attr( get_post_meta( $menu_item_id, dvs_Constants::NAV_MENU_DIVSION_ENABLED_OPTION, TRUE ) );
			$chosen_division = esc_attr( get_post_meta( $menu_item_id, dvs_Constants::NAV_MENU_DIVISION_OPTION, TRUE ) );

			global $tn_divisions_plugin;
			$divisions = $tn_divisions_plugin->get_divisions();
			$options = '<option value="-1" '
				. ($chosen_division == '-1' ? 'selected' : '') . '>'
				.'-- No Division --</option>';
			foreach ($divisions as $division)
			{
				$selected = $division->ID == $chosen_division ? " selected" : "";
				$options = $options
					. "<option value='{$division->ID}' $selected>"
						. $division->post_title
					. "</option>";
			}


			// by means of phpQuery magic, inject a new input field
			$_li->find( '.field-link-target' )
				->after(
					"<p class='description'>"
						."<label for='edit-menu-item-division-enabled-$menu_item_id'>"
							."<input type='checkbox' "
									."id='edit-menu-item-division-enabled-$menu_item_id' "
									."name='menu-item-division-enabled[$menu_item_id]' "
									. ($division_enabled ? "checked" : "")
									.">"
								." Attach fixed division to this link: "
							."</input>"
						."</label>"
						."<select "
								."id='edit-menu-item-division-$menu_item_id' "
								."name='"
									. dvs_Constants::NAV_MENU_DIVISION_EDIT_NAME
									. "[$menu_item_id]'>"
							.$options
						."</select>"
						.'</p>');
			// swap the $output
			$output = $_doc->html();
		}
	}
}
