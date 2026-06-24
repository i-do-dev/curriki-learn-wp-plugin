<?php
namespace Edudeme\Elementor;

use Elementor\Controls_Manager;

class LXP_Student_Courses_Widget extends \Elementor\Widget_Base {

	public function get_name()       { return 'lxp-student-courses'; }
	public function get_title()      { return esc_html__( 'Student Courses', 'tinylxp' ); }
	public function get_icon()       { return 'eicon-library-open'; }
	public function get_categories() { return [ 'general' ]; }

	protected function register_controls() {

		// ── Content: Settings ─────────────────────────────────────────────
		$this->start_controls_section( 'section_settings', [
			'label' => esc_html__( 'Settings', 'tinylxp' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'empty_message', [
			'label'   => esc_html__( 'Empty State Message', 'tinylxp' ),
			'type'    => Controls_Manager::TEXT,
			'default' => esc_html__( 'No classes assigned yet.', 'tinylxp' ),
		] );

		$this->add_control( 'open_label', [
			'label'   => esc_html__( 'Open Course Button Label', 'tinylxp' ),
			'type'    => Controls_Manager::TEXT,
			'default' => esc_html__( 'Open Course', 'tinylxp' ),
		] );

		$this->add_control( 'back_label', [
			'label'   => esc_html__( 'Back Button Label', 'tinylxp' ),
			'type'    => Controls_Manager::TEXT,
			'default' => esc_html__( 'All Classes', 'tinylxp' ),
		] );

		$this->end_controls_section();

		// ── Style: Layout ─────────────────────────────────────────────────
		$this->start_controls_section( 'section_layout', [
			'label' => esc_html__( 'Layout', 'tinylxp' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'columns', [
			'label'   => esc_html__( 'Columns', 'tinylxp' ),
			'type'    => Controls_Manager::SELECT,
			'default' => '3',
			'options' => [
				'2' => esc_html__( '2', 'tinylxp' ),
				'3' => esc_html__( '3', 'tinylxp' ),
				'4' => esc_html__( '4', 'tinylxp' ),
			],
		] );

		$this->end_controls_section();

		// ── Style: Card Header ────────────────────────────────────────────
		$this->start_controls_section( 'section_header', [
			'label' => esc_html__( 'Card Header', 'tinylxp' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'use_color_cycle', [
			'label'        => esc_html__( 'Cycle Card Colors', 'tinylxp' ),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => esc_html__( 'Yes', 'tinylxp' ),
			'label_off'    => esc_html__( 'No', 'tinylxp' ),
			'return_value' => 'yes',
			'default'      => 'yes',
		] );

		$this->add_control( 'header_bg_color', [
			'label'     => esc_html__( 'Header Background Color', 'tinylxp' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#1a73e8',
			'condition' => [ 'use_color_cycle' => '' ],
		] );

		$this->add_control( 'header_text_color', [
			'label'   => esc_html__( 'Header Text Color', 'tinylxp' ),
			'type'    => Controls_Manager::COLOR,
			'default' => '#ffffff',
		] );

		$this->end_controls_section();

		// ── Style: Card Body ──────────────────────────────────────────────
		$this->start_controls_section( 'section_body', [
			'label' => esc_html__( 'Card Body', 'tinylxp' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'card_bg_color', [
			'label'   => esc_html__( 'Card Background', 'tinylxp' ),
			'type'    => Controls_Manager::COLOR,
			'default' => '#ffffff',
		] );

		$this->add_control( 'body_text_color', [
			'label'   => esc_html__( 'Body Text Color', 'tinylxp' ),
			'type'    => Controls_Manager::COLOR,
			'default' => '#3c4043',
		] );

		$this->add_control( 'meta_text_color', [
			'label'   => esc_html__( 'Meta / Badge Text Color', 'tinylxp' ),
			'type'    => Controls_Manager::COLOR,
			'default' => '#70757a',
		] );

		$this->add_control( 'btn_text_color', [
			'label'   => esc_html__( 'Button Color', 'tinylxp' ),
			'type'    => Controls_Manager::COLOR,
			'default' => '#1a73e8',
		] );

		$this->end_controls_section();
	}

	protected function render() {
		if ( ! is_user_logged_in() ) {
			echo '<p>' . esc_html__( 'Please log in to view your courses.', 'tinylxp' ) . '</p>';
			return;
		}

		$settings     = $this->get_settings_for_display();
		$uid          = 'lxp-scw-' . $this->get_id();
		$cols         = absint( $settings['columns'] ) ?: 3;
		$open_label   = esc_html( $settings['open_label'] ?: 'Open Course' );
		$back_label   = esc_html( $settings['back_label'] ?: 'All Classes' );
		$empty_msg    = esc_html( $settings['empty_message'] ?: 'No classes assigned yet.' );
		$use_cycle    = $settings['use_color_cycle'] === 'yes';

		$palette = [ '#1a73e8', '#0f9d58', '#e37400', '#d93025', '#673ab7', '#00838f', '#c2185b' ];

		$student_post = lxp_get_student_post( get_current_user_id() );

		if ( ! $student_post ) {
			echo '<p>' . $empty_msg . '</p>';
			return;
		}

		$classes = lxp_get_student_all_classes( $student_post->ID );

		if ( empty( $classes ) ) {
			echo '<p>' . $empty_msg . '</p>';
			return;
		}

		// Gather per-class data and collect all course IDs for a single batch fetch
		$class_data    = [];
		$all_course_ids = [];

		foreach ( $classes as $class ) {
			$code        = get_post_meta( $class->ID, 'lxp_class_code', true );
			$course_ids  = get_post_meta( $class->ID, 'lxp_class_course_ids' );
			$course_ids  = is_array( $course_ids ) ? array_filter( array_map( 'absint', $course_ids ) ) : [];
			$class_data[] = [
				'post'       => $class,
				'code'       => $code,
				'course_ids' => $course_ids,
			];
			$all_course_ids = array_merge( $all_course_ids, $course_ids );
		}

		// Batch-fetch all LP course posts once
		$all_course_ids = array_values( array_unique( $all_course_ids ) );
		$courses_by_id  = [];

		if ( ! empty( $all_course_ids ) ) {
			$course_posts = get_posts( [
				'post_type'      => TL_COURSE_CPT,
				'post__in'       => $all_course_ids,
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			] );
			foreach ( $course_posts as $cp ) {
				$courses_by_id[ $cp->ID ] = $cp;
			}
		}

		$hdr_text   = esc_attr( $settings['header_text_color'] ?: '#ffffff' );
		$card_bg    = esc_attr( $settings['card_bg_color']     ?: '#ffffff' );
		$body_color = esc_attr( $settings['body_text_color']   ?: '#3c4043' );
		$meta_color = esc_attr( $settings['meta_text_color']   ?: '#70757a' );
		$btn_color  = esc_attr( $settings['btn_text_color']    ?: '#1a73e8' );
		$hdr_bg_fixed = esc_attr( $settings['header_bg_color'] ?: '#1a73e8' );

		// ── Inline CSS ────────────────────────────────────────────────────
		?>
		<style>
		#<?php echo esc_attr( $uid ); ?> {
			font-family: 'Google Sans', Roboto, Arial, sans-serif;
		}
		#<?php echo esc_attr( $uid ); ?> .lxp-scw__back {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			background: none;
			border: none;
			cursor: pointer;
			color: <?php echo $btn_color; ?>;
			font-size: 14px;
			font-weight: 500;
			padding: 8px 0 16px;
			letter-spacing: .25px;
		}
		#<?php echo esc_attr( $uid ); ?> .lxp-scw__back:hover {
			text-decoration: underline;
		}
		#<?php echo esc_attr( $uid ); ?> .lxp-scw__class-title {
			font-size: 22px;
			font-weight: 400;
			color: <?php echo $body_color; ?>;
			margin: 0 0 20px;
		}
		#<?php echo esc_attr( $uid ); ?> .lxp-scw__grid {
			display: grid;
			gap: 16px;
			grid-template-columns: repeat(<?php echo $cols; ?>, 1fr);
		}
		@media (max-width: 900px) {
			#<?php echo esc_attr( $uid ); ?> .lxp-scw__grid { grid-template-columns: repeat(2, 1fr); }
		}
		@media (max-width: 600px) {
			#<?php echo esc_attr( $uid ); ?> .lxp-scw__grid { grid-template-columns: 1fr; }
		}
		/* ── Class cards ── */
		#<?php echo esc_attr( $uid ); ?> .lxp-class-card {
			border-radius: 8px;
			overflow: hidden;
			background: <?php echo $card_bg; ?>;
			box-shadow: 0 1px 3px rgba(0,0,0,.2), 0 1px 2px rgba(0,0,0,.12);
			cursor: pointer;
			transition: transform .15s ease, box-shadow .15s ease;
			display: flex;
			flex-direction: column;
		}
		#<?php echo esc_attr( $uid ); ?> .lxp-class-card:hover {
			transform: translateY(-2px);
			box-shadow: 0 4px 8px rgba(0,0,0,.2), 0 2px 4px rgba(0,0,0,.12);
		}
		#<?php echo esc_attr( $uid ); ?> .lxp-class-card__header {
			position: relative;
			height: 96px;
			padding: 12px 16px;
			display: flex;
			flex-direction: column;
			overflow: hidden;
		}
		#<?php echo esc_attr( $uid ); ?> .lxp-class-card__header-pattern {
			position: absolute;
			inset: 0;
			background-image: radial-gradient(circle, rgba(255,255,255,.15) 1px, transparent 1px);
			background-size: 18px 18px;
			pointer-events: none;
		}
		#<?php echo esc_attr( $uid ); ?> .lxp-class-card__info {
			margin-top: auto;
			position: relative;
			z-index: 1;
		}
		#<?php echo esc_attr( $uid ); ?> .lxp-class-card__name {
			display: block;
			font-size: 18px;
			font-weight: 500;
			color: <?php echo $hdr_text; ?>;
			line-height: 1.2;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}
		#<?php echo esc_attr( $uid ); ?> .lxp-class-card__code {
			display: block;
			font-size: 11px;
			color: <?php echo $hdr_text; ?>;
			opacity: .75;
			margin-top: 2px;
			letter-spacing: .4px;
		}
		#<?php echo esc_attr( $uid ); ?> .lxp-class-card__body {
			padding: 12px 16px;
			flex: 1;
		}
		#<?php echo esc_attr( $uid ); ?> .lxp-class-card__count {
			display: inline-block;
			font-size: 12px;
			color: <?php echo $meta_color; ?>;
			background: rgba(0,0,0,.06);
			border-radius: 12px;
			padding: 2px 10px;
		}
		/* ── Course cards ── */
		#<?php echo esc_attr( $uid ); ?> .lxp-course-card {
			border-radius: 8px;
			overflow: hidden;
			background: <?php echo $card_bg; ?>;
			box-shadow: 0 1px 3px rgba(0,0,0,.2), 0 1px 2px rgba(0,0,0,.12);
			display: flex;
			flex-direction: column;
			transition: transform .15s ease, box-shadow .15s ease;
		}
		#<?php echo esc_attr( $uid ); ?> .lxp-course-card:hover {
			transform: translateY(-2px);
			box-shadow: 0 4px 8px rgba(0,0,0,.2), 0 2px 4px rgba(0,0,0,.12);
		}
		#<?php echo esc_attr( $uid ); ?> .lxp-course-card__header {
			position: relative;
			height: 96px;
			padding: 12px 16px;
			display: flex;
			flex-direction: column;
			overflow: hidden;
		}
		#<?php echo esc_attr( $uid ); ?> .lxp-course-card__header-pattern {
			position: absolute;
			inset: 0;
			background-image: radial-gradient(circle, rgba(255,255,255,.15) 1px, transparent 1px);
			background-size: 18px 18px;
			pointer-events: none;
		}
		#<?php echo esc_attr( $uid ); ?> .lxp-course-card__title {
			margin-top: auto;
			position: relative;
			z-index: 1;
			font-size: 17px;
			font-weight: 500;
			color: <?php echo $hdr_text; ?>;
			line-height: 1.2;
			display: -webkit-box;
			-webkit-line-clamp: 2;
			-webkit-box-orient: vertical;
			overflow: hidden;
		}
		#<?php echo esc_attr( $uid ); ?> .lxp-course-card__body {
			padding: 12px 16px;
			flex: 1;
			color: <?php echo $body_color; ?>;
		}
		#<?php echo esc_attr( $uid ); ?> .lxp-course-card__meta {
			font-size: 12px;
			color: <?php echo $meta_color; ?>;
		}
		#<?php echo esc_attr( $uid ); ?> .lxp-course-card__footer {
			padding: 8px 16px 14px;
			text-align: right;
		}
		#<?php echo esc_attr( $uid ); ?> .lxp-course-card__btn {
			font-size: 13px;
			font-weight: 500;
			color: <?php echo $btn_color; ?>;
			text-decoration: none;
			letter-spacing: .25px;
		}
		#<?php echo esc_attr( $uid ); ?> .lxp-course-card__btn:hover {
			text-decoration: underline;
		}
		</style>

		<?php
		// ── HTML ──────────────────────────────────────────────────────────
		echo '<div class="lxp-scw" id="' . esc_attr( $uid ) . '">';

		// ── Step 1: Class grid ────────────────────────────────────────────
		echo '<div class="lxp-scw__step lxp-scw__step--1">';
		echo '<div class="lxp-scw__grid">';

		foreach ( $class_data as $i => $cd ) {
			$class       = $cd['post'];
			$code        = $cd['code'];
			$course_count = count( $cd['course_ids'] );
			$hdr_color   = $use_cycle
				? $palette[ $i % count( $palette ) ]
				: $hdr_bg_fixed;
			$noun = $course_count === 1 ? 'Course' : 'Courses';
			?>
			<div class="lxp-class-card" data-class-code="<?php echo esc_attr( $code ); ?>"
				 data-class-name="<?php echo esc_attr( $class->post_title ); ?>">
				<div class="lxp-class-card__header" style="background:<?php echo esc_attr( $hdr_color ); ?>">
					<div class="lxp-class-card__header-pattern"></div>
					<div class="lxp-class-card__info">
						<span class="lxp-class-card__name"><?php echo esc_html( $class->post_title ); ?></span>
						<?php if ( $code ) : ?>
						<span class="lxp-class-card__code"><?php echo esc_html( $code ); ?></span>
						<?php endif; ?>
					</div>
				</div>
				<div class="lxp-class-card__body">
					<span class="lxp-class-card__count"><?php echo esc_html( $course_count . ' ' . $noun ); ?></span>
				</div>
			</div>
			<?php
		}

		echo '</div>'; // .lxp-scw__grid
		echo '</div>'; // .lxp-scw__step--1

		// ── Step 2: Course panels (one hidden div per class) ──────────────
		echo '<div class="lxp-scw__step lxp-scw__step--2" hidden>';
		echo '<button class="lxp-scw__back">&#8592; ' . $back_label . '</button>';
		echo '<h2 class="lxp-scw__class-title"></h2>';

		foreach ( $class_data as $i => $cd ) {
			$code = $cd['code'];
			echo '<div class="lxp-courses-panel" data-class-code="' . esc_attr( $code ) . '" hidden>';
			echo '<div class="lxp-scw__grid">';

			if ( empty( $cd['course_ids'] ) ) {
				echo '<p>' . esc_html__( 'No courses assigned to this class yet.', 'tinylxp' ) . '</p>';
			} else {
				$ci = 0;
				foreach ( $cd['course_ids'] as $cid ) {
					if ( ! isset( $courses_by_id[ $cid ] ) ) {
						continue;
					}
					$cp          = $courses_by_id[ $cid ];
					$hdr_color   = $use_cycle
						? $palette[ $ci % count( $palette ) ]
						: $hdr_bg_fixed;
					$lesson_count = 0;
					if ( function_exists( 'learn_press_get_course' ) ) {
						$lp_course = \learn_press_get_course( $cp->ID );
						if ( $lp_course ) {
							$lesson_count = (int) $lp_course->count_items( LP_LESSON_CPT );
						}
					}
					$noun = $lesson_count === 1 ? 'Lesson' : 'Lessons';
					?>
					<div class="lxp-course-card">
						<div class="lxp-course-card__header" style="background:<?php echo esc_attr( $hdr_color ); ?>">
							<div class="lxp-course-card__header-pattern"></div>
							<span class="lxp-course-card__title"><?php echo esc_html( $cp->post_title ); ?></span>
						</div>
						<div class="lxp-course-card__body">
							<span class="lxp-course-card__meta"><?php echo esc_html( $lesson_count . ' ' . $noun ); ?></span>
						</div>
						<div class="lxp-course-card__footer">
							<a href="<?php echo esc_url( get_permalink( $cp->ID ) ); ?>" class="lxp-course-card__btn">
								<?php echo $open_label; ?>
							</a>
						</div>
					</div>
					<?php
					$ci++;
				}
			}

			echo '</div>'; // .lxp-scw__grid
			echo '</div>'; // .lxp-courses-panel
		}

		echo '</div>'; // .lxp-scw__step--2
		echo '</div>'; // #lxp-scw-{uid}

		// ── Inline JS ────────────────────────────────────────────────────
		?>
		<script>
		(function() {
			var root  = document.getElementById(<?php echo wp_json_encode( $uid ); ?>);
			if (!root) return;
			var step1 = root.querySelector('.lxp-scw__step--1');
			var step2 = root.querySelector('.lxp-scw__step--2');
			var title = root.querySelector('.lxp-scw__class-title');

			function getParam() {
				return new URLSearchParams(window.location.search).get('class_code');
			}

			function setParam(code) {
				var p = new URLSearchParams(window.location.search);
				if (code) { p.set('class_code', code); } else { p.delete('class_code'); }
				var qs = p.toString();
				history.pushState({}, '', qs ? '?' + qs : window.location.pathname);
			}

			function showStep1() {
				step1.removeAttribute('hidden');
				step2.setAttribute('hidden', '');
			}

			function showStep2(code) {
				var panel = root.querySelector('.lxp-courses-panel[data-class-code="' + code + '"]');
				if (!panel) return;
				root.querySelectorAll('.lxp-courses-panel').forEach(function(p) {
					p.setAttribute('hidden', '');
				});
				panel.removeAttribute('hidden');
				var card = root.querySelector('.lxp-class-card[data-class-code="' + code + '"]');
				title.textContent = card ? card.dataset.className : '';
				step1.setAttribute('hidden', '');
				step2.removeAttribute('hidden');
			}

			root.querySelectorAll('.lxp-class-card').forEach(function(card) {
				card.addEventListener('click', function() {
					var code = card.dataset.classCode;
					if (!code) return;
					setParam(code);
					showStep2(code);
				});
			});

			root.querySelector('.lxp-scw__back').addEventListener('click', function() {
				setParam(null);
				showStep1();
			});

			window.addEventListener('popstate', function() {
				var code = getParam();
				if (code) { showStep2(code); } else { showStep1(); }
			});

			var initial = getParam();
			if (initial) { showStep2(initial); }
		})();
		</script>
		<?php
	}
}
