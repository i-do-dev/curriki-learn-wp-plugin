<?php
    global $treks_src;
    $students = $args['students'];
    $teacher_post = $args['teacher_post'];
    $edlink_access_token = (isset($_GET['district_id']) && isset($_GET['district_id']) > 0) ? get_post_meta($_GET['district_id'], 'lxp_edlink_provider_access_token', true) : '';
?>

<div class="modal fade classes-modal" id="classModal" tabindex="-1" aria-labelledby="classModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered class-modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-header-title">
                    <h2 class="modal-title" id="classModalLabel"><span id="class-action-heading">New</span> Class &  Groups</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">                
                <div class="alert alert-danger invalid-feedback-student_ids" role="alert" style="display: none;">
                    Please select at least one student.
                </div>
                <!-- <div class="alert alert-danger invalid-feedback-schedule" role="alert" style="display: none;">
                    Please make class schedule with valid time.
                </div> -->
                <form class="row g-3" id="classForm">
                    <input type="hidden" name="class_teacher_id" id="class_teacher_id" value="<?php echo $teacher_post->ID; ?>" />
                    <input type="hidden" name="class_post_id" id="class_post_id" value="0" />
                    <div class="personal_box">
                        <!-- Left Class box -->
                        <div class="class-information">
                            <p class="personal-text">Class & Group information</p>
                            <div class="search_box">
                                <label class="trek-label">Name</label>
                                <input type="text" class="form-control period-select" value="" id="class_name" name="class_name" />
                            </div>
                            <div class="search_box">
                                <label class="trek-label">Description</label>
                                <textarea class="period-select form-control" id="class_description" name="class_description"></textarea>
                            </div>
                            <div class="horizontal-line"></div>
                            <p class="personal-text">Schedule</p>

                            <table class="table table-borderless">
                                <thead>
                                    <tr>
                                        <td>Day</td>
                                        <td>Start time</td>
                                        <td>End time</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="monday" id="monday" name="schedule[]">
                                                <label class="form-check-label" for="monday">Monday</label>
                                            </div>
                                        </td>
                                        <td><input type="time" id="monday-sd" name="monday-sd"></td>
                                        <td><input type="time" id="monday-ed" name="monday-ed"></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="tuesday" id="tuesday" name="schedule[]">
                                                <label class="form-check-label" for="tuesday">Tuesday</label>
                                            </div>
                                        </td>
                                        <td><input type="time" id="tuesday-sd" name="tuesday-sd"></td>
                                        <td><input type="time" id="tuesday-ed" name="tuesday-ed"></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="wednesday" id="wednesday" name="schedule[]">
                                                <label class="form-check-label" for="wednesday">Wednesday</label>
                                            </div>
                                        </td>
                                        <td><input type="time" id="wednesday-sd" name="wednesday-sd"></td>
                                        <td><input type="time" id="wednesday-ed" name="wednesday-ed"></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="thursday" id="thursday" name="schedule[]">
                                                <label class="form-check-label" for="thursday">Thursday</label>
                                            </div>
                                        </td>
                                        <td><input type="time" id="thursday-sd" name="thursday-sd"></td>
                                        <td><input type="time" id="thursday-ed" name="thursday-ed"></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="friday" id="friday" name="schedule[]">
                                                <label class="form-check-label" for="friday">Friday</label>
                                            </div>
                                        </td>
                                        <td><input type="time" id="friday-sd" name="friday-sd"></td>
                                        <td><input type="time" id="friday-ed" name="friday-ed"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- End Left Class box -->

                        <!-- Vertical Line -->
                        <div class="vertical-line"></div>

                        <!-- Right Class box -->
                        <div class="class-information class-information">
                            <p class="personal-text">Class & Group</p>
                            <!-- Select Grade -->
                            <div class="search_box">
                                <label class="trek-label">Grade</label>
                                <select class="form-select form-control" aria-label="Default select example" name="grade" id="grade">
                                    <option value="0">--- Select ---</option>
                                    <option value="1st">1st</option>
                                    <option value="2nd">2nd</option>
                                    <option value="3rd">3rd</option>
                                    <option value="4th">4th</option>
                                    <option value="5th">5th</option>
                                    <option value="6th">6th</option>
                                    <option value="7th">7th</option>
                                    <option value="8th">8th</option>
                                    <option value="9th">9th</option>
                                    <option value="10th">10th</option>
                                    <option value="11th">11th</option>
                                    <option value="12th">12th</option>
                                </select>
                            </div>
                            <!-- When Selected a Grade -->
                            <div class="search_box">
                                <label class="trek-label">
                                    Students
                                </label>
                                <div class="dropdown period-box">
                                    <!-- second-drop-button -->
                                    <button class="input_dropdown dropdown-button" type="button" id="studentsDropdownMenu" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <span>--- Select ---</span>
                                        <img class="rotate-arrow" src="<?php echo $treks_src; ?>/assets/img/down-arrow.svg" alt="logo" />
                                    </button>
                                    <div class="dropdown-menu grade-dropdown-menu" aria-labelledby="studentsDropdownMenu">
                                        <!-- Select All -->
                                        <div class="dropdown-item dropdown-item2 dd-button" id="select-all-students-btn">
                                            <!-- Select Grade -->
                                            <div class="time-date-box class-class-box">
                                                <input class="form-check-input " type="checkbox" value="select-all-students" id="select-all-students" />
                                                <div class="tags-body-detail">
                                                    <p class="select-all">Select All</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="scroll-box">
                                            <?php 
                                                foreach ($students as $student) { 
                                                    $student_admin = get_userdata(get_post_meta($student->ID, 'lxp_student_admin_id', true));
                                            ?>
                                                <!-- Grade-->
                                                <div class="dropdown-item dropdown-item2 dd-button select-student-btn">
                                                    <!-- Select Grade -->
                                                    <div class="time-date-box class-class-box">
                                                        <input class="form-check-input select-student-check" type="checkbox" value="<?php echo $student->ID; ?>" id="checkbox-<?php echo $student->ID; ?>" name="student_ids[]" />
                                                        <img src="<?php echo $treks_src; ?>/assets/img/profile-icon.png" alt="student" />
                                                        <div class="tags-body-detail">
                                                            <p class="class-name"><?php echo $student_admin->display_name?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="horizontal-line"></div>
                            <p class="personal-text">Type</p>
                            <table class="table table-borderless">
                                <tbody>
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" value="classes" id="classes_radio" name="type" checked>
                                                <label class="form-check-label" for="classes">Classes</label>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" value="other_group" id="other_group_radio" name="type">
                                                <label class="form-check-label" for="other_group">Group</label>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- Button Section -->
                    <div class="input_section">
                        <div class="btn_box class_btns">
                            <button class="btn" type="button" data-bs-dismiss="modal" aria-label="Close">Cancel</button>
                            <button class="btn" id="class-action">Add</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script type="text/javascript">

function onClassEdit(class_id) {
    jQuery("#class_post_id").val(class_id);
    jQuery("#class-action-heading").text("Update");
    jQuery("#class-action").text("Update");
    
    let host = window.location.hostname === 'localhost' ? window.location.origin + '<?php echo WORDPRESS_HOST; ?>' : window.location.origin;
    let apiUrl = host + '/wp-json/lms/v1/';

    $.ajax({
        method: "POST",
        enctype: 'multipart/form-data',
        url: apiUrl + "classes",
        data: {class_id}
    }).done(function( response ) {
        let class_record = response.data.class;
        jQuery('#classForm .form-control').removeClass('is-invalid');
        jQuery(".alert-danger").hide();
        jQuery('#classModal #class_name').val(class_record.post_title);
        jQuery('#classModal #class_description').val(class_record.post_content);
        window.class_record = class_record;
        console.log('class_record >> ', class_record);

        Object.keys(class_record.schedule).forEach(day => {
            jQuery('input#' + day).prop("checked", true);
            jQuery('input#' + day + '-sd').val(class_record.schedule[day].start);
            jQuery('input#' + day + '-ed').val(class_record.schedule[day].end);
        });

        jQuery('select#grade').val(class_record.grade);      
        if (class_record.lxp_class_type == 'other_group') {
            jQuery('#classes_radio').attr('checked', false);
            jQuery('#other_group_radio').attr('checked', true);
        } else {
            jQuery('#classes_radio').attr('checked', true);
            jQuery('#other_group_radio').attr('checked', false);
        }
        
        class_record.lxp_student_ids.forEach(student_id => {
            jQuery('input.select-student-check[value="' + student_id + '"]').prop('checked', true);
        });

        jQuery("#studentsDropdownMenu span").text(jQuery(".select-student-check:checked").length);

        classModalObj.show();
    }).fail(function (response) {
        console.error("Can not load class");
    });
}

    jQuery(document).ready(function() { 
        let host = window.location.hostname === 'localhost' ? window.location.origin + '<?php echo WORDPRESS_HOST; ?>' : window.location.origin;
        let apiUrl = host + '/wp-json/lms/v1/';

        var classModal = document.getElementById('classModal');
        classModalObj = new bootstrap.Modal(classModal);
        window.classModalObj = classModalObj;
        
        classModal.addEventListener('hide.bs.modal', function (event) {
            
            jQuery("#class_post_id").val(0);
            jQuery('#classModal #aboutClass').val("");
            jQuery('#classModal #first_name_class').val("");
            jQuery('#classModal #last_name_class').val("");
            jQuery('#classModal #emailClass').val("");
            jQuery('#classModal #inputEmailDefaultClass').val("");
            jQuery('#classModal #passwordClass').val("");
            window.location.reload();
        });

        let classForm = jQuery("#classForm");
        jQuery(classForm).on('submit', function(e) {
            e.preventDefault();
            jQuery("#class-action").attr("disabled", "disabled");
            let beforeText = jQuery("#class-action").text();
            jQuery("#class-action").html(`<i class="fa fa-spinner fa-spin"></i> ` + beforeText);
            jQuery(".alert-danger").hide();
            const formData = new FormData(e.target);
            $.ajax({
                method: "POST",
                enctype: 'multipart/form-data',
                url: apiUrl + "classes/save",
                data: formData,
                processData: false,
                contentType: false,
                cache: false,
            }).done(function( response ) {
                jQuery('#classForm .form-control').removeClass('is-invalid');
                classModalObj.hide();
            }).fail(function (response) {
                jQuery('#classForm .form-control').removeClass('is-invalid');
                if (response.responseJSON !== undefined && response.responseJSON.code === "rest_missing_callback_param") {
                    console.log("yesss", response.responseJSON.data.params);
                    response.responseJSON.data.params.forEach(element => {
                        jQuery(".invalid-feedback-" + element).show();
                    });
                }
                
                if (response.responseJSON !== undefined) {
                    Object.keys(response.responseJSON.data.params).forEach(element => {
                        console.log('element >>> ', element);
                        jQuery('#classModal input[name="' + element + '"]').addClass('is-invalid');
                        jQuery('#classModal textarea[name="' + element + '"]').addClass('is-invalid');
                        jQuery('#classModal select[name="' + element + '"]').addClass('is-invalid');
                        // if (element === "schedule") {
                        //     jQuery(".invalid-feedback-" + element).show();
                        // }
                    });
                }
                jQuery("#class-action").text(beforeText);
                jQuery("#class-action").removeAttr("disabled");
            });
        
        });


        // ==== [start] Students Selection =================
        jQuery("#select-all-students").on('change', function(e) {
            if (jQuery("#select-all-students:checked").length) {
                jQuery(".select-student-check").prop('checked', true);
                jQuery("#studentsDropdownMenu span").text(jQuery(".select-student-check:checked").length);
            } else {
                jQuery(".select-student-check").prop('checked', false);
                jQuery("#studentsDropdownMenu span").text('--- Select ---');
            }
        });
        
        jQuery(".select-student-check").on('change', function(e) {
            jQuery("#select-all-students").prop('checked', false);
            if (jQuery(".select-student-check:checked").length) {
                jQuery("#studentsDropdownMenu span").text(jQuery(".select-student-check:checked").length);
            } else {
                jQuery("#studentsDropdownMenu span").text('--- Select ---');
            }
        });
        // ==== [end] Students Selection =================

    });
</script>