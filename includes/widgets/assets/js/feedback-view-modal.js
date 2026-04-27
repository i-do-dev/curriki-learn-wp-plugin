document.addEventListener("click", function(e) {
    if (e.target && e.target.classList.contains("show-feedback-btn")) {
        let slideNumber = e.target.getAttribute("data-slide");
        // console.log("Feedback button clicked for Slide " + slideNumber);
        viewFeedback(slideNumber);
    }
    // if (e.target && e.target.classList.contains("history-back-button")) {
    //     window.location.href = "/learner-grades";
    // }
});

document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("feedbackViewModal");
    if (modal) {
        document.body.appendChild(modal); // put modal at last of body
        modal.style.display = "none"; // hide modal at start
    }
});

jQuery(document).ready(function ($) {
    const data = typeof FeedbackModalData !== 'undefined' ? FeedbackModalData : null;

    if (!data) {
        console.error('FeedbackModalData not found.');
        return;
    }

    let feedbackViewModal = document.getElementById('feedbackViewModal');
    if (!feedbackViewModal) return;

    let feedbackViewModalObj = new bootstrap.Modal(feedbackViewModal);
    window.feedbackViewModalObj = feedbackViewModalObj;

    $('#feedbackViewForm').on('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        $.ajax({
            method: 'POST',
            url: data.apiUrl + 'assignment/submission/feedback/view',
            data: formData,
            processData: false,
            contentType: false,
            cache: false,
        })
        .done(function (response) {
            const feedbackHtml = response?.data?.feedback && response.data.feedback.length > 0
                ? response.data.feedback
                : '<p><i>No feedback given.</i></p>';
            $('#feedback-container').html(feedbackHtml);
            feedbackViewModalObj.show();
        })
        .fail(function (xhr, status, error) {
            console.error('AJAX Error:', error);
            $('#feedback-container').html('<p class="text-danger">Failed to load feedback.</p>');
            feedbackViewModalObj.show();
        });
    });

    // Optional: Reset on close
    feedbackViewModal.addEventListener('hidden.bs.modal', function () {
        $('#feedback-container').empty();
    });

});

// Global function for buttons to trigger modal
function viewFeedback(slideNumber) {
    if (typeof jQuery !== 'undefined' && window.feedbackViewModalObj) {
        jQuery("#feedbackViewForm #slide").val(parseInt(slideNumber));
        jQuery("#feedbackViewForm").submit();
    } else {
        console.error('Modal or jQuery not loaded.');
    }
}

// $('.viewclose').on('click', function(e) {
//     feedbackViewModalObj.hide();
// });