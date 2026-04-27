<?php

/**
 * Class TL_Student_Post_Type
 * 
 * @author Waqar Muneer
 * @version 1.0
 */
require_once(ABSPATH . 'wp-admin/includes/file.php');
class TL_Student_Post_Type extends TL_Post_Type
{
   /**
    * @var null
    */
   protected static $_instance = null;

   /**
    * @var string
    */
   protected $_post_type = TL_STUDENT_CPT;

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
         'name'               => _x('LXP Student', 'Post Type General Name', 'tinylms'),
         'singular_name'      => _x('LXP Student', 'Post Type Singular Name', 'tinylms'),
         'menu_name'          => __('LXP Students', 'tinylms'),
         'parent_item_colon'  => __('Parent Item:', 'tinylms'),
         'all_items'          => __('LXP Students', 'tinylms'),
         'view_item'          => __('View LXP Student', 'tinylms'),
         'add_new_item'       => __('Add New LXP Student', 'tinylms'),
         'add_new'            => __('Add New', 'tinylms'),
         'edit_item'          => __('Edit LXP Student', 'tinylms'),
         'update_item'        => __('Update LXP Student', 'tinylms'),
         'search_items'       => __('Search LXP Student', 'tinylms'),
         'not_found'          => sprintf(__('You haven\'t had any Student yet. Click <a href="%s">Add new</a> to start', 'tinylms'), admin_url('post-new.php?post_type=tl_student')),
         'not_found_in_trash' => __('No LXP Student found in Trash', 'tinylms'),
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
            'slug'       => 'student',
            'with_front' => false
         ),
         'show_in_rest'       => true,
         'rest_base'          => 'tl_student',
         'supports' => array('title', 'editor', 'thumbnail'),
         // 'capabilities' => array(
         //     'edit_post' => 'edit_lxp_student',
         //     'publish_posts' => 'publish_lxp_students',
         //     'read_post' => 'read_lxp_student',
         //     'read_private_posts' => 'read_private_lxp_students',
         //     'delete_post' => 'delete_lxp_student',
         //     'delete_posts' => 'delete_lxp_students',
         //     'create_posts' => 'create_lxp_students',
         //     'create_post' => 'create_lxp_student'
         // )
      );
      add_theme_support('post-thumbnails');
      return $args;
   }


   public function add_meta_boxes()
   {
      $this->add_meta_box([
         'student-school-id',      // Unique ID
         esc_html__('Student School ', 'student'),    // Title
         array(self::instance(), 'lxp_student_school_metabox_html'),   // Callback function
         $this->_post_type,         // Admin page (or post type)
         'advanced',         // Context
         'default',         // Priority
         // 'show_in_rest' => true,
      ]);

      $this->add_meta_box([
         'student-admin-id',      // Unique ID
         esc_html__('Student Admin ', 'student'),    // Title
         array(self::instance(), 'lxp_student_admin_metabox_html'),   // Callback function
         $this->_post_type,         // Admin page (or post type)
         'advanced',         // Context
         'default',         // Priority
         // 'show_in_rest' => true,
      ]);

      $this->add_meta_box([
         'student-users-id',      // Unique ID
         esc_html__('Student Users ', 'student'),    // Title
         array(self::instance(), 'lxp_student_users_metabox_html'),   // Callback function
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

   public function lxp_student_school_metabox_html($post = null)
   {
      $args = array(
         'post_type' => 'tl_school',
         'orderby'    => 'ID',
         'post_status' => 'publish',
         'order'    => 'DESC',
         'posts_per_page' => -1
      );
      $schools = get_posts($args);
      $selectedSchool =  get_post_meta($post->ID, 'lxp_student_school_id', true);
      $output = '  <h4>Select School</h4>';

      $output .= '<select name="lxp_student_school_id" id="course_select_options" style="margin-top:-10px"> ';
      $output .= '<option value="0">Select School</option>';
      foreach ($schools as $school) {
         if ($selectedSchool == $school->ID) {
            $selected = "selected";
         } else {
            $selected = "";
         }
         $output .= '<option value="' . $school->ID . '" ' . $selected . ' >' . $school->post_title . ' </option>';
      }
      $output .= '</select>';
      echo $output;
   }

   public function lxp_student_admin_metabox_html($post = null)
   {
      $args = array(
         'role' => 'lxp_student_admin',
         'order' => 'ASC'
      );
      $studentAdmins = get_users($args);
      
      $users = get_users(
         array(
            'role' => 'lxp_student_admin',
            'meta_key' => 'lxp_student_admin_id',
            'meta_value' => $post->ID,
            'number' => -1
         )
      );

      $selectedAdmin = isset($users[0]) ? $users[0]->ID : "";
      $output = '<h4>Select Student Admin</h4>';

      $output .= '<select name="lxp_student_admin_id"  style="margin-top:-10px"> ';
      $output .= '<option value="0">Select An Admin</option>';
      foreach ($studentAdmins as $admin) {
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

   public function lxp_student_users_metabox_html($post = null)
   {
      $users = get_users(
         array(
            'meta_key' => 'lxp_student_id',
            'meta_value' => $post->ID,
            'number' => -1
         )
      );

      $html = '<p class="description">';
      $html .= 'Bulk Upload Users.';
      $html .= '</p>';
      $html .= '<input type="file" id="student_users" name="student_users" value=""  />';
      echo $html;

      foreach ($users as $user) {
         echo '<p>  '.$user->display_name .' ('. $user->roles[0] .')
          &nbsp;
          <span class="dashicons dashicons-trash" id="student_remove_lxp_user" lxp_user_id="' . $user->ID . '"></span>
          </p>';
      }
   }


   public function update_edit_form()
   {
      echo ' enctype="multipart/form-data"';
   }

   public function save_tl_post($post_id = null)
   {
      /*
      if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_type']) && 'tl_student' == $_POST['post_type']) {
         if ($_POST['lxp_student_school_id'] != 0) {
            update_post_meta($post_id, 'lxp_student_school_id', $_POST['lxp_student_school_id']);
         } else {
            update_post_meta($post_id, 'lxp_student_school_id', '');
         }

         $users = get_users(
            array(
               'role' => 'lxp_student_admin',
               'meta_key' => 'lxp_student_admin_id',
               'meta_value' => $post_id,
               'number' => -1
            )
         );
    
         if(isset($users[0]->ID)){
            delete_user_meta($users[0]->ID, 'lxp_student_admin_id');
         }
         if ($_POST['lxp_student_admin_id'] != 0) {
            update_user_meta($_POST['lxp_student_admin_id'], 'lxp_student_admin_id', $post_id);
         }

         $filename = $_FILES["student_users"]["tmp_name"];
         if ($_FILES["student_users"]["size"] > 0) {
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
               if ($getData[5] == "Student") {
                  $role = "lxp_student";
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
               update_user_meta($user_id, 'lxp_student_id', $post_id);
            }

            fclose($file);
         }
      }
      */
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
