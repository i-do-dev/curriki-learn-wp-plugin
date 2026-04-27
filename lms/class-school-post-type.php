<?php

/**
 * Class TL_School_Post_Type
 * 
 * @author Waqar Muneer
 * @version 1.0
 */
require_once(ABSPATH . 'wp-admin/includes/file.php');
class TL_School_Post_Type extends TL_Post_Type
{
   /**
    * @var null
    */
   protected static $_instance = null;

   /**
    * @var string
    */
   protected $_post_type = TL_SCHOOL_CPT;

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
         'name'               => _x('LXP School', 'Post Type General Name', 'tinylms'),
         'singular_name'      => _x('LXP School', 'Post Type Singular Name', 'tinylms'),
         'menu_name'          => __('LXP Schools', 'tinylms'),
         'parent_item_colon'  => __('Parent Item:', 'tinylms'),
         'all_items'          => __('LXP Schools', 'tinylms'),
         'view_item'          => __('View LXP School', 'tinylms'),
         'add_new_item'       => __('Add New LXP School', 'tinylms'),
         'add_new'            => __('Add New', 'tinylms'),
         'edit_item'          => __('Edit LXP School', 'tinylms'),
         'update_item'        => __('Update LXP School', 'tinylms'),
         'search_items'       => __('Search LXP School', 'tinylms'),
         'not_found'          => sprintf(__('You haven\'t had any School yet. Click <a href="%s">Add new</a> to start', 'tinylms'), admin_url('post-new.php?post_type=tl_school')),
         'not_found_in_trash' => __('No LXP School found in Trash', 'tinylms'),
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
            'slug'       => 'school',
            'with_front' => false
         ),
         'show_in_rest'       => true,
         'rest_base'          => 'tl_school',
         'supports' => array('title', 'editor', 'thumbnail'),
         // 'capabilities' => array(
         //     'edit_post' => 'edit_lxp_school',
         //     'publish_posts' => 'publish_lxp_schools',
         //     'read_post' => 'read_lxp_school',
         //     'read_private_posts' => 'read_private_lxp_schools',
         //     'delete_post' => 'delete_lxp_school',
         //     'delete_posts' => 'delete_lxp_schools',
         //     'create_posts' => 'create_lxp_schools',
         //     'create_post' => 'create_lxp_school'
         // )
      );
      add_theme_support('post-thumbnails');
      return $args;
   }


   public function add_meta_boxes()
   {
      $this->add_meta_box([
         'school-district-id',      // Unique ID
         esc_html__('School District ', 'school'),    // Title
         array(self::instance(), 'lxp_school_district_metabox_html'),   // Callback function
         $this->_post_type,         // Admin page (or post type)
         'advanced',         // Context
         'default',         // Priority
         // 'show_in_rest' => true,
      ]);

      $this->add_meta_box([
         'school-admin-id',      // Unique ID
         esc_html__('School Admin ', 'school'),    // Title
         array(self::instance(), 'lxp_school_admin_metabox_html'),   // Callback function
         $this->_post_type,         // Admin page (or post type)
         'advanced',         // Context
         'default',         // Priority
         // 'show_in_rest' => true,
      ]);

      $this->add_meta_box([
         'school-users-id',      // Unique ID
         esc_html__('School Users ', 'school'),    // Title
         array(self::instance(), 'lxp_school_users_metabox_html'),   // Callback function
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

   public function lxp_school_district_metabox_html($post = null)
   {
      $args = array(
         'post_type' => 'tl_district',
         'orderby'    => 'ID',
         'post_status' => 'publish',
         'order'    => 'DESC',
         'posts_per_page' => -1
      );
      $districts = get_posts($args);
      $selectedDistrict =  get_post_meta($post->ID, 'lxp_school_district_id', true);
      $output = '  <h4>Select District</h4>';

      $output .= '<select name="lxp_school_district_id" id="course_select_options" style="margin-top:-10px"> ';
      $output .= '<option value="0">Select District</option>';
      foreach ($districts as $district) {
         if ($selectedDistrict == $district->ID) {
            $selected = "selected";
         } else {
            $selected = "";
         }
         $output .= '<option value="' . $district->ID . '" ' . $selected . ' >' . $district->post_title . ' </option>';
      }
      $output .= '</select>';
      echo $output;
   }

   public function lxp_school_admin_metabox_html($post = null)
   {
      $args = array(
         'role' => 'lxp_school_admin',
         'order' => 'ASC'
      );
      $schoolAdmins = get_users($args);
      
      $users = get_users(
         array(
            'role' => 'lxp_school_admin',
            'meta_key' => 'lxp_school_admin_id',
            'meta_value' => $post->ID,
            'number' => -1
         )
      );

      $selectedAdmin = isset($users[0]) ? $users[0]->ID : "";
      $output = '<h4>Select School Admin</h4>';

      $output .= '<select name="lxp_school_admin_id"  style="margin-top:-10px"> ';
      $output .= '<option value="0">Select An Admin</option>';
      foreach ($schoolAdmins as $admin) {
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

   public function lxp_school_users_metabox_html($post = null)
   {
      $users = get_users(
         array(
            'meta_key' => 'lxp_school_id',
            'meta_value' => $post->ID,
            'number' => -1
         )
      );

      $html = '<p class="description">';
      $html .= 'Bulk Upload Users.';
      $html .= '</p>';
      $html .= '<input type="file" id="school_users" name="school_users" value=""  />';
      echo $html;

      foreach ($users as $user) {
         echo '<p>  '.$user->display_name .' ('. $user->roles[0] .')
          &nbsp;
          <span class="dashicons dashicons-trash" id="school_remove_lxp_user" lxp_user_id="' . $user->ID . '"></span>
          </p>';
      }
   }


   public function update_edit_form()
   {
      echo ' enctype="multipart/form-data"';
   }

   public function save_tl_post($post_id = null)
   {
      if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_type']) && 'tl_school' == $_POST['post_type']) {
         if ($_POST['lxp_school_district_id'] != 0) {
            update_post_meta($post_id, 'lxp_school_district_id', $_POST['lxp_school_district_id']);
         } else {
            update_post_meta($post_id, 'lxp_school_district_id', '');
         }

         $users = get_users(
            array(
               'role' => 'lxp_school_admin',
               'meta_key' => 'lxp_school_admin_id',
               'meta_value' => $post_id,
               'number' => -1
            )
         );
    
         if(isset($users[0]->ID)){
            delete_user_meta($users[0]->ID, 'lxp_school_admin_id');
         }
         if ($_POST['lxp_school_admin_id'] != 0) {
            update_user_meta($_POST['lxp_school_admin_id'], 'lxp_school_admin_id', $post_id);
         }

         $filename = $_FILES["school_users"]["tmp_name"];
         if ($_FILES["school_users"]["size"] > 0) {
            $file = fopen($filename, "r");
            $i = 0;
            while (($getData = fgetcsv($file, 10000, ",")) !== FALSE) {
               if ($i == 0) {
                  if( strtolower(trim($getData[0]))  != "user name" ||strtolower(trim($getData[1]))  != "first name" || strtolower(trim($getData[2]))  != "last name" || strtolower(trim($getData[3]))  != "email" || strtolower(trim($getData[4]))  != "password" || strtolower(trim($getData[5])) != "type" ){
                     break;
                  }
                  $i++;
                  continue;
               }
               if ($getData[5] == "Teacher") {
                  $role = "lp_teacher";
               } else {
                  $role = "lxp_student";
               }
               $user_data = array(
                  'user_login' => $getData[0],
                  'first_name' => $getData[1],
                  'last_name' => $getData[2],
                  'user_email' => $getData[3],
                  'display_name' => $getData[1] . ' ' . $getData[2],
                  'user_pass' => $getData[4],
                  'role' => $role
               );
               $user_id  = wp_insert_user($user_data);
               if ($getData[5] == "Teacher" && !is_wp_error($user_id)) {
                  $teacher_user = get_userdata($user_id);
                  // Use only 'lp_teacher' for teacher users.
               }
               update_user_meta($user_id, 'lxp_school_id', $post_id);
            }

            fclose($file);
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
