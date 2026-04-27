<?php

    $school_post = lxp_get_user_school_post();
?>

<style>
    .teacher_input_box {
        padding: 0 0 30px !important;
    }
</style>

<!-- Modal -->
<div class="modal fade teachers-modal" id="teacherModal" tabindex="-1" aria-labelledby="teacherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-header-title">
                    <h2 class="modal-title" id="teacherModalLabel"><span class="teacher-action-head">New</span> Teacher</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="row g-3" id="teacherForm">
                    <input type="hidden" name="school_admin_id" value="<?php echo get_current_user_id(); ?>">
                    <input type="hidden" name="teacher_school_id" value="<?php echo $school_post->ID; ?>">
                    <input type="hidden" name="teacher_post_id" id="teacher_post_id" value="0">

                    <div class="personal_box">
                        <p class="personal-text">Personal information</p>
                        <!-- <div class="info_box">
                            <img class="action_logo" src="<?php // echo $treks_src ?>/assets/img/import.svg" alt="logo" />
                            <p class="personal-text import-text">Import info</p>
                        </div> -->
                    </div>
                    <!-- 
                    <div class="personal_box user-profile-box">
                        <img src="<?php // echo $treks_src ?>/assets/img/user-profile.svg" alt="logo" />
                        <div class="info_box">
                            <img class="action_logo" src="<?php // echo $treks_src ?>/assets/img/upload.svg" alt="logo" />
                            <p class="personal-text import-text">Upload</p>
                        </div>
                        <div class="info_box">
                            <img class="action_logo" src="<?php // echo $treks_src ?>/assets/img/delete-circled-outline.svg"
                                alt="logo" />
                            <p class="personal-text import-text remove_text">Remove</p>
                        </div>
                    </div>
                    -->

                    <div class="input_section">
                        <div class="input_box">
                            <div class="label_box">
                                <label class="label">First name</label>
                                <input class="form-control" type="text" name="lxp_first_name" id="lxp_first_name"
                                    placeholder="Enter teacher’s first name here" />
                            </div>
                        </div>
                        <div class="input_box">
                            <div class="label_box">
                                <label class="label">Last name</label>
                                <input class="form-control" type="text" name="lxp_last_name" id="lxp_last_name"
                                    placeholder="Enter teacher’s last name here" />
                            </div>
                        </div>
                        <div class="input_box">
                            <div class="label_box">
                                <label class="label">Email</label>
                                <input class="form-control" type="email" name="lxp_user_email" id="lxp_email" placeholder="johndoe@gmail.com" />
                                <input type="hidden" id="lxp_inputEmailDefault" name="lxp_user_email_default" />
                            </div>
                        </div>
                        <div class="input_box">
                            <div class="label_box">
                                <label class="label">Password</label>
                                <input class="form-control" type="password" name="lxp_user_password" id="lxp_password" placeholder="***" />
                            </div>
                        </div>
                        <div class="input_box brief_input_box">
                            <div class="label_box brief_label_box">
                                <label class="label">About</label>
                                <input class="brief_info form-control" type="text" name="about" id="about"
                                    placeholder="Enter a brief description here" />
                            </div>
                        </div>
                        <!-- 
                        <div class="input_box brief_input_box">
                            <div class="label_box brief_label_box id_label_box">
                                <label class="label">ID</label>
                                <input class="brief_info id_info" type="password" name="password"
                                    placeholder="0000" />
                            </div>
                        </div>
                        -->
                    </div>
                    <div class="horizontal_line"></div>
                    <div class="input_section">
                        <h3 class="modal-title assign-text">Assign Grades</h3>
                        <div class="input_box brief_input_box">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input grade-checkbox" type="checkbox" name="grades[]" id="gradeCheckbox1" value="1st">
                                <label class="form-check-label" for="gradeCheckbox1">1st</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input grade-checkbox" type="checkbox" name="grades[]" id="gradeCheckbox2" value="2nd">
                                <label class="form-check-label" for="gradeCheckbox2">2nd</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input grade-checkbox" type="checkbox" name="grades[]" id="gradeCheckbox3" value="3rd">
                                <label class="form-check-label" for="gradeCheckbox3">3rd</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input grade-checkbox" type="checkbox" name="grades[]" id="gradeCheckbox4" value="4th">
                                <label class="form-check-label" for="gradeCheckbox4">4th</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input grade-checkbox" type="checkbox" name="grades[]" id="gradeCheckbox5" value="5th">
                                <label class="form-check-label" for="gradeCheckbox5">5th</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input grade-checkbox" type="checkbox" name="grades[]" id="gradeCheckbox6" value="6th">
                                <label class="form-check-label" for="gradeCheckbox6">6th</label>
                            </div>
                            <!-- <div class="label_box brief_label_box id_label_box">
                                <label class="label">Grade</label>
                                <div class="dropdown">
                                    <button class="input_dropdown" type="button" id="dropdownMenu2"
                                        data-bs-toggle="dropdown" aria-haspopup="true"
                                        aria-expanded="false">
                                        Select grades
                                        <img src="<?php // echo $treks_src ?>/assets/img/down-arrow.svg" alt="logo" />
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenu2">
                                        <button class="dropdown-item" type="button">Action</button>
                                        <button class="dropdown-item" type="button">Another
                                            action</button>
                                        <button class="dropdown-item" type="button">Something else
                                            here</button>
                                    </div>
                                </div>
                            </div>
                            -->
                        </div>

                        <div class="input_box brief_input_box">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input grade-checkbox" type="checkbox" name="grades[]" id="gradeCheckbox7" value="7th">
                                <label class="form-check-label" for="gradeCheckbox7">7th</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input grade-checkbox" type="checkbox" name="grades[]" id="gradeCheckbox8" value="8th">
                                <label class="form-check-label" for="gradeCheckbox8">8th</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input grade-checkbox" type="checkbox" name="grades[]" id="gradeCheckbox9" value="9th">
                                <label class="form-check-label" for="gradeCheckbox9">9th</label>
                            </div>
                        </div>

                        <!-- 
                        <div class="input_box brief_input_box">
                            <div class="label_box brief_label_box id_label_box">
                                <label class="label">Class</label>
                                <div class="dropdown">
                                    <button class="input_dropdown" type="button" id="dropdownMenu2"
                                        data-bs-toggle="dropdown" aria-haspopup="true"
                                        aria-expanded="false">
                                        Select classes
                                        <img src="<?php // echo $treks_src ?>/assets/img/down-arrow.svg" alt="logo" />
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenu2">
                                        <button class="dropdown-item" type="button">Action</button>
                                        <button class="dropdown-item" type="button">Another
                                            action</button>
                                        <button class="dropdown-item" type="button">Something else
                                            here</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        -->

                        <div class="btn_box">
                            <button class="btn" type="button" data-bs-dismiss="modal"
                                aria-label="Close">Cancel</button>
                            <button class="btn" id="saveTeacherBtn"><span class="teacher-action">Add</span></button>
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

        var teacherModal = document.getElementById('teacherModal');
        teacherModalObj = new bootstrap.Modal(teacherModal);
        window.teacherModalObj = teacherModalObj;

        jQuery("#addTeacherModal").on('click', function() {
            teacherModalObj.show();
        });

        let teacherForm = jQuery("#teacherForm");
        jQuery(teacherForm).on('submit', function(e) {
            e.preventDefault();
            jQuery("#saveTeacherBtn").attr("disabled", "disabled");
            let beforeText = jQuery("#saveTeacherBtn").text();
            jQuery("#saveTeacherBtn").html(`<i class="fa fa-spinner fa-spin"></i> ` + beforeText);
            const formData = new FormData(e.target);
            $.ajax({
                method: "POST",
                enctype: 'multipart/form-data',
                url: apiUrl + "teachers/save",
                data: formData,
                processData: false,
                contentType: false,
                cache: false,
            }).done(function( response ) {
                jQuery('#teacherForm .form-control').removeClass('is-invalid');
                teacherModalObj.hide();
            }).fail(function (response) {
                jQuery('#teacherForm .form-control').removeClass('is-invalid');
                if (response.responseJSON !== undefined) {
                    Object.keys(response.responseJSON.data.params).forEach(element => {
                        jQuery('#teacherModal input[name="' + element + '"]').addClass('is-invalid');
                        jQuery('#teacherModal textarea[name="' + element + '"]').addClass('is-invalid');
                    });
                }
                jQuery("#saveTeacherBtn").text(beforeText);
                jQuery("#saveTeacherBtn").removeAttr("disabled");
            });
        
        });

        teacherModal.addEventListener('hide.bs.modal', function (event) {
            jQuery("#teacher_post_id").val(0);
            jQuery('#teacherModal #about').val("");
            jQuery('#teacherModal #lxp_first_name').val("");
            jQuery('#teacherModal #lxp_last_name').val("");
            jQuery('#teacherModal #lxp_email').val("");
            jQuery('#teacherModal #lxp_inputEmailDefault').val("");
            jQuery('#teacherModal #lxp_password').val("");
            jQuery('.teacher-action').text("Add");
            jQuery('.teacher-action-head').text("New");
            window.location.reload();
        });
    });

    function onTeacherEdit(teacher_id) {
        jQuery("#teacher_post_id").val(teacher_id);
        jQuery(".teacher-action").text("Update");
        jQuery(".teacher-action-head").text("Update");
        
        let host = window.location.hostname === 'localhost' ? window.location.origin + '<?php echo WORDPRESS_HOST; ?>' : window.location.origin;
        let apiUrl = host + '/wp-json/lms/v1/';

        $.ajax({
            method: "POST",
            enctype: 'multipart/form-data',
            url: apiUrl + "teachers",
            data: {teacher_id}
        }).done(function( response ) {
            let teacher = response.data.teacher;
            let admin = response.data.admin.data;
            jQuery('#teacherForm .form-control').removeClass('is-invalid');
            jQuery('#teacherModal #about').val(teacher.post_content);
            jQuery('#teacherModal #lxp_first_name').val(admin.first_name);
            jQuery('#teacherModal #lxp_last_name').val(admin.last_name);
            jQuery('#teacherModal #lxp_email').val(admin.user_email);
            jQuery('#teacherModal #lxp_inputEmailDefault').val(admin.user_email);
            
            if (teacher.grades) {
                teacher.grades.forEach(grade => jQuery('#teacherModal input.grade-checkbox[value=' + grade +']').prop('checked', true));
            }
            teacherModalObj.show();
        }).fail(function (response) {
            console.error("Can not load teacher");
        });
    }
    
</script>