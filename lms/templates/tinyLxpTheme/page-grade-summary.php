<?php
  $livePath = dirname( __FILE__ );
  // require_once $livePath.'/lxp/functions.php';
	lxp_login_check();


  $treks_src = plugins_url('lms/templates/tinyLxpTheme/treks-src/', __FILE__);
  $userdata = get_userdata(get_current_user_id());
  $userRole = lxp_get_active_role(get_current_user_id());
  switch ($userRole) {
    case 'lxp_student':
      include $livePath.'/lxp/student-grade-summary.php';
      break;
    default:
      echo 'Not a valid User role';
      break;
  }