<?php

class TL_LearnPress_Lesson_Extension {
	private $lti_metadata_repository = null;
	private $section_repository = null;

	private function metadata_repository() {
		if (is_null($this->lti_metadata_repository)) {
			$this->lti_metadata_repository = new TL_LTI_Metadata_Repository();
		}

		return $this->lti_metadata_repository;
	}

	private function section_repository() {
		if (is_null($this->section_repository)) {
			$this->section_repository = new TL_LearnPress_Section_Repository();
		}

		return $this->section_repository;
	}

	private function resolve_course_id_for_lesson($lesson_id = 0, $fallback_course_id = 0) {
		$lesson_id = absint($lesson_id);
		$fallback_course_id = absint($fallback_course_id);

		if ($lesson_id > 0) {
			$course_id = $this->section_repository()->get_course_id_by_item_id($lesson_id);
			if ($course_id > 0) {
				return $course_id;
			}
		}

		return $fallback_course_id;
	}

	private function add_meta_box($args = array()) {
		if (is_array($args) && !empty($args)) {
			call_user_func_array('add_meta_box', $args);
		}
	}

	public function add_meta_boxes() {
		$this->options_metabox();
		$this->lesson_tagline_metabox();
		$this->ai_content_metabox();
	}

	public function ai_content_metabox() {
		$this->add_meta_box( array(
			'lxp-ai-content-gen',
			esc_html__( 'AI Content Gen', 'tiny-lxp-platform' ),
			array( $this, 'ai_content_metabox_html' ),
			TL_LESSON_CPT,
			'side',
			'high',
		) );
	}

	public function ai_content_metabox_html( $post = null ) {
		if ( empty( $post ) || ! isset( $post->ID ) ) {
			return;
		}
		?>
		<input type="hidden" id="lxp-ai-gen-post-id" value="<?php echo esc_attr( $post->ID ); ?>" />
		<p style="margin-bottom: 6px;">
			<button type="button" id="lxp-ai-content-gen-btn" class="button button-primary" style="width: 100%;">
				<?php echo esc_html__( 'AI Content Gen', 'tiny-lxp-platform' ); ?>
			</button>
		</p>
		<p style="margin-top: 6px;">
			<button type="button" id="lxp-ai-content-reset-btn" class="button" style="width: 100%;">
				<?php echo esc_html__( 'Reset to Original', 'tiny-lxp-platform' ); ?>
			</button>
		</p>
		<div id="lxp-ai-content-status" style="margin-top: 8px; font-size: 12px; line-height: 1.5;"></div>
		<?php
	}

	public function lesson_tagline_metabox() {
		$this->add_meta_box( array(
			'lxp-lesson-tagline',
			esc_html__( 'Lesson Tagline', 'tiny-lxp-platform' ),
			array( $this, 'lesson_tagline_metabox_html' ),
			TL_LESSON_CPT,
			'side',
			'default',
		) );
	}

	public function lesson_tagline_metabox_html( $post = null ) {
		if ( empty( $post ) || ! isset( $post->ID ) ) {
			return;
		}
		$tagline = get_post_meta( $post->ID, 'lxp_lesson_tagline', true );
		wp_nonce_field( 'save_lxp_lesson_tagline', 'lxp_lesson_tagline_nonce' );
		?>
		<p>
			<label for="lxp_lesson_tagline_field" style="display:block;margin-bottom:4px;">
				<?php echo esc_html__( 'Short tagline or subtitle shown in lesson hero sections.', 'tiny-lxp-platform' ); ?>
			</label>
			<textarea id="lxp_lesson_tagline_field" name="lxp_lesson_tagline"
				rows="3" style="width:100%;"><?php echo esc_textarea( $tagline ?: '' ); ?></textarea>
		</p>
		<?php
	}

	public function save_lesson_tagline_meta( $post_id = null, $post = null ) {
		$post_id = absint( $post_id );
		if ( $post_id <= 0 ) {
			return;
		}
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( empty( $post ) || ! isset( $post->post_type ) || $post->post_type !== TL_LESSON_CPT ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['lxp_lesson_tagline_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lxp_lesson_tagline_nonce'] ) ), 'save_lxp_lesson_tagline' ) ) {
			return;
		}
		$value = isset( $_POST['lxp_lesson_tagline'] ) ? sanitize_textarea_field( wp_unslash( $_POST['lxp_lesson_tagline'] ) ) : '';
		if ( $value !== '' ) {
			update_post_meta( $post_id, 'lxp_lesson_tagline', $value );
		} else {
			delete_post_meta( $post_id, 'lxp_lesson_tagline' );
		}
	}

	public function options_metabox() {
		$this->add_meta_box(array(
			'lesson-options-class',
			esc_html__('CurrikiStudio Content', 'lesson-options'),
			array($this, 'options_metabox_html'),
			TL_LESSON_CPT,
			'side',
			'default',
		));
	}

	public function post_meta_request_params($args, $request) {
		$args += array(
			'meta_key' => $request['meta_key'],
			'meta_value' => $request['meta_value'],
			'meta_query' => $request['meta_query'],
		);

		return $args;
	}

	public function options_metabox_html($post = null) {
		$metadata = $this->metadata_repository()->get($post->ID);
		$fallback_course_id = isset($_GET['courseid']) ? absint(wp_unslash($_GET['courseid'])) : absint(get_post_meta($post->ID, 'tl_course_id', true));
		$resolved_course_id = $this->resolve_course_id_for_lesson($post->ID, $fallback_course_id);
		$resolved_course = $resolved_course_id > 0 ? get_post($resolved_course_id) : null;
		$shortcode_preview = '';
		if (!empty($metadata->lti_tool_url)) {
			$shortcode_preview = '[currikistudio url=' . esc_url_raw($metadata->lti_tool_url) . ']';
		}

		echo '<h4>Course</h4>';
		if (!empty($resolved_course) && isset($resolved_course->post_title)) {
			echo '<p>' . esc_html($resolved_course->post_title) . '</p>';
		} else {
			echo '<p>' . esc_html__('No linked course found', 'lesson-options') . '</p>';
		}
		echo '<input type="hidden" name="tl_course_id" value="' . esc_attr($resolved_course_id) . '" />';
		wp_nonce_field( 'save_lesson_lti_options', 'lesson_lti_nonce' );
		?>
		<h4>CurrikiStudio Content</h4>
		<div style="width: 100%;margin-top:-10px">
		 <input type="hidden" id="lti_tool_url" name="lti_tool_url" value="<?php echo esc_attr($metadata->lti_tool_url); ?>" style="width: 100%;" />
		 <input type="hidden" id="lti_tool_code" name="lti_tool_code" value="<?php echo esc_attr($metadata->lti_tool_code); ?>" style="width: 100%;" />
		 <input type="hidden" id="lti_content_title" name="lti_content_title" value="<?php echo esc_attr($metadata->lti_content_title); ?>" style="width: 100%;" />
		 <input type="hidden" id="lti_custom_attr" name="lti_custom_attr" value="<?php echo esc_attr($metadata->lti_custom_attr); ?>" style="width: 100%;" />
		 <input type="hidden" id="lti_post_attr_id" name="lti_post_attr_id" value="<?php echo esc_attr($metadata->lti_post_attr_id); ?>" style="width: 100%;" />
		</div>
		<div id="currikistudio-selection-preview" style="width:100%;margin-top:8px;">
			<p><strong><?php echo esc_html__('Selected title', 'lesson-options'); ?>:</strong> <span id="currikistudio-selected-title"><?php echo esc_html($metadata->lti_content_title); ?></span></p>
			<p><strong><?php echo esc_html__('Shortcode', 'lesson-options'); ?>:</strong></p>
			<textarea id="currikistudio-shortcode-preview" readonly style="width:100%;min-height:56px;"><?php echo esc_textarea($shortcode_preview); ?></textarea>
			<div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
				<button type="button" class="button" id="currikistudio-copy-title"><?php echo esc_html__('Copy Title', 'lesson-options'); ?></button>
				<button type="button" class="button" id="currikistudio-copy-shortcode"><?php echo esc_html__('Copy Shortcode', 'lesson-options'); ?></button>
			</div>
			<p id="currikistudio-copy-status" style="margin:8px 0 0;font-size:12px;"></p>
		</div>
		<div id="preview_lit_connections" style="width: 100%;display: inline-block;margin-top: 10px;">
			<div class="preview button" href="#">Select Content<span class="screen-reader-text"> (opens in a new tab)</span></div>
		</div>
		<?php
	}

	public function save_tl_post( $post_id = null, $post = null ) {
		$post_id = absint( $post_id );
		if ( $post_id <= 0 ) {
			return;
		}
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( empty( $post ) || ! isset( $post->post_type ) || $post->post_type !== TL_LESSON_CPT ) {
			return;
		}
		if ( ! isset( $_POST['lesson_lti_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lesson_lti_nonce'] ) ), 'save_lesson_lti_options' ) ) {
			return;
		}
		$posted_course_id = isset( $_POST['tl_course_id'] ) ? absint( wp_unslash( $_POST['tl_course_id'] ) ) : 0;
		$resolved_course_id = $this->resolve_course_id_for_lesson( $post_id, $posted_course_id );
		$metadata_values = array(
			'lti_tool_url'      => isset( $_POST['lti_tool_url'] )      ? sanitize_text_field( wp_unslash( $_POST['lti_tool_url'] ) )      : '',
			'lti_tool_code'     => isset( $_POST['lti_tool_code'] )     ? sanitize_text_field( wp_unslash( $_POST['lti_tool_code'] ) )     : '',
			'lti_content_title' => ( isset( $_POST['lti_content_title'] ) && $_POST['lti_content_title'] !== '' ) ? sanitize_text_field( trim( wp_unslash( $_POST['lti_content_title'] ) ) ) : 'Section',
			'lti_custom_attr'   => isset( $_POST['lti_custom_attr'] )   ? sanitize_text_field( wp_unslash( $_POST['lti_custom_attr'] ) )   : '',
			'lti_post_attr_id'  => isset( $_POST['lti_post_attr_id'] )  ? sanitize_text_field( wp_unslash( $_POST['lti_post_attr_id'] ) )  : '',
		);
		$this->metadata_repository()->update_from_array( $post_id, $metadata_values );
		if ( $resolved_course_id != get_post_meta( $post_id, 'tl_course_id', true ) ) {
			$this->metadata_repository()->update_from_array( $post_id, array( 'lti_course_id' => '' ) );
		}
		update_post_meta( $post_id, 'tl_course_id', $resolved_course_id );
	}

	public function insert_post_api( $post, $request ) {
		if ( ! isset( $request['meta'] ) || ! is_array( $request['meta'] ) ) {
			return;
		}
		$meta = $request['meta'];
		$requested_course_id = isset( $meta['tl_course_id'] ) ? absint( $meta['tl_course_id'] ) : 0;
		$course_id = $this->resolve_course_id_for_lesson( $post->ID, $requested_course_id );
		update_post_meta( $post->ID, 'tl_course_id', $course_id );
		$this->metadata_repository()->update_from_array( $post->ID, array(
			'lti_content_id'    => isset( $meta['lti_content_id'] )    ? sanitize_text_field( $meta['lti_content_id'] )    : '',
			'lti_tool_url'      => isset( $meta['lti_tool_url'] )      ? sanitize_text_field( $meta['lti_tool_url'] )      : '',
			'lti_tool_code'     => isset( $meta['lti_tool_code'] )     ? sanitize_text_field( $meta['lti_tool_code'] )     : '',
			'lti_custom_attr'   => isset( $meta['lti_custom_attr'] )   ? sanitize_text_field( $meta['lti_custom_attr'] )   : '',
			'lti_content_title' => isset( $meta['lti_content_title'] ) ? sanitize_text_field( $meta['lti_content_title'] ) : '',
			'lti_post_attr_id'  => isset( $meta['lti_post_attr_id'] )  ? sanitize_text_field( $meta['lti_post_attr_id'] )  : '',
			'lti_course_id'     => isset( $meta['lti_course_id'] )     ? sanitize_text_field( $meta['lti_course_id'] )     : '',
		) );
	}

	public function provide_lti_launch_metadata($launch_metadata, $post, $deeplink, $ok, $reason) {
		if ($deeplink || $ok || empty($post) || !isset($post->post_type) || $post->post_type !== LP_LESSON_CPT) {
			return $launch_metadata;
		}

		$metadata = $this->metadata_repository()->get($post->ID);
		if (empty($metadata->lti_tool_code) || empty($metadata->lti_post_attr_id)) {
			return $launch_metadata;
		}

		return array_merge(array(
			'tool' => $metadata->lti_tool_code,
			'title' => $metadata->lti_content_title,
			'url' => $metadata->lti_tool_url,
			'custom' => $metadata->lti_custom_attr,
			'id' => $metadata->lti_post_attr_id,
			'target' => 'embed',
		), is_array($launch_metadata) ? $launch_metadata : array());
	}

	private function assignment_context_for_lesson($lesson_id = 0) {
		$lesson_id = absint($lesson_id);
		$assignment_id = isset($_GET['assignment_id']) ? absint(wp_unslash($_GET['assignment_id'])) : 0;
		$text_domain = Tiny_LXP_Platform::get_plugin_name();

		if ($lesson_id <= 0 || $assignment_id <= 0 || !function_exists('lxp_get_assignment')) {
			return null;
		}

		$assignment = lxp_get_assignment($assignment_id);
		if (empty($assignment) || empty($assignment->ID)) {
			return null;
		}

		$assignment_lesson_id = absint(get_post_meta($assignment->ID, 'lxp_lesson_id', true));
		if ($assignment_lesson_id !== $lesson_id) {
			return null;
		}

		$course_id = absint(get_post_meta($assignment->ID, 'course_id', true));
		$course = $course_id > 0 ? get_post($course_id) : null;
		$section_name = $this->section_repository()->get_section_name_by_item_id($lesson_id);
		$assets_src = content_url() . '/plugins/TinyLxp-wp-plugin/lms/templates/tinyLxpTheme/treks-src/';

		return array(
			'assignment' => $assignment,
			'course' => $course,
			'section_name' => $section_name ? $section_name : esc_html__('Uncategorized', $text_domain),
			'assets_src' => $assets_src,
		);
	}

	private function render_assignment_lesson_context($context, $lesson_post) {
		if (!is_array($context) || empty($context['assignment']) || empty($lesson_post) || !isset($lesson_post->post_title)) {
			return;
		}

		$assignment = $context['assignment'];
		$text_domain = Tiny_LXP_Platform::get_plugin_name();
		$course = isset($context['course']) ? $context['course'] : null;
		$course_title = !empty($course) && isset($course->post_title) ? $course->post_title : esc_html__('Course', $text_domain);
		$section_name = isset($context['section_name']) ? $context['section_name'] : esc_html__('Uncategorized', $text_domain);
		$assets_src = isset($context['assets_src']) ? $context['assets_src'] : '';
		$start_date = get_post_meta($assignment->ID, 'start_date', true);
		$start_time = get_post_meta($assignment->ID, 'start_time', true);
		$end_date = get_post_meta($assignment->ID, 'end_date', true);
		$end_time = get_post_meta($assignment->ID, 'end_time', true);

		echo '<style>';
		echo '.tinylxp-assignment-lesson-context{margin:0 0 16px;}.tinylxp-assignment-lesson-context .course_nav_path{display:flex;align-items:center;flex-wrap:wrap;gap:10px;}.tinylxp-assignment-lesson-context .practice_flx{display:flex;gap:16px;color:#979797;align-items:center;}.tinylxp-assignment-lesson-context .practice_flx img{width:23px;height:20px;}.tinylxp-assignment-lesson-context .time-date-box{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px;}.tinylxp-assignment-lesson-context .date-time{font-family:Arial,sans-serif;font-style:normal;font-weight:400;font-size:16px;padding:4px 8px;line-height:24px;background:rgba(31,165,212,0.16);border-radius:8px;color:#0b5d7a;margin:0;}.tinylxp-assignment-lesson-context .to-text{color:#757575;background:none;}';
		echo '</style>';
		echo '<div class="tinylxp-assignment-lesson-context">';
		echo '<div class="course_nav_path">';
		echo '<div class="practice_flx"><img src="' . esc_url($assets_src . '/assets/img/nav_Treks.svg') . '" alt="" /><p class="practice_text">' . esc_html__('My Course', $text_domain) . '</p></div>';
		echo '<div class="practice_flx"><img src="' . esc_url($assets_src . '/assets/img/bc_arrow_right.svg') . '" alt="" /><p class="practice_text">' . esc_html($course_title) . '</p></div>';
		echo '<div class="practice_flx"><img src="' . esc_url($assets_src . '/assets/img/bc_arrow_right.svg') . '" alt="" /><p class="practice_text">' . esc_html($section_name) . '</p></div>';
		echo '<div class="practice_flx"><img src="' . esc_url($assets_src . '/assets/img/bc_arrow_right.svg') . '" alt="" /><p class="practice_text">' . esc_html($lesson_post->post_title) . '</p></div>';
		echo '</div>';

		if (!empty($start_date) && !empty($start_time) && !empty($end_date) && !empty($end_time)) {
			echo '<div class="time-date-box">';
			echo '<p class="date-time">' . esc_html(date_i18n('l, M d, Y h:i A', strtotime($start_date . ' ' . $start_time))) . '</p>';
			echo '<p class="date-time to-text">' . esc_html__('To', $text_domain) . '</p>';
			echo '<p class="date-time">' . esc_html(date_i18n('l, M d, Y h:i A', strtotime($end_date . ' ' . $end_time))) . '</p>';
			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * Resolve the lp_lesson post currently being viewed inside a course context.
	 *
	 * On LearnPress lesson URLs the main WP query resolves to the parent lp_course
	 * post, not the lesson. This helper extracts the lesson being viewed so that
	 * metadata lookups and the LTI launch URL use the correct post ID.
	 *
	 * Resolution order:
	 *  1. $course_post is already LP_LESSON_CPT – pass straight through.
	 *  2. LP_Global::course_item() – the authoritative LP way to get the active item.
	 *  3. URL-path parsing – find the segment after 'lessons/' and look up by slug.
	 *
	 * @param WP_Post $course_post The post returned by get_post() (usually the course).
	 * @return WP_Post|null The lp_lesson post, or null when none can be determined.
	 */
	private function resolve_current_lesson_post( $course_post ) {
		// 1. Already a lesson (e.g. direct post preview).
		if ( ! empty( $course_post ) && isset( $course_post->post_type ) && $course_post->post_type === LP_LESSON_CPT ) {
			return $course_post;
		}

		// 2. LP_Global::course_item() (LearnPress ≥ 4.x, class_exists guard).
		if ( class_exists( 'LP_Global' ) ) {
			$item = LP_Global::course_item();
			if ( ! empty( $item ) && is_object( $item ) && method_exists( $item, 'get_id' ) ) {
				$lesson_id = absint( $item->get_id() );
				if ( $lesson_id > 0 ) {
					$lesson = get_post( $lesson_id );
					if ( ! empty( $lesson ) && $lesson->post_type === LP_LESSON_CPT ) {
						return $lesson;
					}
				}
			}
		}

		// 3. Parse the current request URL for the lesson slug.
		//    LearnPress lesson permalinks are /{course-base}/{course-slug}/lessons/{lesson-slug}/
		$url        = home_url( add_query_arg( array() ) );
		$url_path   = parse_url( $url, PHP_URL_PATH );
		$path       = ( null !== $url_path ) ? $url_path : '';
		$segments   = explode( '/', trim( $path, '/' ) );
		$lessons_idx = array_search( 'lessons', $segments, true );
		if ( $lessons_idx !== false && isset( $segments[ $lessons_idx + 1 ] ) ) {
			$lesson_slug = sanitize_title( $segments[ $lessons_idx + 1 ] );
			if ( ! empty( $lesson_slug ) ) {
				$matches = get_posts( array(
					'name'        => $lesson_slug,
					'post_type'   => LP_LESSON_CPT,
					'post_status' => array( 'publish', 'private', 'draft' ),
					'numberposts' => 1,
				) );
				if ( ! empty( $matches ) && $matches[0]->post_type === LP_LESSON_CPT ) {
					return $matches[0];
				}
			}
		}

		return null;
	}

	public function render_lti_lesson_embed() {
		if (!is_singular(LP_COURSE_CPT)) {
			return;
		}

		$post = get_post();
		if (empty($post) || !isset($post->ID)) {
			return;
		}

		// Resolve the actual lp_lesson being viewed – get_post() returns the course
		// post on all LearnPress lesson URLs because the main WP query runs on lp_course.
		$lesson_post = $this->resolve_current_lesson_post( $post );
		if ( empty( $lesson_post ) ) {
			// Visiting the course overview page (no active lesson) – nothing to embed.
			return;
		}

		$assignment_context = $this->assignment_context_for_lesson($lesson_post->ID);
		if (!empty($assignment_context)) {
			$this->render_assignment_lesson_context($assignment_context, $lesson_post);
		}

		$metadata = $this->metadata_repository()->get($lesson_post->ID);
		if (empty($metadata->lti_tool_code) || empty($metadata->lti_post_attr_id)) {
			return;
		}

		$launch_url = add_query_arg(
			array(
				Tiny_LXP_Platform::get_plugin_name() => '',
				'post' => $lesson_post->ID,
				'id' => $metadata->lti_post_attr_id,
			),
			site_url()
		);

		echo '<div class="tinylxp-lp-lesson-embed" style="margin-top:16px;margin-bottom:16px;">';
		echo '<iframe style="border:none;width:100%;height:706px;" src="' . esc_url($launch_url) . '" allowfullscreen></iframe>';
		echo '</div>';
	}

	public function render_js_debug_panel() {
		if ( ! is_singular( 'lp_course' ) ) {
			return;
		}
		if ( sanitize_key( isset( $_GET['debug'] ) ? $_GET['debug'] : '' ) !== '1' ) {
			return;
		}

		global $wp_scripts;

		$queue = isset( $wp_scripts->queue ) ? (array) $wp_scripts->queue : array();
		$queue = array_filter( $queue, function( $handle ) use ( $wp_scripts ) {
			$src = isset( $wp_scripts->registered[ $handle ] ) ? $wp_scripts->registered[ $handle ]->src : '';
			return $src && strpos( $src, 'TinyLxp-wp-plugin' ) !== false;
		} );
		sort( $queue );
		$count = count( $queue );
		?>
<style id="tinylxp-js-debug-styles">
#tinylxp-js-debug {
	box-sizing: border-box;
	width: 100%;
	margin: 0;
	padding: 20px 24px;
	background: #1a1a2e;
	color: #e0e0e0;
	font-family: 'Courier New', Courier, monospace;
	font-size: 13px;
	line-height: 1.5;
	border-top: 4px solid #e94560;
	position: relative;
	z-index: 99999;
}
#tinylxp-js-debug h2 {
	margin: 0 0 16px;
	color: #e94560;
	font-size: 16px;
	letter-spacing: 0.05em;
	border-bottom: 1px solid #333;
	padding-bottom: 8px;
}
#tinylxp-js-debug .tlxp-notice {
	background: #2a2a40;
	border-left: 3px solid #f5a623;
	padding: 8px 12px;
	margin-bottom: 16px;
	color: #f5a623;
	font-size: 12px;
}
#tinylxp-js-debug details {
	background: #16213e;
	border: 1px solid #2d2d4a;
	border-radius: 4px;
	margin-bottom: 8px;
	overflow: hidden;
}
#tinylxp-js-debug details summary {
	padding: 10px 14px;
	cursor: pointer;
	user-select: none;
	outline: none;
	display: flex;
	align-items: baseline;
	gap: 10px;
	list-style: none;
}
#tinylxp-js-debug details summary::-webkit-details-marker { display: none; }
#tinylxp-js-debug details summary::before {
	content: '▶';
	color: #e94560;
	font-size: 10px;
	transition: transform 0.15s;
	flex-shrink: 0;
}
#tinylxp-js-debug details[open] summary::before { transform: rotate(90deg); }
#tinylxp-js-debug .tlxp-handle {
	color: #7fdbff;
	font-weight: bold;
}
#tinylxp-js-debug .tlxp-src-short {
	color: #aaa;
	font-size: 11px;
}
#tinylxp-js-debug .tlxp-body {
	padding: 10px 14px 14px;
	border-top: 1px solid #2d2d4a;
}
#tinylxp-js-debug .tlxp-row {
	display: flex;
	gap: 8px;
	margin-bottom: 6px;
	align-items: flex-start;
}
#tinylxp-js-debug .tlxp-label {
	min-width: 90px;
	color: #f5a623;
	flex-shrink: 0;
}
#tinylxp-js-debug .tlxp-val {
	color: #e0e0e0;
	word-break: break-all;
}
#tinylxp-js-debug .tlxp-val a {
	color: #7fdbff;
}
#tinylxp-js-debug .tlxp-code {
	background: #0f0f1a;
	border: 1px solid #2d2d4a;
	border-radius: 3px;
	padding: 6px 10px;
	margin-top: 4px;
	max-height: 200px;
	overflow-y: auto;
	white-space: pre-wrap;
	word-break: break-all;
	font-size: 12px;
	color: #b8f5b0;
}
#tinylxp-js-debug .tlxp-deps span {
	display: inline-block;
	background: #2d2d4a;
	color: #ccc;
	border-radius: 3px;
	padding: 1px 6px;
	margin: 2px 2px 2px 0;
	font-size: 11px;
}
</style>
<div id="tinylxp-js-debug">
	<h2>&#x1F527; TinyLxp JS Debug Panel &mdash; <?php echo esc_html( $count ); ?> enqueued script<?php echo $count !== 1 ? 's' : ''; ?></h2>
	<div class="tlxp-notice">&#9888; Only scripts enqueued by <strong>TinyLxp-wp-plugin</strong> are listed here. WordPress core, theme, and other plugin scripts are excluded. Hardcoded <code>&lt;script src=...&gt;</code> tags inside tinyLxpTheme templates are NOT shown &mdash; check browser DevTools &rsaquo; Sources or View Page Source for those.</div>
	<?php foreach ( $queue as $handle ) :
		$registered = isset( $wp_scripts->registered[ $handle ] ) ? $wp_scripts->registered[ $handle ] : null;
		$src         = $registered ? $registered->src : '';
		$deps        = ( $registered && ! empty( $registered->deps ) ) ? (array) $registered->deps : array();
		$ver         = $registered ? $registered->ver : '';
		$data_inline = $wp_scripts->get_data( $handle, 'data' );
		$before      = $wp_scripts->get_data( $handle, 'before' );
		$after       = $wp_scripts->get_data( $handle, 'after' );
		$src_short   = $src ? basename( parse_url( $src, PHP_URL_PATH ) ) : '(dynamic/no src)';
	?>
	<details>
		<summary>
			<span class="tlxp-handle"><?php echo esc_html( $handle ); ?></span>
			<span class="tlxp-src-short"><?php echo esc_html( $src_short ); ?></span>
		</summary>
		<div class="tlxp-body">
			<div class="tlxp-row">
				<span class="tlxp-label">src:</span>
				<span class="tlxp-val"><?php if ( $src ) : ?><a href="<?php echo esc_url( $src ); ?>" target="_blank" rel="noreferrer"><?php echo esc_html( $src ); ?></a><?php else : ?><em>(inline / no src)</em><?php endif; ?></span>
			</div>
			<div class="tlxp-row">
				<span class="tlxp-label">version:</span>
				<span class="tlxp-val"><?php echo esc_html( $ver ? $ver : '—' ); ?></span>
			</div>
			<?php if ( ! empty( $deps ) ) : ?>
			<div class="tlxp-row">
				<span class="tlxp-label">deps:</span>
				<span class="tlxp-val tlxp-deps"><?php foreach ( $deps as $dep ) : ?><span><?php echo esc_html( $dep ); ?></span><?php endforeach; ?></span>
			</div>
			<?php endif; ?>
			<?php if ( $data_inline ) : ?>
			<div class="tlxp-row">
				<span class="tlxp-label">localized:</span>
				<span class="tlxp-val"><div class="tlxp-code"><?php echo esc_html( $data_inline ); ?></div></span>
			</div>
			<?php endif; ?>
			<?php if ( ! empty( $before ) ) : ?>
			<div class="tlxp-row">
				<span class="tlxp-label">before:</span>
				<span class="tlxp-val"><div class="tlxp-code"><?php echo esc_html( implode( "\n", (array) $before ) ); ?></div></span>
			</div>
			<?php endif; ?>
			<?php if ( ! empty( $after ) ) : ?>
			<div class="tlxp-row">
				<span class="tlxp-label">after:</span>
				<span class="tlxp-val"><div class="tlxp-code"><?php echo esc_html( implode( "\n", (array) $after ) ); ?></div></span>
			</div>
			<?php endif; ?>
		</div>
	</details>
	<?php endforeach; ?>
</div>
		<?php
	}
}