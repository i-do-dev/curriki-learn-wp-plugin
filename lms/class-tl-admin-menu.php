<?php

/**
 * Class TL_Admin_Menu
 */

class TL_Admin_Menu 
{
    /**
	 * Array of submenu items.
	 *
	 * @var array
	 */
	protected $menu_items = array();

    /**
     * @var null
     */
   protected static $_instance = null;


    public function __construct() {
		//add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    }

    /**
    * Get Instance
    */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
	 * Register for menu for admin
	 */
	public function admin_menu() {
		// add_menu_page(
		// 	__( 'Learning Management System', 'tinylms' ),
		// 	'Tiny LMS',
		// 	'manage_options',
		// 	'tiny_lms',
		// 	'',
		// 	'dashicons-welcome-learn-more',
		// 	'3.14'
		// );
    }
}

add_action('admin_menu', 'add_custom_post_type_menu');
function add_custom_post_type_menu() {
    add_menu_page(
        'LXP Dashboard', // Page title
        'LXP Dashboard', // Menu title
        'manage_options', // Capability
        '../dashboard', // Menu slug
        '', // Callback function (optional, leave empty if not needed)
        'dashicons-admin-post', // Icon (optional)
        25 // Position in the menu
    );
}
