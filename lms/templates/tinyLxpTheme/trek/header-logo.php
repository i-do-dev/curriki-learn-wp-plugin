<?php
	global $treks_src;
    $custom_logo_id = get_theme_mod( 'custom_logo' );
    $logo_url = wp_get_attachment_image_src( $custom_logo_id , 'full' );
?>
<a class="navbar-brand" href="<?php echo site_url("dashboard"); ?>/">
    <div class="header-logo-search">
        <!-- logo -->
        <?php if(has_custom_logo()) { ?>
            <img width="200" height="80" src="<?php echo esc_url( $logo_url[0] ); ?>" alt="TinyLxp Logo" />
        <?php } else { ?>
            <div class="header-logo rounded">
                <img width="200" src="<?php echo $treks_src; ?>/assets/img/test.png" alt="LXP" />
            </div>
        <?php } ?>
    </div>
</a>