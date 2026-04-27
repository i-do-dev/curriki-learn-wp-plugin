<?php
$livePath = dirname( __FILE__ );
// require_once $livePath.'/lxp/functions.php';
lxp_login_check();

$treks_src = content_url().'/plugins/TinyLxp-wp-plugin/lms/templates/tinyLxpTheme/treks-src/';
$userdata = get_userdata(get_current_user_id());
$userRole = lxp_get_active_role(get_current_user_id());
switch ($userRole) {
  case 'lp_teacher':
    include $livePath.'/lxp/teacher-search.php';
    break;
  case 'lxp_student':
    include $livePath.'/lxp/student-search.php';
    break;
  default:
    echo 'Not a valid User role';
    break;
}