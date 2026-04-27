<?php
/**
 * Admin partial: Workbook Submission detail page.
 *
 * Loaded by Tiny_LXP_Platform_Admin::workbook_submissions_page() when ?view=detail&id=N.
 *
 * @var object $submission  Row from the workbook submissions repository (or null).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$back_url = admin_url( 'admin.php?page=curriki-learn-workbook-submissions' );
?>
<div class="wrap">
	<h1>
		<?php esc_html_e( 'Workbook Submission Detail', 'tiny-lxp-platform' ); ?>
		<a href="<?php echo esc_url( $back_url ); ?>" class="page-title-action">
			<?php esc_html_e( '&larr; Back to list', 'tiny-lxp-platform' ); ?>
		</a>
	</h1>

	<?php if ( ! $submission ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'Submission not found.', 'tiny-lxp-platform' ); ?></p></div>
	<?php else :
		$fields = json_decode( $submission->fields, true );
		?>

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

	<h2 style="margin-top:28px;"><?php esc_html_e( 'Workbook Answers', 'tiny-lxp-platform' ); ?></h2>

	<?php if ( empty( $fields ) ) : ?>
		<p><?php esc_html_e( 'No answers recorded.', 'tiny-lxp-platform' ); ?></p>
	<?php else : ?>
		<table class="widefat striped" style="max-width:860px;">
			<thead>
				<tr>
					<th style="width:35%;"><?php esc_html_e( 'Field', 'tiny-lxp-platform' ); ?></th>
					<th><?php esc_html_e( 'Answer', 'tiny-lxp-platform' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $fields as $label => $answer ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $label ); ?></strong></td>
					<td style="white-space:pre-wrap;"><?php echo esc_html( $answer ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<?php endif; ?>
</div>
