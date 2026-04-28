<?php
/**
 * Admin partial: Capstone Submissions list page.
 *
 * Loaded by Tiny_LXP_Platform_Admin::capstone_submissions_page() when no ?view=detail.
 *
 * @var TL_Capstone_Submission_List_Table $list_table  Prepared list table instance.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Capstone Submissions', 'tiny-lxp-platform' ); ?></h1>

	<?php $list_table->render_filter_bar(); ?>

	<form method="post">
		<?php
		$list_table->display();
		?>
	</form>
</div>
