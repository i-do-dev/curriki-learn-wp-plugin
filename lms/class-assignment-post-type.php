<?php

/**
 * Class TL_ASSIGNMENT_CPT
 * 
 * @author Waqar Muneer
 * @version 1.0
 */

class TL_Assingment_Post_Type extends TL_Post_Type
{
   /**
    * @var null
    */
   protected static $_instance = null;

   /**
    * @var string
    */
   protected $_post_type = TL_ASSIGNMENT_CPT;

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
    * Register Assignment post type.
    */
   public function args_register_post_type(): array
   {

      $labels           =     array(
         'name'               => _x('LXP Assignment', 'Post Type General Name', 'tinylms'),
         'singular_name'      => _x('LXP Assignment', 'Post Type Singular Name', 'tinylms'),
         'menu_name'          => __('LXP Assignments', 'tinylms'),
         'parent_item_colon'  => __('Parent Item:', 'tinylms'),
         'all_items'          => __('LXP Assignments', 'tinylms'),
         'view_item'          => __('View LXP Assignment', 'tinylms'),
         'add_new_item'       => __('Add New LXP Assignment', 'tinylms'),
         'add_new'            => __('Add New', 'tinylms'),
         'edit_item'          => __('Edit LXP Assignment', 'tinylms'),
         'update_item'        => __('Update LXP Assignment', 'tinylms'),
         'search_items'       => __('Search LXP Assignment', 'tinylms'),
         'not_found'          => sprintf(__('You haven\'t had any Assignment yet. Click <a href="%s">Add new</a> to start', 'tinylms'), admin_url('post-new.php?post_type=tl_assignment')),
         'not_found_in_trash' => __('No LXP Assignment found in Trash', 'tinylms'),
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
         'rest_base'          => 'tl_assignment',
         'supports' => array('title', 'editor', 'thumbnail'),
      );
      add_theme_support('post-thumbnails');
      return $args;
   }


   public function add_meta_boxes()
   {
      $this->add_meta_box([
         'assignment-teacher-id',      // Unique ID
         esc_html__('Teacher ', 'Teacher'),    // Title
         array(self::instance(), 'lxp_teacher_metabox_html'),   // Callback function
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

   public function lxp_teacher_metabox_html($post = null)
   {
         $args = array(
             'role' => 'lp_teacher',
             'order' => 'ASC'
            );
         $clientAdmins = get_users($args);


      $users = get_users(
         array(
            'role' => 'lp_teacher',
            'meta_key' => 'lxp_assignment_teacher_id',
            'meta_value' => $post->ID,
            'number' => -1
         )
      );

      $selectedAdmin = isset($users[0]) ? $users[0]->ID : "";
      $output = '<h4>Select Teacher</h4>';

      $output .= '<select name="lxp_assignment_teacher_id"  style="margin-top:-10px"> ';
      $output .= '<option value="0">Select An Teacher</option>';
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
      if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_type']) && 'tl_assignment' == $_POST['post_type']) {
         $users = get_users(
            array(
               'role' => 'lp_teacher',
               'meta_key' => 'lxp_assignment_teacher_id',
               'meta_value' => $post_id,
               'number' => -1
            )
         );
         if(isset($users[0]->ID)){
            delete_user_meta($users[0]->ID, 'lxp_assignment_teacher_id');
         }
         if($_POST['lxp_assignment_teacher_id'] != 0){
            update_user_meta($_POST['lxp_assignment_teacher_id'], 'lxp_assignment_teacher_id', $post_id);
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
