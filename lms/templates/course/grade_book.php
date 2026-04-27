<?php
/*
Template Name: gradebook-template
*/
?>
<!doctype html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <style>
        body {
            background-color: white;
        }
    </style>
</head>

<body <?php body_class(); ?>>

    <div class="wp-site-blocks">
        <header class="wp-block-template-part site-header">
            <?php //block_header_area(); ?>
        </header>
        <div id="primary" class="content-area">
            <main id="main" class="site-main" role="main" style="overflow-y:auto;width:98%;">
            <iframe style="border: none;width: 100%;height: 400px;" class="" src="<?php echo site_url() ?>?lti-platform&post=<?php echo $_GET['lesson_id'] ?>&id=jcfvxikc&is_summary=1&student_id=<?php echo $_GET['student_id'] ?>"  allowfullscreen></iframe>
            </main><!-- .site-main -->
        </div><!-- .content-area -->

    </div>
</body>
