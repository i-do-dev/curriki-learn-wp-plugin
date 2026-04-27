<?php
    global $treks_src;    
    $teacher_id = $args['teacher_post']->ID;
    $school_id = $args['school_post']->ID;
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<style>
    .student-multi-select-dropdown {
        max-height: 250px;
        overflow-y: auto;
        border: 1px solid #ccc;
        display: none;
        background-color: white;
    }
    .student-multi-select-item {
        padding: 5px;
    }
    .student-multi-select-search {
        width: 100%;
        padding: 5px;
        margin-bottom: 5px;
    }
    .student-multi-select-count {
        font-weight: bold;
    }
</style>

<!-- Modal -->
<div class="modal fade students-modal" id="takenStudentModal" tabindex="-1" aria-labelledby="takenStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-header-title">
                    <h2 class="modal-title" id="takenStudentModalLabel">Add Lxp Students</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="row g-3" id="takingStudentForm">
                    <!-- Check Logged User Role -->
                    
                    <input type="hidden" name="teacher_id" id="current_teacher_id" value="<?= $teacher_id; ?>">
                    <div class="label_box modal_students_loader" style="color: #0000ff"></div>
                    <div class="label_box" id="take_student_error" style="color: #dc3545"></div>
                    <div class="input_section">
                        <div class="input_box">
                            <div class="input_box brief_input_box">
                                <div class="multi-select-container" id="take_add_student_container">
                                    <div class="multi-select-header">
                                        <span class="student-multi-select-count">0 selected</span>
                                        <span class="student-multi-select-limit"></span>
                                    </div>                                
                                    <input type="text" class="student-multi-select-search multi-select-toggle" placeholder="Search...">
                                    <div class="student-multi-select-dropdown">
                                        <!-- Single Select/Unselect All checkbox -->
                                        <div class="student-multi-select-item">
                                            <input type="checkbox" id="student-select-unselect-all"> Select/Unselect All
                                        </div>
                                        <div id="take-student-multi-select-container"></div>
                                    </div>
                                </div>
                            </div>                            
                        </div>                        
                    </div>
                    
                    <div class="horizontal_line"></div>
                    <div class="btn_box">
                        <button class="btn" type="button" data-bs-dismiss="modal" aria-label="Close">Cancel</button>
                        <button class="btn" id="takenStudentSaveBtn"><span class="student-action">Add</span></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script type="text/javascript">

    apiUrl = window.location.origin + '/wp-json/lms/v1/';

    selectionLimit = 10;
    // Listen for individual checkbox changes to update the count
    function studentMultiSelectChange() {
        studentUpdateSelectedCount();
        // If all items are selected, check the "Select/Unselect All" box; if any are unchecked, uncheck it
        const allChecked = jQuery('.student-multi-select-option').length === jQuery('.student-multi-select-option:checked').length;
        jQuery('#student-select-unselect-all').prop('checked', allChecked);
    }

    // Update count of selected items
    function studentUpdateSelectedCount() {
        const selectedCount = jQuery('.student-multi-select-option:checked').length;
        jQuery('.student-multi-select-count').text(`${selectedCount} selected`);
        //jQuery('.student-multi-select-limit').text(`out of ${selectionLimit} maximum`);
        jQuery("#takenStudentSaveBtn").attr("disabled", false);

        if (selectedCount >= selectionLimit) {
            jQuery('.student-multi-select-option:not(:checked)').prop('disabled', true);
        } else {
            jQuery('.student-multi-select-option').prop('disabled', false);
        }
    }

    jQuery(document).ready(function() {

        // Toggle the dropdown display
        jQuery('.multi-select-toggle').on('click', function() {
            jQuery('.student-multi-select-dropdown').toggle();
        });

        // Initial count update
        studentUpdateSelectedCount();

        // Select/Unselect All functionality with selection limit
        jQuery('#student-select-unselect-all').on('change', function() {
            const isChecked = jQuery(this).is(':checked');
            const checkboxes = jQuery('.student-multi-select-option');
            
            // Determine how many checkboxes can still be checked based on the selection limit
            const currentSelectedCount = checkboxes.filter(':checked').length;
            const availableToSelect = selectionLimit - currentSelectedCount;

            if (isChecked) {
                // Only select up to the limit
                checkboxes.each(function(index) {
                    if (index < availableToSelect || jQuery(this).is(':checked')) {
                        jQuery(this).prop('checked', true);
                    }
                });
            } else {
                // Uncheck all if "Select/Unselect All" is unchecked
                checkboxes.prop('checked', false);
            }

            studentUpdateSelectedCount();
        });

        // Filter items based on search input
        jQuery('.student-multi-select-search').on('input', function() {
            const searchValue = jQuery(this).val().toLowerCase();
            jQuery('.student-multi-select-item').each(function() {
                const itemText = jQuery(this).text().toLowerCase();
                jQuery(this).toggle(itemText.includes(searchValue));
            });
        });

        // Close the dropdown when clicking outside
        jQuery(document).on('click', function(event) {
            if (!jQuery(event.target).closest('.multi-select-container').length) {
                jQuery('.student-multi-select-dropdown').hide();
            }
        });

        var takenStudentModal = document.getElementById('takenStudentModal');
        takenStudentModalObj = new bootstrap.Modal(takenStudentModal);
        window.takenStudentModalObj = takenStudentModalObj;

        jQuery("#takenStudentModalBtn").on('click', function() {
            var teacher_id = '<?php echo $teacher_id; ?>';
            if (teacher_id > 0) {
                jQuery('#takenStudentModal #current_teacher_id').val(teacher_id);
            }
            getTakenStudents();
            jQuery("#take_add_student_container").show();
            takenStudentModalObj.show();
        });

        takenStudentModal.addEventListener('hide.bs.modal', function (event) {
            jQuery('.student-multi-select-search').removeAttr("style");
            jQuery('#takenStudentModal #current_teacher_id').removeClass('is-invalid');            
            jQuery('#takenStudentModal #current_teacher_id').val(0);
            
            jQuery('#takenStudentModal #lxp_aboutStudent').val("");
            jQuery("#take-student-multi-select-container").html('');
            jQuery('#student-select-unselect-all').prop('checked', false);
            jQuery('#takenStudentModal input.grade-checkbox').prop('checked', false);
            studentUpdateSelectedCount();
            jQuery("#takenStudentNameInput").html('<option value="0"> Choose...</option>');
            jQuery(".modal_students_loader").html('');
            jQuery("#take_student_error").html("");
            jQuery('.student-multi-select-limit').text('');
        });

        let takingStudentForm = jQuery("#takingStudentForm");
        jQuery(takingStudentForm).on('submit', function(e) {
            jQuery("#take_student_error").html("");
            jQuery("#takenStudentSaveBtn").attr("disabled", true);
            jQuery(".modal_students_loader").html('<i class="fa fa-spinner fa-spin" style="font-size:25px"></i> Saving ...');
            e.preventDefault();
            const formData = new FormData(e.target);

            $.ajax({
                method: "POST",
                enctype: 'multipart/form-data',
                url: apiUrl + "students/taken_student",
                data: formData,
                processData: false,
                contentType: false,
                cache: false,
            }).done(function( response ) {
                jQuery('#takingStudentForm .form-control').removeClass('is-invalid');
                takenStudentModalObj.hide();
                window.location.reload();
            }).fail(function (response) {
                jQuery("#take_student_error").html("Please select at least one recored!");
                jQuery(".modal_students_loader").html('');
                jQuery("#takenStudentSaveBtn").attr("disabled", false);
            });
        
        });
    });

    function getTakenStudents() {
        jQuery("#takenStudentSaveBtn").attr("disabled", true);
        jQuery("#takenStudentNameInput").html('');
        jQuery(".modal_students_loader").html('<i class="fa fa-spinner fa-spin" style="font-size:25px"></i> Loading ...');
        var school_id = '<?php echo $school_id; ?>';
        var teacher_id = '<?php echo $teacher_id; ?>';
        $.ajax({
            method: "GET",
            url: apiUrl + "students/taken_student",
            data: {"teacher_id" : teacher_id, "school_id": school_id},
        }).done(function( response ) {
            // Set Student Data
            selectionLimit = Object.keys(response.data.students).length;
            jQuery('.student-multi-select-limit').text(`out of ${selectionLimit}`);
            var html = '';
            Object.entries(response.data.students).forEach(([key, person]) => {
                html += '<div class="student-multi-select-item">';
                html += '<input type="checkbox" name="student_ids[]" class="student-multi-select-option" onChange="javascript:studentMultiSelectChange();" value="'+person["id"]+'"> '+person["first_name"]+' '+person["last_name"]+' ('+person["email"]+')';
                html += '</div>';
            });
            jQuery("#take-student-multi-select-container").html(html);
            jQuery("#takenStudentSaveBtn").attr("disabled", false);
            jQuery(".modal_students_loader").html('');
        });
    }
</script>