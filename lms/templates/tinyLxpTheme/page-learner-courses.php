<?php
get_header();
?>
<style>
	.lp-hidden {
		display: none;
	}
	.has-sidebar {
		display: flex;
	}
	.lp-main-content {
		width: 75%;
	}
	.course-readmore {
		display: none !important;
	}
</style>

<?php
	// Force LearnPress archive course template 
	learn_press_get_template( 'archive-course.php' );
get_footer();
