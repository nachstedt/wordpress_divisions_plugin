<?php

if ( !class_exists( "dvs_Constants" ) )
{
	class dvs_Constants extends Walker_Nav_Menu_Edit {
		
		const DIVISION_REPLACED_NAV_MENUS_OPTION = 'replaced_nav_menus';
		const DIVISION_POST_TYPE = 'dvs_division';
		
		const NAV_MENU_DIVSION_ENABLED_OPTION = 'dvs_division_enabled';
		const NAV_MENU_DIVISION_OPTION = 'dvs_division';

		const HEADER_IMAGE_MODE_OPTION = 'dvs_header_image_option';
		const HEADER_IMAGE_MODE_USE_DEFAULT = 'use_default';
		const HEADER_IMAGE_MODE_NO_IMAGE = 'none';
		const HEADER_IMAGE_MODE_REPLACE = 'replace';
		const HEADER_IMAGE_URL_OPTION = 'dvs_header_image_url';

		const QUERY_ARG_NAME_DIVISION = 'division';
		
		const CSS_CLASS_MENU_ITEM_CURRENT_DIVISION = 'current-division-item';
		
	};
}
?>
