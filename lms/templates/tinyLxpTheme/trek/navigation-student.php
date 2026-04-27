<?php
  global $treks_src;
  global $post;
?>
<nav class="nav-section">
  <ul>
    <li class="<?php echo $post->post_name === "dashboard" ? "nav-section-selected" : ""; ?>">
      <img src="<?php echo $treks_src; ?>/assets/img/<?php echo $post->post_name === "dashboard" ? "nav_dashboard-dots.svg" : "nav_dashboard-dots_gray.svg"; ?>" />
      <a href="<?php echo site_url("dashboard") ?>/">Dashboard</a>
    </li>
    <li class="<?php echo $post->post_name === "courses" || $post->post_type === TL_COURSE_CPT ? "nav-section-selected" : ""; ?>">
      <img src="<?php echo $treks_src; ?>/assets/img/<?php echo $post->post_name === "courses" || $post->post_type === TL_COURSE_CPT ? "nav_treks_selected.svg" : "nav_Treks.svg"; ?>" />
      <a href="<?php echo site_url("courses") ?>/">Courses</a>
    </li>
    <li class="<?php echo $post->post_name === "grades" ? "nav-section-selected" : ""; ?>">
      <img width="28" src="<?php echo $treks_src; ?>/assets/img/review-icon.svg" style="opacity: 0.7;" />
      <a href="<?php echo site_url("grades"); ?>/">Grades</a>
    </li>
    <li class="<?php echo $post->post_name === "assignments" ? "nav-section-selected" : ""; ?>">
      <img src="<?php echo $treks_src; ?>/assets/img/nav_reports.svg" />
      <a href="<?php echo site_url("assignments"); ?>/">Assignments</a>
    </li>
    <!-- <li class="<?php //echo ( $post->post_name === "calendar" ) ? "nav-section-selected" : ""; ?>">
      <img src="<?php //echo $treks_src; ?>/assets/img/calendar.svg" />
      <a href="<?php //echo site_url("calendar"); ?>">Calendar</a>
    </li> -->
  </ul>
</nav>