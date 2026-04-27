<?php
    global $treks_src;
    $classes = $args['classes'];
    $other_groups = $args['other_groups'];
    $teacher_post = $args['teacher_post'];
?>

<div class="modal fade classes-modal" id="groupModal" tabindex="-1" aria-labelledby="groupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered class-modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-header-title">
                    <h2 class="modal-title" id="groupModalLabel"><span id="small-group-action-heading">New</span> Group</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">                
                <div class="alert alert-danger invalid-feedback-sg_student_ids" role="alert" style="display: none;">
                    Please select at least one student.
                </div>
                <form class="row g-3" id="groupForm">
                    <input type="hidden" name="group_teacher_id" id="group_teacher_id" value="<?php echo $teacher_post->ID; ?>" />
                    <input type="hidden" name="group_post_id" id="group_post_id" value="0" />
                    <input type="hidden" name="group_type" id="group_type" value="classes" />
                    <input type="hidden" name="classes_other_group_name" id="classes_other_group_name" value="" />
                    <div class="personal_box">
                        <!-- Left Class box -->
                        <div class="class-information">
                            <p class="personal-text">Group information</p>
                            <div class="search_box">
                                <label class="trek-label">Name</label>
                                <input type="text" class="form-control period-select" value="" id="group_name" name="group_name" />
                            </div>
                            <div class="search_box">
                                <label class="trek-label">Description</label>
                                <textarea class="period-select form-control" id="group_description" name="group_description"></textarea>
                            </div>
                        </div>
                        <!-- End Left Class box -->

                        <!-- Vertical Line -->
                        <div class="vertical-line"></div>

                        <!-- Right Class box -->
                        <div class="class-information class-information">
                            <p class="personal-text">Add Students</p>
                            <!-- Select Grade -->
                            <div class="search_box">
                                <label class="trek-label">Classes/Groups</label>
                                <select onChange="fetch_class_students()" class="form-select form-control" aria-label="Default select example" name="classes_other_group" id="classes_other_group">
                                    <option value="0">Select Classes/Group</option>
                                    <optgroup value="classes" label="Classes">
                                        <?php
                                            foreach ($classes as $class) {
                                        ?>
                                            <option value="<?php echo $class->ID; ?>"><?php echo $class->post_title; ?></option>
                                        <?php        
                                            }
                                        ?>
                                    </optgroup>
                                    <optgroup value="other_group" label="Group">
                                        <?php
                                            foreach ($other_groups as $other_group) {
                                        ?>
                                            <option value="<?php echo $other_group->ID; ?>"><?php echo $other_group->post_title; ?></option>
                                        <?php        
                                            }
                                        ?>
                                    </optgroup>
                                </select>
                            </div>
                            <!-- Select Students -->
                            <div class="search_box">
                                <label class="trek-label">Students</label>
                                <div class="dropdown period-box">
                                    <button class="input_dropdown dropdown-button second-drop-button" type="button"
                                        id="studentsDD" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <span id="select-students-label">Select students</span>
                                        <img class="rotate-arrow" src="<?php echo $treks_src; ?>/assets/img/down-arrow.svg" alt="logo" />
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="studentsDD">
                                        <!-- Select All -->
                                        <button class="dropdown-item dropdown-item2 practice-button">
                                            <!-- Select Student -->
                                            <div class="time-date-box class-student-box">
                                                <input class="form-check-input " type="checkbox" id="select-all-students" id="checkbox" onChange="select_students(this)"/>
                                                <div class="tags-body-detail">
                                                    <p class="select-all">Select All</p>
                                                </div>
                                            </div>
                                        </button>
                                        <div class="scroll-box">
                                            <div id="students-container"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>                            
                        </div>
                    </div>
                    <!-- Button Section -->
                    <div class="input_section">
                        <div class="btn_box class_btns">
                            <button class="btn" type="button" data-bs-dismiss="modal" aria-label="Close">Cancel</button>
                            <button class="btn" id="small-group-action">Add</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script type="text/javascript">
let host = window.location.hostname === 'localhost' ? window.location.origin + '<?php echo WORDPRESS_HOST; ?>' : window.location.origin;
let apiUrl = host + '/wp-json/lms/v1/';
window.selected_students_ids = [];
window.selected_sg_students_ids = [];

function fetch_class_students() {
    var class_id = jQuery('#classes_other_group :selected').val();
    jQuery("#select-students-label").text("Select students");
    jQuery("#select-all-students").prop('checked', false);
    $.ajax({
        method: "POST",
        enctype: 'multipart/form-data',
        url: apiUrl + "class/students",
        data: {class_id}
    }).done(function( response ) {
        window.class_students = response.data.students;
        let class_students_dd_html = window.class_students.map(student => class_student_dd_html(student)).join('\n');
        jQuery('#students-container').html(class_students_dd_html);
        class_students_select_event_init();
        if ( window.selected_sg_students_ids.length > 0 ) {
            onStudentsCheckboxSelect();
        }
    }).fail(function (response) {
        console.error("Can not load students");
    });
}

function class_student_dd_html(student) {
    // Handle edit case
    let students_id_index = ( window.selected_sg_students_ids.length > 0 ) ? jQuery.inArray(student.post.ID.toString(), window.selected_sg_students_ids) : 0;
    let student_id = ( students_id_index >= 0 ) ? window.selected_sg_students_ids[students_id_index] : 0; 
    let checked = ( student_id == student.post.ID ) ? 'checked' : '';

    return `
    <button class="dropdown-item dropdown-item2 practice-button">
        <div class="time-date-box class-student-box">
            <input class="form-check-input select-sg-student-check" type="checkbox" value="` + student.post.ID + `" id="student_id_` + student.post.ID + `" name="sg_student_ids[]" ` + checked + `/>
            <img src="<?php echo $treks_src; ?>/assets/img/profile-icon.png" alt="logo" />
            <div class="tags-body-detail">
                <p class="student-name">` + student.user.display_name + `</p>
            </div>
        </div>
    </button>
    `;
}

function class_students_select_event_init() {
    jQuery("input[name='sg_student_ids[]']").on('change', function(e) {
        onStudentsCheckboxSelect();
    });
} 

function onStudentsCheckboxSelect() {
    window.selected_students_ids = jQuery("input[name='sg_student_ids[]']:checked").get().map(student_checkbox => jQuery(student_checkbox).val());
    let selected_students_label = window.selected_students_ids.length > 0 ? window.selected_students_ids.length : "Select students";
    jQuery("#select-students-label").text(selected_students_label);
    set_student_logos();
}

function set_student_logos() {
    let logos_html = window.selected_students_ids.map(student_id => `<img class="student-logo" src="<?php echo $treks_src; ?>/assets/img/profile-icon.png" alt="logo" />`).join('\n');
    jQuery('.select-students-logos').html(logos_html);
}

function select_students(element) {    
    if (element.checked) {
        jQuery("input[name='sg_student_ids[]']").prop('checked', true);
    } else {
        jQuery("input[name='sg_student_ids[]']").prop('checked', false);
    }
    onStudentsCheckboxSelect();
}

function onGroupEdit(group_post_id) {
    jQuery("#group_post_id").val(group_post_id);
    jQuery("#small-group-action-heading").text("Update");
    jQuery("#small-group-action").text("Update");
    $.ajax({
        method: "POST",
        enctype: 'multipart/form-data',
        url: apiUrl + "group",
        data: {group_post_id}
    }).done(function( response ) {
        let group_record = response.data.group;
        window.selected_sg_students_ids = group_record.lxp_group_student_ids;
        jQuery('#groupForm .form-control').removeClass('is-invalid');
        jQuery(".alert-danger").hide();
        jQuery('#groupModal #group_name').val(group_record.post_title);
        jQuery('#groupModal #group_description').val(group_record.post_content);
        jQuery('#groupModal #classes_other_group').val(group_record.lxp_class_group_id).change();
        groupModalObj.show();
    }).fail(function (response) {
        console.error("Can not load Groups");
    });
}

    jQuery(document).ready(function() {

        var groupModal = document.getElementById('groupModal');
        groupModalObj = new bootstrap.Modal(groupModal);
        window.groupModalObj = groupModalObj;
        
        groupModal.addEventListener('hide.bs.modal', function (event) {
            
            jQuery("#group_post_id").val(0);
            jQuery("#group_type").val('classes');
            window.location.reload();
        });

        let groupForm = jQuery("#groupForm");
        jQuery(groupForm).on('submit', function(e) {
            e.preventDefault();

            jQuery("#small-group-action").attr("disabled", "disabled");
            let beforeText = jQuery("#small-group-action").text();
            jQuery("#small-group-action").html(`<i class="fa fa-spinner fa-spin"></i> ` + beforeText);

            jQuery(".alert-danger").hide();
            var group_type = jQuery('#classes_other_group :selected').parent().attr('value');
            var name = jQuery('#classes_other_group :selected').text();
            jQuery("#group_type").val(group_type);
            jQuery("#classes_other_group_name").val(name);
            const formData = new FormData(e.target);
            $.ajax({
                method: "POST",
                enctype: 'multipart/form-data',
                url: apiUrl + "group/save",
                data: formData,
                processData: false,
                contentType: false,
                cache: false,
            }).done(function( response ) {
                jQuery('#groupForm .form-control').removeClass('is-invalid');
                groupModalObj.hide();
            }).fail(function (response) {
                jQuery('#groupForm .form-control').removeClass('is-invalid');
                if (response.responseJSON !== undefined && response.responseJSON.code === "rest_missing_callback_param") {
                    response.responseJSON.data.params.forEach(element => {
                        jQuery(".invalid-feedback-" + element).show();
                    });
                }
                
                if (response.responseJSON !== undefined) {
                    Object.keys(response.responseJSON.data.params).forEach(element => {
                        console.log('element >>> ', element);
                        jQuery('#groupModal input[name="' + element + '"]').addClass('is-invalid');
                        jQuery('#groupModal textarea[name="' + element + '"]').addClass('is-invalid');
                        jQuery('#groupModal select[name="' + element + '"]').addClass('is-invalid');
                    });
                }
                jQuery("#small-group-action").text(beforeText);
                jQuery("#small-group-action").removeAttr("disabled");
            });
        
        });
    });
</script>