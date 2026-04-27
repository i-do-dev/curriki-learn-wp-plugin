<?php
$livePath = dirname( __FILE__ );
// require_once ABSPATH . 'wp-load.php';
// require_once $livePath.'/lxp/functions.php';
lxp_login_check();

$treks_src = content_url().'/plugins/TinyLxp-wp-plugin/lms/templates/tinyLxpTheme/treks-src/';
$userdata = get_userdata(get_current_user_id());

$userRole = lxp_get_active_role(get_current_user_id());
switch ($userRole) {
  case 'lxp_client_admin':
    include $livePath.'/lxp/client-students.php';
    break;
  case 'lxp_school_admin':
    include $livePath.'/lxp/school-students.php';
    break;
  case 'lp_teacher':
    include $livePath.'/lxp/teacher-students.php';
    break;
  case 'administrator':
    include $livePath.'/lxp/admin-students.php';
    break;
  default:
    echo 'Not a valid User role';
    break;
}

?>