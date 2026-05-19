<?php

if ( ! class_exists( 'Rest_Lxp_AI_Content' ) ) {
	require_once dirname( __DIR__ ) . '/../lms/lms-rest-apis/ai-content.php';
}

$catalog = Rest_Lxp_AI_Content::get_block_catalog();
$allowed_html = wp_kses_allowed_html( 'post' );
$allowed_html['style'] = array(
	'type'  => true,
	'media' => true,
);

foreach ( array( 'section', 'div', 'p', 'li', 'blockquote', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'ul', 'ol', 'h3', 'h4', 'span' ) as $tag ) {
	if ( ! isset( $allowed_html[ $tag ] ) || ! is_array( $allowed_html[ $tag ] ) ) {
		$allowed_html[ $tag ] = array();
	}

	$allowed_html[ $tag ]['style'] = true;
	$allowed_html[ $tag ]['class'] = true;
	$allowed_html[ $tag ]['id']    = true;
}

$lxp_block_reference_safe_styles = static function ( $styles ) {
	foreach ( array( 'display', 'grid-template-columns', 'gap', 'align-items', 'justify-content', 'box-shadow', 'text-transform', 'letter-spacing', 'transform', 'transform-origin' ) as $property ) {
		if ( ! in_array( $property, $styles, true ) ) {
			$styles[] = $property;
		}
	}

	return $styles;
};

add_filter( 'safe_style_css', $lxp_block_reference_safe_styles );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Block Reference', 'tiny-lxp-platform' ); ?></h1>
	<p><?php esc_html_e( 'Use these markers in the original lesson content to assemble a block-based AI lesson page. Write plain-language context inside the marker, copy it into the lesson editor, then run Generate (Block Mode) to have AI shape that intent into the selected block pattern.', 'tiny-lxp-platform' ); ?></p>

	<style>
		.lxp-block-reference-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
			gap: 20px;
			margin-top: 20px;
		}
		.lxp-block-reference-card {
			background: #fff;
			border: 1px solid #dcdcde;
			border-radius: 12px;
			padding: 18px;
			box-shadow: 0 8px 24px rgba(0,0,0,.04);
		}
		.lxp-block-reference-head {
			display: flex;
			justify-content: space-between;
			gap: 12px;
			align-items: center;
			margin-bottom: 10px;
		}
		.lxp-block-reference-head h2 {
			margin: 0;
			font-size: 18px;
		}
		.lxp-block-reference-marker {
			font-family: Consolas, monospace;
			font-size: 12px;
			background: rgba(68,46,102,.08);
			padding: 4px 8px;
			border-radius: 999px;
		}
		.lxp-block-reference-preview {
			height: 260px;
			overflow: auto;
			border: 1px solid rgba(68,46,102,.08);
			border-radius: 10px;
			background: #f6f7f7;
			padding: 12px;
			margin: 14px 0;
		}
		.lxp-block-reference-preview-inner {
			transform: scale(.62);
			transform-origin: top left;
			width: 161%;
			max-width: none;
		}
		.lxp-block-copy-status {
			margin-left: 8px;
			font-weight: 600;
			color: #1d6f42;
		}
		.lxp-block-reference-sample {
			margin: 14px 0;
			padding: 12px 14px;
			border: 1px solid #dcdcde;
			border-radius: 10px;
			background: #f6f7f7;
			overflow: auto;
		}
		.lxp-block-reference-sample code {
			display: block;
			white-space: pre-wrap;
			word-break: break-word;
			font-family: Consolas, monospace;
			font-size: 12px;
			line-height: 1.5;
		}
	</style>

	<div class="lxp-block-reference-grid">
		<?php foreach ( $catalog as $block ) : ?>
			<div class="lxp-block-reference-card">
				<div class="lxp-block-reference-head">
					<h2><?php echo esc_html( $block['label'] ); ?></h2>
					<span class="lxp-block-reference-marker"><?php echo esc_html( ':::' . $block['type'] ); ?></span>
				</div>
				<p><?php echo esc_html( $block['description'] ); ?></p>
				<pre class="lxp-block-reference-sample"><code><?php echo esc_html( $block['marker'] ); ?></code></pre>
				<p>
					<button type="button" class="button lxp-copy-marker-btn" data-marker="<?php echo esc_attr( $block['marker'] ); ?>">
						<?php esc_html_e( 'Copy Marker', 'tiny-lxp-platform' ); ?>
					</button>
					<span class="lxp-block-copy-status" aria-live="polite"></span>
				</p>
				<div class="lxp-block-reference-preview">
					<div class="lxp-block-reference-preview-inner">
						<?php echo wp_kses( $block['preview_html'], $allowed_html ); ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
	<?php remove_filter( 'safe_style_css', $lxp_block_reference_safe_styles ); ?>

	<script>
		document.addEventListener('click', function (event) {
			if (!event.target.classList.contains('lxp-copy-marker-btn')) {
				return;
			}

			var button = event.target;
			var marker = button.getAttribute('data-marker') || '';
			var status = button.parentNode.querySelector('.lxp-block-copy-status');

			if (!navigator.clipboard || !marker) {
				if (status) {
					status.textContent = '<?php echo esc_js( __( 'Clipboard not available.', 'tiny-lxp-platform' ) ); ?>';
				}
				return;
			}

			navigator.clipboard.writeText(marker).then(function () {
				if (status) {
					status.textContent = '<?php echo esc_js( __( 'Copied.', 'tiny-lxp-platform' ) ); ?>';
				}
			});
		});
	</script>
</div>