<?php

/**
 * Class TL_TREK_Post_Type
 * 
 * @author Waqar Muneer
 * @version 1.0
 */

class TL_TREK_Post_Type extends TL_Post_Type
{
   /**
    * @var null
    */
   protected static $_instance = null;

   /**
    * @var string
    */
   protected $_post_type = TL_COURSE_CPT;

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

      add_action("wp_ajax_trek_settings", array($this, "trek_settings"));
      add_action("wp_ajax_trek_student_section", array($this, "trek_student_section"));
   }

   function trek_student_section()
   {
      $post_id = $_POST['post_id'];
      // update post meta 'student_section_overview' section using 'content' posted data
      update_post_meta($post_id, 'student_section_overview', $_POST['content']);
      echo json_encode(array('success' => true));
      wp_die();
   }

   function trek_settings()
   {
      $post_id = $_POST['post_id'];
      $sort = $_POST['sort'];
      update_post_meta($post_id, 'sort', $sort);
      $strands = $_POST['strands'];
      delete_post_meta($post_id, 'strands');
      foreach ($strands as $strand) {
         update_post_meta($post_id, 'strands', $strand);
      }
      $tekversion = $_POST['tekversion'];
      update_post_meta($post_id, 'tekversion', $tekversion);
      echo json_encode(array('success' => true));
      wp_die();
   }

   /**
    * Register course post type.
    */
   public function args_register_post_type(): array
   {

      $located = locate_template('single-tl_trek.php');
      if (empty($located)) {
         add_filter('single_template', function ($page_template, $type) {
            global $post;
            if ($post->post_type == TL_COURSE_CPT) {
               $page_template = dirname(__FILE__) . '/templates/trek/single-tl_trek.php';
            }
            return $page_template;
         }, 20, 2);
      }

      global $wpdb;
      $wpdb->query("CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "trek_sections(
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			trek_id bigint(20) default NULL,
            title varchar(255) default NULL,
            type varchar(255) default NULL,
            content longtext default NULL,
            link varchar(255) default NULL,
			PRIMARY KEY (id)
		)");

      $wpdb->query("CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "trek_events(
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
         trek_section_id bigint(20) default NULL,
            start  bigint(20) default NULL,
            end  bigint(20) default NULL,
            user_id  bigint(20) default NULL,
			PRIMARY KEY (id)
		)");

      $labels           =     array(
         'name'               => _x('LXP TREK', 'Post Type General Name', 'tinylms'),
         'singular_name'      => _x('LXP TREK', 'Post Type Singular Name', 'tinylms'),
         'menu_name'          => __('LXP TREKs', 'tinylms'),
         'parent_item_colon'  => __('Parent Item:', 'tinylms'),
         'all_items'          => __('LXP TREKs', 'tinylms'),
         'view_item'          => __('View LXP TREK', 'tinylms'),
         'add_new_item'       => __('Add New LXP TREK', 'tinylms'),
         'add_new'            => __('Add New', 'tinylms'),
         'edit_item'          => __('Edit LXP TREK', 'tinylms'),
         'update_item'        => __('Update LXP TREK', 'tinylms'),
         'search_items'       => __('Search LXP TREK', 'tinylms'),
         'not_found'          => sprintf(__('You haven\'t had any TREK yet. Click <a href="%s">Add new</a> to start', 'tinylms'), admin_url('post-new.php?post_type=tl_trek')),
         'not_found_in_trash' => __('No LXP TREK found in Trash', 'tinylms'),
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
            'slug'       => 'trek',
            'with_front' => false
         ),
         'show_in_rest'       => false,
         'rest_base'          => 'tl_trek',
         'supports' => array('title', 'editor', 'author', 'thumbnail'),
      );
      add_theme_support('post-thumbnails');
      return $args;
   }


   public function add_meta_boxes()
   {
      $this->add_meta_box([
         'trek-settings-options-id',      // Unique ID
         esc_html__('TREK Settings ', 'settings'),    // Title
         array(self::instance(), 'trek_settings_metabox_html'),   // Callback function
         $this->_post_type,         // Admin page (or post type)
         'advanced',         // Context
         'default',         // Priority
         'show_in_rest' => true,
      ]);

      $this->add_meta_box([
         'trek-course-options-id',      // Unique ID
         esc_html__('Course ', 'course'),    // Title
         array(self::instance(), 'course_metabox_html'),   // Callback function
         $this->_post_type,         // Admin page (or post type)
         'advanced',         // Context
         'default',         // Priority
         'show_in_rest' => true,
      ]);


      $this->add_meta_box([
         'trek-sections-chips-id',      // Unique ID
         esc_html__('TREK Sections', 'trek-Sections'),    // Title
         array(self::instance(), 'trek_section_chips_metabox_html'),   // Callback function
         $this->_post_type,         // Admin page (or post type)
         'advanced',         // Context
         'default',         // Priority
         'show_in_rest' => true,
      ]);

      $this->add_meta_box([
         'trek-sections-input-id',      // Unique ID
         esc_html__('Manage Teacher Instruction section', ',manage_teacher_instruction_section'),    // Title
         array(self::instance(), 'trek_sections_metabox_html'),   // Callback function
         $this->_post_type,         // Admin page (or post type)
         'advanced',         // Context
         'default',         // Priority
         'show_in_rest' => true,
      ]);

      $this->add_meta_box([
         'trek-student-section-input-id',      // Unique ID
         esc_html__('Manage Student Section', 'manage_student_section'),    // Title
         array(self::instance(), 'trek_student_section_metabox_html'),   // Callback function
         $this->_post_type,         // Admin page (or post type)
         'advanced',         // Context
         'default',         // Priority
         'show_in_rest' => true,
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

   public function trek_settings_metabox_html($post)
   {
      include(__DIR__ . '/templates/parts/trek-settings.php');
   }



   public function course_metabox_html($post = null)
   {
      $args = array(
         'post_type' => TL_COURSE_CPT,
         'orderby'    => 'ID',
         'post_status' => 'publish,draft',
         'order'    => 'DESC',
         'posts_per_page' => -1
      );
      $courses = get_posts($args);
      $selectedCourse =  isset($_GET['courseid']) ? $_GET['courseid'] : get_post_meta($post->ID, 'tl_course_id', true);
      $output = '  <h4>Select Course</h4>';

      $output .= '<select name="tl_course_id" id="course_select_options" style="margin-top:-10px"> ';
      foreach ($courses as $course) {
         if ($selectedCourse == $course->ID) {
            $selected = "selected";
         } else {
            $selected = "";
         }
         $output .= '<option value="' . $course->ID . '" ' . $selected . ' >' . $course->post_title . ' </option>';
      }
      $output .= '</select>';
      echo $output;
   }

   public function trek_sections_metabox_html($post = null)
   {
      global $wpdb;
      $output = '  <h3 id="section-title">Add New Section</h3>';
      $output .= '<div id="appendme" class="container" >';
      $selectOption = '';
      $selectOption .= "<select name='title' class='option-title-input' id='option-title-select-box'> ";
      $append =   "<br><b>Content</b> <textarea  class='ckeditor'  id='ck-editor-id' rows='12' cols='50' name='option_content' />  </textarea> ";
      $selectOption .= '</select>';
      $output .=  "<div class='row option-body'  id='option-body'> <span id='playlist-select-area'> <b>Select Section</b> <br>" . $selectOption . " </span><button type='button' id='btnSaveSection' class='button button-primary'>Create</button>  <button style='display:none' type='button' id='btnCancelUpdate' class='button button-secondary'>Cancel</button>   <br>" . $append . "  <input type='hidden' name='option-type[]' value=''><br><hr> </div>";
      $output .=  '<div>
                     <b>Sort Order Number</b> <br>
                     <input type="number" id="trek_sort" name="trek_sort" min="0" max="10000" value="0">
                  </div>';
      $output .=  '</div>';
      echo $output;
   }

   public function trek_student_section_metabox_html($post = null)
   {
      global $wpdb;
      $content = get_post_meta($post->ID, 'student_section_overview', true);
      $output = '';
      $output .= '<div id="appendme" class="container" >';
      $append =   "<br><h3><b>Overview</b></h3> <textarea  class='ckeditor'  id='student-section-editor' rows='12' cols='50' name='student_section_content'>" . $content . "</textarea> ";
      $output .=  "<div class='row option-body'  id='option-body'>" . $append . "</div>";
      $output .=  '<div>
                  </div>';
      $output .=  "<br /><button type='button' id='btnSaveStudentSection' class='button button-primary'>Save</button>";
      $output .=  '</div>';
      echo $output;
   }

   public function trek_section_chips_metabox_html($post = null)
   {
      $removeBtn = '<span type="button" class="chip-close"><span style="margin-top:5px" class="dashicons dashicons-no"></span> </span> ';
      global $wpdb;
      $query = "SELECT * FROM " . $wpdb->prefix . "trek_sections WHERE trek_id={$post->ID} ORDER BY sort";
      $options =  $wpdb->get_results($query);
      $output = "<div id='option-chips' tota-chips=" . count($options) . ">";
      if (count($options) == 0) {
         $output .= "<div id='chips-alternate'>No section avialable. Create one using form given below.</div>";
      }
      foreach ($options as $key => $value) {
         $output .=     '<div class="playlist-chip " identifier="' . $value->id . '" > 
                           <span id="chip-title-' . $value->id . '"> ' . $value->title . ' </span>
                           <span class="edit-trek-options">
                              <span style="margin-top:5px" class="dashicons dashicons-edit"></span> 
                           </span> '
            . $removeBtn .
            '</div>';
      }
      $output .= "</div>";
      echo $output;
   }

   public function save_tl_post($post_id = null)
   {
      if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_type']) && 'tl_trek' == $_POST['post_type']) {
         update_post_meta($post_id, 'tl_course_id', $_POST['tl_course_id']);
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
