<?php
$interaction_num = 1;
foreach ($chunk_interactions as $page_key => $foun_interactions) {
?>
<div class="carousel-item <?php echo $page_key == 0 ? ' active' : '' ?>">
    <div class="slider_cards_flex">
    <?php
    $last_key = count($foun_interactions) - 1;
    foreach ($foun_interactions as $slide_key => $single_interaction) {

        $interaction_name = $single_interaction['action']['metadata']['contentType'];
        $interaction_time = convetToInterectiveVideoTimeFormate($single_interaction['duration']['from'], 1);
        ?>
        <div class="student_grade_card <?php echo $slide_key == $last_key ? 'no-right-border' : '' ?>">
            <span class="student_slide green_slide ">Question <?php echo $interaction_num.'&nbsp;('.$interaction_time[0].')';  ?></span>
            <?php
                $sub_content_id_array = [];
                if ($interaction_name == 'Statements' || $interaction_name ==  'Summary') {
                    $sub_content_id_array = array_column( $single_interaction['action']['params']['summaries'], 'subContentId' );
                } elseif ($interaction_name == 'Single Choice Set') {
                    $sub_content_id_array = array_column( $single_interaction['action']['params']['choices'], 'subContentId');
                } else {
                    $sub_content_id_array[] = $single_interaction['action']['subContentId'];
                }
                $scores = array_map(function($interaction_sub_content_id) use ($interaction_xapis) {
                    $filtered = array_filter($interaction_xapis, function($statement) use ($interaction_sub_content_id) {
                        $xapi_sub_content_id = $statement['object']['definition']['extensions']['http://h5p.org/x-api/h5p-subContentId'];
                        return ($interaction_sub_content_id == $xapi_sub_content_id && $statement['verb']['display']['en-US'] == 'answered');
                    });
                    if ($filtered) {
                        $xapi = reset($filtered);
                        return [
                            'score' => $xapi['result']['score'] ?? ''
                        ];
                    }
                    
                    return [ 'score' => '' ];
                }, $sub_content_id_array);
            ?>
            <div class="truncate"><?php echo $single_interaction['action']['metadata']['title']; ?></div>
            <?php
                if ( isset($scores[0]['score']['max']) ) {
                    $score_raw = array_sum(array_column($scores[0], 'raw'));
                    $score_max = array_sum(array_column($scores[0], 'max'));
                    $graded_type = ($interaction_name == 'Free Text Question') ? 'manual_grade' : 'Auto-graded';
                    if ($graded_type == 'manual_grade') {
                        $sub_content_id = $single_interaction['action']['subContentId'];
                        $score_raw = get_post_meta($assignment_submission['ID'], "slide_{$sub_content_id}_grade", true);
                    }
                ?>
                <h2 class="gray_grade">
                <?php
                    echo ($graded_type == 'manual_grade' && $score_raw == '')  ?'Not Graded' : $score_raw.'/'.$score_max;
                ?>
                </h2>
                <br />
                <?php
                    $temp_interaction_num = $interaction_num - 1;
                    $type_and_sci = (isset($_GET['type']) && isset($_GET['subContentId'])) ? "&type=".$_GET['type']."&subContentId=".$_GET['subContentId'] : '';
                    $interactionUrl = $type_and_sci."&action=grade&ivInteraction=".$temp_interaction_num."&ivInteractionTime=".$interaction_time[1];
                    $interactionUrl .= isset($_GET['secondSubContentId']) ? "&slide=".$_GET['slide']."&question=".$_GET['question']."&secondSubContentId=".$_GET['secondSubContentId'] : '';
                    if ($graded_type == 'manual_grade') {
                ?>
                        <a href="#" onclick="viewFeedback('<?php echo $sub_content_id; ?>')">
                            <img width="30" src="<?php echo $treks_src . "/assets/img/feedback-icon.png"; ?>" />
                        </a>
                        <button class="grade_btn" onclick="video_grade('<?php echo $interactionUrl ?>')">Grade</button>
                <?php
                    } else {
                ?>      
                        <a href="#" onclick="video_grade('<?php echo $interactionUrl ?>')"><span class="badge bg-secondary" style="margin-bottom:18px;"><?php echo $graded_type; ?></span></a>
                        <br />
                        <img src="<?php echo $treks_src; ?>/assets/img/check-g.svg" alt="" class="check-g" style="margin-top: 25px;" />
                <?php
                    }
                ?>
                <?php
                } else {
                ?>
                    <h2 class="gray_grade">Not Attempted</h2><br />
                <?php
                    // echo $inter_SCI;
                }
                $interaction_num++;
            ?>
        </div>
        <?php
    }
?>
    </div>
</div>
<?php
}