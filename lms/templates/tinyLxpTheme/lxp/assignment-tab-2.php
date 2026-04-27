<?php
global $treks_src, $trek_post;

$args = array(
    'posts_per_page'   => -1,
    'post_type'        => LP_COURSE_CPT,
    'order' => 'asc'
);
$courses = get_posts($args);
$course_id = ( isset($_GET['course']) && $_GET['course'] > 0 ) ? $_GET['course'] : 0;
if ( isset($course_id) && $course_id == 0 && isset($_GET['section']) && $_GET['section'] == 0 ) {
    $course_post = $courses[0];
    $course_id = $course_post->ID;
}

$course_post = get_post($course_id);
$select_course_title = !boolval($course_post) ? "Select a Course" : $course_post->post_title;
$lxp_section = isset($_GET['section']) ? $_GET['section'] : '';
// ?course=72&section=Index%3A%20Technical%20Evolution%20of%20Filmmaking
$lessons = lxp_get_lessons_by_course($course_id);
?>
<div class="tab-pane fade show" id="step-2-tab-pane" role="tabpanel" aria-labelledby="setp-2-tab" tabindex="1">

    <!-- New Assignment Calendar Section -->
    <section class="calendar-container select-assignment-section">

        <!-- New Assignment -->
        <div class="select-trek-box" id="new_assignment_data_1">
            <h3 class="new-assignment-heading">New Assignment</h3>
            <div class="select-calendar-box">
                <h4 class="new-assignment-heading select-calendar-heading">Calendar</h4>
                    <a href='javascript:void();' onClick='set_date_time();'>
                    <div class="calendar-time-date">
                        <img src="<?php echo $treks_src; ?>/assets/img/clock-outline.svg" alt="logo" />                    
                        <div class="time-date-box days-box">
                            <div class="time-date-box">
                                <p class="date-time"><span id="assignment_day"></span>, <span id="assignment_month"></span> <span id="assignment_date"></span></p>
                                <p class="date-time" id="assignment_time_start"></p>
                                <p class="date-time to-text">To</p>
                                <p class="date-time"><span id="assignment_day_end"></span>, <span id="assignment_month_end"></span> <span id="assignment_date_end"></span></p>
                                <p class="date-time" id="assignment_time_end"></p>
                            </div>
                            <!-- <label class="to-text all-day-label">
                                <input class="form-check-input" type="checkbox" />
                                All day
                            </label> -->
                        </div>                    
                    </div>
                </a>
                
                <?php if (boolval($course_post)) { ?>
                    <!-- TREK -->
                    <h4 class="new-assignment-heading select-calendar-heading third-calendar-heading">Course</h4>
                    <div class="third-trek-box">
                        <div class="third-card-box">
                            <span class="select-course-image">
                                <?php
                                    if ( has_post_thumbnail( $course_post->ID ) ) {
                                        echo get_the_post_thumbnail($course_post->ID, array(50,50), array( 'class' => 'rounded' )); 
                                    } else {
                                ?>
                                    <img width="50" src="<?php echo $treks_src; ?>/assets/img/tr_main.jpg" class="rounded wp-post-image" /> 
                                <?php        
                                    }
                                ?>
                            </span>
                            <p class="select-course-title"><?php echo $course_post->post_title ?></p>
                        </div>
                    </div>
                <?php } ?>                    
                <!-- horizontal line -->
                <div class="horizontal-line"></div>
                    <!-- Lessons -->
                    <h4 class="new-assignment-heading select-calendar-heading">Lessons</h4>
                    <div id="lxp_lessons_view_container">                    
                </div> 
                    
                    <!-- horizontal line -->
                <div class="horizontal-line"></div>

                <!-- Number of Students -->
                <h4 class="new-assignment-heading select-calendar-heading">Students</h4>

                <!-- Student Period and Grade-->
                <div class="time-date-box days-box">
                    <div class="time-date-box">
                        <!-- <p class="date-time student-period">Science 3rd period</p>
                        <p class="date-time student-period">5th grade</p> -->
                        <p class="date-time student-period"><span class="student_count students_count_label">0</span> students</p>
                    </div>
                </div>

                <!-- Select Student Profile logos -->
                <div class="select-students-logos">
                    <!-- <img class="" src="<?php //echo $treks_src; ?>/assets/img/class-student.svg" alt="logo" />
                    <img class="student-logo" src="<?php //echo $treks_src; ?>/assets/img/class-student.svg" alt="logo" />
                    <img class="student-logo" src="<?php //echo $treks_src; ?>/assets/img/class-student.svg" alt="logo" />
                    <img class="student-logo" src="<?php //echo $treks_src; ?>/assets/img/class-student.svg" alt="logo" />
                    <img class="student-logo" src="<?php //echo $treks_src; ?>/assets/img/class-student.svg" alt="logo" />
                    <img class="student-logo" src="<?php //echo $treks_src; ?>/assets/img/class-student.svg" alt="logo" />
                    <img class="student-logo" src="<?php //echo $treks_src; ?>/assets/img/class-student.svg" alt="logo" />
                    <img class="student-logo" src="<?php //echo $treks_src; ?>/assets/img/class-student.svg" alt="logo" /> -->
                </div>

            </div>
        </div>

        <!-- Vertical Line -->
        <div class="tab-vertical-line"></div>

        <!-- Assign Content -->
        <div class="select-trek-box assign-content">
            <h3 class="new-assignment-heading assign-heading">Assign Content</h3>
            <p class="date-time assign-text">Select a content to assign</p>
            <div class="search_box">
                <input type="hidden" name="course_id" id="course_id" value="<?php echo $course_id ?>" />
                <label class="trek-label">Course</label>
                <div class="dropdown period-box">
                    <button class="input_dropdown dropdown-button" type="button" id="dropdownMenu2"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="select-course-title"><?php echo $select_course_title; ?></span>
                        <img class="rotate-arrow" src="<?php echo $treks_src; ?>/assets/img/down-arrow.svg" alt="logo" />
                    </button>
                    
                    <div class="dropdown-menu" aria-labelledby="dropdownMenu2">
                        
                        <?php foreach ($courses as $course) { ?>
                            <button class="dropdown-item dropdown-item2 dropdown-class" onClick="set_course_id(<?php echo $course->ID; ?>)" >
                                <div class="third-card-box">
                                    <?php
                                        if ( has_post_thumbnail( $course->ID ) ) {
                                            echo get_the_post_thumbnail($course->ID, array(50,50), array( 'class' => 'rounded', 'id' => 'post_image_'.$course->ID )); 
                                        } else {
                                    ?>
                                    <img id="post_image_<?php echo $course->ID; ?>" width="50" src="<?php echo $treks_src; ?>/assets/img/tr_main.jpg" class="rounded wp-post-image" /> 
                                    <?php        
                                        }
                                    ?>
                                    <p class="interdependence-text">
                                        <?php echo $course->post_title; ?>
                                    </p>
                                </div>
                            </button>
                        <?php } ?>
                    </div>
                    <div class="invalid-feedback" id="lxp_course_select_error">
                        Please select Course
                    </div>
                </div>                
            </div>
            <!-- Select a lxp course sections -->
            <div class="search_box">                
                <div class="search_box">
                    <label class="trek-label">Sections</label>
                    <div class="dropdown period-box">
                        <button class="input_dropdown dropdown-button second-drop-button" type="button"
                            id="lxpSectionsDD" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span id="select-lxp-sections-label">Select Sections</span>
                            <img class="rotate-arrow" src="<?php echo $treks_src; ?>/assets/img/down-arrow.svg" alt="logo" />
                        </button>
                        <div class="dropdown-menu" aria-labelledby="lxpSectionsDD">
                            <!-- Select All -->
                            <button class="dropdown-item dropdown-item2 practice-button">
                                <!-- Select Student -->
                                <div class="time-date-box class-student-box">
                                    <input class="form-check-input " type="checkbox" id="select-all-lxp-sections" onchange="select_lxp_sections(this)" id="checkbox" />
                                    <div class="tags-body-detail">
                                        <p class="select-all">Select All</p>
                                    </div>
                                </div>
                            </button>
                            <div class="scroll-box">
                                <div id="lxp-sections-container"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="invalid-feedback" id="lxp_sections_select_error">
                    Please Select Sections
                </div>
            </div>
            </br>
            <!-- Select a lxp course sections lessons -->
            <div class="search_box">
                <div class="search_box">
                    <label class="trek-label">Lessons</label>
                    <div class="dropdown period-box">
                        <button class="input_dropdown dropdown-button second-drop-button" type="button"
                            id="lessonsDD" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span id="select-lxp-lessons-label">Select Lessons</span>
                            <img class="rotate-arrow" src="<?php echo $treks_src; ?>/assets/img/down-arrow.svg" alt="logo" />
                        </button>
                        <div class="dropdown-menu" aria-labelledby="lessonsDD">
                            <!-- Select All -->
                            <button class="dropdown-item dropdown-item2 practice-button">
                                <!-- Select Student -->
                                <div class="time-date-box class-student-box">
                                    <input class="form-check-input " type="checkbox" id="select-all-lxp-lessons" onchange="select_lxp_lessons(this)" id="checkbox" />
                                    <div class="tags-body-detail">
                                        <p class="select-all">Select All</p>
                                    </div>
                                </div>
                            </button>
                            <div class="scroll-box">
                                <div id="lxp-lessons-container"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="invalid-feedback" id="lxp_lessons_select_error">
                    Please Select Lessons
                </div>
            </div>
        </div>
    </section>

    <!-- Button Section -->
    <section class="calendar-container select-assignment-section btns-container">
        <div class="input_section">
            <div class="btn_box profile_buttons">
                <button class="btn profile_btn" type="button" aria-label="Close" onclick="go_previous()">Previous</button>
                <button class="btn profile_btn" onclick="go_step_3()">Continue</button>
            </div>
        </div>
    </section>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
    host = window.location.hostname === 'localhost' ? window.location.origin + '<?php echo WORDPRESS_HOST; ?>' : window.location.origin;
    apiUrl = host + '/wp-json/lms/v1/';
    window.selected_lxp_sections = [];
    window.selected_lxp_lessons = [];
    window.courses = <?php echo json_encode($courses); ?>;
    window.lessons = <?php echo json_encode($lessons); ?>;
    var course_id = <?php echo $course_id; ?>;
    window.lxp_section = '<?php echo $lxp_section; ?>';

    if ( course_id > 0 ) {
        set_course_id(course_id, true);
    }

    function set_course_id(course_id, assign_btn_status = false) {
        if ( assign_btn_status ) {
            jQuery('#course_id').val(course_id);
            let course_record = window.courses.filter(course => course.ID == course_id)[0];            
            jQuery('.select-course-title').text(course_record.post_title);
            fetch_lxp_sections(course_id);
        } else if (jQuery('#course_id').val() != course_id) {
            Swal.fire({
              title: 'Do you want to change the Course?',
              showCancelButton: true,
              confirmButtonText: 'Yes',
            }).then((result) => {
              if (result.isConfirmed) {
                jQuery('#course_id').val(course_id);
                let course_record = window.courses.filter(course => course.ID == course_id)[0];
                jQuery('.select-course-title').text(course_record.post_title);
                jQuery('.select-course-image').html(`<img width="50" class="rounded wp-post-image" src="`+jQuery('#post_image_'+course_record.ID).attr('src')+`" alt="logo" />`);
                jQuery('#select-lxp-sections-label').text('Select Sections');
                jQuery('#lxp_sections_select_error').hide();
                jQuery('#select-all-lxp-sections').prop('checked', false);
                jQuery('#select-lxp-lessons-label').text('Select Lessons');
                jQuery('#select-all-lxp-lessons').prop('checked', false);
                jQuery('#lxp_lessons_select_error').hide();
                jQuery('#lxp_lessons_view_container').html('');
                fetch_lxp_sections(course_id);
                fetch_lxp_lesson_by_course(course_id);
              }
            })
        }
    }

    function fetch_lxp_lesson_by_course(course_id) {
        $.ajax({
            method: "POST",
            enctype: 'multipart/form-data',
            url: apiUrl + "course/lxp_lessons",
            data: {course_id}
        }).done(function( response ) {
            window.lessons = response.data.lxp_lessons;
        }).fail(function (response) {
            console.error("Can not load lessons by course");
        });
    }

    function fetch_lxp_sections(course_id) {
        $.ajax({
            method: "POST",
            enctype: 'multipart/form-data',
            url: apiUrl + "course/lxp_sections",
            data: {course_id}
        }).done(function( response ) {
            window.lxp_sections = response.data.lxp_sections;
            let lxp_sections_html = window.lxp_sections.map(lxp_section => lxp_sections_dd_html(lxp_section)).join('\n');
            jQuery('#lxp-sections-container').html(lxp_sections_html);
            onLxpSectionsCheckboxSelect();
            lxp_sections_select_event_init();
        }).fail(function (response) {
            console.error("Can not load sections");
        });
    }

    function lxp_sections_dd_html(lxp_section) {
        let checked = ( window.lxp_section == lxp_section['section_id'] ) ? 'checked' : '';
        return `
        <button class="dropdown-item dropdown-item2 practice-button">
            <div class="time-date-box class-student-box">
                <input class="form-check-input" type="checkbox" value="` + lxp_section['section_id'] + `" id="lxp_sections_` + lxp_section['section_id'] + `" ` + checked + ` name="lxp_sections[]" />
                <img src="<?php echo $treks_src; ?>/assets/img/interdependence-logo.svg" alt="logo" />
                <div class="tags-body-detail">
                    <p class="lxp-sections">` + lxp_section['section_name'] + `</p>
                </div>
            </div>
        </button>
        `;
    }

    function lxp_sections_select_event_init() {
        jQuery("input[name='lxp_sections[]']").on('change', function(e) {            
            onLxpSectionsCheckboxSelect();
        });
    } 
    
    function select_lxp_sections(element) {        
        if (element.checked) {
            jQuery("input[name='lxp_sections[]']").prop('checked', true);
        } else {
            jQuery('#select-lxp-lessons-label').text('Select Lessons');
            jQuery('#select-all-lxp-lessons').prop('checked', false);
            jQuery("input[name='lxp_sections[]']").prop('checked', false);
        }
        onLxpSectionsCheckboxSelect();
    }

    function onLxpSectionsCheckboxSelect() {
        window.selected_lxp_sections = jQuery("input[name='lxp_sections[]']:checked").get().map(lxp_section_checkbox => jQuery(lxp_section_checkbox).val());        
        let selected_lxp_sections_label = window.selected_lxp_sections.length > 0 ? window.selected_lxp_sections.length : "Select Sections";
        jQuery("#select-lxp-sections-label").text(selected_lxp_sections_label);
        fetch_lxp_lessons(window.selected_lxp_sections);
    }

    function fetch_lxp_lessons(lxp_sections) {
        var course_id = jQuery('#course_id').val();
        $.ajax({
            method: "POST",
            enctype: 'multipart/form-data',
            url: apiUrl + "course/lxp_section/lessons",
            data: {course_id, lxp_sections}
        }).done(function( response ) {
            window.lxp_lessons = response.data.lxp_lessons;
            jQuery('#lxp-lessons-container').html('');
            for (var key in window.lxp_lessons) {
                // let lxp_lessons_html = lxp_lessons[key].map(lxp_lesson => lxp_lessons_dd_html(lxp_lesson)).join('\n');
                let lxp_lessons_html = lxp_lessons_dd_html(key, lxp_lessons[key]);
                jQuery('#lxp-lessons-container').append(lxp_lessons_html);                
                onLxpLessonsCheckboxSelect();
                lxp_lessons_select_event_init();
            }
            handle_lxp_lessons_html_view();
            onLxpLessonsCheckboxSelect();

        }).fail(function (response) {
            console.error("Can not load lessons");
        });
        
    }

    function lxp_lessons_dd_html(lxp_section, lxp_lessons) {
        var html = '';
        lxp_lessons.forEach(function(lxp_lesson) {
            var lesson_id = jQuery("[title='lesson-view-box-"+lxp_lesson['ID']+"']").attr('id');
            var checked = ( lesson_id > 0 && lesson_id == lxp_lesson['ID'] ) ? 'checked' : '';
            html += `
            <button class="dropdown-item dropdown-item2 practice-button">
                <div class="time-date-box">
                    <input class="form-check-input lesson-checkbox" type="checkbox" title="` + lxp_lesson['post_title'] + `" value="` + lxp_lesson['ID'] + `" id="lesson_id_` + lxp_lesson['ID'] + `" name="lesson_ids[]" ` + checked + ` section="`+lxp_section+`"/>                
                    <div class="tags-body-polygon">
                        <span>L</span>
                    </div>
                    <div class="tags-body-detail">
                        <p data-id="` + lxp_lesson['ID'] + `" id="lesson_body_id_` + lxp_lesson['ID'] + `" title="` + lxp_lesson['post_title'] + `" class="lesson-name">` + lxp_lesson['post_title'] + `</p>
                    </div>
                </div>
            </button>
            `;
        });
        return html;        
    }

    function lxp_lessons_select_event_init() {
        jQuery("input[name='lesson_ids[]']").on('change', function(e) {
            handle_lxp_lessons_html_view();
            onLxpLessonsCheckboxSelect();
        });
    }     

    function select_lxp_lessons(element) {
        if (element.checked) {
            jQuery("input[name='lesson_ids[]']").prop('checked', true);
        } else {
            jQuery("input[name='lesson_ids[]']").prop('checked', false);
        }
        handle_lxp_lessons_html_view();
        onLxpLessonsCheckboxSelect();
    }

    function handle_lxp_lessons_html_view() {
        var selectedLessonsHtml = jQuery("input[name='lesson_ids[]']:checked").get()
            .map( lesson_checked => lxp_lessons_view_html(lesson_checked.value) )
            .join("\n");
        jQuery("#lxp_lessons_view_container").html(selectedLessonsHtml);
    }
    
    function lxp_lessons_view_html(lesson_id) {
        var lxp_section = jQuery('#lesson_id_'+lesson_id).attr('section');
        var lxp_lesson_title = jQuery('#lesson_id_'+lesson_id).attr('title');
        return `
        <div id="lesson-view_` + lesson_id + `" lxpsection="`+lxp_section+`" class="`+lxp_section+`"><div id="` + lesson_id + `" class="third-trek-box practice-a-trek-box" title="lesson-view-box-` + lesson_id + `">
            <div class="tags-body practice-a-poly-body">
                <div class="tags-body-polygon">
                    <span>L</span>
                </div>
                <div class="tags-body-detail">
                    <span>` + lxp_lesson_title + `</span>
                </div>
            </div>
        </div></div>`;
    }

    function onLxpLessonsCheckboxSelect() {
        window.selected_lxp_lessons = jQuery("input[name='lesson_ids[]']:checked").get().map(lxp_lesson_checkbox => jQuery(lxp_lesson_checkbox).val());
        let selected_lxp_lessons_label = window.selected_lxp_lessons.length > 0 ? window.selected_lxp_lessons.length : "Select Lessons";
        jQuery("#select-lxp-lessons-label").text(selected_lxp_lessons_label);
    }

    function set_date_time() {
        jQuery('#set_date_time').val(1);
        bootstrap.Tab.getOrCreateInstance(document.querySelector('#step-1-tab')).show();
    }

    function go_step_3() {
        jQuery('#set_date_time').val(0);
        jQuery('#set_date_time_alert').hide();
        
        ok = true;
        if (!parseInt(jQuery('#course_id').val())) {
            jQuery('#lxp_course_select_error').show();
            ok = false;
        } else {
            jQuery('#lxp_course_select_error').hide();
        }

        let sections_count = jQuery("input[name='lxp_sections[]']:checked").length;
        if (sections_count) {
            jQuery('#lxp_sections_select_error').hide();    
        } else {
            jQuery('#lxp_sections_select_error').show();
            ok = false;
        }

        let lessons_count = jQuery("input[name='lesson_ids[]']:checked").length;
        if (lessons_count) {
            jQuery('#lxp_lessons_select_error').hide();    
        } else {
            jQuery('#lxp_lessons_select_error').show();
            ok = false;
        }
        
        if (ok) {
            bootstrap.Tab.getOrCreateInstance(document.querySelector('#step-3-tab')).show();
        }
    }
</script>