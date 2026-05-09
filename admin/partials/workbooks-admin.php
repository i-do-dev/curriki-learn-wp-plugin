<?php
/**
 * Admin partial: Workbooks selector page.
 *
 * @var array<int, WP_User>                    $users
 * @var int                                    $selected_user_id
 * @var WP_User|false                          $selected_user
 * @var array<int, array<string, mixed>>       $courses
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : 'curriki-learn';
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Workbooks', 'tiny-lxp-platform' ); ?></h1>
	<p><?php esc_html_e( 'Select a user to view the courses where their workbook is available.', 'tiny-lxp-platform' ); ?></p>

	<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="margin: 20px 0 30px; display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
		<input type="hidden" name="page" value="<?php echo esc_attr( $current_page ); ?>" />
		<div>
			<label for="workbook_user_id" style="display: block; font-weight: 600; margin-bottom: 6px;"><?php esc_html_e( 'User', 'tiny-lxp-platform' ); ?></label>
			<select id="workbook_user_id" name="workbook_user_id" style="min-width: 320px;">
				<option value="0"><?php esc_html_e( 'Select a user', 'tiny-lxp-platform' ); ?></option>
				<?php foreach ( $users as $user ) : ?>
					<option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( $selected_user_id, $user->ID ); ?>>
						<?php echo esc_html( $user->display_name . ' (' . $user->user_email . ')' ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<p style="margin: 0;">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Load Courses', 'tiny-lxp-platform' ); ?></button>
		</p>
	</form>

	<?php if ( $selected_user && empty( $courses ) ) : ?>
		<div class="notice notice-info inline"><p><?php esc_html_e( 'No relevant workbook courses were found for the selected user.', 'tiny-lxp-platform' ); ?></p></div>
	<?php endif; ?>

	<?php if ( $selected_user && ! empty( $courses ) ) : ?>
		<h2 style="margin-bottom: 12px;">
			<?php echo esc_html( sprintf( __( 'Courses for %s', 'tiny-lxp-platform' ), $selected_user->display_name ) ); ?>
		</h2>
		<table class="widefat striped" style="max-width: 980px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Course', 'tiny-lxp-platform' ); ?></th>
					<th><?php esc_html_e( 'Action', 'tiny-lxp-platform' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $courses as $course ) : ?>
					<tr>
						<td>
							<strong><?php echo esc_html( (string) $course['title'] ); ?></strong>
						</td>
						<td>
							<a class="button button-secondary" href="<?php echo esc_url( (string) $course['workbook_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open Workbook', 'tiny-lxp-platform' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>