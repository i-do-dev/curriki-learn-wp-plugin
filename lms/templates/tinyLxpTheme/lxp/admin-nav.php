<?php
global $treks_src;
global $post;
$userdata = get_userdata(get_current_user_id());
?>
<ul>
        <?php if ($post->post_name === 'dashboard') {?>
            <li class="nav-section-selected">
                <img src="<?php echo $treks_src; ?>/assets/img/nav_dashboard-dots.svg" />
                <a href="<?php echo site_url("dashboard") ?>">Dashboard</a>
            </li>
        <?php } else {?>
            <li>
                <img src="<?php echo $treks_src; ?>/assets/img/nav_dashboard-dots_gray.svg" />
                <a href="<?php echo site_url("dashboard") ?>">Dashboard</a>
            </li>
        <?php } ?>

        <?php if (in_array( 'lxp_client_admin', $userdata->roles)) { ?>
            <?php if ($post->post_name === 'schools') {?>
                <li class="nav-section-selected">
                    <img src="<?php echo $treks_src; ?>/assets/img/select-nav-home.svg" />
                    <a href="<?php echo site_url("schools") ?>">Schools</a>
                </li>
            <?php } else { ?>
                <li>
                    <img src="<?php echo $treks_src; ?>/assets/img/nav-home.svg" />
                    <a href="<?php echo site_url("schools") ?>">Schools</a>
                </li>
            <?php }?>        

        <?php } ?>

        <?php if ($post->post_name === 'teachers') {?>
            <li class="nav-section-selected">
                <img src="<?php echo $treks_src; ?>/assets/img/select-verified-user.svg" />
                <a href="<?php echo site_url("teachers") ?>">Teachers</a>
            </li>
            
        <?php } else {?>
            <li>
                <img src="<?php echo $treks_src; ?>/assets/img/nav-verified-user.svg" />
                <a href="<?php echo site_url("teachers") ?>">Teachers</a>
            </li>
            
        <?php } ?>

        <?php if ($post->post_name === 'students') {?>
            <li class="nav-section-selected">
                <img src="<?php echo $treks_src; ?>/assets/img/select-user.svg" />
                <a href="<?php echo site_url("students") ?>">Students</a>
            </li>
        <?php } else {?>
            <li>
                <img src="<?php echo $treks_src; ?>/assets/img/nav-user.svg" />
                <a href="<?php echo site_url("students") ?>">Students</a>
            </li>
        <?php } ?>

</ul>