<?php
    $assignment_submission_id = $args['assignment_submission_id'];
?>
<style>
    .feedback-modal {
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
        z-index: var(--bs-modal-zindex);
        display: none;
        width: 100%;
        height: 100%;
        overflow-x: hidden;
        overflow-y: auto;
        outline: 0;
    }
    .modal.show .modal-dialog {
        transform: none;
    }
    .feedback-modal-content {
        position: relative;
        display: flex;
        flex-direction: column;
        width: 100%;
        color: var(--bs-modal-color);
        pointer-events: auto;
        background-color: var(--bs-modal-bg);
        background-clip: padding-box;
        border: var(--bs-modal-border-width) solid var(--bs-modal-border-color);
        border-radius: var(--bs-modal-border-radius);
        outline: 0;
    }
    .modal-header {
        display: flex;
        flex-shrink: 0;
        align-items: center;
        justify-content: space-between;
        padding: var(--bs-modal-header-padding);
        border-bottom: var(--bs-modal-header-border-width) solid var(--bs-modal-header-border-color);
        border-top-left-radius: var(--bs-modal-inner-border-radius);
        border-top-right-radius: var(--bs-modal-inner-border-radius);
    }
    .modal-title {
        margin-bottom: 0;
        line-height: var(--bs-modal-title-line-height);
    }
    .modal-cross {
        box-sizing: content-box;
        width: 1em;
        height: 1em;
        padding: .25em .25em;
        color: #000;
        background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23000'%3e%3cpath d='M.293.293a1 1 0 0 1 1.414 0L8 6.586 14.293.293a1 1 0 1 1 1.414 1.414L9.414 8l6.293 6.293a1 1 0 0 1-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 0 1-1.414-1.414L6.586 8 .293 1.707a1 1 0 0 1 0-1.414z'/%3e%3c/svg%3e") center/1em auto no-repeat;
        border: 0;
        border-radius: .375rem;
        opacity: .5;
    }
</style>
<!-- Modal -->
<div class="feedback-modal fade modal-lg" id="feedbackViewModal" tabindex="-1" aria-labelledby="feedbackViewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="feedback-modal-content">

            <div class="modal-header">
                <div class="modal-header-title">
                    <h2 class="modal-title" id="exampleModalLabel">Grade Feedback</h2>
                </div>
                <button type="button" class="modal-cross viewclose"></button>
            </div>
            <div class="modal-body">
                <form class="row g-3" id="feedbackViewForm">
                    <input type="hidden" name="slide" id="slide" value="0" />
                    <input type="hidden" name="assignment_submission_id" value="<?php echo $assignment_submission_id; ?>" />

                    <div class="input_section">
                        <div class="input_box brief_input_box">
                            <div class="label_box brief_label_box">
                                <div id="feedback-container"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="input_section">
                        <div class="btn_box">
                            <button class="btn btn-outline-secondary viewclose" type="button">Close</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">

    function viewFeedback(slide) {
        jQuery("#feedbackViewForm #slide").val(slide);
        jQuery("#feedbackViewForm").submit();
    }

    jQuery(document).ready(function() {
        let host = window.location.hostname === 'localhost' ? window.location.origin + '<?php echo WORDPRESS_HOST; ?>' : window.location.origin;
        let apiUrl = host + '/wp-json/lms/v1/';

        var feedbackViewModal = document.getElementById('feedbackViewModal');
        feedbackViewModalObj = new bootstrap.Modal(feedbackViewModal);
        window.feedbackViewModalObj = feedbackViewModalObj;
        
        let feedbackViewForm = jQuery("#feedbackViewForm");
        jQuery(feedbackViewForm).on('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(e.target);
            
            $.ajax({
                method: "POST",
                enctype: 'multipart/form-data',
                url: apiUrl + "assignment/submission/feedback/view",
                data: formData,
                processData: false,
                contentType: false,
                cache: false,
            }).done(function( response ) {
                response.data.feedback.length > 0 ? jQuery('#feedback-container').html(response.data.feedback) : jQuery('#feedback-container').html('<p><i>No feedback given.</i></p>');
                feedbackViewModalObj.show();
            }).fail(function (response) {
                console.log('fail');
            });
        
        });

        feedbackViewModal.addEventListener('hide.bs.modal', function (event) {
            //jQuery('#inputFeedback').val("");
            //jQuery('.feedback-action').text("Add");
            //window.location.reload();
        });
    });
    
</script>