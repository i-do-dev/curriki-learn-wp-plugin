<?php

/**
 * Class TL_Post_Type
 * 
 * @author Waqar Muneer
 * @version 1.0
 */
define( 'LMS__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once( LMS__PLUGIN_DIR . 'lms-rest-apis/lms-rest-api.php' );
 abstract class TL_Post_Type {
    /**
	 * Type of post
	 *
	 * @var string
	 */
	protected $_post_type = '';

    /**
	 * Columns display on list table
	 *
	 * @var array
	 */
	protected $_columns = array();

	/**
	 * Metaboxes registered
	 *
	 * @var array
	 */
	protected $_meta_boxes = array();

    /**
	 * @var array
	 */
	protected $_default_metas = array();

    /**
	 * Constructor
	 *
	 * @param string
	 * @param mixed
	 */
	
	public function __construct( $post_type = '', $args = '' ) {

        if ( ! empty( $post_type ) ) {
			$this->_post_type = $post_type;
			
		}
		add_action( 'init', array( $this, 'register' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_tl_post' ), 10, 2 );
		// add_action( 'the_content', array( $this, 'tl_post_content' ));
		add_action( 'rest_'.$this->_post_type.'_query', array( $this, 'post_meta_request_params' ),10, 2 );
		add_action( 'rest_insert_'.$this->_post_type, array( $this, 'insert_post_api' ),10, 2 );
		add_action( 'rest_api_init', array( 'LMS_REST_API', 'init' ) );
		add_filter( 'post_row_actions', array( $this, 'modify_list_row_actions' ), 10, 2 );
		add_action( 'admin_menu', array($this, 'register_views' ));	
		add_action( 'post_edit_form_tag', array($this, 'update_edit_form' ));	
    }

    /**
	 * This function is invoked along with 'init' action to register
	 * new post type with WP.
	 */
	public function register() {
		$args = $this->args_register_post_type();
		if ( $args ) {
			register_post_type( $this->_post_type, $args );
			// flush_rewrite_rules();
		}
	}

    /**
	 * Args to register custom post type.
	 *
	 * @return array
	 */
	public function args_register_post_type() : array {
		return array();
	}

	/**
	 * Add Metabox.
	 *
	 * @return void
	 */
	public function add_meta_box($args = array()) {
		if (is_array($args) && !empty($args)) {
			call_user_func_array('add_meta_box', $args);
		}
	}

	
	/**
	 * Add Metaboxs.
	 *
	 * @return void
	 */
	
	public function add_meta_boxes () {}

	public function save_tl_post () {}
	
	public function tl_post_content () {}

	public function update_edit_form () {}
	

}