<?php

global $treks_src;

$assignment_id = intval( $_GET['assignment'] );
$student_id = 0;
if ( (isset($_GET['student']) && intval($_GET['student']) > 0) ) {
    $student_id = intval($_GET['student']);
}

$assignment = lxp_get_assignment($assignment_id);
$assignment_submission = lxp_get_assignment_submissions($assignment->ID, $student_id);
$mark_as_graded = null; $code = '';
if ($submit_status) {
    $local_user_id = get_post_meta($student_id, 'lxp_student_admin_id', true);
    if ( isset($_GET['type']) && $_GET['type'] == 'IV' ) {
        $all_interactions = filterInteractiveVideoInteractions($interactive_videos);
    } elseif ( isset($_GET['type']) && $_GET['type'] == 'QS' ) {
        $all_interactions = filterQuestionSetInteractions($interactive_videos);
    } elseif ( isset($_GET['type']) && $_GET['type'] == 'CP' ) {
        $all_interactions = filterInteractiveBookInteractions($interactive_videos);
    }
    
    $given_interaction = 0;
    $given_interaction_time = 0;
    $single_interaction = [];
    if ( $all_interactions['type'] == 'interactive_video' && isset($_GET['ivInteraction']) && isset($_GET['ivInteractionTime']) ) {
        $given_interaction = intval($_GET['ivInteraction']);
        $given_interaction_time = intval($_GET['ivInteractionTime']);
        $single_interaction = isset($all_interactions['data'][$given_interaction]) ? $all_interactions['data'][$given_interaction] : [];
        $sub_content_id = $single_interaction['action']['subContentId'];
        $assignment_submission['sub_content_id'] = isset($single_interaction['action']) ? $single_interaction['action']['subContentId'] : null;
    } elseif ( $all_interactions['type'] == 'question_set' && isset($_GET['type']) && $_GET['type'] == 'QS' ) {
        $question_number = intval($_GET['question']) - 1;
        $single_interaction = isset($all_interactions['data'][$question_number]) ? $all_interactions['data'][$question_number] : [];
        $sub_content_id = $single_interaction['subContentId'];
        $assignment_submission['sub_content_id'] = isset($single_interaction['action']) ? $single_interaction['action']['subContentId'] : null;
    } elseif ( $all_interactions['type'] == 'interactive_book' && isset($_GET['type']) && $_GET['type'] == 'CP' ) {
        $slide_number = intval($_GET['slide'])-1;
        $question_number = intval($_GET['question']);
        if (isset($_GET['ivInteraction'])) {
            $given_iv_interaction_num = intval($_GET['ivInteraction']);
            $single_interaction = isset($all_interactions['data'][$slide_number]['elements'][$question_number]) ? $all_interactions['data'][$slide_number]['elements'][$question_number] : [];
            $iv_interactions = filterInteractiveVideoInteractions($single_interaction['action']['params']['interactiveVideo']);
            $single_interaction = $iv_interactions['data'][$given_iv_interaction_num];
            $sub_content_id = $single_interaction['action']['subContentId'];
            $assignment_submission['sub_content_id'] = isset($single_interaction['action']) ? $single_interaction['action']['subContentId'] : null;
        } else {
            $single_interaction = isset($all_interactions['data'][$slide_number]['elements'][$question_number]) ? $all_interactions['data'][$slide_number]['elements'][$question_number] : [];
            $sub_content_id = $single_interaction['action']['subContentId'];
            $assignment_submission['sub_content_id'] = isset($single_interaction['action']) ? $single_interaction['action']['subContentId'] : null;
        }
    }
    
    $grade = $assignment_submission ? get_post_meta($assignment_submission['ID'], "slide_" . $sub_content_id . "_grade", true) : '';
    $result = $assignment_submission ? get_post_meta($assignment_submission['ID'], "slide_" . $sub_content_id . "_result", true) : '';
    
    $assignment_submission['sub_content_id'] = $sub_content_id;
    $interaction_xapis = get_submitted_xapi_data($assignment_submission, 'answered');
    $interaction_xapis = isset($interaction_xapis[0]) ? $interaction_xapis[0] : '';
}
?>
<div class="tab-content" id="myTabContent">
    <div class="container">
        <div class="row">
            <div class="col col-md-8">
                <iframe style="border: none; width: 100%; height: 395px; " class="" src="<?= site_url() ?>?lti-platform&post=<?= $course_lesson->ID ?>&id=<?= $attrId ?><?= $queryParam ?>&ivInteraction=<?= $given_interaction ?>&ivInteractionTime=<?= $given_interaction_time ?>" allowfullscreen></iframe>
            </div>
            <?php 
            if ($submit_status && $interaction_xapis) {
                $max_grades = $interaction_xapis['result']['score']['max'];
                if (isset($single_interaction['libraryTitle'])) {
                    $content_type = $single_interaction['libraryTitle'];
                    $content_title = $single_interaction['libraryTitle'];
                } elseif (isset($single_interaction['metadata']['contentType'])) {
                    $content_type = $single_interaction['metadata']['contentType'];
                    $content_title = $single_interaction['metadata']['title'];
                } else {
                    $content_type = $single_interaction['action']['metadata']['contentType'];
                    $content_title = $single_interaction['action']['metadata']['title'];
                }
            ?>
            <div class="col col-md-4">
                <div class="grade-box">
                    <span class="grade-box-slide">
                        <center>
                            <?= $content_title; ?> <br />
                            <?= ($all_interactions['type'] == 'interactive_video') ? '('.convetToInterectiveVideoTimeFormate($single_interaction['duration']['from'], 1)[0].')' : ''; ?></center>
                    </span>
                    <?php if ($content_type == 'Free Text Question' || $content_type == 'Essay') { ?>
                        <div class="grade-select">
                            <select name="grade" id="grade" class="form-select">
                                <option value="">----</option>
                                <?php foreach (range(0, intval($max_grades)) as $grade_number): ?>
                                <option value="<?= $grade_number; ?>"> <?= $grade_number; ?> </option>
                                <?php endforeach; ?>
                            </select>
                            <button class="grade-box-btn" onclick="assign_grade('<?= $sub_content_id; ?>')">Grade</button>
                            <br />
                            <button class="add-feedback-btn btn btn-outline-info btn-lg" data-subcontentid="<?= $sub_content_id; ?>" id="addFeedbackModal">Add Feedback</button>
                            <button class="grade-box-btn" onclick="back()">Back</button>
                        </div>
                    <?php 
                        } else {
                    ?>
                        <div class="alert alert-warning text-center" role="alert">
                            <i>Question <?= $given_interaction; ?></i> is not gradable.
                            <button class="grade-box-btn" onclick="back()">Back</button>
                        </div>
                    <?php
                        }
                    ?>
            </div>
            <?php } else { ?>
            <div class="col col-md-4">
                <div class="alert alert-warning text-center" role="alert">
                    <i>Question <?= $given_interaction; ?></i>
                    <button class="grade-box-btn" onclick="back()">Back</button>
                </div>
            </div>
            <?php } ?>
        </div>

    </div>
</div>