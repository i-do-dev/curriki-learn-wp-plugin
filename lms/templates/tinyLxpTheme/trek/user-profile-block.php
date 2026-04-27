<?php
global $post;
global $treks_src;
$user = wp_get_current_user();
if (is_user_logged_in()) {
?>
<!-- notification -->
<div class="header-notification">
  <img src="<?php echo $treks_src; ?>/assets/img/header_bell-notification.svg" alt="svg" />
</div>
<!-- user detail & Image  -->
<div class="header-user">
  <!-- User Avatar -->
  <div class="user-avatar">
    <img src="<?php echo $treks_src; ?>/assets/img/profile-icon.png" alt="svg" />
  </div>
  <!-- User short detail -->
  <div class="user-detail">
    <span class="user-detail-name"><?php echo $user ? $user->data->display_name : "Kristin Watson"; ?></span>
    <!-- <span>Science teacher</span> -->
    <a id="logout-link" href="<?php echo wp_logout_url("login"); ?>">Logout</a>
  </div>
  <!-- Arrow for open menu -->
  <div class="user-options">
    <img src="<?php echo $treks_src; ?>/assets/img/header_arrow open.svg" alt="svg" />
  </div>
</div>
<?php
} else if($post->post_name !== "login") {
?>
<!-- notification -->
<div class="header-notification">
  <img src="<?php echo $treks_src; ?>/assets/img/header_bell-notification.svg" alt="svg" />
</div>
<!-- user detail & Image  -->
<div class="header-user">
  <!-- User Avatar -->
  <div class="user-avatar">
    <img src="<?php echo $treks_src; ?>/assets/img/header_avatar.svg" alt="svg" />
  </div>
  <!-- User short detail -->
  <div class="user-detail">
    <span class="user-detail-name">Kristin Watson</span>
    <span>Science teacher</span>
  </div>
  <!-- Arrow for open menu -->
  <div class="user-options">
    <img src="<?php echo $treks_src; ?>/assets/img/header_arrow open.svg" alt="svg" />
  </div>
</div>
<?php
}
?>