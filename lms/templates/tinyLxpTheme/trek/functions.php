<?php
function lxp_login_check()
{
  if (!is_user_logged_in()) {
    global $wp;
    $url = "http" . (isset($_SERVER["HTTPS"]) ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    wp_redirect(site_url('login') . '?redirect=' . urlencode($url));
  }
}

function lxp_get_all_students() {
  global $wpdb;
  $students = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "students");
  return $students;
}
/* 
function trek_assigned_students($trek_id) {
  global $wpdb;
  $query = "SELECT {$wpdb->prefix}students.* FROM {$wpdb->prefix}students
        JOIN {$wpdb->prefix}student_assignments ON {$wpdb->prefix}student_assignments.student_id = {$wpdb->prefix}students.id
        JOIN {$wpdb->prefix}trek_events ON {$wpdb->prefix}trek_events.id = {$wpdb->prefix}student_assignments.assignment_id
        JOIN {$wpdb->prefix}trek_sections ON {$wpdb->prefix}trek_sections.id = {$wpdb->prefix}trek_events.trek_section_id
        JOIN {$wpdb->prefix}posts ON {$wpdb->prefix}posts.ID = {$wpdb->prefix}trek_sections.trek_id
        WHERE {$wpdb->prefix}posts.ID = {$trek_id}
      ";
  return $wpdb->get_results($query);
}
 */
function trek_section_assigned_students($trek_segment_id) {
  $teacher_id = get_current_user_id();
  global $wpdb;
  $query = "SELECT {$wpdb->prefix}students.* FROM {$wpdb->prefix}students
        JOIN {$wpdb->prefix}student_assignments ON {$wpdb->prefix}student_assignments.student_id = {$wpdb->prefix}students.id
        JOIN {$wpdb->prefix}trek_events ON {$wpdb->prefix}trek_events.id = {$wpdb->prefix}student_assignments.assignment_id
        WHERE {$wpdb->prefix}trek_events.trek_section_id = \"{$trek_segment_id}\" AND {$wpdb->prefix}trek_events.user_id = \"{$teacher_id}\"
      ";
  return $wpdb->get_results($query);
}