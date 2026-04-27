<?php
$assignment = $args['assignment'];
// $sub_content_id use in teacher-video-grade-single and use here if isset()
$slide = (isset($_GET['subContentId']) ?  $sub_content_id : ( isset($_GET['slide']) && !isset($_GET['subContentId']) ? $_GET['slide'] : ( isset($_GET['ivInteraction']) ? $_GET['ivInteraction'] : '1' ) ) );
$student = $args['student'];
$assignment_submission_id = $args['assignment_submission_id'];
$feedback = get_post_meta($assignment_submission_id, "slide_{$slide}_feedback", true);
?>

<!-- Modal -->
<div class="modal fade modal-lg" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <div class="modal-header-title">
                    <h2 class="modal-title" id="exampleModalLabel">Grade Feedback</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="row g-3" id="feedbackForm">
                    <input type="hidden" name="assignment" value="<?php echo $assignment; ?>" />
                    <input type="hidden" name="slide" value="<?php echo $slide; ?>" />
                    <input type="hidden" name="student" value="<?php echo $student; ?>" />
                    <input type="hidden" name="assignment_submission_id" value="<?php echo $assignment_submission_id; ?>" />

                    <div class="input_section">
                        <div class="input_box brief_input_box">
                            <div class="label_box brief_label_box">
                                <label class="label">Feedback</label>
                                <textarea class="brief_info form-control feedback_input_box" id="inputFeedback" name="feedback" placeholder="Enter grade feedback"><?php echo $feedback; ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="input_section">
                        <div class="btn_box">
                            <button class="grade-box-btn feedback-btn" id="assignFeedbackBtn"><span class="feedback-action">Save</span></button>
                            <button class="grade-box-btn feedback-btn" type="button" data-bs-dismiss="modal" aria-label="Close">Cancel</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function() {
        let host = window.location.hostname === 'localhost' ? window.location.origin + '<?php echo WORDPRESS_HOST; ?>' : window.location.origin;
        let apiUrl = host + '/wp-json/lms/v1/';

        var feedbackModal = document.getElementById('feedbackModal');
        feedbackModalObj = new bootstrap.Modal(feedbackModal);
        window.feedbackModalObj = feedbackModalObj;

        jQuery("#addFeedbackModal").on('click', function() {
            var subcontentId = $(this).data('subcontentid');
            $("input[name=slide]").val(subcontentId);
            feedbackModalObj.show();
        });
        
        let feedbackForm = jQuery("#feedbackForm");
        jQuery(feedbackForm).on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            
            $.ajax({
                method: "POST",
                enctype: 'multipart/form-data',
                url: apiUrl + "assignment/submission/feedback",
                data: formData,
                processData: false,
                contentType: false,
                cache: false,
            }).done(function( response ) {
                jQuery('#feedbackForm .form-control').removeClass('is-invalid');
                feedbackModalObj.hide();
            }).fail(function (response) {
                console.log('fail');
            });
        
        });

        feedbackModal.addEventListener('hide.bs.modal', function (event) {
            //jQuery('#inputFeedback').val("");
            //jQuery('.feedback-action').text("Add");
            //window.location.reload();
        });
    });
    
</script>