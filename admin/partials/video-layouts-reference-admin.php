<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─── Layout catalogue ─────────────────────────────────────────────────────────
$lxp_vl_layouts = array(
	array(
		'slug'        => 'intro',
		'name'        => 'Intro',
		'description' => 'Opening scene with a central title card and orbiting concept pills that establish the lesson topic and key themes.',
		'best_for'    => 'Always used as the first scene. Sets up the lesson subject, tone, and main concepts.',
		'sample'      => ":::intro\nAI-assisted differentiated instruction lets teachers produce three lesson\nvariants \xe2\x80\x94 beginner, on-level, advanced \xe2\x80\x94 from a single master prompt.\n:::",
	),
	array(
		'slug'        => 'problem',
		'name'        => 'Problem',
		'description' => 'A vertical stack of cards that frames a challenge or pain point, with the key problem item highlighted in gold.',
		'best_for'    => 'Establishing what is broken, missing, or difficult before introducing the solution.',
		'sample'      => ":::problem\nTeachers spend hours writing lesson variants by hand, often repeating\nthe same structure with minor tweaks. This is error-prone and slow.\n:::",
	),
	array(
		'slug'        => 'framework',
		'name'        => 'Framework',
		'description' => 'Numbered architectural blocks arranged in a blueprint-style row that defines the layers of a system or concept.',
		'best_for'    => 'Introducing systems, models, or multi-part structures where order and naming matter.',
		'sample'      => ":::framework\nThe lesson generation system has four layers: the teacher prompt layer,\nthe AI interpretation layer, the differentiation engine, and the output review layer.\n:::",
	),
	array(
		'slug'        => 'process',
		'name'        => 'Process',
		'description' => 'A horizontal pipeline of stages connected by animated gold arrows showing a sequential left-to-right flow.',
		'best_for'    => 'Workflows, procedures, and step-by-step processes where order is critical.',
		'sample'      => ":::process\nThe workflow runs in three stages: the teacher writes the prompt, the AI generates\nthree differentiated drafts, and the teacher reviews and publishes.\n:::",
	),
	array(
		'slug'        => 'contrast',
		'name'        => 'Contrast',
		'description' => 'Two-column layout comparing a problematic approach on the left against a preferred approach on the right.',
		'best_for'    => 'Good vs. bad comparisons, before/after mindset shifts, or myth vs. reality.',
		'sample'      => ":::contrast\nThe old way relies on copy-paste and manual editing, creating three slightly\ndifferent files that quickly fall out of sync. The new way uses a single\nsource prompt and generates clean, independent variants instantly.\n:::",
	),
	array(
		'slug'        => 'evaluation',
		'name'        => 'Evaluation',
		'description' => 'Vertical checklist of test cases with colour-coded pass, warn, or gap status indicators per row.',
		'best_for'    => 'Quality audits, readiness assessments, or gap analyses where some criteria fail.',
		'sample'      => ":::evaluation\nFive criteria for a quality AI-generated lesson: correct reading level, appropriate\nvocabulary, relevant examples, no missing scaffolding, and teacher sign-off.\nThe reading level check often reveals a gap that needs manual adjustment.\n:::",
	),
	array(
		'slug'        => 'options',
		'name'        => 'Options',
		'description' => 'Side-by-side option cards with the recommended choice visually distinguished by a featured badge.',
		'best_for'    => 'Decision points where the audience must choose between viable alternatives.',
		'sample'      => ":::options\nThree approaches to differentiation: manually write all variants, use a template\nwith fill-in-the-blank gaps, or use AI generation with a single master prompt.\nThe AI generation approach is the recommended option for busy teachers.\n:::",
	),
	array(
		'slug'        => 'conclusion',
		'name'        => 'Conclusion',
		'description' => 'Closing scene with a horizontal row of numbered action items connected by gold lines to drive the call to action.',
		'best_for'    => 'Always used as the last (or second-to-last) scene. Reinforces what to do next.',
		'sample'      => ":::conclusion\nStart with one lesson, generate three drafts, review for accuracy, publish\nthe best version, and refine based on student outcomes. The loop makes\nyou a faster, more responsive teacher over time.\n:::",
	),
	array(
		'slug'        => 'card-list',
		'name'        => 'Card List',
		'description' => 'A plain stacked list of topic cards with equal visual weight; one card can be optionally featured.',
		'best_for'    => 'Listing resources, principles, rules, or concepts without implying a priority order.',
		'sample'      => ":::card-list\nKey principles of effective AI prompting for educators: be specific about the audience,\nstate the learning objective clearly, set the tone and reading level, include subject\ncontext, and request a particular output format.\n:::",
	),
	array(
		'slug'        => 'branching-flow',
		'name'        => 'Branching Flow',
		'description' => 'One source item fans out through arrows to multiple output branches, showing a one-to-many relationship.',
		'best_for'    => 'Showing how one input generates multiple outputs, or a decision that triggers different paths.',
		'sample'      => ":::branching-flow\nA teacher writes one master prompt. The AI produces three separate lesson plans:\none for beginners who need visual scaffolding, one for on-level students working\nindependently, and one for advanced students ready for extension tasks.\n:::",
	),
	array(
		'slug'        => 'before-after',
		'name'        => 'Before & After',
		'description' => 'Two-column layout that directly compares the state before and the state after a change, with colour-coded headings.',
		'best_for'    => 'Transformation stories, showing concrete impact, or demonstrating measurable improvement.',
		'sample'      => ":::before-after\nBefore: A teacher spends 90 minutes writing three lesson variants by hand,\nresulting in inconsistent quality and formatting.\nAfter: The same teacher spends 8 minutes writing one prompt and reviewing\nthree clean AI-generated drafts ready to publish.\n:::",
	),
	array(
		'slug'        => 'quad-grid',
		'name'        => 'Quad Grid',
		'description' => 'Four items shown in an animated 2\xc3\x972 grid, each with a label and optional sub-label.',
		'best_for'    => 'Four-part frameworks, quadrant models, or any concept that splits cleanly into four equal parts.',
		'sample'      => ":::quad-grid\nThe differentiation model covers four student dimensions: readiness level,\nlearning pace, prior knowledge depth, and preferred content format. Each\ndimension can be tuned independently in the AI prompt.\n:::",
	),
	array(
		'slug'        => 'three-step-flow',
		'name'        => 'Three Step Flow',
		'description' => 'Three large boxes connected by prominent arrows, showing a clear three-stage sequence.',
		'best_for'    => 'Simple three-step processes where each step carries equal weight.',
		'sample'      => ":::three-step-flow\nThe AI lesson generation workflow is three steps: write the master prompt\nwith full context, receive and review three differentiated drafts, then\npublish the approved version to your LMS.\n:::",
	),
	array(
		'slug'        => 'cycle-loop',
		'name'        => 'Cycle Loop',
		'description' => 'Four nodes arranged in a diamond with clockwise arc arrows, representing a continuous improvement loop.',
		'best_for'    => 'Iterative processes, continuous improvement cycles, or repeating workflows.',
		'sample'      => ":::cycle-loop\nThe lesson improvement cycle repeats continuously: plan the learning objective,\ngenerate AI drafts for three levels, collect student performance data,\nthen refine the prompt and repeat the loop.\n:::",
	),
	array(
		'slug'        => 'split-blueprint',
		'name'        => 'Split Blueprint',
		'description' => 'Two columns showing input items on the left and output items on the right, connected by a central arrow.',
		'best_for'    => 'Input-to-output mappings, data transformation, or how components produce results.',
		'sample'      => ":::split-blueprint\nThe inputs to the differentiation engine are: the lesson topic, the learning\nobjective, the target grade, and the curriculum standard. The outputs are three\nlesson plans with adapted vocabulary and scaffolding for each readiness level.\n:::",
	),
	array(
		'slug'        => 'fuel-engine',
		'name'        => 'Fuel Engine',
		'description' => 'Multiple ingredient items on the left feed into a process and produce one highlighted result on the right.',
		'best_for'    => 'Showing how multiple inputs combine to produce a single outcome, like a recipe or formula.',
		'sample'      => ":::fuel-engine\nThree ingredients power the AI lesson engine: a clearly stated learning objective,\nthe subject and grade context, and the desired student action verb. Combined in\none prompt, they produce a differentiated lesson set ready to publish.\n:::",
	),
	array(
		'slug'        => 'checklist-reveal',
		'name'        => 'Checklist Reveal',
		'description' => 'A vertical checklist that reveals items sequentially with pass, warn, or gap status indicators per row.',
		'best_for'    => 'Step-by-step readiness checks, quality gates, or pre-publish checklists.',
		'sample'      => ":::checklist-reveal\nBefore publishing an AI-generated lesson: confirm the reading level is accurate,\ncheck that all examples are culturally relevant, verify the scaffolding is sufficient\nfor the beginner version, and flag any content the AI may have hallucinated.\n:::",
	),
	array(
		'slug'        => 'deployment-circles',
		'name'        => 'Deployment Circles',
		'description' => 'Four concentric rings labelled from the innermost unit outward, representing expanding scope of impact.',
		'best_for'    => 'Rollout strategies, organisational impact levels, or nested scope models.',
		'sample'      => ":::deployment-circles\nThe AI differentiation rollout expands in four rings: start with a single teacher\nand one class, then expand to the department, scale to the whole school, and\nfinally deploy across the district with standardised prompt templates.\n:::",
	),
	array(
		'slug'        => 'comparison',
		'name'        => 'Comparison (A vs B)',
		'description' => 'Two panels shown side-by-side with a central VS marker, optionally resolving into a single merged result card.',
		'best_for'    => 'Head-to-head choices: chat box vs. document, spreadsheet vs. PDF, blueprint vs. bricks.',
		'sample'      => ":::comparison\nCompare drafting a prompt directly in the chat box against drafting it first in a\nreusable external document, and show that the document approach wins.\n:::",
	),
	array(
		'slug'        => 'gate',
		'name'        => 'Gate (Clarify / Confirm)',
		'description' => 'A checkpoint that surfaces clarifying questions or confirmation checks, then opens to reveal the cleared result.',
		'best_for'    => 'Tools that should ask before they act: clarification mandates and confirmation gates.',
		'sample'      => ":::gate\nBefore the tool generates a lesson it must ask three questions \xe2\x80\x94 grade level,\nsubject, and format \xe2\x80\x94 and only proceed once they are answered.\n:::",
	),
	array(
		'slug'        => 'routing',
		'name'        => 'Routing (Sort to Buckets)',
		'description' => 'Each item animates from a source into its correct labelled destination bucket.',
		'best_for'    => 'Matching tasks to categories: routing each request to the right AI engine or sharing tier.',
		'sample'      => ":::routing\nRoute each task to the right engine: a routine email to Fast Mode, a curriculum map\nto Deep Reasoning, and a current-events question to Web Search.\n:::",
	),
	array(
		'slug'        => 'stat-highlight',
		'name'        => 'Stat Highlight (Metric)',
		'description' => 'One striking hero metric, or a before\xe2\x86\x92after pair of numbers, displayed large and centred.',
		'best_for'    => 'Quantified impact and leverage: time saved, effort reduced, scale achieved.',
		'sample'      => ":::stat-highlight\nShow how a custom grading tool turns a two-hour feedback workflow into a\nten-minute one.\n:::",
	),
	array(
		'slug'        => 'transform-text',
		'name'        => 'Transform Text (Rewrite)',
		'description' => 'A single weak statement morphs in place into a sharp, precise version.',
		'best_for'    => 'Showing instruction or tone improvements: vague rule rewritten into a precise one.',
		'sample'      => ":::transform-text\nRewrite the vague instruction \xe2\x80\x9cgive feedback\xe2\x80\x9d into the precise rule \xe2\x80\x9cprovide two\nstrengths and one improvement tied to the rubric.\xe2\x80\x9d\n:::",
	),
);
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Video Layout Reference', 'tiny-lxp-platform' ); ?></h1>
	<p><?php esc_html_e( 'Use these markers in the Generate Video modal to control scene layout. Write plain-language prose inside each block \xe2\x80\x94 AI derives the scene title, narration, and items from your text. No formatting cues or hints required.', 'tiny-lxp-platform' ); ?></p>
	<p style="background:#fff3cd;border:1px solid #ffc107;border-radius:6px;padding:10px 14px;font-size:13px;display:inline-block;margin-bottom:4px;">
		<strong><?php esc_html_e( 'Block format:', 'tiny-lxp-platform' ); ?></strong>
		<code style="background:transparent;">:::layout-name</code> &nbsp;&rarr;&nbsp; <?php esc_html_e( 'your prose content', 'tiny-lxp-platform' ); ?> &nbsp;&rarr;&nbsp; <code style="background:transparent;">:::</code>
	</p>

	<style>
		.lxp-vl-grid {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
			gap: 20px;
			margin-top: 20px;
		}
		.lxp-vl-card {
			background: #fff;
			border: 1px solid #dcdcde;
			border-radius: 12px;
			padding: 18px;
			box-shadow: 0 4px 16px rgba(0,0,0,.05);
		}
		.lxp-vl-head {
			display: flex;
			justify-content: space-between;
			align-items: center;
			gap: 10px;
			margin-bottom: 10px;
		}
		.lxp-vl-head h2 {
			margin: 0;
			font-size: 17px;
		}
		.lxp-vl-marker {
			font-family: Consolas, monospace;
			font-size: 12px;
			background: rgba(15,27,45,.07);
			color: #0F1B2D;
			padding: 4px 8px;
			border-radius: 999px;
			white-space: nowrap;
		}
		.lxp-vl-preview {
			height: 200px;
			overflow: hidden;
			border-radius: 8px;
			margin: 12px 0;
			position: relative;
		}
		.lxp-vl-preview-inner {
			transform: scale(.62);
			transform-origin: top left;
			width: 161%;
			height: 161%;
			pointer-events: none;
		}
		.lxp-vl-desc {
			font-size: 13px;
			color: #3c434a;
			margin: 0 0 6px;
		}
		.lxp-vl-best {
			font-size: 12px;
			color: #646970;
			margin: 0 0 12px;
		}
		.lxp-vl-sample {
			background: #f6f7f7;
			border: 1px solid #dcdcde;
			border-radius: 8px;
			padding: 10px 12px;
			margin-bottom: 10px;
			overflow: auto;
		}
		.lxp-vl-sample pre {
			margin: 0;
			white-space: pre-wrap;
			word-break: break-word;
		}
		.lxp-vl-sample code {
			font-family: Consolas, monospace;
			font-size: 12px;
			color: #1d1d1d;
		}
		.lxp-vl-copy-btn {
			font-size: 12px;
		}
		.lxp-vl-copy-status {
			margin-left: 8px;
			font-size: 12px;
			font-weight: 600;
			color: #1d6f42;
		}
	</style>

	<div class="lxp-vl-grid">
	<?php foreach ( $lxp_vl_layouts as $lxp_vl_layout ) : ?>
		<div class="lxp-vl-card">
			<div class="lxp-vl-head">
				<h2><?php echo esc_html( $lxp_vl_layout['name'] ); ?></h2>
				<span class="lxp-vl-marker">:::<?php echo esc_html( $lxp_vl_layout['slug'] ); ?></span>
			</div>

			<div class="lxp-vl-preview">
				<div class="lxp-vl-preview-inner">
					<?php lxp_vl_render_mockup( $lxp_vl_layout['slug'] ); ?>
				</div>
			</div>

			<p class="lxp-vl-desc"><?php echo esc_html( $lxp_vl_layout['description'] ); ?></p>
			<p class="lxp-vl-best"><strong><?php esc_html_e( 'Best for:', 'tiny-lxp-platform' ); ?></strong> <?php echo esc_html( $lxp_vl_layout['best_for'] ); ?></p>

			<div class="lxp-vl-sample">
				<pre><code><?php echo esc_html( $lxp_vl_layout['sample'] ); ?></code></pre>
			</div>
			<button type="button" class="button lxp-vl-copy-btn" data-sample="<?php echo esc_attr( $lxp_vl_layout['sample'] ); ?>">
				<?php esc_html_e( 'Copy sample', 'tiny-lxp-platform' ); ?>
			</button>
			<span class="lxp-vl-copy-status"></span>
		</div>
	<?php endforeach; ?>
	</div>

	<script>
	(function () {
		document.querySelectorAll('.lxp-vl-copy-btn').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var text = btn.getAttribute('data-sample');
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(text).then(function () {
						var s = btn.nextElementSibling;
						s.textContent = 'Copied!';
						setTimeout(function () { s.textContent = ''; }, 2200);
					});
				} else {
					var ta = document.createElement('textarea');
					ta.value = text;
					ta.style.position = 'fixed';
					ta.style.opacity = '0';
					document.body.appendChild(ta);
					ta.select();
					document.execCommand('copy');
					document.body.removeChild(ta);
					var s = btn.nextElementSibling;
					s.textContent = 'Copied!';
					setTimeout(function () { s.textContent = ''; }, 2200);
				}
			});
		});
	})();
	</script>
</div>
<?php

// ─── Mockup renderers ─────────────────────────────────────────────────────────
// All mockups use dark navy (#0F1B2D) background with gold (#F5B800) accents
// to approximate the actual video aesthetic.

function lxp_vl_render_mockup( $slug ) {
	switch ( $slug ) {

		case 'intro': ?>
<div style="background:#0F1B2D;height:322px;border-radius:6px;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;">
	<div style="background:#162236;border:1px solid rgba(245,184,0,.35);border-radius:10px;padding:22px 32px;text-align:center;z-index:2;">
		<div style="color:#F5B800;font-size:11px;font-weight:700;letter-spacing:.1em;margin-bottom:6px;text-transform:uppercase;">Lesson Topic</div>
		<div style="color:rgba(255,255,255,.9);font-size:20px;font-weight:700;">Lesson Title</div>
	</div>
	<div style="position:absolute;top:28px;left:22px;background:rgba(245,184,0,.12);border:1px solid rgba(245,184,0,.35);border-radius:999px;padding:5px 12px;color:#F5B800;font-size:11px;">Concept A</div>
	<div style="position:absolute;top:28px;right:22px;background:rgba(245,184,0,.12);border:1px solid rgba(245,184,0,.35);border-radius:999px;padding:5px 12px;color:#F5B800;font-size:11px;">Concept B</div>
	<div style="position:absolute;bottom:28px;left:22px;background:rgba(245,184,0,.12);border:1px solid rgba(245,184,0,.35);border-radius:999px;padding:5px 12px;color:#F5B800;font-size:11px;">Concept C</div>
	<div style="position:absolute;bottom:28px;right:22px;background:rgba(245,184,0,.12);border:1px solid rgba(245,184,0,.35);border-radius:999px;padding:5px 12px;color:#F5B800;font-size:11px;">Concept D</div>
</div>
		<?php break;

		case 'problem': ?>
<div style="background:#0F1B2D;height:322px;border-radius:6px;display:flex;flex-direction:column;align-items:stretch;justify-content:center;padding:20px;gap:10px;box-sizing:border-box;">
	<div style="background:#162236;border:1px solid rgba(255,255,255,.1);border-radius:7px;padding:11px 14px;color:rgba(255,255,255,.55);font-size:14px;">Challenge item one</div>
	<div style="background:#162236;border:2px solid #F5B800;border-radius:7px;padding:11px 14px;color:#F5B800;font-size:14px;font-weight:700;">&#9733; Key problem item</div>
	<div style="background:#162236;border:1px solid rgba(255,255,255,.1);border-radius:7px;padding:11px 14px;color:rgba(255,255,255,.55);font-size:14px;">Challenge item three</div>
	<div style="background:#162236;border:1px solid rgba(255,255,255,.1);border-radius:7px;padding:11px 14px;color:rgba(255,255,255,.55);font-size:14px;">Challenge item four</div>
</div>
		<?php break;

		case 'framework': ?>
<div style="background:#0F1B2D;height:322px;border-radius:6px;display:flex;align-items:center;justify-content:center;padding:24px;gap:10px;box-sizing:border-box;">
	<div style="background:#162236;border:1px solid rgba(245,184,0,.25);border-radius:7px;padding:16px 10px;text-align:center;flex:1;">
		<div style="color:#F5B800;font-size:26px;font-weight:700;">1</div>
		<div style="color:rgba(255,255,255,.7);font-size:12px;margin-top:6px;">Layer One</div>
	</div>
	<div style="background:#162236;border:1px solid rgba(245,184,0,.25);border-radius:7px;padding:16px 10px;text-align:center;flex:1;">
		<div style="color:#F5B800;font-size:26px;font-weight:700;">2</div>
		<div style="color:rgba(255,255,255,.7);font-size:12px;margin-top:6px;">Layer Two</div>
	</div>
	<div style="background:#162236;border:1px solid rgba(245,184,0,.25);border-radius:7px;padding:16px 10px;text-align:center;flex:1;">
		<div style="color:#F5B800;font-size:26px;font-weight:700;">3</div>
		<div style="color:rgba(255,255,255,.7);font-size:12px;margin-top:6px;">Layer Three</div>
	</div>
	<div style="background:#162236;border:1px solid rgba(245,184,0,.25);border-radius:7px;padding:16px 10px;text-align:center;flex:1;">
		<div style="color:#F5B800;font-size:26px;font-weight:700;">4</div>
		<div style="color:rgba(255,255,255,.7);font-size:12px;margin-top:6px;">Layer Four</div>
	</div>
</div>
		<?php break;

		case 'process': ?>
<div style="background:#0F1B2D;height:322px;border-radius:6px;display:flex;align-items:center;justify-content:center;padding:24px;gap:0;box-sizing:border-box;">
	<div style="background:#162236;border:1px solid rgba(245,184,0,.3);border-radius:7px;padding:18px 12px;text-align:center;flex:1;">
		<div style="color:#F5B800;font-size:11px;font-weight:700;margin-bottom:6px;letter-spacing:.08em;">STEP 1</div>
		<div style="color:rgba(255,255,255,.8);font-size:13px;">Stage One</div>
	</div>
	<div style="color:#F5B800;font-size:22px;padding:0 8px;flex-shrink:0;">&rarr;</div>
	<div style="background:#162236;border:1px solid rgba(245,184,0,.3);border-radius:7px;padding:18px 12px;text-align:center;flex:1;">
		<div style="color:#F5B800;font-size:11px;font-weight:700;margin-bottom:6px;letter-spacing:.08em;">STEP 2</div>
		<div style="color:rgba(255,255,255,.8);font-size:13px;">Stage Two</div>
	</div>
	<div style="color:#F5B800;font-size:22px;padding:0 8px;flex-shrink:0;">&rarr;</div>
	<div style="background:#162236;border:1px solid rgba(245,184,0,.3);border-radius:7px;padding:18px 12px;text-align:center;flex:1;">
		<div style="color:#F5B800;font-size:11px;font-weight:700;margin-bottom:6px;letter-spacing:.08em;">STEP 3</div>
		<div style="color:rgba(255,255,255,.8);font-size:13px;">Stage Three</div>
	</div>
</div>
		<?php break;

		case 'contrast': ?>
<div style="background:#0F1B2D;height:322px;border-radius:6px;display:flex;align-items:stretch;padding:24px;gap:16px;box-sizing:border-box;">
	<div style="background:rgba(220,38,38,.1);border:1px solid rgba(220,38,38,.4);border-radius:7px;flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:14px;">
		<div style="color:rgba(220,38,38,.9);font-size:12px;font-weight:700;margin-bottom:8px;letter-spacing:.08em;">&#10007; BAD</div>
		<div style="color:rgba(255,255,255,.6);font-size:12px;text-align:center;">Old approach &mdash; slow and error-prone</div>
	</div>
	<div style="background:rgba(22,163,74,.1);border:1px solid rgba(22,163,74,.4);border-radius:7px;flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:14px;">
		<div style="color:rgba(22,163,74,.9);font-size:12px;font-weight:700;margin-bottom:8px;letter-spacing:.08em;">&#10003; GOOD</div>
		<div style="color:rgba(255,255,255,.6);font-size:12px;text-align:center;">New approach &mdash; fast and consistent</div>
	</div>
</div>
		<?php break;

		case 'evaluation': ?>
<div style="background:#0F1B2D;height:322px;border-radius:6px;display:flex;flex-direction:column;align-items:stretch;justify-content:center;padding:20px;gap:8px;box-sizing:border-box;">
	<div style="background:#162236;border-radius:7px;padding:10px 14px;display:flex;align-items:center;gap:10px;">
		<div style="width:10px;height:10px;border-radius:50%;background:#22c55e;flex-shrink:0;"></div>
		<div style="color:rgba(255,255,255,.8);font-size:12px;flex:1;">Reading level check</div>
		<div style="color:#22c55e;font-size:10px;font-weight:700;">PASS</div>
	</div>
	<div style="background:#162236;border-radius:7px;padding:10px 14px;display:flex;align-items:center;gap:10px;">
		<div style="width:10px;height:10px;border-radius:50%;background:#22c55e;flex-shrink:0;"></div>
		<div style="color:rgba(255,255,255,.8);font-size:12px;flex:1;">Vocabulary accuracy</div>
		<div style="color:#22c55e;font-size:10px;font-weight:700;">PASS</div>
	</div>
	<div style="background:#162236;border:1px solid rgba(239,68,68,.4);border-radius:7px;padding:10px 14px;display:flex;align-items:center;gap:10px;">
		<div style="width:10px;height:10px;border-radius:50%;background:#ef4444;flex-shrink:0;"></div>
		<div style="color:rgba(255,255,255,.8);font-size:12px;flex:1;">Scaffolding coverage</div>
		<div style="color:#ef4444;font-size:10px;font-weight:700;">GAP</div>
	</div>
	<div style="background:#162236;border-radius:7px;padding:10px 14px;display:flex;align-items:center;gap:10px;">
		<div style="width:10px;height:10px;border-radius:50%;background:#eab308;flex-shrink:0;"></div>
		<div style="color:rgba(255,255,255,.8);font-size:12px;flex:1;">Cultural relevance</div>
		<div style="color:#eab308;font-size:10px;font-weight:700;">WARN</div>
	</div>
</div>
		<?php break;

		case 'options': ?>
<div style="background:#0F1B2D;height:322px;border-radius:6px;display:flex;align-items:center;justify-content:center;padding:24px;gap:12px;box-sizing:border-box;">
	<div style="background:#162236;border:1px solid rgba(255,255,255,.12);border-radius:7px;flex:1;padding:18px 10px;text-align:center;">
		<div style="color:rgba(255,255,255,.5);font-size:12px;font-weight:700;margin-bottom:8px;">OPTION A</div>
		<div style="color:rgba(255,255,255,.55);font-size:11px;">Manual</div>
	</div>
	<div style="background:#162236;border:2px solid #F5B800;border-radius:7px;flex:1;padding:18px 10px;text-align:center;position:relative;">
		<div style="position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:#F5B800;color:#0F1B2D;font-size:9px;font-weight:700;padding:3px 8px;border-radius:999px;">BEST</div>
		<div style="color:#F5B800;font-size:12px;font-weight:700;margin-bottom:8px;">OPTION B</div>
		<div style="color:rgba(255,255,255,.75);font-size:11px;">AI-driven</div>
	</div>
	<div style="background:#162236;border:1px solid rgba(255,255,255,.12);border-radius:7px;flex:1;padding:18px 10px;text-align:center;">
		<div style="color:rgba(255,255,255,.5);font-size:12px;font-weight:700;margin-bottom:8px;">OPTION C</div>
		<div style="color:rgba(255,255,255,.55);font-size:11px;">Template</div>
	</div>
</div>
		<?php break;

		case 'conclusion': ?>
<div style="background:#0F1B2D;height:322px;border-radius:6px;display:flex;align-items:center;justify-content:center;padding:24px;box-sizing:border-box;">
	<div style="display:flex;align-items:center;gap:0;width:100%;">
		<div style="flex:1;text-align:center;">
			<div style="width:42px;height:42px;border-radius:50%;background:#F5B800;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;font-size:16px;font-weight:700;color:#0F1B2D;">1</div>
			<div style="color:rgba(255,255,255,.8);font-size:12px;">Draft</div>
		</div>
		<div style="height:2px;flex:0 0 20px;background:rgba(245,184,0,.4);"></div>
		<div style="flex:1;text-align:center;">
			<div style="width:42px;height:42px;border-radius:50%;background:#F5B800;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;font-size:16px;font-weight:700;color:#0F1B2D;">2</div>
			<div style="color:rgba(255,255,255,.8);font-size:12px;">Test</div>
		</div>
		<div style="height:2px;flex:0 0 20px;background:rgba(245,184,0,.4);"></div>
		<div style="flex:1;text-align:center;">
			<div style="width:42px;height:42px;border-radius:50%;background:#F5B800;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;font-size:16px;font-weight:700;color:#0F1B2D;">3</div>
			<div style="color:rgba(255,255,255,.8);font-size:12px;">Share</div>
		</div>
		<div style="height:2px;flex:0 0 20px;background:rgba(245,184,0,.4);"></div>
		<div style="flex:1;text-align:center;">
			<div style="width:42px;height:42px;border-radius:50%;background:#F5B800;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;font-size:16px;font-weight:700;color:#0F1B2D;">4</div>
			<div style="color:rgba(255,255,255,.8);font-size:12px;">Refine</div>
		</div>
	</div>
</div>
		<?php break;

		case 'card-list': ?>
<div style="background:#0F1B2D;height:322px;border-radius:6px;display:flex;flex-direction:column;align-items:stretch;justify-content:center;padding:20px;gap:8px;box-sizing:border-box;">
	<div style="background:#162236;border:1px solid rgba(255,255,255,.1);border-radius:7px;padding:10px 14px;color:rgba(255,255,255,.7);font-size:13px;">Card item one</div>
	<div style="background:#162236;border:1px solid rgba(255,255,255,.1);border-radius:7px;padding:10px 14px;color:rgba(255,255,255,.7);font-size:13px;">Card item two</div>
	<div style="background:#162236;border:1px solid rgba(245,184,0,.4);border-radius:7px;padding:10px 14px;color:#F5B800;font-size:13px;font-weight:700;">&#9733; Featured item</div>
	<div style="background:#162236;border:1px solid rgba(255,255,255,.1);border-radius:7px;padding:10px 14px;color:rgba(255,255,255,.7);font-size:13px;">Card item four</div>
	<div style="background:#162236;border:1px solid rgba(255,255,255,.1);border-radius:7px;padding:10px 14px;color:rgba(255,255,255,.7);font-size:13px;">Card item five</div>
</div>
		<?php break;

		case 'branching-flow': ?>
<div style="background:#0F1B2D;height:322px;border-radius:6px;display:flex;align-items:center;padding:24px;gap:0;box-sizing:border-box;">
	<div style="background:#162236;border:2px solid rgba(245,184,0,.5);border-radius:7px;padding:18px 12px;text-align:center;min-width:80px;flex-shrink:0;">
		<div style="color:#F5B800;font-size:11px;font-weight:700;margin-bottom:5px;letter-spacing:.08em;">INPUT</div>
		<div style="color:rgba(255,255,255,.8);font-size:12px;">Source</div>
	</div>
	<div style="flex:1;display:flex;align-items:center;justify-content:center;">
		<div style="color:#F5B800;font-size:26px;">&rArr;</div>
	</div>
	<div style="display:flex;flex-direction:column;gap:10px;">
		<div style="background:#162236;border:1px solid rgba(245,184,0,.25);border-radius:7px;padding:10px 14px;color:rgba(255,255,255,.75);font-size:12px;min-width:80px;text-align:center;">Branch A</div>
		<div style="background:#162236;border:1px solid rgba(245,184,0,.25);border-radius:7px;padding:10px 14px;color:rgba(255,255,255,.75);font-size:12px;min-width:80px;text-align:center;">Branch B</div>
		<div style="background:#162236;border:1px solid rgba(245,184,0,.25);border-radius:7px;padding:10px 14px;color:rgba(255,255,255,.75);font-size:12px;min-width:80px;text-align:center;">Branch C</div>
	</div>
</div>
		<?php break;

		case 'before-after': ?>
<div style="background:#0F1B2D;height:322px;border-radius:6px;display:flex;align-items:stretch;padding:24px;gap:16px;box-sizing:border-box;">
	<div style="flex:1;display:flex;flex-direction:column;">
		<div style="color:rgba(220,38,38,.9);font-size:10px;font-weight:700;letter-spacing:.1em;margin-bottom:10px;text-align:center;text-transform:uppercase;">Before</div>
		<div style="background:rgba(220,38,38,.08);border:1px solid rgba(220,38,38,.3);border-radius:7px;flex:1;padding:14px;display:flex;align-items:center;justify-content:center;">
			<div style="color:rgba(255,255,255,.55);font-size:12px;text-align:center;">Old state &mdash; slow, error-prone</div>
		</div>
	</div>
	<div style="flex:1;display:flex;flex-direction:column;">
		<div style="color:rgba(22,163,74,.9);font-size:10px;font-weight:700;letter-spacing:.1em;margin-bottom:10px;text-align:center;text-transform:uppercase;">After</div>
		<div style="background:rgba(22,163,74,.08);border:1px solid rgba(22,163,74,.3);border-radius:7px;flex:1;padding:14px;display:flex;align-items:center;justify-content:center;">
			<div style="color:rgba(255,255,255,.75);font-size:12px;text-align:center;">New state &mdash; fast, consistent</div>
		</div>
	</div>
</div>
		<?php break;

		case 'quad-grid': ?>
<div style="background:#0F1B2D;height:322px;border-radius:6px;display:flex;align-items:center;justify-content:center;padding:24px;box-sizing:border-box;">
	<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;width:100%;">
		<div style="background:#162236;border:1px solid rgba(245,184,0,.25);border-radius:7px;padding:18px 12px;text-align:center;">
			<div style="color:#F5B800;font-size:18px;margin-bottom:6px;">&#10003;</div>
			<div style="color:rgba(255,255,255,.75);font-size:12px;">Dimension One</div>
		</div>
		<div style="background:#162236;border:1px solid rgba(245,184,0,.25);border-radius:7px;padding:18px 12px;text-align:center;">
			<div style="color:#F5B800;font-size:18px;margin-bottom:6px;">&#10003;</div>
			<div style="color:rgba(255,255,255,.75);font-size:12px;">Dimension Two</div>
		</div>
		<div style="background:#162236;border:1px solid rgba(245,184,0,.25);border-radius:7px;padding:18px 12px;text-align:center;">
			<div style="color:#F5B800;font-size:18px;margin-bottom:6px;">&#10003;</div>
			<div style="color:rgba(255,255,255,.75);font-size:12px;">Dimension Three</div>
		</div>
		<div style="background:#162236;border:1px solid rgba(245,184,0,.25);border-radius:7px;padding:18px 12px;text-align:center;">
			<div style="color:#F5B800;font-size:18px;margin-bottom:6px;">&#10003;</div>
			<div style="color:rgba(255,255,255,.75);font-size:12px;">Dimension Four</div>
		</div>
	</div>
</div>
		<?php break;

		case 'three-step-flow': ?>
<div style="background:#0F1B2D;height:322px;border-radius:6px;display:flex;align-items:center;justify-content:center;padding:24px;box-sizing:border-box;">
	<div style="display:flex;align-items:center;gap:0;width:100%;">
		<div style="background:#162236;border:1px solid rgba(245,184,0,.3);border-radius:8px;flex:1;padding:22px 12px;text-align:center;">
			<div style="color:#F5B800;font-size:24px;font-weight:700;margin-bottom:6px;">1</div>
			<div style="color:rgba(255,255,255,.8);font-size:13px;">Write Prompt</div>
		</div>
		<div style="color:#F5B800;font-size:28px;padding:0 8px;flex-shrink:0;">&#10151;</div>
		<div style="background:#162236;border:1px solid rgba(245,184,0,.3);border-radius:8px;flex:1;padding:22px 12px;text-align:center;">
			<div style="color:#F5B800;font-size:24px;font-weight:700;margin-bottom:6px;">2</div>
			<div style="color:rgba(255,255,255,.8);font-size:13px;">Review Drafts</div>
		</div>
		<div style="color:#F5B800;font-size:28px;padding:0 8px;flex-shrink:0;">&#10151;</div>
		<div style="background:#162236;border:1px solid rgba(245,184,0,.3);border-radius:8px;flex:1;padding:22px 12px;text-align:center;">
			<div style="color:#F5B800;font-size:24px;font-weight:700;margin-bottom:6px;">3</div>
			<div style="color:rgba(255,255,255,.8);font-size:13px;">Publish</div>
		</div>
	</div>
</div>
		<?php break;

		case 'cycle-loop': ?>
<div style="background:#0F1B2D;height:322px;border-radius:6px;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;box-sizing:border-box;">
	<div style="color:rgba(245,184,0,.2);font-size:180px;line-height:1;user-select:none;">&#8635;</div>
	<div style="position:absolute;top:28px;left:50%;transform:translateX(-50%);background:#162236;border:1px solid rgba(245,184,0,.4);border-radius:7px;padding:8px 14px;color:#F5B800;font-size:12px;font-weight:700;white-space:nowrap;">Plan</div>
	<div style="position:absolute;top:50%;right:22px;transform:translateY(-50%);background:#162236;border:1px solid rgba(245,184,0,.4);border-radius:7px;padding:8px 14px;color:#F5B800;font-size:12px;font-weight:700;white-space:nowrap;">Generate</div>
	<div style="position:absolute;bottom:28px;left:50%;transform:translateX(-50%);background:#162236;border:1px solid rgba(245,184,0,.4);border-radius:7px;padding:8px 14px;color:#F5B800;font-size:12px;font-weight:700;white-space:nowrap;">Collect</div>
	<div style="position:absolute;top:50%;left:22px;transform:translateY(-50%);background:#162236;border:1px solid rgba(245,184,0,.4);border-radius:7px;padding:8px 14px;color:#F5B800;font-size:12px;font-weight:700;white-space:nowrap;">Refine</div>
</div>
		<?php break;

		case 'split-blueprint': ?>
<div style="background:#0F1B2D;height:322px;border-radius:6px;display:flex;align-items:center;padding:24px;gap:10px;box-sizing:border-box;">
	<div style="flex:1;display:flex;flex-direction:column;gap:8px;">
		<div style="color:rgba(34,211,238,.7);font-size:9px;font-weight:700;letter-spacing:.1em;margin-bottom:2px;text-transform:uppercase;">Inputs</div>
		<div style="background:rgba(34,211,238,.08);border:1px solid rgba(34,211,238,.25);border-radius:5px;padding:8px 10px;color:rgba(255,255,255,.7);font-size:12px;">Topic</div>
		<div style="background:rgba(34,211,238,.08);border:1px solid rgba(34,211,238,.25);border-radius:5px;padding:8px 10px;color:rgba(255,255,255,.7);font-size:12px;">Objective</div>
		<div style="background:rgba(34,211,238,.08);border:1px solid rgba(34,211,238,.25);border-radius:5px;padding:8px 10px;color:rgba(255,255,255,.7);font-size:12px;">Grade Level</div>
	</div>
	<div style="color:#F5B800;font-size:26px;flex-shrink:0;">&rArr;</div>
	<div style="flex:1;display:flex;flex-direction:column;gap:8px;">
		<div style="color:rgba(245,184,0,.7);font-size:9px;font-weight:700;letter-spacing:.1em;margin-bottom:2px;text-transform:uppercase;">Outputs</div>
		<div style="background:rgba(245,184,0,.08);border:1px solid rgba(245,184,0,.25);border-radius:5px;padding:8px 10px;color:rgba(255,255,255,.7);font-size:12px;">Beginner Plan</div>
		<div style="background:rgba(245,184,0,.08);border:1px solid rgba(245,184,0,.25);border-radius:5px;padding:8px 10px;color:rgba(255,255,255,.7);font-size:12px;">On-Level Plan</div>
		<div style="background:rgba(245,184,0,.08);border:1px solid rgba(245,184,0,.25);border-radius:5px;padding:8px 10px;color:rgba(255,255,255,.7);font-size:12px;">Advanced Plan</div>
	</div>
</div>
		<?php break;

		case 'fuel-engine': ?>
<div style="background:#0F1B2D;height:322px;border-radius:6px;display:flex;align-items:center;padding:24px;gap:0;box-sizing:border-box;">
	<div style="flex:1;display:flex;flex-direction:column;gap:10px;">
		<div style="background:#162236;border:1px solid rgba(255,255,255,.12);border-radius:7px;padding:10px 12px;color:rgba(255,255,255,.65);font-size:12px;text-align:center;">Ingredient A</div>
		<div style="background:#162236;border:1px solid rgba(255,255,255,.12);border-radius:7px;padding:10px 12px;color:rgba(255,255,255,.65);font-size:12px;text-align:center;">Ingredient B</div>
		<div style="background:#162236;border:1px solid rgba(255,255,255,.12);border-radius:7px;padding:10px 12px;color:rgba(255,255,255,.65);font-size:12px;text-align:center;">Ingredient C</div>
	</div>
	<div style="color:#F5B800;font-size:26px;padding:0 10px;flex-shrink:0;">&rArr;</div>
	<div style="background:#162236;border:2px solid rgba(245,184,0,.55);border-radius:8px;padding:20px 14px;text-align:center;min-width:80px;flex-shrink:0;">
		<div style="color:#F5B800;font-size:11px;font-weight:700;margin-bottom:6px;text-transform:uppercase;letter-spacing:.08em;">Result</div>
		<div style="color:rgba(255,255,255,.85);font-size:13px;">Output</div>
	</div>
</div>
		<?php break;

		case 'checklist-reveal': ?>
<div style="background:#0F1B2D;height:322px;border-radius:6px;display:flex;flex-direction:column;align-items:stretch;justify-content:center;padding:20px;gap:8px;box-sizing:border-box;">
	<div style="background:#162236;border-radius:7px;padding:10px 14px;display:flex;align-items:center;gap:10px;">
		<div style="width:16px;height:16px;border-radius:4px;background:#22c55e;display:flex;align-items:center;justify-content:center;color:#fff;font-size:11px;flex-shrink:0;">&#10003;</div>
		<div style="color:rgba(255,255,255,.8);font-size:12px;">Check item one</div>
	</div>
	<div style="background:#162236;border-radius:7px;padding:10px 14px;display:flex;align-items:center;gap:10px;">
		<div style="width:16px;height:16px;border-radius:4px;background:#22c55e;display:flex;align-items:center;justify-content:center;color:#fff;font-size:11px;flex-shrink:0;">&#10003;</div>
		<div style="color:rgba(255,255,255,.8);font-size:12px;">Check item two</div>
	</div>
	<div style="background:#162236;border:1px solid rgba(239,68,68,.4);border-radius:7px;padding:10px 14px;display:flex;align-items:center;gap:10px;">
		<div style="width:16px;height:16px;border-radius:4px;background:#ef4444;display:flex;align-items:center;justify-content:center;color:#fff;font-size:11px;flex-shrink:0;">&#10007;</div>
		<div style="color:rgba(239,68,68,.9);font-size:12px;">Gap item (flagged)</div>
	</div>
	<div style="background:#162236;border-radius:7px;padding:10px 14px;display:flex;align-items:center;gap:10px;">
		<div style="width:16px;height:16px;border-radius:4px;background:#eab308;display:flex;align-items:center;justify-content:center;color:#fff;font-size:11px;flex-shrink:0;">!</div>
		<div style="color:rgba(255,255,255,.8);font-size:12px;">Warn item four</div>
	</div>
</div>
		<?php break;

		case 'deployment-circles': ?>
<div style="background:#0F1B2D;height:322px;border-radius:6px;display:flex;align-items:center;justify-content:center;overflow:hidden;box-sizing:border-box;">
	<div style="width:240px;height:240px;border-radius:50%;border:1px solid rgba(245,184,0,.18);display:flex;align-items:center;justify-content:center;position:relative;">
		<div style="position:absolute;bottom:10px;color:rgba(255,255,255,.35);font-size:11px;">District</div>
		<div style="width:178px;height:178px;border-radius:50%;border:1px solid rgba(245,184,0,.28);display:flex;align-items:center;justify-content:center;position:relative;">
			<div style="position:absolute;bottom:10px;color:rgba(255,255,255,.45);font-size:11px;">School</div>
			<div style="width:118px;height:118px;border-radius:50%;border:1px solid rgba(245,184,0,.42);display:flex;align-items:center;justify-content:center;position:relative;">
				<div style="position:absolute;bottom:8px;color:rgba(255,255,255,.55);font-size:11px;">Team</div>
				<div style="width:58px;height:58px;border-radius:50%;background:rgba(245,184,0,.2);border:1px solid rgba(245,184,0,.7);display:flex;align-items:center;justify-content:center;">
					<span style="color:#F5B800;font-size:10px;font-weight:700;">Core</span>
				</div>
			</div>
		</div>
	</div>
</div>
		<?php break;

		default:
			echo '<div style="background:#0F1B2D;height:322px;border-radius:6px;display:flex;align-items:center;justify-content:center;"><span style="color:rgba(255,255,255,.3);font-size:13px;">' . esc_html( $slug ) . '</span></div>';
			break;
	}
}
