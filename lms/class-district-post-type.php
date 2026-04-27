<?php

/**
 * Class TL_District_Post_Type
 * 
 * @author Waqar Muneer
 * @version 1.0
 */

class TL_District_Post_Type extends TL_Post_Type
{
   /**
    * @var null
    */
   protected static $_instance = null;

   /**
    * @var string
    */
   protected $_post_type = TL_DISTRICT_CPT;

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
    * Register district post type.
    */
   public function args_register_post_type(): array
   {

      $labels           =     array(
         'name'               => _x('LXP District', 'Post Type General Name', 'tinylms'),
         'singular_name'      => _x('LXP District', 'Post Type Singular Name', 'tinylms'),
         'menu_name'          => __('LXP Districts', 'tinylms'),
         'parent_item_colon'  => __('Parent Item:', 'tinylms'),
         'all_items'          => __('LXP Districts', 'tinylms'),
         'view_item'          => __('View LXP District', 'tinylms'),
         'add_new_item'       => __('Add New LXP District', 'tinylms'),
         'add_new'            => __('Add New', 'tinylms'),
         'edit_item'          => __('Edit LXP District', 'tinylms'),
         'update_item'        => __('Update LXP District', 'tinylms'),
         'search_items'       => __('Search LXP District', 'tinylms'),
         'not_found'          => sprintf(__('You haven\'t had any District yet. Click <a href="%s">Add new</a> to start', 'tinylms'), admin_url('post-new.php?post_type=tl_district')),
         'not_found_in_trash' => __('No LXP District found in Trash', 'tinylms'),
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
            'slug'       => 'district',
            'with_front' => false
         ), 
         'show_in_rest'       => false,
         'rest_base'          => 'tl_district',
         'supports' => array('title', 'editor', 'thumbnail'),
         // 'capabilities' => array(
         //     'edit_post' => 'edit_lxp_district',
         //     'publish_posts' => 'publish_lxp_districts',
         //     'read_post' => 'read_lxp_district',
         //     'read_private_posts' => 'read_private_lxp_districts',
         //     'delete_post' => 'delete_lxp_district',
         //     'delete_posts' => 'delete_lxp_districts',
         //     'create_posts' => 'create_lxp_districts',
         //     'create_post' => 'create_lxp_district'
         // )
      );
      add_theme_support('post-thumbnails');
      return $args;
   }


   public function add_meta_boxes()
   {
      $this->add_meta_box([
         'district-client-admin-id',      // Unique ID
         esc_html__('District Admin ', 'district'),    // Title
         array(self::instance(), 'lxp_client_admin_metabox_html'),   // Callback function
         $this->_post_type,         // Admin page (or post type)
         'advanced',         // Context
         'default',         // Priority
         // 'show_in_rest' => true,
      ]);
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

   public function lxp_client_admin_metabox_html($post = null)
   {
      $args = array(
         'role' => 'lxp_client_admin',
         'order' => 'ASC'
        );
      $clientAdmins = get_users($args);


      $users = get_users(
         array(
            'role' => 'lxp_client_admin',
            'meta_key' => 'lxp_client_admin_id',
            'meta_value' => $post->ID,
            'number' => -1
         )
      );

      // $selectedAdmin = get_post_meta($post->ID, 'lxp_client_admin_id', true);
      $selectedAdmin = isset($users[0]) ? $users[0]->ID : "";
      $output = '<h4>Select District Admin</h4>';

      $output .= '<select name="lxp_client_admin_id"  style="margin-top:-10px"> ';
      $output .= '<option value="0">Select An Admin</option>';
      foreach ($clientAdmins as $admin) {
         if ($selectedAdmin == $admin->ID) {
            $selected = "selected";
         } else {
            $selected = "";
         }
         $output .= '<option value="' . $admin->ID . '" ' . $selected . ' >' . $admin->display_name . ' </option>';
      }
      $output .= '</select>';
      echo $output;
   }

   public function save_tl_post($post_id = null)
   {
      if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_type']) && 'tl_district' == $_POST['post_type']) {
         $users = get_users(
            array(
               'role' => 'lxp_client_admin',
               'meta_key' => 'lxp_client_admin_id',
               'meta_value' => $post_id,
               'number' => -1
            )
         );
         if(isset($users[0]->ID)){
            delete_user_meta($users[0]->ID, 'lxp_client_admin_id');
         }
         if($_POST['lxp_client_admin_id'] != 0){
            update_user_meta($_POST['lxp_client_admin_id'], 'lxp_client_admin_id', $post_id);
            if (get_post_meta( $post_id, 'lxp_district_admin', true )) {
               update_post_meta( $post_id, 'lxp_district_admin',  $_POST['lxp_client_admin_id']);
            } else {
               add_post_meta( $post_id, 'lxp_district_admin',  $_POST['lxp_client_admin_id'], true);
            }
         }
      }
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
