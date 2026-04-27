<?php
/*
 *  wordpress-tiny-lxp-platform - Enable WordPress to act as an Tiny LXP Platform.

 *  Copyright (C) 2022  Waqar Muneer
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License along
 *  with this program; if not, write to the Free Software Foundation, Inc.,
 *  51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 *  Contact: Waqar Muneer <waqarmuneer@gmail.com>
 */

/**
 * Define the WordPress Tiny LXP Tool class.
 *
 * Extends the Tool class to include methods specific to a WordPress instance of an Tiny LXP tool.
 *
 * @since      1.0.0
 * @package    Tiny_LXP_Platform
 * @subPackage Tiny_LXP_Platform/includes
 * @author     Waqar Muneer <waqarmuneer@gmail.com>
 */

use ceLTIc\LTI\Tool;

class Tiny_LXP_Platform_Tool extends Tool
{

    const POST_TYPE = 'lti-platform-tool';
    const POST_TYPE_NETWORK = 'lti-platform-ms-tool';

    public $blogId = null;
    public $code = null;
    public $useContentItem = false;
    public $contentItemUrl = null;
    public $deleted = false;
    private static $options = null;

    public static function fromCode($code, $dataConnector)
    {
        $tool = null;
        $post = null;
        if (is_multisite()) {
            switch_to_blog(0);
            $post = get_page_by_path($code, OBJECT, Tiny_LXP_Platform_Tool::POST_TYPE_NETWORK);
            restore_current_blog();
            if ($post instanceof WP_POST) {
                $tool = new self($dataConnector);
                Tiny_LXP_Platform::$tinyLxpPlatformDataConnector->fromPost($tool, $post, 0);
                if (!$tool->enabled) {
                    $tool = null;
                }
            }
        }
        if (empty($tool)) {
            $post = get_page_by_path($code, OBJECT, Tiny_LXP_Platform_Tool::POST_TYPE);
            if ($post instanceof WP_POST) {
                $tool = new self($dataConnector);
                Tiny_LXP_Platform::$tinyLxpPlatformDataConnector->fromPost($tool, $post, get_current_blog_id());
            }
        }

        return $tool;
    }

    public static function getOptions()
    {
        if (empty(self::$options)) {
            if (is_multisite()) {
                self::$options = get_site_option(Tiny_LXP_Platform::get_settings_name(), array());
            } else {
                self::$options = get_option(Tiny_LXP_Platform::get_settings_name(), array());
            }
            if (!is_array(self::$options)) {
                self::$options = array();
            }
        }

        return self::$options;
    }

    public static function getOption($name, $default)
    {
        self::getOptions();
        if (array_key_exists($name, self::$options)) {
            $default = self::$options[$name];
        }
        return $default;
    }

    public function __construct($dataConnector = null)
    {
        parent::__construct($dataConnector);
        $options = self::getOptions();
        $this->setSetting(
            'sendUserName',
            (isset($options['sendusername']) && ($options['sendusername'] === 'true')) ? 'true' : 'false'
        );
        $this->setSetting('sendUserId', (isset($options['senduserid']) && ($options['senduserid'] === 'true')) ? 'true' : 'false');
        $this->setSetting(
            'sendUserEmail',
            (isset($options['senduseremail']) && ($options['senduseremail'] === 'true')) ? 'true' : 'false'
        );
        $this->setSetting(
            'sendUserRole',
            (isset($options['senduserrole']) && ($options['senduserrole'] === 'true')) ? 'true' : 'false'
        );
        $this->setSetting(
            'sendUserUsername',
            (isset($options['senduserusername']) && ($options['senduserusername'] === 'true')) ? 'true' : 'false'
        );
        $this->setSetting('presentationTarget', (!empty($options['presentationtarget'])) ? $options['presentationtarget'] : '');
        $this->setSetting('presentationWidth', (!empty($options['presentationwidth'])) ? $options['presentationwidth'] : '');
        $this->setSetting('presentationHeight', (!empty($options['presentationheight'])) ? $options['presentationheight'] : '');
    }

    public function save($quiet = false)
    {
        $platform = new Tiny_LXP_Platform_Platform(Tiny_LXP_Platform::$tinyLxpPlatformDataConnector);
        $tools = $platform->getTools();
        $ok = true;
        $this->code = strtolower($this->code);
        foreach ($tools as $tool) {
            if ($tool->getRecordId() === $this->getRecordId()) {
                continue;
            } elseif ($tool->code === $this->code) {
                $ok = false;
                if (!$quiet) {
                    add_action('all_admin_notices', array($this, 'save_notice_duplicate'));
                }
                break;
            }
        }
        if ($ok) {
            if ($this->enabled && !$this->canBeEnabled()) {
                $this->enabled = false;
                if (!$quiet) {
                    add_action('all_admin_notices', array($this, 'save_notice_disabled'));
                }
            }
            $ok = $this->dataConnector->saveTool($this);
            if ($ok) {
                if (!$quiet) {
                    add_action('all_admin_notices', array($this, 'save_notice_success'));
                }
            } else if (!$quiet) {
                add_action('all_admin_notices', array($this, 'save_notice_error'));
            }
        }

        return $ok;
    }

    public function save_notice($message, $type = 'success')
    {
        echo ('    <div class="notice notice-' . esc_html($type) . ' is-dismissible">' . "\n");
        echo ('        <p>' . esc_html($message) . '</p>' . "\n");
        echo ('    </div>' . "\n");
    }

    public function save_notice_success()
    {
        $this->save_notice('Tool updated.');
    }

    public function save_notice_error()
    {
        $this->save_notice('An error occurred when saving tool.', 'error');
    }

    public function save_notice_duplicate()
    {
        $this->save_notice('A tool already exists with this code.', 'error');
    }

    public function save_notice_disabled()
    {
        $this->save_notice(
            'This tool cannot be enabled because it is not fully configured for either Tiny LXP 1.0 or Tiny LXP 1.3, or no private key has been defined.',
            'warning'
        );
    }

    public function trash()
    {
        return $this->dataConnector->trashTool($this);
    }

    public function restore()
    {
        return $this->dataConnector->restoreTool($this);
    }

    public function canUseTinyLXP13()
    {
        self::getOptions();
        return !empty($this->initiateLoginUrl) && !empty($this->redirectionUris) &&
            !empty(self::$options['kid']) && !empty(self::$options['privatekey']);
    }

    public static function all($args = array())
    {
        return Tiny_LXP_Platform::$tinyLxpPlatformDataConnector->getToolsWithArgs($args);
    }

    public static function register()
    {
        register_post_type(
            self::POST_TYPE,
            array(
                'labels' => array(
                    'name' => __('Tiny LXP Tools', Tiny_LXP_Platform::get_plugin_name()),
                    'singular_name' => __('Tiny LXP Tool', Tiny_LXP_Platform::get_plugin_name()),
                ),
                'rewrite' => false,
                'query_var' => false,
                'public' => false,
                'capability_type' => 'page',
            )
        );
        register_post_type(
            self::POST_TYPE_NETWORK,
            array(
                'labels' => array(
                    'name' => __('Network Tiny LXP Tools', Tiny_LXP_Platform::get_plugin_name()),
                    'singular_name' => __('Network Tiny LXP Tool', Tiny_LXP_Platform::get_plugin_name()),
                ),
                'rewrite' => false,
                'query_var' => false,
                'public' => false,
                'capability_type' => 'page',
            )
        );
        self::lxp_add_user_roles();
        add_shortcode(Tiny_LXP_Platform::get_plugin_name(), array('Tiny_LXP_Platform_Tool', 'shortcode'));
        add_shortcode('currikistudio', array('Tiny_LXP_Platform_Tool', 'currikistudio_shortcode'));
        add_shortcode('Schools-Short-Code', array('Tiny_LXP_Platform_Tool', 'schools_short_code'));
        add_shortcode('Teachers-Short-Code', array('Tiny_LXP_Platform_Tool', 'teachers_short_code'));
        add_shortcode('Students-Short-Code', array('Tiny_LXP_Platform_Tool', 'students_short_code'));
        add_shortcode('Dashboard-Short-Code', array('Tiny_LXP_Platform_Tool', 'dashboard_counts'));
    }



    private static function resolve_currikistudio_lesson_post($global_post = null)
    {
        if (!empty($global_post) && isset($global_post->post_type) && $global_post->post_type === TL_LESSON_CPT) {
            return $global_post;
        }

        if (class_exists('LP_Global')) {
            $item = LP_Global::course_item();
            if (!empty($item) && is_object($item) && method_exists($item, 'get_id')) {
                $lesson_id = absint($item->get_id());
                if ($lesson_id > 0) {
                    $lesson = get_post($lesson_id);
                    if (!empty($lesson) && isset($lesson->post_type) && $lesson->post_type === TL_LESSON_CPT) {
                        return $lesson;
                    }
                }
            }
        }

        if (!empty($_GET['post'])) {
            $requested_post_id = absint(wp_unslash($_GET['post']));
            if ($requested_post_id > 0) {
                $requested_post = get_post($requested_post_id);
                if (!empty($requested_post) && isset($requested_post->post_type) && $requested_post->post_type === TL_LESSON_CPT) {
                    return $requested_post;
                }
            }
        }

        return null;
    }

    private static function get_currikistudio_metadata_by_url($url = '', $lesson_post = null)
    {
        $url = esc_url_raw($url);
        if (empty($url)) {
            return null;
        }

        if (!empty($lesson_post) && isset($lesson_post->ID)) {
            $lesson_url = (string) get_post_meta($lesson_post->ID, 'lti_tool_url', true);
            $lesson_tool = (string) get_post_meta($lesson_post->ID, 'lti_tool_code', true);
            $lesson_attr_id = (string) get_post_meta($lesson_post->ID, 'lti_post_attr_id', true);
            if ($lesson_url === $url && !empty($lesson_tool) && !empty($lesson_attr_id)) {
                return array(
                    'lesson_id' => (int) $lesson_post->ID,
                    'tool' => $lesson_tool,
                    'id' => $lesson_attr_id,
                );
            }
        }

        $lesson_ids = get_posts(array(
            'post_type' => TL_LESSON_CPT,
            'post_status' => array('publish', 'private', 'draft'),
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => 'lti_tool_url',
                    'value' => $url,
                ),
            ),
        ));

        if (!empty($lesson_ids)) {
            $lesson_id = (int) $lesson_ids[0];
            $tool = (string) get_post_meta($lesson_id, 'lti_tool_code', true);
            $attr_id = (string) get_post_meta($lesson_id, 'lti_post_attr_id', true);
            if (!empty($tool) && !empty($attr_id)) {
                return array(
                    'lesson_id' => $lesson_id,
                    'tool' => $tool,
                    'id' => $attr_id,
                );
            }
        }

        return null;
    }

    public static function currikistudio_shortcode($atts, $content, $tag)
    {
        global $post;

        $atts = shortcode_atts(array(
            'url' => '',
            'height' => '475px',
        ), $atts);

        $url = esc_url_raw($atts['url']);
        if (empty($url)) {
            return '<strong>Missing attribute(s): url</strong>';
        }

        $lesson_post = self::resolve_currikistudio_lesson_post($post);
        $metadata = self::get_currikistudio_metadata_by_url($url, $lesson_post);
        if (empty($metadata) || empty($metadata['lesson_id']) || empty($metadata['tool']) || empty($metadata['id'])) {
            return '<strong>Unable to resolve CurrikiStudio metadata for this URL.</strong>';
        }

        $height = sanitize_text_field($atts['height']);
        if (empty($height)) {
            $height = '475px';
        }

        $launch_url = add_query_arg(
            array(
                Tiny_LXP_Platform::get_plugin_name() => '',
                'post' => (int) $metadata['lesson_id'],
                'id' => $metadata['id'],
            ),
            get_site_url()
        );

        return '<div class="currikistudio-embed" style="margin-top:16px;margin-bottom:16px;"><iframe style="border:none;width:100%;height:' . esc_attr($height) . ';" src="' . esc_url($launch_url) . '" allowfullscreen></iframe></div>';
    }

    public static function dashboard_counts()
    {
        $user = wp_get_current_user();
        $userDistrict = get_user_meta($user->ID, 'lxp_client_admin_id');
        $totalSchools = 0;
        $totalStudents = 0;
        $totalTeachers = 0;

        $args = array(
            'posts_per_page'   => -1,
            'post_type'        => 'tl_school',
            'meta_query' => array(
                array(
                    'key'   => 'lxp_school_district_id',
                    'value' =>  $userDistrict[0]
                )
            )
        );
        $schools = get_posts($args);
        foreach ($schools as  $key => $school) {
            $totalSchools++;
            $students = get_users(
                array(
                    'role' => 'lxp_student',
                    'meta_key' => 'lxp_school_id',
                    'meta_value' => $school->ID,
                    'number' => -1
                )
            );
            $totalStudents +=  count($students);
            $teachers = get_users(
                array(
                    'role' => 'lp_teacher',
                    'meta_key' => 'lxp_school_id',
                    'meta_value' => $school->ID,
                    'number' => -1
                )
            );
            $totalTeachers +=  count($teachers);
        }
        echo   'Schools ' . $totalSchools . '<br> Teachers ' . $totalTeachers . '<br>' . 'Students' . $totalStudents;
    }


    public static function schools_short_code()
    {
        $user = wp_get_current_user();
        $userDistrict = get_user_meta($user->ID, 'lxp_client_admin_id');
        $districtPost = get_post($userDistrict[0]);
        $table = "<table class='table' >";
        $table .= "<tr><th>School</th><th>Added On</th><th>ID</th><th>Administrator</th><th>District</th><th>Actions</th><tr>";
        global $wpdb;

        $posts_per_page = 1;
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
       
        $queryParam = "";
        $searchParam = "";
        if(isset($_GET['search_param']) && $_GET['search_param'] != ""){
            $searchParam = $_GET['search_param'];
           $queryParam = " (wp_posts.post_title LIKE '%".$_GET['search_param']. "%' OR wp_posts.ID LIKE '%" . $_GET['search_param'] . "%') and";
        }

        $total_posts = $wpdb->get_var("SELECT COUNT(*) FROM wp_posts INNER JOIN wp_postmeta ON (wp_posts.ID = wp_postmeta.post_id) WHERE  ".$queryParam." wp_postmeta.meta_key = 'lxp_school_district_id' AND wp_postmeta.meta_value = '" . $userDistrict[0] . "' AND wp_posts.post_type = 'tl_school'");
        $offset = ($paged - 1) * $posts_per_page;
        $base_url = get_pagenum_link(1);
        if(isset($_GET['search_param']) && $_GET['search_param'] != ""){
            $total_pages = ceil($total_posts / $posts_per_page);
            $max_offset = ($total_pages - 1) * $posts_per_page;
            if($offset > $max_offset){
                if($total_posts){
                    $base_url = add_query_arg('search_param', $searchParam, $base_url);
                }else{
                    $base_url = remove_query_arg('search_param', $base_url);
                }
                wp_redirect(add_query_arg('paged', '1', $base_url));
                exit;
            }
        }
        $query = "
            SELECT wp_posts.*
            FROM wp_posts
            INNER JOIN wp_postmeta ON (wp_posts.ID = wp_postmeta.post_id)
            WHERE ".$queryParam." wp_postmeta.meta_key = 'lxp_school_district_id' AND wp_postmeta.meta_value = '" . $userDistrict[0] . "'
            AND wp_posts.post_type = 'tl_school' 
            ORDER BY wp_posts.post_date DESC
            LIMIT $offset, $posts_per_page
        ";

        $posts = $wpdb->get_results($query);
        if ($posts) {
            foreach ($posts as $school) {
                $users = get_users(
                    array(
                        'role' => 'lxp_school_admin',
                        'meta_key' => 'lxp_school_admin_id',
                        'meta_value' => $school->ID,
                        'number' => -1
                    )
                );
                $adminName = isset($users[0]) ? $users[0]->display_name : '';
                $table .= "<tr>
                <td>" . $school->post_title . "</td>
                <td>" . $school->post_date . "</td>
                <td>" . $school->ID . "</td>
                <td>" . $adminName   . "</td>
                <td>" . $districtPost->post_title . "</td>
                 <td><a href='". home_url('/teachers/?school_id=') . $school->ID ."' class='btn btn-primary'>View Teachers</a>
                 <a href='". home_url('/students/?school_id=') . $school->ID ."' class='btn btn-primary'>View Students</a>
                 </td>
                <tr>";
            }
            echo  $table .= "</table>";
            wp_reset_postdata();

            $total_pages = ceil($total_posts / $posts_per_page);
            if ($total_pages > 1) {
                $base_url = add_query_arg('search_param', $searchParam, $base_url);
                $current_page = max(1, get_query_var('paged'));
                if ($current_page > $total_pages) {
                    $current_page = $total_pages;
                }
                echo '<div class="pagination">';
                echo paginate_links(array(
                    'base' => $base_url . '%_%',
                    'format' => '&paged=%#%',
                    'current' => $current_page,
                    'total' => $total_pages,
                    'prev_text' => __('&laquo; Previous'),
                    'next_text' => __('Next &raquo;')
                ));
                echo '</div>';
            }
        } else {
            echo 'No posts found';
        }
    }

    public static function teachers_short_code()
    {

        $user = wp_get_current_user();
        $userDistrict = get_user_meta($user->ID, 'lxp_client_admin_id');
        $districtPost = get_post($userDistrict[0]);
        $table = "<table class='table' >";
        $table .= "<tr><th>Teacher</th><th>Grade</th><th>ID</th><th>School</th><th>Region/District</th><tr>";
        global $wpdb;
        $posts_per_page = 1;
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

        $queryParam = "";
        $searchParam = "";
        $base_url = get_permalink();
        if(isset($_GET['search_param']) && $_GET['search_param'] != ""){
            $searchParam = $_GET['search_param'];
           $queryParam = " (u.display_name LIKE '%".$_GET['search_param']. "%' OR u.ID LIKE '%" . $_GET['search_param'] . "%') and";
        }
        $schoolID = "";
        if(isset($_GET['school_id']) && $_GET['school_id'] != ""){
            $schoolID = $_GET['school_id'];
            $queryParam .= " p.ID = " . $schoolID . " AND ";
            $base_url = add_query_arg(array( 'school_id' => $_GET['school_id']), $base_url);
        }
        $total_posts = $wpdb->get_var(" SELECT COUNT(*) 
        FROM wp_posts p
        JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = 'lxp_school_district_id' AND pm.meta_value = '" . $userDistrict[0] . "'
        JOIN wp_users u ON u.ID IN (
            SELECT user_id FROM wp_usermeta WHERE meta_key = 'wp_capabilities' AND meta_value LIKE '%lp_teacher%'
        ) 
        JOIN wp_usermeta um ON u.ID = um.user_id AND um.meta_key = 'lxp_school_id' AND um.meta_value = p.ID
        WHERE ".$queryParam." p.post_type = 'tl_school'");

        $offset = ($paged - 1) * $posts_per_page;
        if(isset($_GET['search_param']) && $_GET['search_param'] != ""){
            $total_pages = ceil($total_posts / $posts_per_page);
            $max_offset = ($total_pages - 1) * $posts_per_page;
            if($offset > $max_offset){
                if($total_posts){
                    $base_url = add_query_arg('search_param', $searchParam, $base_url);
                }else{
                    $base_url = remove_query_arg('search_param', $base_url); 
                }
                wp_redirect(add_query_arg('paged', '1', $base_url));
                exit;
            }
        }
        $query = "SELECT p.ID AS post_id,p.post_title AS post_title, u.ID AS user_id, u.display_name, u.user_email
           FROM wp_posts p
           JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = 'lxp_school_district_id' AND pm.meta_value = '" . $userDistrict[0] . "'
           JOIN wp_users u ON u.ID IN (
               SELECT user_id FROM wp_usermeta WHERE meta_key = 'wp_capabilities' AND meta_value LIKE '%lp_teacher%'
           ) 
           JOIN wp_usermeta um ON u.ID = um.user_id AND um.meta_key = 'lxp_school_id' AND um.meta_value = p.ID
           WHERE  ".$queryParam." p.post_type = 'tl_school'
           ORDER BY p.post_date DESC
           LIMIT $offset, $posts_per_page ";
        $posts = $wpdb->get_results($query);
        if ($posts) {
            foreach ($posts as $post) {
                $table .= "<tr>
               <td>" . $post->display_name . "</td>
               <td>" . '' . "</td>
               <td>" . $post->user_id . "</td>
               <td>" . $post->post_title . "</td>
               <td>" . $districtPost->post_title . "</td>
               <tr>";
            }
            echo  $table .= "</table>";
            wp_reset_postdata();
            $total_pages = ceil($total_posts / $posts_per_page);
            if ($total_pages > 1) {
                $base_url = add_query_arg('search_param', $searchParam, $base_url);
                $current_page = max(1, get_query_var('paged'));
                if ($current_page > $total_pages) {
                    $current_page = $total_pages;
                }
                echo '<div class="pagination">';
                echo paginate_links(array(
                    'base' => $base_url . '%_%',
                    'format' => '&paged=%#%',
                    'current' => $current_page,
                    'total' => $total_pages,
                    'prev_text' => __('&laquo; Previous'),
                    'next_text' => __('Next &raquo;')
                ));
                echo '</div>';
            }
        } else {
            echo 'No posts found';
        }
    }

    public static function students_short_code()
    {
        $user = wp_get_current_user();
        $userDistrict = get_user_meta($user->ID, 'lxp_client_admin_id');
        $districtPost = get_post($userDistrict[0]);
        $table = "<table class='table' >";
        $table .= "<tr><th>First Name</th><th>Last Name</th><th>Email</th><th>School</th><th>Grade</th><th>Level</th><tr>";

        global $wpdb;
        $posts_per_page = 1;
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        $offset = ($paged - 1) * $posts_per_page;
        $base_url = get_permalink();
        $queryParam = "";
        $searchParam = "";
        if(isset($_GET['search_param']) && $_GET['search_param'] != ""){
            $searchParam = $_GET['search_param'];
           $queryParam = " (um2.meta_value LIKE '%".$_GET['search_param']. "%' OR um3.meta_value LIKE '%" . $_GET['search_param'] . "%' OR u.ID LIKE '%" . $_GET['search_param'] . "%') and";
        }
        $schoolID = "";
        if(isset($_GET['school_id']) && $_GET['school_id'] != ""){
            $schoolID = $_GET['school_id'];
            $queryParam .= " p.ID = " . $schoolID . " AND ";
            $base_url = add_query_arg(array( 'school_id' => $_GET['school_id']), $base_url);
        }
        $total_posts = $wpdb->get_var(" SELECT COUNT(*) 
        FROM wp_posts p
         JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = 'lxp_school_district_id' AND pm.meta_value = '" . $userDistrict[0] . "'
         JOIN wp_users u ON u.ID IN (
            SELECT user_id FROM wp_usermeta WHERE meta_key = 'wp_capabilities' AND meta_value LIKE '%lxp_student%'
         ) 
         JOIN wp_usermeta um ON u.ID = um.user_id AND um.meta_key = 'lxp_school_id' AND um.meta_value = p.ID
         JOIN wp_usermeta um2 ON u.ID = um2.user_id AND um2.meta_key = 'first_name'
         JOIN wp_usermeta um3 ON u.ID = um3.user_id AND um3.meta_key = 'last_name'
         WHERE ". $queryParam ." p.post_type = 'tl_school'");
        if(isset($_GET['search_param']) && $_GET['search_param'] != ""){
            $total_pages = ceil($total_posts / $posts_per_page);
            $max_offset = ($total_pages - 1) * $posts_per_page;
            if($offset > $max_offset){
                if($total_posts){
                    $base_url = add_query_arg('search_param', $searchParam, $base_url);
                }else{
                    $base_url = remove_query_arg('search_param', $base_url); // remove search parameter
                }
                wp_redirect(add_query_arg('paged', '1', $base_url));
                exit;
            }
        }

        $query = "SELECT p.ID AS post_id, p.post_title AS post_title, u.ID AS user_id, u.display_name, u.user_email, um2.meta_value AS first_name, um3.meta_value AS last_name
        FROM wp_posts p
        JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = 'lxp_school_district_id' AND pm.meta_value = '" . $userDistrict[0] . "'
        JOIN wp_users u ON u.ID IN (
           SELECT user_id FROM wp_usermeta WHERE meta_key = 'wp_capabilities' AND meta_value LIKE '%lxp_student%'
        ) 
        JOIN wp_usermeta um ON u.ID = um.user_id AND um.meta_key = 'lxp_school_id' AND um.meta_value = p.ID
        JOIN wp_usermeta um2 ON u.ID = um2.user_id AND um2.meta_key = 'first_name'
        JOIN wp_usermeta um3 ON u.ID = um3.user_id AND um3.meta_key = 'last_name'
        WHERE  ". $queryParam ."  p.post_type = 'tl_school'
        ORDER BY p.post_date DESC
        LIMIT $offset, $posts_per_page";
        $posts = $wpdb->get_results($query);





        if ($posts) {
            foreach ($posts as $post) {
                $table .= "<tr>
               <td>" . $post->first_name. "</td>
               <td>" . $post->last_name . "</td>
               <td>" . $post->user_email . "</td>
               <td>" . $post->post_title . "</td>
               <td>" . $districtPost->post_title . "</td>
               <tr>";
            }
            echo  $table .= "</table>";
            wp_reset_postdata();
            $total_pages = ceil($total_posts / $posts_per_page);
            if ($total_pages > 1) {
                $base_url = add_query_arg('search_param', $searchParam, $base_url);
                $current_page = max(1, get_query_var('paged'));
                if ($current_page > $total_pages) {
                    $current_page = $total_pages;
                }
                echo '<div class="pagination">';
                echo paginate_links(array(
                    'base' => $base_url . '%_%',
                    'format' => '&paged=%#%',
                    'current' => $current_page,
                    'total' => $total_pages,
                    'prev_text' => __('&laquo; Previous'),
                    'next_text' => __('Next &raquo;')
                ));
                echo '</div>';
            }
        } else {
            echo 'No posts found';
        }
    }

    public static function lxp_add_user_roles()
    {

        add_role(
            'lp_teacher',
            'LP Instructor',
            array()
        );

        add_role(
            'lxp_student',
            'Lxp Student ',
            array()
        );

        add_role(
            'lxp_client_admin',
            'Lxp Client Admin ',
            array()
        );


        add_role(
            'lxp_school_admin',
            'Lxp School Admin ',
            array()
        );


        $course_cap = "_lxp_course";
        $lesson_cap = "_lxp_lesson";
        $school_cap = "_lxp_school";
        $district_cap = "_lxp_district";

        $lp_teacher = get_role('lp_teacher');
        if ($lp_teacher) {
            $lp_teacher->add_cap('read' . $course_cap);
            $lp_teacher->add_cap('read_private' . $course_cap . "s");
            $lp_teacher->add_cap('create' . $course_cap);
            $lp_teacher->add_cap('create' . $course_cap . "s");
            $lp_teacher->add_cap('delete' . $course_cap);
            $lp_teacher->add_cap('lp_teacher' . $course_cap . "s");
            $lp_teacher->add_cap('edit' . $course_cap);
            $lp_teacher->add_cap('edit' . $course_cap . "s");
            $lp_teacher->add_cap('publish' . $course_cap . "s");
            $lp_teacher->add_cap('grades' . $course_cap);
            $lp_teacher->add_cap('read' . $lesson_cap);
            $lp_teacher->add_cap('create' . $lesson_cap);
            $lp_teacher->add_cap('create' . $lesson_cap . "s");
            $lp_teacher->add_cap('delete' . $lesson_cap);
            $lp_teacher->add_cap('edit' . $lesson_cap);
            $lp_teacher->add_cap('edit' . $lesson_cap . "s");
            $lp_teacher->add_cap('publish' . $lesson_cap . "s");
        }

        $student = get_role('lxp_student');
        if ($student) {
            $student->add_cap('read' . $course_cap);
            $student->add_cap('read' . $lesson_cap);
        }

        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('read' . $course_cap);
            $admin->add_cap('read_private' . $course_cap . "s");
            $admin->add_cap('create' . $course_cap);
            $admin->add_cap('create' . $course_cap . "s");
            $admin->add_cap('delete' . $course_cap);
            $admin->add_cap('delete' . $course_cap . "s");
            $admin->add_cap('edit' . $course_cap);
            $admin->add_cap('edit' . $course_cap . "s");
            $admin->add_cap('publish' . $course_cap . "s");

            $admin->add_cap('manage_category' . $course_cap);
            $admin->add_cap('edit_category' . $course_cap);
            $admin->add_cap('delete_category' . $course_cap);
            $admin->add_cap('assign_category' . $course_cap);
            $admin->add_cap('manage_tag' . $course_cap);
            $admin->add_cap('edit_tag' . $course_cap);
            $admin->add_cap('delete_tag' . $course_cap);
            $admin->add_cap('assign_tag' . $course_cap);

            $admin->add_cap('read' . $lesson_cap);
            $admin->add_cap('read_private' . $course_cap);
            $admin->add_cap('create' . $lesson_cap);
            $admin->add_cap('create' . $lesson_cap . "s");
            $admin->add_cap('delete' . $lesson_cap);
            $admin->add_cap('delete' . $lesson_cap . "s");
            $admin->add_cap('edit' . $lesson_cap . "s");
            $admin->add_cap('edit' . $lesson_cap);
            $admin->add_cap('publish' . $lesson_cap . "s");
            $admin->add_cap('manage_tag' . $lesson_cap);
            $admin->add_cap('edit_tag' . $lesson_cap);
            $admin->add_cap('delete_tag' . $lesson_cap);
            $admin->add_cap('assign_tag' . $lesson_cap);
        }

        $school_admin = get_role('lxp_school_admin');                    //Trek school admin
        if ($school_admin) {
            $school_admin->add_cap('read' . $school_cap);
        }



        $client_admin = get_role('lxp_client_admin');                    //Trek district admin
        if ($client_admin) {
            $client_admin->add_cap('read' . $school_cap);
            $client_admin->add_cap('read_private' . $school_cap . "s");
            $client_admin->add_cap('read_private' . $school_cap);
            $client_admin->add_cap('create' . $school_cap);
            $client_admin->add_cap('create' . $school_cap . "s");
            $client_admin->add_cap('delete' . $school_cap);
            $client_admin->add_cap('delete' . $school_cap . "s");
            $client_admin->add_cap('edit' . $school_cap);
            $client_admin->add_cap('edit' . $school_cap . "s");
            $client_admin->add_cap('publish' . $school_cap . "s");


            $client_admin->add_cap('read' . $district_cap);
            // $client_admin->add_cap('read_private' . $district_cap . "s");
            // $client_admin->add_cap('read_private' . $district_cap);
            // $client_admin->add_cap('create' . $district_cap);
            // $client_admin->add_cap('create' . $district_cap . "s");
            // $client_admin->add_cap('delete' . $district_cap);
            // $client_admin->add_cap('delete' . $district_cap . "s");
            // $client_admin->add_cap('edit' . $district_cap);
            // $client_admin->add_cap('edit' . $district_cap . "s");
            // $client_admin->add_cap('publish' . $district_cap . "s");
        }
    }


    public static function shortcode($atts, $content, $tag)
    {
        global $post;

        $html = '<em>Tiny LXP link appears here</em>';

        $atts = shortcode_atts(array(
            'tool' => '',
            'id' => '',
            'custom' => '',
            'target' => '',
            'width' => '',
            'height' => ''
        ), $atts);

        $error = '';
        $missing = array();
        if (empty($atts['tool'])) {
            $missing[] = 'tool';
        }
        if (empty($atts['id'])) {
            $missing[] = 'id';
        }
        if (!empty($missing)) {
            $error = 'Missing attribute(s): ' . implode(', ', $missing);
        }
        if (empty($error)) {
            $tool = Tiny_LXP_Platform_Tool::fromCode($atts['tool'], Tiny_LXP_Platform::$tinyLxpPlatformDataConnector);
            if (empty($tool)) {
                $error = 'Tool parameter not recognised: ' . $atts['tool'];
            }
        }
        if (empty($error) && !$tool->enabled) {
            $error = 'Tiny LXP Tool is not available';
        }
        if (empty($error)) {
            $target = (!empty($atts['target'])) ? $atts['target'] : $tool->getSetting('presentationTarget');
            if (!in_array($target, array('window', 'popup', 'iframe', 'embed'))) {
                $error = 'Invalid presentation target: ' . $target;
            }
        }
        if (empty($error)) {
            if (!empty($content)) {
                $link_text = $content;
            } else {
                $link_text = $atts['tool'];
            }
            if (($target === 'popup') || ($target === 'embed')) {
                $width = $tool->getSetting('presentationWidth');
                if (!empty($atts['width'])) {
                    $width = intval($atts['width']);
                }
                $height = $tool->getSetting('presentationHeight');
                if (!empty($atts['height'])) {
                    $height = intval($atts['height']);
                }
                if ($target === 'popup') {
                    $sep = ',';
                    $sep2 = '=';
                    if (empty($width)) {
                        $width = '800';
                    }
                    if (empty($height)) {
                        $height = '500';
                    }
                } else {
                    $sep = ';';
                    $sep2 = ': ';
                    if (empty($width)) {
                        $width = '100%';
                    }
                    if (empty($height)) {
                        $height = '400px';
                    }
                }
                $size = '';
                if (!empty($width)) {
                    $size = "width{$sep2}{$width}{$sep}";
                }
                if (!empty($height)) {
                    $size .= "height{$sep2}{$height}{$sep}";
                }
                if (!empty($size) && ($target === 'popup')) {
                    $size = substr($size, 0, -1);
                }
            }
            $url = add_query_arg(
                array(Tiny_LXP_Platform::get_plugin_name() => '', 'post' => $post->ID, 'id' => $atts['id']),
                get_site_url()
            );
            switch ($target) {
                case 'window':
                    $html = "<a href=\"{$url}\" title=\"Launch {$atts['tool']} tool\" target=\"_blank\">{$link_text}</a>";
                    break;
                case 'popup':
                    $html = "<a href=\"#\" title=\"Launch {$atts['tool']} tool\" onclick=\"window.open('{$url}', '', '{$size}'); return false;\">{$link_text}</a>";
                    break;
                case 'iframe':
                    $url = add_query_arg(array('embed' => ''), $url);
                    $html = "<a href=\"{$url}\" title=\"Embed {$atts['tool']} tool\">{$link_text}</a>";
                    break;
                case 'embed':
                    $html = "{$content}</p><div><iframe style=\"border: none;{$size}\" class=\"\" src=\"{$url}\" allowfullscreen></iframe></div><p>";
                    break;
            }
        } else {
            $html = "<strong>{$error}</strong>";
        }

        return $html;
    }

    public function canBeEnabled()
    {
        return !empty($this->messageUrl) &&
            ((!empty($this->getKey()) && !empty($this->secret)) || $this->canUseTinyLXP13());
    }
}
