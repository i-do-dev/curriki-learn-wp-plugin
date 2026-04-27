<?php

/**
 * Class TL_Group_Post_Type
 * 
 * @author Waqar Muneer
 * @version 1.0
 */
require_once(ABSPATH . 'wp-admin/includes/file.php');
class TL_Group_Post_Type extends TL_Post_Type
{
   /**
    * @var null
    */
   protected static $_instance = null;

   /**
    * @var string
    */
   protected $_post_type = TL_GROUP_CPT;

   /**
    * Get Instance
    */
   public static function instance()
   {
      if (is_null(self::$_instance)) {
         self::$_instance = new self();
      }

      return self::$_instance;
   }

   /**
    * Constructor
    */
   public function __construct()
   {
      parent::__construct();
   }

   /**
    * Register course post type.
    */
   public function args_register_post_type(): array
   {

      $labels           =     array(
         'name'               => _x('LXP Group', 'Post Type General Name', 'tinylms'),
         'singular_name'      => _x('LXP Group', 'Post Type Singular Name', 'tinylms'),
         'menu_name'          => __('LXP Groups', 'tinylms'),
         'parent_item_colon'  => __('Parent Item:', 'tinylms'),
         'all_items'          => __('LXP Groups', 'tinylms'),
         'view_item'          => __('View LXP Group', 'tinylms'),
         'add_new_item'       => __('Add New LXP Group', 'tinylms'),
         'add_new'            => __('Add New', 'tinylms'),
         'edit_item'          => __('Edit LXP Group', 'tinylms'),
         'update_item'        => __('Update LXP Group', 'tinylms'),
         'search_items'       => __('Search LXP Group', 'tinylms'),
         'not_found'          => sprintf(__('You haven\'t had any Group yet. Click <a href="%s">Add new</a> to start', 'tinylms'), admin_url('post-new.php?post_type=tl_group')),
         'not_found_in_trash' => __('No LXP Group found in Trash', 'tinylms'),
      );

      $args = array(
         'labels'             => $labels,
         'public'             => true,
         'query_var'          => true,
         'publicly_queryable' => true,
         'show_ui'            => true,
         'has_archive'        => true,
         'show_in_menu'       => true,
         'show_in_admin_bar'  => true,
         'show_in_nav_menus'  => true,
         'rewrite'            => array(
            'slug'       => 'group',
            'with_front' => false
         ),
         'show_in_rest'       => true,
         'rest_base'          => 'tl_group',
         'supports' => array('title', 'editor', 'thumbnail')
      );
      add_theme_support('post-thumbnails');
      return $args;
   }

   function post_meta_request_params($args, $request)
   {
      $args += array(
         'meta_key'   => $request['meta_key'],
         'meta_value' => $request['meta_value'],
         'meta_query' => $request['meta_query'],
      );
      return $args;
   }

   public function update_edit_form()
   {
      echo ' enctype="multipart/form-data"';
   }

   public function save_tl_post($post_id = null)
   {

   }


   public function insert_post_api($post, $request)
   {
   }

   function modify_list_row_actions($actions, $post)
   {
      return $actions;
   }

   public  function grade_view()
   {
   }

   public  function grade_book_view()
   {
   }

   public function register_views()
   {
   }
}
