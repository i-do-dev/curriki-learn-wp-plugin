<?php
$livePath = dirname( __FILE__ );
// require_once ABSPATH . 'wp-load.php';
// require_once $livePath.'/lxp/functions.php';
lxp_login_check();

$treks_src = plugins_url('lms/templates/tinyLxpTheme/treks-src/', __FILE__);
$userdata = get_userdata(get_current_user_id());

$userRole = lxp_get_active_role(get_current_user_id());
switch ($userRole) {  
  case 'lp_teacher':
    include $livePath.'/lxp/teacher-groups.php';
    break;
  case 'administrator':
    include $livePath.'/lxp/admin-groups.php';
    break;
  default:
    echo 'Not a valid User role';
    break;
}

?>