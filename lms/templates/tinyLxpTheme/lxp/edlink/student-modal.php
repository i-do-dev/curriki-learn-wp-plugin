<?php
    global $treks_src;    
    $school_post = $args['school_post'];
    $teachers = $args['teachers'];
    $teacher_id = (isset($args['role']) && $args['role'] == 'teacher') ? $args['teacher_post']->ID : 0;
    $edlink_school_id = get_post_meta($school_post->ID, 'lxp_edlink_school_id', true);
    if (!empty($args['district_post'])) {
        $edlink_access_token = get_post_meta($args['district_post']->ID, 'lxp_edlink_provider_access_token', true);
    } else {
        $edlink_access_token = (isset($_GET['district_id']) && isset($_GET['district_id']) > 0) ? get_post_meta($_GET['district_id'], 'lxp_edlink_provider_access_token', true) : '';
    }
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
<div class="modal fade students-modal" id="edlinkStudentModal" tabindex="-1" aria-labelledby="edlinkStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-header-title">
                    <h2 class="modal-title" id="edlinkStudentModalLabel"><span class="student-action-head">New</span> Student</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="row g-3" id="edlinkStudentForm">
                    <!-- Check Logged User Role -->
                    <?php
                        if (isset($args['role']) && ($args['role'] == 'school' || $args['role'] == 'teacher') ) {
                    ?>
                            <input type="hidden" name="edlink_school_admin_id" id="edlink_school_admin_id" value="<?php echo get_current_user_id(); ?>">
                    <?php        
                        } else {
                    ?>
                            <input type="hidden" name="edlink_school_admin_id" id="edlink_school_admin_id" value="<?php echo get_post_meta( $school_post->ID, 'lxp_school_admin_id', true ); ?>">
                    <?php        
                        }
                    ?>
                    <?php
                        if (isset($args['role']) && $args['role'] == 'teacher') {
                    ?>
                            <input type="hidden" name="teacher_id" id="teacher_id" value="<?php echo $teacher_id; ?>">
                    <?php        
                        }
                    ?>
                    <input type="hidden" name="edlink_student_school_id" id="edlink_student_school_id" value="<?php echo $school_post->ID; ?>">
                    <input type="hidden" name="edlink_student_post_id" id="edlink_student_post_id" value="0">
                    <div class="label_box edlink_loader" style="color: #0000ff"></div>
                    <div class="label_box" id="edlink_error" style="color: #dc3545"></div>
                    <div class="input_section">                        
                        <div class="input_box">
                            <div id="edlink_edit_student_container">
                                <div class="input_box brief_input_box">
                                    <div class="label_box brief_label_box id_label_box">
                                        <label class="label">Edlink Students</label>
                                        <!-- <input type="text" id="searchInput" placeholder="Search..."> -->
                                        <select id="edlinkInputStudentName" name="edlink_students" class="form-select">
                                        </select>
                                    </div>
                                </div>
                                <br/>
                                <div class="input_box brief_input_box">
                                    <div class="label_box brief_label_box">
                                        <label class="label">About</label>
                                        <input class="form-control" type="text" name="lxp_about" id="lxp_aboutStudent"
                                            placeholder="Something about Student" />
                                    </div>
                                </div>
                            </div>
                            <div class="input_box brief_input_box">
                                <div class="multi-select-container" id="edlink_add_student_container">
                                    <div class="multi-select-header">
                                        <button type="button" class="btn">Edlink Students</button>
                                        <span class="student-multi-select-count">0 selected</span>
                                        <span class="student-multi-select-limit"></span>
                                    </div>                                
                                    <input type="text" class="student-multi-select-search multi-select-toggle" placeholder="Search...">
                                    <div class="student-multi-select-dropdown">
                                        <!-- Single Select/Unselect All checkbox -->
                                        <div class="student-multi-select-item">
                                            <input type="checkbox" id="student-select-unselect-all"> Select/Unselect All
                                        </div>
                                        <div id="edlink-student-multi-select-container">
                                        </div>
                                    </div>
                                </div>
                            </div>                            
                        </div>                        
                    </div>
                    <?php
                        if (!empty($args['teachers'])) {
                    ?>
                            <div class="input_section col-md-6">                        
                                <div class="input_box">
                                    <div class="label_box">
                                        <label class="label">Assign Teacher</label>                                
                                        <select name="teacher_id" id="teacher_id" class="form-select" onChange="javascript:changeTeacher();">
                                            <option value="0">Select Teacher</option>
                                            <?php foreach ($teachers as $teacher) { ?>
                                                <option value="<?php echo $teacher->ID; ?>" <?php echo isset($_GET['teacher_id']) && $_GET['teacher_id'] == $teacher->ID ? "selected=selected" : ""; ?> ><?php echo $teacher->post_title; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                    <?php        
                        }
                    ?>
                    <div class="horizontal_line"></div>
                    <div class="input_section">
                        <h3 class="modal-title assign-text">Assign Grades</h3>
                        <div class="input_box brief_input_box">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input grade-checkbox" type="checkbox" name="grades[]" id="studentGradeCheckbox1" value="1st">
                                <label class="form-check-label" for="studentGradeCheckbox1">1st</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input grade-checkbox" type="checkbox" name="grades[]" id="studentGradeCheckbox2" value="2nd">
                                <label class="form-check-label" for="studentGradeCheckbox2">2nd</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input grade-checkbox" type="checkbox" name="grades[]" id="studentGradeCheckbox3" value="3rd">
                                <label class="form-check-label" for="studentGradeCheckbox3">3rd</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input grade-checkbox" type="checkbox" name="grades[]" id="studentGradeCheckbox4" value="4th">
                                <label class="form-check-label" for="studentGradeCheckbox4">4th</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input grade-checkbox" type="checkbox" name="grades[]" id="studentGradeCheckbox5" value="5th">
                                <label class="form-check-label" for="studentGradeCheckbox5">5th</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input grade-checkbox" type="checkbox" name="grades[]" id="studentGradeCheckbox6" value="6th">
                                <label class="form-check-label" for="studentGradeCheckbox6">6th</label>
                            </div>
                        </div>

                        <div class="input_box brief_input_box">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input grade-checkbox" type="checkbox" name="grades[]" id="studentGradeCheckbox7" value="7th">
                                <label class="form-check-label" for="studentGradeCheckbox7">7th</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input grade-checkbox" type="checkbox" name="grades[]" id="studentGradeCheckbox8" value="8th">
                                <label class="form-check-label" for="studentGradeCheckbox8">8th</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input grade-checkbox" type="checkbox" name="grades[]" id="studentGradeCheckbox9" value="9th">
                                <label class="form-check-label" for="studentGradeCheckbox9">9th</label>
                            </div>
                        </div>
                        <div class="btn_box">
                            <button class="btn" type="button" data-bs-dismiss="modal"
                                aria-label="Close">Cancel</button>
                            <button class="btn" id="edlinkSaveStudentBtn"><span class="student-action">Add</span></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script type="text/javascript">
    host = window.location.hostname === 'localhost' ? window.location.origin + '<?php echo WORDPRESS_HOST; ?>' : window.location.origin;
    apiUrl = host + '/wp-json/lms/v1/';

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
        jQuery("#edlinkSaveStudentBtn").attr("disabled", false);

        if (selectedCount >= selectionLimit) {
            jQuery('.student-multi-select-option:not(:checked)').prop('disabled', true);
        } else {
            jQuery('.student-multi-select-option').prop('disabled', false);
        }
    }

    function changeTeacher() {
        jQuery("#edlinkSaveStudentBtn").attr("disabled", false);
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

        var edlinkStudentModal = document.getElementById('edlinkStudentModal');
        edlinkStudentModalObj = new bootstrap.Modal(edlinkStudentModal);
        window.edlinkStudentModalObj = edlinkStudentModalObj;
        //edlinkStudentModalObj.hide();

        jQuery("#edlinkStudentModalBtn").on('click', function() {
            var teacher_id = '<?php echo $teacher_id; ?>';            
            if (teacher_id > 0) {
                jQuery('#edlinkStudentModal #teacher_id').val(teacher_id);
            }
            getEdlinkStudents();
            jQuery("#edlink_add_student_container").show();
            jQuery("#edlink_edit_student_container").hide();
            edlinkStudentModalObj.show();
        });

        edlinkStudentModal.addEventListener('hide.bs.modal', function (event) {
            jQuery('.student-multi-select-search').removeAttr("style");
            jQuery('#edlinkStudentModal #teacher_id').removeClass('is-invalid');            
            jQuery('#edlinkStudentModal #teacher_id').val(0);
            jQuery("#edlink_student_post_id").val(0);
            jQuery('#edlinkStudentModal #lxp_aboutStudent').val("");
            jQuery("#edlink-student-multi-select-container").html('');
            jQuery('#student-select-unselect-all').prop('checked', false);
            jQuery('#edlinkStudentModal input.grade-checkbox').prop('checked', false);
            studentUpdateSelectedCount();
            jQuery("#edlinkInputStudentName").html('<option value="0"> Choose...</option>');
            jQuery(".edlink_loader").html('');
            jQuery("#edlink_error").html("");
            jQuery('.student-action').text("Add");
            jQuery('.student-action-head').text("New");
            jQuery('.student-multi-select-limit').text('');
        });

        let edlinkStudentForm = jQuery("#edlinkStudentForm");
        jQuery(edlinkStudentForm).on('submit', function(e) {
            jQuery("#edlinkSaveStudentBtn").attr("disabled", true);
            jQuery(".student-action").html(`<i class="fa fa-spinner fa-spin" style="font-size:25px"></i> Add`);
            e.preventDefault();
            const formData = new FormData(e.target);
            $.ajax({
                method: "POST",
                enctype: 'multipart/form-data',
                url: apiUrl + "edlink/students/save",
                data: formData,
                processData: false,
                contentType: false,
                cache: false,
            }).done(function( response ) {                
                jQuery('#edlinkStudentForm .form-control').removeClass('is-invalid');
                edlinkStudentModalObj.hide();
                jQuery("#edlinkSaveStudentBtn").attr("disabled", false);
                jQuery(".student-action").text(`Add`);
                window.location.reload();
            }).fail(function (response) {
                jQuery('#edlinkStudentForm .form-control').removeClass('is-invalid');
                if (response.responseJSON !== undefined) {
                    Object.keys(response.responseJSON.data.params).forEach(element => {
                        if (element == 0) {
                            jQuery('.student-multi-select-search').css(
                                {
                                    "border": "1px solid #dc3545"
                                }
                            );
                        }
                        jQuery('#edlinkStudentModal input[name="' + element + '"]').addClass('is-invalid');
                        jQuery('#edlinkStudentModal textarea[name="' + element + '"]').addClass('is-invalid');
                        jQuery('#edlinkStudentModal select[name="' + element + '"]').addClass('is-invalid');
                    });
                }
                jQuery(".student-action").text(`Add`);
            });
        
        });
    });

    // document.getElementById('searchInput').addEventListener('input', function () {
    //     let filter = this.value.toLowerCase();
    //     let options = document.getElementById('edlinkInputStudentName').options;
        
    //     for (let i = 0; i < options.length; i++) {
    //         let optionText = options[i].text.toLowerCase();
    //         if (optionText.includes(filter)) {
    //             options[i].style.display = '';  // Show the option
    //         } else {
    //             options[i].style.display = 'none';  // Hide the option
    //         }
    //     }
    // });


    function onEdlinkStudentEdit(lxp_student_id) {        
        jQuery("#edlink_student_post_id").val(lxp_student_id);
        jQuery(".student-action").text("Update");
        jQuery(".student-action-head").text("Update");

        jQuery("#edlinkInputStudentName").html('<option value="0"> Choose...</option>');
        jQuery("#edlink_edit_student_container").show();
        jQuery("#edlink_add_student_container").hide();        
        jQuery("#edlinkSaveStudentBtn").attr("disabled", true);
        
        // let host = window.location.hostname === 'localhost' ? window.location.origin + '<?php echo WORDPRESS_HOST; ?>' : window.location.origin;
        // let apiUrl = host + '/wp-json/lms/v1/';

        $.ajax({
            method: "POST",
            enctype: 'multipart/form-data',
            url: apiUrl + "students",
            data: {lxp_student_id}
        }).done(function( response ) {
            let student = response.data.student;
            let admin = response.data.admin.data;
            jQuery('#edlinkStudentForm .form-control').removeClass('is-invalid');
            getEdlinkStudentForEdit('<?php echo $edlink_access_token; ?>', admin);
            jQuery("#edlinkStudentModal #edlink_student_post_id").val(student.ID);
            jQuery('#edlinkStudentModal #lxp_aboutStudent').val(student.post_content);
            //jQuery('#edlinkStudentModal #idStudent').val(student.student_id);
            jQuery('#edlinkStudentModal #teacher_id').val(student.teacher_id);
            jQuery("#edlinkSaveStudentBtn").attr("disabled", false);
            
            if (student.grades) {
                student.grades.forEach(grade => jQuery('#edlinkStudentModal input.grade-checkbox[value=' + grade +']').prop('checked', true));
            }
            edlinkStudentModalObj.show();
        }).fail(function (response) {
            jQuery("#edlinkSaveStudentBtn").attr("disabled", true);
            console.error("Can not load student");
        });
    }

    function getEdlinkStudents() {
        jQuery("#edlinkSaveStudentBtn").attr("disabled", true);
        jQuery("#edlinkInputStudentName").html('');
        jQuery(".edlink_loader").html('<i class="fa fa-spinner fa-spin" style="font-size:25px"></i> Loading ...');
        var access_token = '<?php echo $edlink_access_token; ?>';
        var edlink_school_id = '<?php echo $edlink_school_id; ?>';
        $.ajax({
            method: "POST",            
            url: apiUrl + "edlink/provider/people-by-role",
            data: {access_token, "role" : "student", "school_id" : edlink_school_id}
        }).done(function( response ) {
            // Set Student Data
            if (typeof response.people === 'object' && response.people !== null && !response['people']['error']) {
                selectionLimit = Object.keys(response.people).length;
                jQuery('.student-multi-select-limit').text(`out of ${selectionLimit}`);
                var html = '';
                Object.entries(response.people).forEach(([key, person]) => {
                    var val = ''+person["first_name"]+'|'+person["last_name"]+'|'+person["email"]+'';
                    html += '<div class="student-multi-select-item">';
                    html += '<input type="checkbox" name="edlink_students[]" class="student-multi-select-option" onChange="javascript:studentMultiSelectChange();" value="'+val+'" id="'+person["id"]+'" firstName="'+person["first_name"]+'" lastName="'+person["last_name"]+'"> '+person["first_name"]+' '+person["last_name"]+' ('+person["email"]+')';
                    html += '</div>';
                });
                jQuery("#edlink-student-multi-select-container").html(html);
                jQuery("#edlinkSaveStudentBtn").attr("disabled", false);
            } else {                
                jQuery("#edlinkSaveStudentBtn").attr("disabled", true);                
                jQuery("#edlink_error").html(response['people']['error']);
                jQuery("#edlink-student-multi-select-container").html('');
            }
            jQuery(".edlink_loader").html('');
        });
    }

    function getEdlinkStudentForEdit(access_token, admin) {
        var user_email = admin.user_email;
        var edlink_school_id = '<?php echo $edlink_school_id; ?>';
        jQuery(".edlink_loader").html('<i class="fa fa-spinner fa-spin" style="font-size:25px"></i> Loading ...');
        $.ajax({
            method: "POST",            
            url: apiUrl + "edlink/provider/people/edit",
            data: {access_token, user_email, "role" : "student", "school_id" : edlink_school_id}
        }).done(function( response ) {
            // Set Student Data
            if (typeof response.people === 'object' && response.people !== null && !response['people']['error']) {
                selectionLimit = Object.keys(response.people).length;
                jQuery('.student-multi-select-limit').text(`out of ${selectionLimit}`);
                var html = '';
                html += '';
                Object.entries(response.people).forEach(([key, person]) => {
                    var val = ''+person["first_name"]+'|'+person["last_name"]+'|'+person["email"]+'';
                    html += '<option value="'+val+'">'+person["first_name"]+' '+person["last_name"]+' ('+person["email"]+')</option>';
                });
                jQuery("#edlinkInputStudentName").html(html);
                var user_cred = ''+admin.first_name+'|'+admin.last_name+'|'+admin.user_email+'';
                jQuery('#edlinkInputStudentName').val(user_cred);
                jQuery("#edlinkSaveStudentBtn").attr("disabled", false);
            } else {
                jQuery("#edlinkSaveStudentBtn").attr("disabled", true);                        
                jQuery("#edlink_error").html(response['people']['error']);
                jQuery("#edlinkInputStudentName").html('<option value="0"> Choose...</option>');
            }
            jQuery(".edlink_loader").html('');
        });
    }
</script>