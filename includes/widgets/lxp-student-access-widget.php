<?php
namespace Edudeme\Elementor;

use Elementor\Controls_Manager;

class LXP_Student_Access_Widget extends \Elementor\Widget_Base {

	public function get_name()       { return 'lxp-student-access'; }
	public function get_title()      { return esc_html__( 'LXP Student Access', 'tinylxp' ); }
	public function get_icon()       { return 'eicon-lock-user'; }
	public function get_categories() { return [ 'general' ]; }

	protected function register_controls() {

		// ── Content ───────────────────────────────────────────────────────
		$this->start_controls_section( 'section_content', [
			'label' => esc_html__( 'Content', 'tinylxp' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'heading', [
			'label'   => esc_html__( 'Heading', 'tinylxp' ),
			'type'    => Controls_Manager::TEXT,
			'default' => esc_html__( 'Enter your Student ID', 'tinylxp' ),
		] );

		$this->add_control( 'field_label', [
			'label'   => esc_html__( 'Field Label', 'tinylxp' ),
			'type'    => Controls_Manager::TEXT,
			'default' => esc_html__( 'Student ID', 'tinylxp' ),
		] );

		$this->add_control( 'button_label', [
			'label'   => esc_html__( 'Button Label', 'tinylxp' ),
			'type'    => Controls_Manager::TEXT,
			'default' => esc_html__( 'Enter', 'tinylxp' ),
		] );

		$this->end_controls_section();

		// ── Style ─────────────────────────────────────────────────────────
		$this->start_controls_section( 'section_style', [
			'label' => esc_html__( 'Style', 'tinylxp' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'box_bg_color', [
			'label'   => esc_html__( 'Box Background', 'tinylxp' ),
			'type'    => Controls_Manager::COLOR,
			'default' => '#ffffff',
		] );

		$this->add_control( 'text_color', [
			'label'   => esc_html__( 'Text Color', 'tinylxp' ),
			'type'    => Controls_Manager::COLOR,
			'default' => '#3c4043',
		] );

		$this->add_control( 'btn_bg_color', [
			'label'   => esc_html__( 'Button Background', 'tinylxp' ),
			'type'    => Controls_Manager::COLOR,
			'default' => '#1a73e8',
		] );

		$this->add_control( 'btn_text_color', [
			'label'   => esc_html__( 'Button Text Color', 'tinylxp' ),
			'type'    => Controls_Manager::COLOR,
			'default' => '#ffffff',
		] );

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$uid      = 'lxp-sa-' . $this->get_id();

		if ( is_user_logged_in() ) {
			echo '<p class="lxp-sa-signed-in">' . esc_html__( 'You are signed in.', 'tinylxp' ) . '</p>';
			return;
		}

		$heading      = esc_html( $settings['heading'] );
		$field_label  = esc_html( $settings['field_label'] ?: 'Student ID' );
		$button_label = esc_html( $settings['button_label'] ?: 'Enter' );

		$box_bg     = esc_attr( $settings['box_bg_color']   ?: '#ffffff' );
		$text_color = esc_attr( $settings['text_color']     ?: '#3c4043' );
		$btn_bg     = esc_attr( $settings['btn_bg_color']   ?: '#1a73e8' );
		$btn_text   = esc_attr( $settings['btn_text_color'] ?: '#ffffff' );

		$rest_url = esc_url( rest_url( 'lms/v1/student/access-login' ) );
		?>
		<style>
		#<?php echo esc_attr( $uid ); ?> {
			max-width: 380px;
			margin: 0 auto;
			background: <?php echo $box_bg; ?>;
			color: <?php echo $text_color; ?>;
			border-radius: 8px;
			box-shadow: 0 1px 3px rgba(0,0,0,.2), 0 1px 2px rgba(0,0,0,.12);
			padding: 24px;
			font-family: 'Google Sans', Roboto, Arial, sans-serif;
		}
		#<?php echo esc_attr( $uid ); ?> .lxp-sa-heading {
			font-size: 18px;
			font-weight: 500;
			margin: 0 0 16px;
			color: <?php echo $text_color; ?>;
		}
		#<?php echo esc_attr( $uid ); ?> label {
			display: block;
			font-size: 13px;
			margin-bottom: 6px;
			color: <?php echo $text_color; ?>;
		}
		#<?php echo esc_attr( $uid ); ?> input[type="text"] {
			width: 100%;
			box-sizing: border-box;
			padding: 10px 12px;
			font-size: 15px;
			border: 1px solid #dadce0;
			border-radius: 6px;
			margin-bottom: 16px;
		}
		#<?php echo esc_attr( $uid ); ?> button {
			width: 100%;
			padding: 10px 16px;
			font-size: 15px;
			font-weight: 500;
			border: none;
			border-radius: 6px;
			cursor: pointer;
			background: <?php echo $btn_bg; ?>;
			color: <?php echo $btn_text; ?>;
			transition: opacity .15s ease;
		}
		#<?php echo esc_attr( $uid ); ?> button:hover { opacity: .92; }
		#<?php echo esc_attr( $uid ); ?> button[disabled] { opacity: .6; cursor: default; }
		#<?php echo esc_attr( $uid ); ?> .lxp-sa-msg {
			margin-top: 12px;
			font-size: 13px;
			min-height: 16px;
			color: #d93025;
		}
		</style>

		<form class="lxp-sa" id="<?php echo esc_attr( $uid ); ?>" autocomplete="off">
			<?php if ( $heading ) : ?>
			<div class="lxp-sa-heading"><?php echo $heading; ?></div>
			<?php endif; ?>
			<label for="<?php echo esc_attr( $uid ); ?>-input"><?php echo $field_label; ?></label>
			<input type="text" id="<?php echo esc_attr( $uid ); ?>-input" class="lxp-sa-input" required />
			<button type="submit" class="lxp-sa-btn"><?php echo $button_label; ?></button>
			<div class="lxp-sa-msg" aria-live="polite"></div>
		</form>

		<script>
		(function() {
			var form = document.getElementById(<?php echo wp_json_encode( $uid ); ?>);
			if (!form) return;
			var input = form.querySelector('.lxp-sa-input');
			var btn   = form.querySelector('.lxp-sa-btn');
			var msg   = form.querySelector('.lxp-sa-msg');
			var restUrl = <?php echo wp_json_encode( $rest_url ); ?>;

			form.addEventListener('submit', function(e) {
				e.preventDefault();
				msg.textContent = '';
				var studentId = input.value.trim();
				if (!studentId) { return; }

				var classCode = new URLSearchParams(window.location.search).get('class_code');
				if (!classCode) {
					msg.textContent = 'No class code in the page link. Please use the link your teacher shared.';
					return;
				}

				btn.setAttribute('disabled', 'disabled');

				var body = new FormData();
				body.append('student_id', studentId);
				body.append('class_code', classCode);

				fetch(restUrl, { method: 'POST', body: body, credentials: 'same-origin' })
					.then(function(res) { return res.json().then(function(j) { return { ok: res.ok, json: j }; }); })
					.then(function(r) {
						if (r.ok && r.json && r.json.success) {
							window.location.reload();
						} else {
							var m = (r.json && (r.json.message || (r.json.data && r.json.data))) || 'Login failed.';
							if (typeof m !== 'string') { m = 'Login failed.'; }
							msg.textContent = m;
							btn.removeAttribute('disabled');
						}
					})
					.catch(function() {
						msg.textContent = 'Something went wrong. Please try again.';
						btn.removeAttribute('disabled');
					});
			});
		})();
		</script>
		<?php
	}
}
