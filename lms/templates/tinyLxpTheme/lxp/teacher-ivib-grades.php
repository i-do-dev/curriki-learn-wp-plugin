<?php
global $treks_src;

$assignment_id = intval( $_GET['assignment'] );

$student_id = 0;
if ( (isset($_GET['student']) && intval($_GET['student']) > 0) ) {
  $student_id = intval($_GET['student']);
}

$assignment = lxp_get_assignment($assignment_id);
$assignment_submission = lxp_get_assignment_submissions($assignment->ID, $student_id);
$mark_as_graded = null;
if ($submit_status) {
    $mark_as_graded = $assignment_submission ? get_post_meta($assignment_submission['ID'], 'mark_as_graded', true) : null;
    $local_user_id = get_post_meta($student_id, 'lxp_student_admin_id', true);
    $activity_style = 'Interactive Video';
}
$merged_url = isset($_GET['type']) && isset($_GET['subContentId']) ? '&type='.$_GET['type'].'&subContentId='.$_GET['subContentId'] : '';
// url added in case of interactive vide added in course slide presentation activity
$merged_url .= isset($_GET['slide']) && isset($_GET['question']) && isset($_GET['secondSubContentId']) ? '&slide='.$_GET['slide'].'&question='.$_GET['question'].'&secondSubContentId='.$_GET['secondSubContentId'] : '';
?>

<style type="text/css">
    .student_grade_card {
        cursor: pointer;
    }
    table {
        border-collapse: collapse;
        width: 100%;
    }
    /* td {
        background-color: #abc;
        border-radius: 10px;
        border-bottom: 2px solid #d4dfe3;
    } */
    /* Item styling - level 1 */
    .scrol-slide {
      margin: 10px;
      padding: 10px;
      /* border: 1px solid #ccc; */
      display: inline-block;
      width: 200px;
      height: 300px; /* fixed height */
      /* overflow-y: scroll; 
      overflow-x: scroll; 
      vertical-align: top; */
      overflow-y: auto;
      overflow-x: auto; /* or scroll */
      white-space: nowrap; /* if inline or text content */
    }
    .item {
        padding: 8px;
        margin: 5px 0;
        background: #e0e0e0;
        color: #545454;
        position: relative;
        border-radius: 20px;
        cursor: pointer;
    }
    
    /* Info box - level 2 (appears on item hover) */
    .info-box {
        display: none;
        position: relative;
        left: 20%; /* Position outside parent */
        top: 0;
        width: 200px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        z-index: 100; /* Ensure it appears above everything */
    }
    
    /* Info item styling */
    .info-item {
        padding: 5px;
        margin: 3px 0;
        background: #f0f0f0;
        position: relative;
    }
    
    /* Sub-info box - level 3 (appears on info-item hover) */
    .sub-info-box {
        display: none;
        position: absolute;
        left: 20%;
        top: 0;
        background: white;
        border: 1px solid #ccc;
        padding: 10px;
        width: 180px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    /* Sub-info item styling */
    .sub-info-item {
        padding: 4px;
        margin: 2px 0;
        background: #f8f8f8;
    }
    
    .first-modal {
        --bs-modal-zindex: 1051;
        --bs-modal-width: 500px;
        --bs-modal-padding: 1rem;
        --bs-modal-margin: 0.5rem;
        --bs-modal-color: ;
        --bs-modal-bg: #fff;
        --bs-modal-border-color: var(--bs-border-color-translucent);
        --bs-modal-border-width: 1px;
        --bs-modal-border-radius: 0.5rem;
        --bs-modal-box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        --bs-modal-inner-border-radius: calc(0.5rem - 1px);
        --bs-modal-header-padding-x: 1rem;
        --bs-modal-header-padding-y: 1rem;
        --bs-modal-header-padding: 1rem 1rem;
        --bs-modal-header-border-color: var(--bs-border-color);
        --bs-modal-header-border-width: 1px;
        --bs-modal-title-line-height: 1.5;
        --bs-modal-footer-gap: 0.5rem;
        --bs-modal-footer-bg: ;
        --bs-modal-footer-border-color: var(--bs-border-color);
        --bs-modal-footer-border-width: 1px;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1051;
        display: none;
        width: 100%;
        height: 100%;
        overflow-x: hidden;
        overflow-y: auto;
        outline: 0;
    }
    
    .modal-breadcrumb {
        font-size: 15px;
        color: #939393;
    }

    .modal-breadcrumb h2 {
        position: relative;
        padding-bottom: 10px; /* adds space between text and line */
    }

    .modal-breadcrumb h2::after {
        content: '';
        display: block;
        width: 1138px;
        height: 1px;
        background-color: #ccc; /* change color as needed */
        position: absolute;
        bottom: 0;
        left: -16px;
    }
    
    .modal-item {
        width: 25%;
        padding: 15px;
        box-sizing: border-box;
        text-align: center;
        min-height: 400px;
        border-right: 1px solid #eaedf1;
        cursor: pointer;
    }

    .modal-item img {
        width: 10%;
        margin-bottom: 10px;
    }

    .modal-header .btn-close {
        position: absolute;
        right: 1rem; /* Fixed distance from the right */
        top: 1rem;   /* Align vertically */
        margin: 0;   /* Override Bootstrap's default margin */
    }

    .modal-footer {
        display: flex;
        justify-content: center;
        padding: calc(var(--bs-modal-padding) - var(--bs-modal-footer-gap) * .5);
        border-top: var(--bs-modal-footer-border-width) solid var(--bs-modal-footer-border-color);
    }

    .carousel-item {
        position: relative;
        display: none;
        float: left;
        width: 100%;
        height: 450px;
        margin-right: -100%;
        -webkit-backface-visibility: hidden;
        backface-visibility: hidden;
        transition: transform .8s ease-in-out;
    }
    .carousel-item.active, 
    .carousel-item-next, 
    .carousel-item-prev {
        display: flex;
    }
    .popup-content {
        background-color: white;
        padding: 20px;
        border-radius: 5px;
        width: 80%;
        height: 80%;
        overflow: auto;
        position: relative;
    }
    .popup-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: none;
        justify-content: center;
        align-items: center;
    }
    .second-level-close {
        width: 5em;
        padding: .25em .25em;
        border: 0;
    }
    /* Second popup styling - appears on top of first */
    .popup-overlay.second-level {
        z-index: 1051;
        background-color: rgba(0, 0, 0, 0.3);
    }
    .carousel-control-prev, 
    .carousel-control-next {
        width: 5%;
        opacity: 1;
    }
    
    .carousel-modal-next-icon {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23fff'%3e%3cpath d='M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
    }
    .carousel-modal-prev-icon {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23fff'%3e%3cpath d='M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z'/%3e%3c/svg%3e");
    }

    .carousel-modal-prev-icon,
    .carousel-modal-next-icon {
        background-color: #000;
        background-repeat: no-repeat;
        border-radius: 50%;
        width: 2rem;
        height: 2rem;
    }
    
    .carousel-control-prev {
        left: 2px;
    }
        
    .carousel-control-next {
        right: 2px;
    }

    .student_slide{
        display: flex;
        justify-content: center;
        padding: 8px 24px;
        margin-bottom: 8px;
        border-radius: 20px;
    }
    .sub-items-container {
        height: 356px;
        overflow-y: auto;
    }

    .grade_btn{
        display: flex;
        padding: 8px 40px;
        font-family: 'Nunito';
        margin: 0 auto;
        color: #0b5d7a;
        background-color: transparent;
        border: 1px solid #0b5d7a;
        border-radius: 8px;
    }
    
    #loader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }

    .loader-spinner {
        border: 5px solid #f3f3f3;
        border-top: 5px solid #3498db;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 1s linear infinite;
    }

    .btn-outline-secondary {
        margin-bottom: 5px;
    }

</style>

<div class="tab-content" id="myTabContent">
    <!-- Teachers Table -->
     
    <h1 class="stu_heading">Submissions</h1>
    <?php if ($submit_status):  ?>
        <div class="row justify-content-end">
            <div class="col-md-3">
                <div class="row justify-content-end">
                    <div class="col-md-12">
                        <!-- View Summary Button -->
                        <a href="<?= site_url('grade-assignment/?assignment=' . $_GET['assignment'] . '&student='. $_GET['student'] . '&action=summary'.$merged_url); ?>" rel="permalink" class="primary-btn lx-space summary_link">View Summary</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="row justify-content-start">
                    <div class="col-md-10">
                        <div class="btn btn-info" role="alert">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="markGraded" <?= $mark_as_graded === 'true' ? 'checked' : ''; ?> />
                                <label class="form-check-label" for="markGraded"><strong>Mark Graded</strong></label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <div
        class="tab-pane fade show active"
        id="one-tab-pane"
        role="tabpanel"
        aria-labelledby="one-tab"
        tabindex="0"
    >
        <div id="carouselExampleControlsNoTouching" class="carousel slide" data-bs-touch="false" data-bs-interval="false">
            <div class="carousel-inner" style="height: 350px;">
            <?php
                if ($submit_status && count($all_interactions) > 0) {
                    $chunk_interactions = array_chunk($all_interactions['data'], 4);
                    // check both conditions 
                    if ($all_interactions['type'] == 'interactive_video') {
                        include $livePath.'/lxp/interactive-video-grade.php';
                        // if ($activity_style == 'Course Presentation') {
                        //     include $livePath.'/lxp/course-presentation-grade.php';
                        // } elseif ($activity_style == 'Interactive Video') {
                        //     include $livePath.'/lxp/interactive-video-grade.php';
                        // } elseif ($activity_style == 'Question Set') {
                        //     include $livePath.'/lxp/question-set-grade.php';
                        // }
                    } else {
                        $chapter_num = 0;
                        foreach ($chunk_interactions as $page_key => $foun_interactions) {
                        ?>
                        <div class="carousel-item <?= $page_key == 0 ? ' active' : '' ?>">
                            <div class="slider_cards_flex">
                                <?php
                                $last_key = count($foun_interactions) - 1;
                                foreach ($foun_interactions as $slide_key => $single_chapter) {
                                    ?>
                                    <div data-bs-toggle="modal" data-bs-target="#carouselModal" onclick="getLevel1PopupInteractions('<?= $chapter_num; ?>')" class="student_grade_card <?= $slide_key == $last_key ? 'no-right-border' : '' ?>">
                                        
                                        <span class="student_slide green_slide ">Chapter <?= $chapter_num+1; ?></span>
                                        <span class="student_slide gray_slide "><?= $single_chapter['chapter']['metadata']['title'] ?></span>
                                        <?php
                                            $num_count = 0; $progressed = 0;
                                            foreach($single_chapter['chapter']['params']['content'] as $single_chapter) {
                                                $content_type = $single_chapter['content']['metadata']['contentType'];
                                                $sub_content_id = '0';
                                                if (in_array($content_type, Allowed_Activity_types)) {
                                                    $num_count++;
                                                    $sub_content_id = $single_chapter['content']['subContentId'];
                                                }
                                                $exists = !empty(array_filter($interaction_xapis, function($statement) use ($sub_content_id) {
                                                    $xapi_sub_content_id = $statement['object']['definition']['extensions']['http://h5p.org/x-api/h5p-subContentId'];
                                                    // echo $sub_content_id .'=='. $xapi_sub_content_id.'<br />';
                                                    return $sub_content_id == $xapi_sub_content_id;
                                                }));

                                                // echo(($sub_content_id));
                                                $progressed = $exists ? ++$progressed : $progressed;
                                            }
                                        ?>
                                        <br />
                                        <h6 class="truncate" >
                                            <?= $num_count > 0 ? $progressed.' of '.$num_count.' interactions completed' : 'No interactions'; ?>
                                        </h6>
                                    </div>
                                    <?php
                                    $chapter_num++;
                                } ?>
                            </div>
                        </div>
                    <?php }
                        
                    }
                } else { ?>
                    <div class="student_grade_card no-right-border">
                        <span class="student_slide green_slide ">Assignment</span>
                        <h2 class="gray_grade"><?= isset($submit_status) ? 'Not Attempted' : 'No interactions found'; ?></h2>
                    </div>
                <?php
                }
                ?>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleControlsNoTouching" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true">
                    <img src="<?= $treks_src; ?>/assets/img/slide-icon.svg" alt="">
                </span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleControlsNoTouching" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true">
                    <img src="<?= $treks_src; ?>/assets/img/slide-icon.svg" alt="">
                </span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="first-modal fade" id="carouselModal" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="modalBreadCrumb"></h5>
            <button type="button" class="btn-close modalclose" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body main-modal">
        </div>
        <div class="modal-footer">
        </div>
    </div>
  </div>
</div>

<script>
    // level_3 = slide number and lever_4 = quesition number
    function getLevel1PopupInteractions(chapter_num, ch_ques_num='', activity_type='IB', level_3='', level_4='', level_5='', c_error='0') {
        $('#loader').show();

        const all_interactions_data = <?= json_encode($all_interactions['data']); ?>;
        let items = '';
        let modal_heading = '';
        let forth_polygon = '';
        let sub_content_id = '';
        let parent_activity_type = activity_type;
        // let polygon_width = '';
        let polygon_width = (activity_type == 'IB' || activity_type == 'CP' || activity_type == 'IV') ? '50px' : '40px';

        if (activity_type == 'IB') {

            items = all_interactions_data[chapter_num]['chapter']['params']['content'];
            
            if (level_5 === '') {
                
                modal_heading = `<span class="green_slide">Chapter `+(parseInt(chapter_num)+1)+`: </span>`+all_interactions_data[chapter_num]['chapter']['metadata']['title'];                

            } else {
                modal_heading = items[level_5]['content']['metadata']['title'];

                sub_content_id = items[level_5]['content']['subContentId'];
                
                forth_polygon = polygon_html(50)+'CH '+(parseInt(level_5)+1)+'</h6></div>'+all_interactions_data[chapter_num]['chapter']['metadata']['title'];
            }
        } else if (activity_type == 'CP') {

            items = all_interactions_data[chapter_num]['chapter']['params']['content'][ch_ques_num]['content']['params']['presentation']['slides'];
            
            if (level_5 === '') {

                forth_polygon = polygon_html(50)+'CH '+(parseInt(chapter_num)+1)+'</h6></div>'+all_interactions_data[chapter_num]['chapter']['metadata']['title'];
                    
                modal_heading = `<span class="green_slide">CP: </span>`+all_interactions_data[chapter_num]['chapter']['params']['content'][ch_ques_num]['content']['metadata']['title'];
                
                parent_activity_type = 'IB';
            } else {
                
                cp_bcm_heading = all_interactions_data[chapter_num]['chapter']['params']['content'][ch_ques_num]['content']['metadata']['title'];

                forth_polygon = polygon_html(50)+'CH '+(parseInt(chapter_num)+1)+'</h6></div>'+all_interactions_data[chapter_num]['chapter']['metadata']['title']+polygon_html()+'CP</h6></div><span class="green_slide">Slide '+(parseInt(level_5)+1)+' : </span>'+cp_bcm_heading;

                modal_heading = items[level_5]['elements'][0]['action']['metadata']['title'];

                sub_content_id = items[level_5]['elements'][0]['action']['subContentId'];

            }
        } else if (activity_type == 'IV') {
            items = all_interactions_data[chapter_num]['chapter']['params']['content'][ch_ques_num]['content'];

            if (level_3 === '') { // means iv is on Chapter
                    
                if (level_5 === '') {
                    modal_heading = '<span class="green_slide"> IV :</span>'+items['metadata']['title']; // do not change the place of this code
                    
                    items = items['params']['interactiveVideo'];
                    
                    forth_polygon = polygon_html(50)+'CH '+(parseInt(chapter_num)+1)+'</h6></div>'+all_interactions_data[chapter_num]['chapter']['metadata']['title'];

                    parent_activity_type = 'IB';
                } else {
                    modal_heading = items['metadata']['title']; // do not change the place of this code
                    
                    items = items['params']['interactiveVideo'];

                    forth_polygon = polygon_html(50)+'CH '+(parseInt(chapter_num)+1)+'</h6></div>'+all_interactions_data[chapter_num]['chapter']['metadata']['title']+polygon_html()+'IV</h6></div>'+modal_heading;

                    for (const iv_interaction of items['assets']['interactions']) {
                        modal_heading = (iv_interaction['action']['subContentId'] == level_5) ? iv_interaction['action']['metadata']['title'] : modal_heading;

                        sub_content_id = (iv_interaction['action']['subContentId'] == level_5) ? level_5 : sub_content_id;
                    }
                    
                    parent_activity_type = 'IV';
                }
            } else { // means iv is on Course Presentation
                
                cp_bcm_heading = items['metadata']['title'];

                modal_heading = items['params']['presentation']['slides'][level_3]['elements'][level_4]['action']['metadata']['title'];

                items = items['params']['presentation']['slides'][level_3]['elements'][level_4]['action']['params']['interactiveVideo'];

                if (level_5 === '') {
                    // (Slide #'+(parseInt(level_3)+1)+')
                    modal_heading = `<span class="green_slide">(IV): </span>`+modal_heading;

                    forth_polygon = polygon_html(50)+'CH '+(parseInt(chapter_num)+1)+'</h6></div>'+all_interactions_data[chapter_num]['chapter']['metadata']['title']+polygon_html()+'CP</h6></div> <span class="green_slide">Slide '+(parseInt(level_3)+1)+' : </span>'+cp_bcm_heading;

                    parent_activity_type = 'CP';
                } else {
                    forth_polygon = polygon_html(50)+'CH '+(parseInt(chapter_num)+1)+'</h6></div>'+all_interactions_data[chapter_num]['chapter']['metadata']['title']+polygon_html()+'CP</h6></div> <span class="green_slide">Slide '+(parseInt(level_3)+1)+' : </span>'+cp_bcm_heading+polygon_html()+'IV</h6></div>'+modal_heading;
                    for (const iv_interaction of items['assets']['interactions']) {
                        modal_heading = (iv_interaction['action']['subContentId'] == level_5) ? iv_interaction['action']['metadata']['title'] : modal_heading;
                        sub_content_id = (iv_interaction['action']['subContentId'] == level_5) ? level_5 : sub_content_id;
                    }
                    parent_activity_type = 'IV';
                }
            }
        } else { // else part for Activity of Question Set 
            items = all_interactions_data[chapter_num]['chapter']['params']['content'][ch_ques_num]['content'];

            if (level_5 === '') {
                modal_heading = '<span class="green_slide"> QS :</span>'+items['metadata']['title']; // do not change the place of this code

                items = items['params']['questions'];

                forth_polygon = polygon_html(50)+'CH '+(parseInt(chapter_num)+1)+'</h6></div>'+all_interactions_data[chapter_num]['chapter']['metadata']['title'];

                parent_activity_type = 'IB';
            } else {
                modal_heading = items['metadata']['title']; // do not change the place of this code

                items = items['params']['questions'];
                
                forth_polygon = polygon_html(50)+'CH '+(parseInt(chapter_num)+1)+'</h6></div>'+all_interactions_data[chapter_num]['chapter']['metadata']['title']+polygon_html()+'QS</h6></div>'+modal_heading;

                sub_content_id = items[level_5]['subContentId'];

                modal_heading = items[level_5]['metadata']['title'];

                parent_activity_type = 'QS';
            }
        }
        
        backBtn = (activity_type == 'IB' && level_5 === '') ? '' : '<button type="button" class="btn btn-outline-secondary" onClick="getLevel1PopupInteractions('+chapter_num+', \'' + ch_ques_num + '\', \'' + parent_activity_type + '\', \'' + level_3 + '\', \'' + level_4 + '\')"><i class="bi bi-arrow-return-left"></i> Back</button> <br />';
        
        $("#modalBreadCrumb").html(`<span class="modal-breadcrumb"><h2>Grading - <?= $course->post_title ?></h2>  <?= get_the_post_thumbnail($course->ID, array(35,35), array( 'class' => 'rounded')).' '.$course->post_title ?> `+polygon_html()+`S</h6></div> <?= $section_name ?> `+polygon_html()+`L</h6></div> <?= $lxp_lesson_post->post_title ?>`+forth_polygon+` </span> `);
        $("#modalBreadCrumb").css('color', 'black');
        $('.main-modal').html(modal_heading+'<div class="carousel slide" id="multiItemCarousel" data-bs-interval="false"></div>');
        $('.modal-footer').html(backBtn);

        if (level_5 === '' && c_error === '0') {
            let lti_user_id = "<?= $assignment_submission['lti_user_id']; ?>";
            let h5p_content_id = "<?= $assignment_submission['h5p_content_id']; ?>";
            let assignment_submission_id = "<?= $assignment_submission['ID']; ?>";
            let apiUrl = window.location.origin + '/wp-json/lms/v1/';
            
            $.ajax({
                url: apiUrl + 'assignment/interactions',
                type: "POST",
                data: JSON.stringify({
                    "assignment_submission_id": assignment_submission_id,
                    "lti_user_id": lti_user_id,
                    "h5p_content_id": h5p_content_id,
                    "interaction_type": activity_type,
                    "interactions_data" : items
                }),
                success: function (response) {
                    // Initialize carousel
                    if (activity_type == 'CP') {
                        slideCarousel(response.data, chapter_num, ch_ques_num, activity_type);
                    } else {
                        interactionsPopupHtml(response.data, chapter_num, ch_ques_num, activity_type, level_3, level_4);
                    }
                },
                error: function (error) {
                    getLevel1PopupInteractions(chapter_num, ch_ques_num, activity_type, level_3, level_4, level_5, '1');
                },
                complete: function() {
                    // Hide loader when request is complete (success or error)
                    $('#loader').hide();
                }
            });
        } else if (c_error === '1') {
            let carousel = $('#multiItemCarousel');
            let carouselInnerDiv = $('<div class="carousel-inner"></div>');
            carouselInnerDiv.append('<div class="carousel-item active" style="color:red;font-size: 30px;">Failed to load data</div>');
            carousel.append(carouselInnerDiv);
        } else {
            showSingleGradePopup(sub_content_id);
        }

        function polygon_html(polygon_width = 40) {
            return '<img src="<?= $treks_src; ?>/assets/img/bc_arrow_right.svg" alt="user" class="students-breadcrumb-arrow" /> <div class"modal-tab-polygon"="" style=" width: '+polygon_width+'px; height: 32px; clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%); display: inline-flex; align-items: center; justify-content: center; color: #ffffff; background-color: #1fa5d4;"><h6>';
        }
    }

    function interactionsPopupHtml (items, chapter_num, ch_ques_num, type, level_3, level_4) {
        
        const carouselInner = $('#multiItemCarousel');
        const carouselDiv = $('<div class="carousel-inner"></div>');
        // carouselInner.empty();
        // Calculate number of slides needed (4 items per slide)
        const itemsPerSlide = 4;
        const totalSlides = Math.ceil(items.length / itemsPerSlide);
        let back_address = [];
        let k = '';
        
        for (let i = 0; i < totalSlides; i++) {
            const slide = $('<div class="carousel-item"></div>');
            if (i === 0) slide.addClass('active');
            
            // Add items to this slide
            const startIndex = i * itemsPerSlide;
            const endIndex = Math.min(startIndex + itemsPerSlide, items.length);

            for (let j = startIndex; j < endIndex; j++) {

                const item = items[j];
                const itemContainer = $('<div class="modal-item"></div>');
                if (endIndex == (j+1)) itemContainer.addClass('no-right-border');
                // Add main item title
                itemContainer.append(`<span class="student_slide green_slide ">`+item['title']+`</span>`);
                if (item['auto_grade'] == '1') {
                    if (item['attempted'][0]['score']) {

                        itemContainer.append('<h2 class="gray_grade">'+item['score']+'</h2>');

                        itemContainer.append(`<a class="feedback-btn" onclick="showGradePopup(\'`+item['sub_content_id']+`\','1')"><img width="30" src="<?= $treks_src . "/assets/img/feedback-icon.png"; ?>"></a>`);
                        k = (type == 'IV') ? item['sub_content_id'] : j;
                        itemContainer.append('<button class="grade_btn" onclick="getLevel1PopupInteractions(\'' +chapter_num+ '\',\'' +ch_ques_num+ '\',\'' +type+ '\',\'' +level_3+ '\',\'' +level_4+ '\',\'' +k+ '\')">Grade</button>');

                    } else {
                        itemContainer.append('<h2 class="gray_grade">Not Attempted</h2>');
                    }
                } else if ( item['interaction_type'] == 'Course Presentation' || item['interaction_type'] == 'Interactive Video' || item['interaction_type'] == 'Question Set') {
                    if (item['interaction_type'] == 'Course Presentation') {
                        itemContainer.append('<h2 class="gray_grade">'+item['score']+'</h2><button class="grade_btn" onclick="getLevel1PopupInteractions('+chapter_num+','+j+',\'CP\')">Grade</button>');
                    } else if (item['interaction_type'] == 'Interactive Video') {
                        itemContainer.append('<h2 class="gray_grade">'+item['score']+'</h2><button class="grade_btn" onclick="getLevel1PopupInteractions('+chapter_num+','+j+',\'IV\')">Grade</button>');
                    } else {
                        itemContainer.append('<h2 class="gray_grade">'+item['score']+'</h2><button class="grade_btn" onclick="getLevel1PopupInteractions('+chapter_num+','+j+',\'QS\')">Grade</button>');
                    }
                } else {
                    itemContainer.append('<h2 class="gray_grade">'+item['score']+'</h2>');

                    item['attempted'][0]['score'] ? itemContainer.append('<span class="badge bg-secondary" style="margin-bottom:18px;">Auto-graded</span><br /><img src="<?= $treks_src; ?>/assets/img/check-g.svg" alt="" class="check-g" />') : '';
                }
                slide.append(itemContainer);
            }
            const leftRightArrow = $(`<button class="carousel-control-prev" type="button" data-bs-target="#multiItemCarousel" data-bs-slide="prev"><span class="carousel-modal-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous</span> </button> <button class="carousel-control-next" type="button" data-bs-target="#multiItemCarousel" data-bs-slide="next"> <span class="carousel-modal-next-icon" aria-hidden="true"></span> <span class="visually-hidden">Next</span>  </button>`);
            
            carouselDiv.append(slide);
            carouselInner.append(carouselDiv);
            carouselInner.append(leftRightArrow);
        }
    }

    function slideCarousel(items, chapter_num, ch_ques_num, interaction_type) {

        const carouselInner = $('#multiItemCarousel');
        const carouselDiv = $('<div class="carousel-inner"></div>');        
        carouselInner.empty();
        // Calculate number of slides needed (4 items per slide)
        const itemsPerSlide = 4;
        const totalSlides = Math.ceil(items.length / itemsPerSlide);
        // Create slides
        for (let i = 0; i < totalSlides; i++) {
            const slide = $('<div class="carousel-item"></div>');
            if (i === 0) slide.addClass('active');
            // Add items to this slide
            const startIndex = i * itemsPerSlide;
            const endIndex = Math.min(startIndex + itemsPerSlide, items.length);
            for (let j = startIndex; j < endIndex; j++) {
                const item = items[j];
                // Create container for the main item
                const itemContainer = $('<div class="modal-item"></div>');
                if (endIndex == (j+1)) itemContainer.addClass('no-right-border');
                // Add main item title
                itemContainer.append(`<span class="student_slide green_slide ">Slide `+(parseInt(item.slide_num)+1)+`</span>`);

                const sub_content_id = item.sub_content_id;
                const gradeText = (item.interactive_video ? '<button class="grade_btn" onclick="getLevel1PopupInteractions(\'' + chapter_num + '\',\'' + ch_ques_num + '\',\'IV\',\'' + item.slide_num + '\',\'' + item.slide_question_num + '\')">Grade</button> ' : (item.attempted[0] === '' || item.auto_grade == '0' ? '' : '<span class="badge bg-secondary" style="margin-bottom:18px;">Auto-graded</span><br /><img src="<?= $treks_src; ?>/assets/img/check-g.svg" alt="" class="check-g" />'));

                const feedbackicon = item.auto_grade ? '' : '<a class="feedback-btn" onclick="viewFeedback(\'' + sub_content_id + '\')"> <img width="30" src="<?php echo $treks_src . "/assets/img/feedback-icon.png"; ?>" /> </a>';

                const gradeCondition = item.auto_grade ? '' : '<button class="grade_btn" onclick="getLevel1PopupInteractions(\'' + chapter_num + '\',\'' + ch_ques_num + '\',\'' +interaction_type+ '\',\'\',\'\',\'' +item.slide_num+ '\')">Grade</button>';
                // Add sub-items
                const subItemsContainer = $('<div class="sub-items-container"></div>');
                const subQuest = $(`
                        <div class="sub-item">
                            <p> ${item.title}</p>
                            <h2 class="gray_grade">${item.score}</h2>
                            ${gradeText}
                            ${feedbackicon}
                            ${gradeCondition}
                        </div>
                    `);
                subItemsContainer.append(subQuest);
                itemContainer.append(subItemsContainer);
                slide.append(itemContainer);
            }
            const leftRightArrow = $(`<button class="carousel-control-prev" type="button" data-bs-target="#multiItemCarousel" data-bs-slide="prev"><span class="carousel-modal-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous</span> </button> <button class="carousel-control-next" type="button" data-bs-target="#multiItemCarousel" data-bs-slide="next"> <span class="carousel-modal-next-icon" aria-hidden="true"></span> <span class="visually-hidden">Next</span>  </button>`);
            
            carouselDiv.append(slide);
            carouselInner.append(carouselDiv);
            carouselInner.append(leftRightArrow);
        }
    }
    
    function showSingleGradePopup(sub_content_id) {
        
        const carouselInner = $('#multiItemCarousel');
        carouselInner.html('');

        const rowhtml = $('<div class="row"></div>');
        const div1 = $(`
            <div class="col col-md-8">
                <iframe style="border: none; width: 100%; height: 395px;" src="<?= site_url() ?>?lti-platform&post=<?= $course_lesson->ID ?>&id=<?= $attrId ?><?= $queryParam ?>" allowfullscreen></iframe>                
            </div>
        `);
        const div2 = $(`
            <div class="col col-md-4">
                <div class="grade-box">
                    <div class="grade-select">
                        <select name="grade" id="grade" class="form-select">
                            <option value="">----</option>
                            <option value="0">0</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                            <option value="6">6</option>
                            <option value="7">7</option>
                        </select>
                        <button class="grade-box-btn" onclick="assign_grade(\`` +sub_content_id+ `\`)" >&nbsp;&nbsp;&nbsp;Grade&nbsp;&nbsp;&nbsp;</button>
                        <br>
                        <button class="add-feedback-btn btn btn-outline-info btn-lg" onclick="showGradePopup(\`` +sub_content_id+ `\`)">Add Feedback</button>
                    </div>
                </div>
            </div>
        `);
        
        rowhtml.append(div1);
        rowhtml.append(div2);
        carouselInner.append(rowhtml);

        // get graded number from db using sub_content_id
        showGradePopup(sub_content_id, '2');
    }

    // it will show two popup on condition
    // 1-> feedback_given_popup, 
    // 2-> feedback_view_popup
    function showGradePopup(sub_content_id, flag=0) {
        $('#loader').show();

        $('#inputFeedback').val('');
        jQuery('input[name="slide"]').val(sub_content_id);
        let apiUrl = window.location.origin + '/wp-json/lms/v1/';
        $.ajax({
            url: apiUrl + "assignment/submission/feedback/view",
            type: 'POST', //  POST for this kind of data
            dataType: 'json', // Expect JSON response
            data: {
                "assignment_submission_id": "<?= $assignment_submission['ID']; ?>",
                "slide": sub_content_id
            },
            success: function(response) {
                if (flag==0) {
                    feedbackModalObj.show();
                    $("#inputFeedback").val(response.data['feedback']);
                } else if (flag==1) {
                    response.data.feedback.length > 0 ? jQuery('#feedback-container').html(response.data.feedback) : jQuery('#feedback-container').html('<p><i>No feedback given.</i></p>');
                    feedbackViewModalObj.show();
                } else {
                    jQuery("#grade").val(response.data['grade_num']);                    
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
            },complete: function() {
            // Hide loader when request is complete (success or error)
                $('#loader').hide();
            }
        });
    }
</script>