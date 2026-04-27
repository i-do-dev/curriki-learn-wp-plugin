<?php
$assignment_submission = $args['assignment_submission'];
$slides = $args['slides'];
// $slidesData = $slides->data;
$slides = (isset($slides->slides)) ? $slides->slides : false ;

// get assignment submission 'mark_as_graded' post meta
$mark_as_graded = get_post_meta($assignment_submission['ID'], 'mark_as_graded', true);
global $post;
?>
<div class="tab-content" id="myTabContent">
    <div class="container">
        <?php if ($post->post_name === 'grade-assignment') { ?>
            <div class="row">
                <div class="col-md-3">
                    <button type="button" class="btn btn-outline-secondary" onclick="back()"><i class="bi bi-arrow-return-left"></i> Back</button>
                </div>
                <div class="col-md-3 offset-md-6">
                    <div class="btn btn-info" role="alert">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="markGraded" <?php echo $mark_as_graded === 'true' ? 'checked' : ''; ?> />
                            <label class="form-check-label" for="markGraded"><strong>Mark Graded</strong></label>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
        <div class="row">
            <div class="col-md-12">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Slide</th>
                            <th scope="col">Feedback</th>
                            <th scope="col">Result</th>
                            <th scope="col">Points/Total</th>
                            <th scope="col">Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            if ($slides) {
                                foreach ($slides as $slide) { 
                        ?>
                            <tr>
                                <td>Slide <?php echo $slide->slide; ?>: <?php echo $slide->title; ?></td>
                                <td>
                                    <?php
                                        $feedback = $assignment_submission ? get_post_meta($assignment_submission['ID'], "slide_" . $slide->slide . "_feedback", true) : "";
                                        if ($feedback) {
                                    ?>
                                            <button class="btn btn-link" onclick="viewFeedback(<?php echo $slide->slide; ?>)"><strong>Yes</strong></button>
                                    <?php
                                        } else {
                                    ?>
                                            <button class="btn btn-link" onclick="viewFeedback(<?php echo $slide->slide; ?>)">No</button>
                                    <?php
                                        }
                                    ?>
                                    
                                </td>
                                    <?php
                                        if(in_array($slide->type, array('Essay'))) {
                                            $grade = $assignment_submission ? get_post_meta($assignment_submission['ID'], "slide_" . $slide->slide . "_grade", true) : "";
                                            
                                            if ($grade) {
                                                $score = $grade;
                                                $max = $slide->gradedManually && $slide->totalGrades ? $slide->totalGrades : 10;
                                                $percentage = ($score / $max) * 100;
                                                if ($percentage >= 80) {
                                                    $progress_class = "bg-success";
                                                    $icon = 'check-lg';
                                                } else {
                                                    $progress_class = "bg-danger";
                                                    $icon = 'x-lg';
                                                }
                                    ?>
                                                
                                                    <td>
                                                        <div class="<?php echo $progress_class; ?> rounded-pill" style="height: 25px; width: 100%;">
                                                            <center><i class="bi bi-<?php echo $icon; ?> text-white"></i></center>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php
                                                            if(in_array($slide->type, array('Essay'))) {
                                                                $grade = $assignment_submission ? get_post_meta($assignment_submission['ID'], "slide_" . $slide->slide . "_grade", true) : "";
                                                                echo $grade === "" ? "---" : "$grade/$max";
                                                            } else {
                                                                $auto_score = lxp_assignment_submission_auto_score($assignment_submission['ID'], intval($slide->slide));
                                                                $score = $auto_score['score'];
                                                                $max = $auto_score['max'];
                                                                if ($max > 0) {
                                                                    echo $score. '/' . $max;
                                                                } else {
                                                                    echo "---";
                                                                }
                                                            }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <div class="progress" style="height: 25px;">
                                                            <div class="progress-bar <?php echo $progress_class; ?>" role="progressbar" style="width: <?php echo $percentage; ?>%;" aria-valuenow="<?php echo $score; ?>" aria-valuemin="0" aria-valuemax="<?php echo $max; ?>">
                                                                <?php echo round(($score / $max) * 100); ?>%
                                                            </div>
                                                        </div>
                                                    </td>
                                                
                                    <?php        
                                            } else {
                                    ?>
                                            
                                            <td>
                                                <div class="bg-warning rounded-pill" style="height: 25px; width: 100%;">
                                                    <center><i class="bi bi-dash-lg text-white"></i></center>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                    if(in_array($slide->type, array('Essay'))) {
                                                        $grade = $assignment_submission ? get_post_meta($assignment_submission['ID'], "slide_" . $slide->slide . "_grade", true) : "";
                                                        $max = $slide->gradedManually && $slide->totalGrades ? $slide->totalGrades : 10;
                                                        echo $grade === "" ? "---" : "$grade/$max";
                                                    } else {
                                                        $auto_score = lxp_assignment_submission_auto_score($assignment_submission['ID'], intval($slide->slide));
                                                        $score = $auto_score['score'];
                                                        $max = $auto_score['max'];
                                                        if ($max > 0) {
                                                            echo $score. '/' . $max;
                                                        } else {
                                                            echo "---";
                                                        }
                                                    }
                                                ?>
                                            </td>
                                            <td>
                                                To Be Graded
                                            </td>
                                            
                                    <?php
                                            }
                                        } else {
                                            $auto_score = lxp_assignment_submission_auto_score($assignment_submission['ID'], intval($slide->slide));
                                            $score = $auto_score['score'];
                                            $max = $auto_score['max'];
                                            if ($max > 0) {
                                                $percentage = ($score / $max) * 100;
                                                if ($percentage >= 80) {
                                                    $progress_class = "bg-success";
                                                    $icon = 'check-lg';
                                                } else {
                                                    $progress_class = "bg-danger";
                                                    $icon = 'x-lg';
                                                }
                                    ?>
                                                
                                                    <td>
                                                        <div class="<?php echo $progress_class; ?> rounded-pill" style="height: 25px; width: 100%;">
                                                            <center><i class="bi bi-<?php echo $icon; ?> text-white"></i></center>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php
                                                            if(in_array($slide->type, array('Essay'))) {
                                                                $grade = $assignment_submission ? get_post_meta($assignment_submission['ID'], "slide_" . $slide->slide . "_grade", true) : "";
                                                                $max = $slide->gradedManually && $slide->totalGrades ? $slide->totalGrades : 10;
                                                                echo $grade === "" ? "---" : "$grade/$max";
                                                            } else {
                                                                $auto_score = lxp_assignment_submission_auto_score($assignment_submission['ID'], intval($slide->slide));
                                                                $score = $auto_score['score'];
                                                                $max = $auto_score['max'];
                                                                if ($max > 0) {
                                                                    echo $score. '/' . $max;
                                                                } else {
                                                                    echo "---";
                                                                }
                                                            }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <div class="progress" style="height: 25px;">
                                                            <div class="progress-bar <?php echo $progress_class; ?>" role="progressbar" style="width: <?php echo $percentage; ?>%;" aria-valuenow="<?php echo $score; ?>" aria-valuemin="0" aria-valuemax="<?php echo $max; ?>"><?php echo round(($score / $max) * 100); ?>%</div>
                                                        </div>
                                                    </td>
                                                
                                    <?php
                                            } else {
                                    ?>
                                            
                                                <td>
                                                    <div class="bg-secondary rounded-pill" style="height: 25px; width: 100%;">
                                                        <center><i class="bi bi-dash-lg text-white"></i></center>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php
                                                        if(in_array($slide->type, array('Essay'))) {
                                                            $grade = $assignment_submission ? get_post_meta($assignment_submission['ID'], "slide_" . $slide->slide . "_grade", true) : "";
                                                            $max = $slide->gradedManually && $slide->totalGrades ? $slide->totalGrades : 10;
                                                            echo $grade === "" ? "---" : "$grade/$max";
                                                        } else {
                                                            $auto_score = lxp_assignment_submission_auto_score($assignment_submission['ID'], intval($slide->slide));
                                                            $score = $auto_score['score'];
                                                            $max = $auto_score['max'];
                                                            if ($max > 0) {
                                                                echo $score. '/' . $max;
                                                            } else {
                                                                echo "---";
                                                            }
                                                        }
                                                    ?>
                                                </td>
                                                <td>
                                                    Not Auto-graded
                                                </td>
                                            
                                    <?php
                                            }
                                        }
                                    ?>
                            </tr>    
                        <?php } } else { ?>
                            <tr>  <td colspan="5"> No results found</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>