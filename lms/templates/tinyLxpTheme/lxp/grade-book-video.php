<?php
$assignment_id = $assignment->ID;
$assignment_submission = $args['assignment_submission'];
$assignment_submission['assignment_submission_id'] = $assignment_submission['ID'];

$mark_as_graded = get_post_meta($assignment_submission['ID'], 'mark_as_graded', true);

$actual_link = $_SERVER['REQUEST_URI'];
$parts = parse_url($actual_link, PHP_URL_QUERY);
// Parse the query string into an associative array
parse_str($parts, $queryParams);

global $post;
?>
<style>
    .progress-card {
        background-color: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .progress-title {
        font-size: 18px;
        color: #333;
        margin-bottom: 15px;
        font-weight: 600;
    }
    
    .progress-container {
        display: flex;
        align-items: center;
        justify-content: space-around;
        width: 100%;
    }
    
    .progress-percent {
        font-size: 24px;
        font-weight: bold;
    }
    
    .progress-details {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }
    
    .progress-count {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 5px;
    }
    
    .progress-label {
        font-size: 14px;
        color: #666;
    }
        
    .grades-modal-body {
        padding: 0;
        height: 450px;
    }

    .grade-modal-content {
        position: relative;
        display: flex;
        flex-direction: column;
        width: 100%;
        height: 500px;
        outline: 0;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
<div class="tab-content" id="myTabContent">
    <div class="container">
        <div class="row">
            <div class="col-sm-3">
                <button type="button" class="btn btn-outline-secondary" onclick="back()"><i class="bi bi-arrow-return-left"></i> Back</button>
            </div>
            <div class="col-md-3 offset-md-6">
                <div class="btn btn-info" role="alert">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="markGraded" <?= $mark_as_graded === 'true' ? 'checked' : ''; ?> />
                        <label class="form-check-label" for="markGraded"><strong>Mark Graded</strong></label>
                    </div>
                </div>
            </div>
        </div>
        
        <?php
        
        if ($all_interactions['type'] == 'interactive_video') {
            // interactive-video Grade-Summary start here
        ?>
            <div class="row">
                <div class="col-md-12">
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 40%;">Question</th>
                                <th scope="col" style="width: 30%;">Feedback</th>
                                <th scope="col" style="width: 10%;">Result</th>
                                <th scope="col" style="width: 10%;">Points/Total</th>
                                <th scope="col" style="width: 10%;">Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                foreach ($all_interactions['data'] as $main_key => $single_iv_question):
                                    $sub_content_id_array = [];

                                    $intrect_type_name = $single_iv_question['action']['metadata']['contentType'];
                                    if ( isset($intrect_type_name) && in_array($intrect_type_name, Allowed_Activity_types) ) {
                                        if ($intrect_type_name == 'Free Text Question') {
                                            $sub_content_id_array[] = $single_iv_question['action']['subContentId'];
                                            $scores_array = getScoresByGivenXapiStatements($sub_content_id_array, $interaction_xapis);
                                            $xapi_res_max = array_sum(array_column($scores, 'max'));

                                            $i_grade = get_post_meta($assignment_submission['ID'], 'slide_'.$sub_content_id_array[0].'_grade', true);
                                            $score_raw = ($scores_array ? ($i_grade == '' ? '0' : $i_grade) : '0');
                                            $score_max = (isset($xapi_res_max) && $xapi_res_max > 0) ? $xapi_res_max : '7';
                                        } else {
                                            if ($intrect_type_name == 'Statements' || $intrect_type_name == 'Summary') {
                                                $sub_content_id_array = array_column( $single_iv_question['action']['params']['summaries'], 'subContentId' );
                                            } elseif ($intrect_type_name == 'Single Choice Set') {
                                                $sub_content_id_array = array_column( $single_iv_question['action']['params']['choices'], 'subContentId');
                                            } else {
                                                $sub_content_id_array[] = $single_iv_question['action']['subContentId'];
                                            }
                                            $scores_array = getScoresByGivenXapiStatements($sub_content_id_array, $interaction_xapis);
                                            $scores = array_column($scores_array, 'score');
                                            $score_raw = array_sum(array_column($scores, 'raw'));
                                            $score_max = array_sum(array_column($scores, 'max'));
                                        }
                                        $percentage = $score_raw == '' || $score_raw == 0 || $score_max == 0 ? '' : ($score_raw / $score_max) * 100;
                                        if ($percentage >= 80) {
                                            $progress_class = "bg-success";
                                            $icon = 'check-lg';
                                        } else {
                                            $progress_class = "bg-danger";
                                            $icon = 'x-lg';
                                        }
                                        ?>
                                        <tr>
                                            <td>
                                                <?= ++$main_key.'. '.$single_iv_question['action']['metadata']['title'] ?>
                                            </td>
                                            <td>
                                                <?php
                                                    $feedback = $assignment_submission ? get_post_meta($assignment_submission['ID'], "slide_" . $sub_content_id_array[0] . "_feedback", true) : "";
                                                    if ($feedback) {
                                                ?>
                                                        <button class="btn btn-link" onclick="viewFeedback('<?= $sub_content_id_array[0]; ?>')"><strong>Yes</strong></button>
                                                <?php } else { ?>
                                                        No
                                                <?php } ?>
                                                
                                            </td>
                                            <td>
                                                <div class="<?= $progress_class; ?> rounded-pill" style="height: 25px; width: 100%;">
                                                    <center><i class="bi bi-<?= $icon; ?> text-white"></i></center>
                                                </div>
                                            </td>
                                            <td>
                                                <?= $score_raw > 0 ? $score_raw. '/' . $score_max : "---"; ?>
                                            </td>
                                            <td>
                                                <?php if($score_raw == '' || $score_max == 0): ?>
                                                    ---
                                                <?php else: ?>
                                                    <div class="progress" style="height: 25px;">
                                                        <div class="progress-bar <?= $progress_class; ?>" role="progressbar" style="width: <?= $percentage; ?>%;" aria-valuenow="<?= $score_raw; ?>" aria-valuemin="0" aria-valuemax="<?= $score_max; ?>">
                                                            <?= round(($score_raw / $score_max) * 100); ?>%
                                                        </div>
                                                    </div>
                                                <?php endif ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                            ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php
            // interactive-Video Grade-Summary end here

            // interactive-Book Grade-Summary start here
        } else {
            $interaction_count = 0; $interaction_seen_count = 0;
            
            $progress_count = 0; $obtain_score = 0; $total_score = 0;
            foreach ($all_interactions['data'] as $single_chapter) {
                $chapter_sub_content_id = $single_chapter['chapter']['subContentId'];
                foreach ($single_chapter['chapter']['params']['content'] as $single_interaction) {
                    if(in_array( $single_interaction['content']['metadata']['contentType'], Allowed_Activity_types )) {
                        $interaction_count++;
                        foreach ($interaction_xapis as $statement) {
                            $sub_content_id = $statement['object']['definition']['extensions']['http://h5p.org/x-api/h5p-subContentId'];
                            if ($sub_content_id == $single_interaction['content']['subContentId']) {
                                $interaction_seen_count++;
                            }
                        }
                    }
                }
                foreach ($interaction_xapis as $statement) {
                    $sub_content_id = $statement['object']['definition']['extensions']['http://h5p.org/x-api/h5p-subContentId'];
                    if ($sub_content_id == $chapter_sub_content_id && $statement['verb']['display']['en-US'] == 'progressed') {
                        $progress_count++;
                    } elseif ($sub_content_id == $chapter_sub_content_id && $statement['verb']['display']['en-US'] == 'completed') {
                        $obtain_score += $statement['result']['score']['raw'];
                        $total_score += $statement['result']['score']['max'];
                    }
                }
            }
            $total_score = $total_score == 0 ? 1 : $total_score;
            $marks_float = $obtain_score/$total_score;
            $interactions_float = $interaction_seen_count/$interaction_count;
            $interaction_percentage = round($interactions_float*100);
            $progress_float = $progress_count/count($all_interactions['data']);
            $book_progress_percentage = round($progress_float*100);
            ?>
            <div class="row">
                <div class="col-md-4 progress-card">
                    <div class="progress-title">Total score</div>
                    <div class="progress-container">
                        <div class="progress-percent"><?= $obtain_score.'/'.$total_score; ?></div>
                        <div class="progress-details">
                        <div class="score-progress"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 progress-card">
                    <div class="progress-title">Book progress</div>
                    <div class="progress-container">
                        <div class="progress-details">
                            <div class="progress-count"><?= $book_progress_percentage; ?>%</div>
                            <div class="progress-label"><?= $progress_count.' of '.count($all_interactions['data']); ?> pages</div>
                        </div>
                        <div class="book-progress"></div>
                    </div>
                </div>
                <div class="col-md-4 progress-card">
                    <div class="progress-title">Interactions progress</div>
                    <div class="progress-container">
                        <div class="progress-details">
                            <div class="progress-count"><?= $interaction_percentage; ?>%</div>
                            <div class="progress-label"><?= $interaction_seen_count.' of '.$interaction_count; ?> interactions</div>
                        </div>
                        <div class="interaction-progress"></div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 40%;">Chapter</th>
                                <th scope="col" style="width: 40%;">Section</th>
                                <th scope="col" style="width: 10%;">Points/Total</th>
                                <th scope="col" style="width: 10%;">Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $num = 1;
                            foreach ($all_interactions['data'] as $main_key => $single_chapter): ?>
                            <tr class="department-row">
                                <td rowspan="<?= count($single_chapter['chapter']['params']['content']) ?>"><?= $num.'. '.htmlspecialchars($single_chapter['chapter']['metadata']['title']) ?>
                                </td>
                                <?php
                                    $firstTeam = true;
                                    foreach ($single_chapter['chapter']['params']['content'] as $sub_key => $interaction_per_chapter):
                                    if (!$firstTeam):
                                ?>
                                <tr class="team-row">
                                <?php endif; ?>
                                    <td>
                                        <?php
                                        $interaction_type = $interaction_per_chapter['content']['metadata']['contentType'];
                                        $sub_content_id = $interaction_per_chapter['content']['subContentId'];
                                        echo htmlspecialchars($interaction_per_chapter['content']['metadata']['title']);

                                        
                                        $raw = 0;
                                        $max = 0;
                                        if ($interaction_type == 'Course Presentation') {
                                            $slides = $interaction_per_chapter['content']['params']['presentation']['slides'];
                                            $result = get_cp_interactions_score($assignment_submission, $slides, $interaction_xapis);
                                            $raw 			  = $result['score_raw'];
                                            $max 			  = $result['score_max'];
                                            $f_score = $raw.'/'.$max;
                                        } elseif ( $interaction_type == 'Interactive Video') {
                                            $iv_data = $interaction_per_chapter['content']['params']['interactiveVideo'];
                                            $result = get_iv_interactions_score($assignment_submission, $iv_data, $interaction_xapis);
                                            $raw 			  = $result['score_raw'];
                                            $max 			  = $result['score_max'];
                                            $f_score = $raw.'/'.$max;
                                        } elseif ( $interaction_type == 'Question Set') { 
                                            $qs_data = $interaction_per_chapter['content']['params']['questions'];
                                            $result = get_question_set_interactions_score($assignment_submission, $qs_data, $interaction_xapis);
                                            $raw 			  = $result['score_raw'];
                                            $max 			  = $result['score_max'];
                                        } else {
                                            $interactioin_sub_content_id = $interaction_per_chapter['content']['subContentId'];
                                            $filtered = array_filter($interaction_xapis, function($xapi) use ($interactioin_sub_content_id) {
                                                $sub_content_id = $xapi['object']['definition']['extensions']['http://h5p.org/x-api/h5p-subContentId'];
                                                return ($interactioin_sub_content_id == $sub_content_id && ( $xapi['verb']['display']['en-US'] == 'completed' || $xapi['verb']['display']['en-US'] == 'answered' ));
                                            });
                                            $xapi_score = reset($filtered);
                                            if ( isset($xapi_score['result']['score']) ) {
                                                $raw = $xapi_score['result']['score']['raw'];
                                                $max = $xapi_score['result']['score']['max'];
                                            }
                                        }
                                        $f_score = $raw > 0 && $max > 0 ? $raw.'/'.$max : '---';
                                        $percentage = $raw > 0 && $max > 0 ? ($raw / $max) * 100 : '0';
                                        $progress_class = $percentage >= 80 ? "bg-success" : 'bg-danger';
                                        $percentage_html = $raw > 0 && $max > 0 ? '<div class="progress" style="height: 25px;"> <div class="progress-bar '.$progress_class.'" role="progressbar" style="width: '.$percentage.'%;" > '.round($percentage).'% </div> </div>' : '---';
                                        ?>
                                    </td>
                                    <td >
                                        <?= $f_score ?>
                                    </td>
                                    <td>
                                        <?= $percentage_html; ?>
                                    </td>
                                    <?php $firstTeam = false; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tr>
                            <?php $num++; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php
        }
        // interactive-Book Grade-Summary end here
        ?>

    </div>
</div>