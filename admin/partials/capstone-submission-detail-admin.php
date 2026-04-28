<?php
/**
 * Admin partial: Capstone Submission detail page.
 *
 * Loaded by Tiny_LXP_Platform_Admin::capstone_submissions_page() when ?view=detail&id=N.
 *
 * @var object $submission  Row from the capstone submissions repository (or null).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$back_url = admin_url( 'admin.php?page=curriki-learn-capstone-submissions' );
?>
<div class="wrap">
	<h1>
		<?php esc_html_e( 'Capstone Submission Detail', 'tiny-lxp-platform' ); ?>
		<a href="<?php echo esc_url( $back_url ); ?>" class="page-title-action">
			<?php esc_html_e( '&larr; Back to list', 'tiny-lxp-platform' ); ?>
		</a>
	</h1>

	<?php if ( ! $submission ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'Submission not found.', 'tiny-lxp-platform' ); ?></p></div>
	<?php else : ?>

	<table class="form-table widefat" style="max-width:860px;">
		<tbody>
			<tr>
				<th><?php esc_html_e( 'Student', 'tiny-lxp-platform' ); ?></th>
				<td>
					<?php echo esc_html( $submission->display_name ?: '—' ); ?>
					<?php if ( $submission->user_email ) : ?>
						<span style="color:#666;"> &lt;<?php echo esc_html( $submission->user_email ); ?>&gt;</span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Lesson', 'tiny-lxp-platform' ); ?></th>
				<td>
					<?php if ( $submission->lesson_title ) : ?>
						<a href="<?php echo esc_url( get_permalink( absint( $submission->lesson_id ) ) ); ?>" target="_blank">
							<?php echo esc_html( $submission->lesson_title ); ?>
						</a>
					<?php else : ?>
						<?php echo esc_html( $submission->lesson_id ); ?>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Course', 'tiny-lxp-platform' ); ?></th>
				<td>
					<?php if ( $submission->course_title ) : ?>
						<a href="<?php echo esc_url( get_permalink( absint( $submission->course_id ) ) ); ?>" target="_blank">
							<?php echo esc_html( $submission->course_title ); ?>
						</a>
					<?php else : ?>
						<?php echo esc_html( $submission->course_id ); ?>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Submitted', 'tiny-lxp-platform' ); ?></th>
				<td><?php echo esc_html( $submission->submitted_at ?: '—' ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Last Updated', 'tiny-lxp-platform' ); ?></th>
				<td><?php echo esc_html( $submission->updated_at ?: '—' ); ?></td>
			</tr>
		</tbody>
	</table>

	<h2 style="margin-top:28px;"><?php esc_html_e( 'Capstone Response', 'tiny-lxp-platform' ); ?></h2>

	<?php if ( empty( $submission->response ) ) : ?>
		<p><?php esc_html_e( 'No response recorded.', 'tiny-lxp-platform' ); ?></p>
	<?php else : ?>
		<div style="
			max-width:860px;
			background:#f9f8fc;
			border:1px solid rgba(68,46,102,.15);
			border-radius:10px;
			padding:20px 24px;
			white-space:pre-wrap;
			font-size:0.97rem;
			line-height:1.7;
			color:#333;
		"><?php echo esc_html( $submission->response ); ?></div>
	<?php endif; ?>

	<?php endif; ?>
</div>
