<?php
require_once plugin_dir_path(dirname(__FILE__)) . 'tiny-lxp-resource/Activity.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'lms/tl-constants.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'lms/xapi-constants.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'lms/models/class-lti-metadata.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'lms/repositories/class-lti-metadata-repository.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'lms/repositories/class-section-repository-interface.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'lms/repositories/class-learnpress-section-repository.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'lms/class-abstract-tl-post-type.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'lms/class-learnpress-course-extension.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'lms/class-learnpress-lesson-extension.php';
// require_once plugin_dir_path(dirname(__FILE__)) . 'lms/class-trek-post-type.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'lms/class-tl-admin-menu.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'lms/class-district-post-type.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'lms/class-school-post-type.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'lms/class-assignment-post-type.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'lms/class-teacher-post-type.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'lms/class-student-post-type.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'lms/class-class-post-type.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'lms/class-assignment-submission-post-type.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'lms/class-group-post-type.php';

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// TL_TREK_Post_Type::instance();
TL_District_Post_Type::instance();
TL_School_Post_Type::instance();
TL_Assingment_Post_Type::instance();
TL_Admin_Menu::instance();
TL_Teacher_Post_Type::instance();
TL_Student_Post_Type::instance();
TL_Class_Post_Type::instance();
TL_Group_Post_Type::instance();
TL_Assingment_Submission_Post_Type::instance();

/**
 * @author Abid
 * @return return_on_templage_specific_page_of_given_url_of_page_using_include_template_hook
 */
function tinyLxp_page_templates($template) {
    // Check if the current page is a specific page
    require_once plugin_dir_path(dirname( __FILE__ )). '/lms/templates/tinyLxpTheme/lxp/functions.php';
    if (is_page('login')) {
        $template = plugin_dir_path(dirname( __FILE__ )).'/lms/templates/tinyLxpTheme/page-login.php';
    }
    if (is_user_logged_in()) {
        $userdata = get_userdata(get_current_user_id());
        if (is_page('dashboard')) {
            if ( !in_array( 'lxp_student', (array) $userdata->roles ) ) {
                $template = plugin_dir_path(dirname( __FILE__ )).'/lms/templates/tinyLxpTheme/page-dashboard.php';
            }
        } elseif (is_page('districts')) {
            $template = plugin_dir_path(dirname( __FILE__ )).'/lms/templates/tinyLxpTheme/page-districts.php';
        } elseif (is_page('schools')) {
            $template = plugin_dir_path(dirname( __FILE__ )).'/lms/templates/tinyLxpTheme/page-schools.php';
        } elseif (is_page('teachers')) {
            $template = plugin_dir_path(dirname( __FILE__ )).'/lms/templates/tinyLxpTheme/page-teachers.php';
        } elseif (is_page('students')) {
            $template = plugin_dir_path(dirname( __FILE__ )).'/lms/templates/tinyLxpTheme/page-students.php';
        } elseif (is_page('classes')) {
            $template = plugin_dir_path(dirname( __FILE__ )).'/lms/templates/tinyLxpTheme/page-classes.php';
        } elseif (is_page('groups')) {
            $template = plugin_dir_path(dirname( __FILE__ )).'/lms/templates/tinyLxpTheme/page-groups.php';
        } elseif (is_page('courses')) {
            // $template = plugin_dir_path(dirname( __FILE__ )) . '/lms/templates/tinyLxpTheme/page-learner-courses.php';
            // $template = plugin_dir_path(dirname( __FILE__ )).'/lms/templates/tinyLxpTheme/page-courses.php';
            $template = plugin_dir_path(dirname( __FILE__ )).'/lms/templates/tinyLxpTheme/page-learner-courses.php';
        } elseif (is_page('assignments')) {
            $template = plugin_dir_path(dirname( __FILE__ )).'/lms/templates/tinyLxpTheme/page-assignments.php';
        } elseif (is_page('assignment')) {
            $template = plugin_dir_path(dirname( __FILE__ )).'/lms/templates/tinyLxpTheme/page-assignment.php';
        } elseif (is_page('search')) {
            $template = plugin_dir_path(dirname( __FILE__ )).'/lms/templates/tinyLxpTheme/page-search.php';
        } elseif (is_page('calendar')) {
            $template = plugin_dir_path(dirname( __FILE__ )).'/lms/templates/tinyLxpTheme/page-calendar.php';
        } elseif (is_page('grades')) {
            $template = plugin_dir_path(dirname( __FILE__ )).'/lms/templates/tinyLxpTheme/page-grades.php';
        } elseif (is_page('grade-assignment')) {
            $template = plugin_dir_path(dirname( __FILE__ )).'/lms/templates/tinyLxpTheme/page-grade-assignment.php';
        // } elseif (is_page('grade-summary')) {
        //     $template = plugin_dir_path(dirname( __FILE__ )).'/lms/templates/tinyLxpTheme/page-grade-summary.php';
        } elseif (is_page('edlink-integration')) {
            $template = plugin_dir_path(dirname( __FILE__ )) . '/lms/templates/tinyLxpTheme/page-edlink-integration.php';
        } elseif (is_page('learner-courses')) {
            $template = plugin_dir_path(dirname( __FILE__ )) . '/lms/templates/tinyLxpTheme/page-learner-courses.php';
        }
    }
    // $template = plugin_dir_path(dirname( __FILE__ )) . '/lms/templates/tinyLxpTheme/single-tl_course.php';
    return $template; // Return the original template if conditions are not met
}

// Hook your callback function to the page_template filter hook
add_filter('template_include', 'tinyLxp_page_templates', 99);

// add_filter('show_admin_bar', '__return_false');

function get_selected_course_data() {
    // Get the current URL
    $url = home_url( add_query_arg( array() ) );
    
    // Parse the URL and extract the query string
    $query_string = parse_url($url, PHP_URL_QUERY);
        
    $currentPostName = $query_string ? $query_string : null;

    if ($currentPostName != null) {
        $args = array(
            'name'        => sanitize_title($currentPostName),
            'post_type'   => TL_COURSE_CPT,
            'post_status' => 'publish',
            'posts_per_page' => 1
        );
        $query = new WP_Query($args);
    }

    if (isset($query) && $query->have_posts()) {
        // Output the HTML with PHP variables
        echo '<div class="post-container">';
        echo '<div class="post-thumbnail">';
        if (has_post_thumbnail($query->post->ID)) {
            echo '<img src="' . get_the_post_thumbnail_url($query->post->ID) . '" alt="Post Thumbnail">';
        } else {
            $treks_src = plugin_dir_url( __DIR__ ) . 'lms/templates/tinyLxpTheme/treks-src/';
            echo '<img width="300" height="180" style="height:313px" src="'.$treks_src.'/assets/img/tr_main.jpg" class="rounded wp-post-image" />';
        }
        echo '</div>';
        echo '<div class="post-content">';
        echo '<h2 class="post-title">' . esc_html($query->post->post_title) . '</h2>';
        echo '<p class="post-description">' . esc_html(wp_trim_words($query->post->post_content, 40, '...')) . '</p>';
        echo '<p></p>';
        echo '<p></p>';
        echo '<p></p></div>';
        echo '<button id="loginButton" style="margin-top:25%" class="glow-on-hover" type="button">Enroll Now</button>';
        echo '</div>';
    } else {
        echo '<p>No course found.</p>';
    }

}
add_shortcode('selected_course', 'get_selected_course_data');

function get_activity() {
    $url = home_url( add_query_arg( array() ) );    
    $url_components = parse_url($url);
    // Split path into segments
    if (isset($url_components['path'])) {
        $path_segments = explode('/', trim($url_components['path'], '/'));
    }
    $args = array(
        'name'        => $path_segments[3],
        'post_type'   => LP_LESSON_CPT,
        'post_status' => 'publish',
        'numberposts' => 1
    );
    $posts = get_posts($args);
    $post = $posts[0];
    $_GET['post'] = $post->ID;
    $content = get_post_meta($post->ID);
    $attrId =  isset($content['lti_post_attr_id'][0]) ? $content['lti_post_attr_id'][0] : "";
    $title =  isset($content['lti_content_title'][0]) ? $content['lti_content_title'][0] : "";
    $toolCode =  isset($content['lti_tool_code'][0]) ? $content['lti_tool_code'][0] : "";
    $customAttr =  isset($content['lti_custom_attr'][0]) ? $content['lti_custom_attr'][0] : "";
    $toolUrl =  isset($content['lti_tool_url'][0]) ? $content['lti_tool_url'][0] : "";
    $plugin_name = Tiny_LXP_Platform::get_plugin_name();
    $content = '<p>' . $post->post_content . '</p>';
    if ($attrId) {
        $content .= '<p> [' . $plugin_name . ' tool=' . $toolCode . ' id=' . $attrId . ' title=\"' . $title . '\" url=' . $toolUrl . ' custom=' . $customAttr . ']' . "" . '[/' . $plugin_name . ']  </p>';
    }
    
    $queryParam = '';
    echo '<iframe style="border: none;width: 100%;height: 482px;" class="" src="'.site_url().'?lti-platform&post='.$post->ID.'&id='.$attrId.$queryParam.'" allowfullscreen></iframe>'; //  height = 706 
}
add_shortcode('selected_activity', 'get_activity');

/**
 *  redirect thought for multiple conditions
 *  for lxp-teacher on single page
 *  for lxp-student on courses to learn-courses page
 */
function save_course_view_page_id() {
    // Avoid running in admin
    if (is_admin()) {
        return;
    }
    // Get the current user ID
    $user_id = get_current_user_id();
    // Code to execute when a user is on a single course page
    if ($user_id > 0) {
        require_once plugin_dir_path(dirname( __FILE__ )). '/lms/templates/tinyLxpTheme/lxp/functions.php';
        $userdata = get_userdata($user_id);
        $userRole = lxp_get_active_role($user_id);
        // condition 1
        // update_post_meta($user_post_id->ID, 'lxp_visited_courses', $course_id);
        if (is_singular('lp_course') && $userRole == 'lp_teacher') {
            // Get the current course post ID
            $course_id = get_the_ID();
            
            $teacher_post = lxp_get_teacher_post($user_id);
            // Update the course post meta with the current page ID
            $lxp_visited_courses = get_post_meta($teacher_post->ID, 'lxp_visited_courses');
            if (!in_array($course_id, $lxp_visited_courses)) {
                add_post_meta($teacher_post->ID, 'lxp_visited_courses', $course_id);
            }
        }
        // condition 2
        // redirect to learner-course for only Role of lxp_students is_post_type_archive == /courses/
        // if ( $userRole == 'lxp_student' && is_post_type_archive('lp_course') ) {
        //     wp_redirect( home_url('/learner-courses') );
        //     exit;
        // }
    }
    // condition 3
    // Check if 'redirect_to' is in the query parameters
    // if ( isset($_GET['redirect_to']) ) {
    //     $url = esc_url_raw( $_GET['redirect_to'] );

    //     // Only allow same-domain redirects (security)
    //     $site_url = home_url();
    //     if ( strpos( $url, $site_url ) === 0 ) {
    //         wp_safe_redirect( $url );
    //         exit;
    //     }
    // }
    // condition 4
    // new functionality start below
    $page_slug_1 = 'enroll-courses';
	$page_slug_2 = 'selected-course';
    // Get the home URL
    $home_url = esc_url(home_url('/'));
	
	// $condition = true;
    // if ( is_plugin_active( 'TinyLxp-wp-plugin/tiny-lxp-platform.php' ) ) {
    //     $condition = false;
    // }

    // Check if the current page is the specific page
    if (is_page($page_slug_1) || is_page($page_slug_2)) {
        // Perform the redirect
        wp_redirect($home_url);
        exit;
    }
}

add_action('template_redirect', 'save_course_view_page_id');


// hide enroll courses button if plugin is deactivate i think no need
// because our theme in now in tiny-lxp-theme
// add_filter('wp_nav_menu_objects', 'hide_menu_item_conditionally', 10, 2);
function hide_menu_item_conditionally($items) {
	$condition = 0;
    if ( is_plugin_active( 'TinyLxp-wp-plugin/tiny-lxp-platform.php' ) ) {
        $condition = 1;
    }
    // Loop through the menu items and remove the specified item if the condition is met
    foreach ($items as $key => $item) {
        if ($item->title == 'Courses' && $condition == 0) {
            // unset($items[$key]);
        } elseif ($item->title == 'Dashboard' && !is_user_logged_in()) {
            // Remove the Dashboard menu item if the user is not logged in
            unset($items[$key]);
        } elseif ($item->title == 'Login' && is_user_logged_in()) {
            // Remove the Login menu item if the user is logged in
            unset($items[$key]);
        }
    }
    return $items;
}

// Custom login redirect
// function custom_login_redirect( $redirect_to, $request, $user ) {

//     if ( isset( $user->ID ) ) {
//         // Use requested redirect (e.g. from wp_login_url())
//         return ! empty( $request ) ? $request : home_url('/home');
//     }
//     return home_url('/home'); // fallback
// }
#// add_filter( 'login_redirect', 'custom_login_redirect', 10, 3 );

// Display current user's display name on after header section edudeme theme
function show_current_user_name() {
    $titleText = 'Welcome, Guest Learner';
    if (is_user_logged_in()) {
        $request_uri    = $_SERVER['REQUEST_URI'] ?? '';
        $has_assignment = isset($_GET['assignment_id']);
        if ( is_page('learner-courses') ) {
            $titleText = '<b>My Courses</b>';
        } elseif ( is_singular('lp_course') ) {
            $titleText = '<b>My Course</b>';
        } elseif ( is_page('learner-assignments') ) {
            $titleText = '<b>My Assignments</b>';
        } elseif ( is_page('learner-grades') ) {
            $titleText = '<b>My Grades</b>';
        } elseif ( is_page('grade-summary') ) {
            $titleText = '<b>Grade Summary</b>';
        } elseif ( $has_assignment && (strpos($request_uri, '/lessons/') !== false || strpos($request_uri, '/quizzes/') !== false) ) {
            $titleText = '<b>My Assignment</b>';
        } else {
            $current_user = wp_get_current_user();
            $titleText = '<b>Welcome</b> ' . esc_html($current_user->display_name);
        }
    }
    return '<div style="margin-left: 10pc; font-family: Arsenal, sans-serif; color: #7659ff; font-size: 30px;">'.$titleText.'</div>';
}
add_shortcode('after_header_user_title', 'show_current_user_name');

function custom_login_redirect( $redirect_to, $request, $user ) {
    // Ensure user is valid
    if (is_user_logged_in()) {
        $userdata = get_userdata(get_current_user_id());
        $user_roles = $userdata ? (array) $userdata->roles : array();
        $userRole = in_array('lp_teacher', $user_roles, true) ? 'lp_teacher' : (count($user_roles) > 0 ? array_values($user_roles)[0] : '');
        if ( $userRole == 'lxp_student') {
            // Prevent loop if already on learner page
            if ( strpos( $_SERVER['REQUEST_URI'], '/learner' ) === false ) {
                return site_url( '/learner/' );
            } else {
                return $redirect_to;
            }
        } else {
            // For other roles, redirect to dashboard
            if ( strpos( $_SERVER['REQUEST_URI'], '/dashboard' ) === false ) {
                return site_url( '/dashboard/' );
            } else {
                return $redirect_to;
            }
        }
    }
    // Fallback
    return $redirect_to;
}

add_filter( 'login_redirect', 'custom_login_redirect', 10, 3 );

// Add to your child theme's functions.php
function my_custom_course_tab($tabs, $course) {
    // Add a new "Custom Tab" as the fourth tab
    if (is_user_logged_in()) {
        $tabs['custom_tab'] = array(
            'title'    => __( 'Assignments', 'learnpress' ),
            'priority' => 50, // This makes it the 4th tab
            'callback' => 'assignment_tab_content'
        );
    }
    return $tabs;
}
add_filter('learn-press/course-tabs', 'my_custom_course_tab', 10, 2);

// Callback function to display your custom tab content for student assignment single course view
function assignment_tab_content() {
    require_once plugin_dir_path(dirname( __FILE__ )). '/lms/templates/tinyLxpTheme/lxp/functions.php';
    $given_course = LP_Global::course();
    $student_post = lxp_get_student_post(get_current_user_id());
    $assignments = [];
    if($student_post) {
        $assignments = lxp_get_student_assignments($student_post->ID);
    }
    echo '<div class="stu-assig-cards">';
    if (count($assignments) == 0) {
        echo '<div style="color: gray;"><i><h6>No Assignment(s)</h6></i></div>';
    } else {
        foreach ($assignments as $assignment) {
            $course = get_post(get_post_meta($assignment->ID, 'course_id', true));
            if ($given_course->get_id() != $course->ID) {
                continue;
            }
            $lxp_lesson_post = get_post(get_post_meta($assignment->ID, 'lxp_lesson_id', true));
            $segmentColor = "#1fa5d4";
            $args = array( 'posts_per_page' => -1, 'post_type' => TL_LESSON_CPT, 'meta_query' => array(array('key'   => 'tl_course_id', 'value' =>  $course->ID)));
            $lessons = get_posts($args);
            $digital_journal_link = null;
            foreach($lessons as $lesson){ if ( $lxp_lesson_post->ID === $lesson->ID ) { $digital_journal_link = get_permalink($lesson->ID); }; }
                $digital_journal_link = $digital_journal_link . '?assignment_id=' . $assignment->ID;
            ?>
            <a href="<?php echo $digital_journal_link; ?>" class="student-assignment-block" target="_blank">

                    <div class="assig-label-card">
                        <div class="header">
                            <div class="tags-body-polygon bg-green" style="background-color: <?php echo $segmentColor; ?>">
                                <span><?php echo 'L'; ?></span>
                            </div>
                        </div>
                        <div class="tag-assig-tetaul">
                            <h3 style="color: <?php echo $segmentColor; ?>"><?php echo $lxp_lesson_post->post_title; ?></h3>
                            <p><?php echo $course->post_title; ?></p>
                        </div>
                    </div>
                    <div class="progress" style="height: 4px">
                        <div style="width: 25%"></div>
                    </div>
            </a>
        <?php } 
    }
    echo '</div>';
}
