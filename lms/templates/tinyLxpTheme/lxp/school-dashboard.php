<?php
    $school_post = lxp_get_user_school_post();
    $teachers = lxp_get_school_teachers($school_post->ID);

    $school_students = lxp_get_school_students($school_post->ID);
    $students = array();
    $is_teacher_assignment_needed = false;
    if (isset($_GET['teacher_id']) && $_GET['teacher_id'] != 0) {
        $teacher_id = $_GET['teacher_id'];
        $students = lxp_get_school_teacher_students($teacher_id);
    } else {
        // filter out students who are not assigned to any teacher
        $students = array_filter($school_students, function ($student) {
            return !get_post_meta($student->ID, 'lxp_teacher_id', true);
        });
        // if all students are already assigned to teachers then show all students
        if (count($students) == 0) {
            $students = $school_students;
        } else {
            $is_teacher_assignment_needed = true;
        }
    }

    $school_teachers_ids = array_map(function ($teacher) { return $teacher->ID; }, $teachers);
    $assignments = lxp_get_all_teachers_assignments($school_teachers_ids);
    //$classes = lxp_get_all_teachers_classes($school_teachers_ids);
    $default_classes = lxp_get_teacher_all_default_classes($school_teachers_ids);
    $classes = lxp_get_all_teachers_group_by_type($school_teachers_ids, 'classes');
    $other_groups = lxp_get_all_teachers_group_by_type($school_teachers_ids, 'other_group');
    $countClassesOtherGroup = count($default_classes) + count($classes) + count($other_groups);
    $groups = lxp_get_all_teachers_groups($school_teachers_ids);
    $classes = array_merge($default_classes, $classes);

    $district_post = get_post(get_post_meta($school_post->ID, 'lxp_school_district_id', true));
    $district_type = get_post_meta($district_post->ID, 'lxp_district_type', true);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>School Dashboard</title>
    <link href="<?php echo $treks_src; ?>/style/main.css" rel="stylesheet" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/header-section.css" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/schoolDashboard.css" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/schoolAdminTeachers.css" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/addNewTeacherModal.css" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/calendar.css" />
    <link href="<?php echo $treks_src; ?>/style/treksstyle.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous" />
    <style type="text/css">
        .tab-content > .active {
            display: block !important;
        }
        
        label.add-heading {
         cursor: pointer;
        }
    </style>
</head>

<body>

    <!-- Header Section -->
    <nav class="navbar navbar-expand-lg bg-light">
        <div class="container-fluid">
            <?php include $livePath.'/trek/header-logo.php'; ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <div class="navbar-nav me-auto mb-2 mb-lg-0">
                    <div class="header-logo-search">
                        <!-- searching input -->
                        <div class="header-search">
                            <img src="<?php echo $treks_src; ?>/assets/img/header_search.svg" alt="svg" />
                            <input placeholder="Search" />
                        </div>
                    </div>
                </div>
                <div class="d-flex" role="search">
                    <div class="header-notification-user">
                        <?php include $livePath.'/trek/user-profile-block.php'; ?>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Nav Section -->
    <section class="main-container">
        <nav class="nav-section">
            <?php include $livePath.'/lxp/admin-nav.php'; ?>
        </nav>
    </section>

    <!-- Welcome: section-->
    <section class="welcome-section">

        <!-- Welcome: heading-->
        <div class="welcome-content">
            <h2 class="welcome-heading">Welcome <?php echo $school_post->post_title; ?>!</h2>
            <p class="welcome-text">Here's how your academic system looks like</p>
        </div>

        <!-- Total Schools: section-->
        <section class="school-section">
            <section class="school-cards-section">
                <div class="cards-box">
                    <div class="card">
                        <img src="<?php echo $treks_src; ?>/assets/img/verified-user.svg" alt="logo" />
                        <h3 class="numbers-heading"><?php echo count($teachers); ?></h3>
                        <p class="name-text">Teachers</p>
                    </div>
                    <div class="card">
                        <img src="<?php echo $treks_src; ?>/assets/img/classes.svg" alt="logo" />
                        <h3 class="numbers-heading"><?php echo $countClassesOtherGroup; ?></h3>
                        <p class="name-text">Classes & Groups</p>
                    </div>
                    <div class="card">
                        <img src="<?php echo $treks_src; ?>/assets/img/groups.svg" alt="logo" />
                        <h3 class="numbers-heading"><?php echo count($groups); ?></h3>
                        <p class="name-text">Groups</p>
                    </div>
                    <div class="card">
                        <img src="<?php echo $treks_src; ?>/assets/img/user.svg" alt="logo" />
                        <h3 class="numbers-heading"><?php echo count($school_students); ?></h3>
                        <p class="name-text">Students</p>
                    </div>
                    <div class="card">
                        <img src="<?php echo $treks_src; ?>/assets/img/assignment.svg" alt="logo" />
                        <h3 class="numbers-heading"><?php echo count($assignments); ?></h3>
                        <p class="name-text">Assignments</p>
                    </div>

                </div>

                <!-- Table Section -->
                <section class="recent-treks-section-div table-section">
                    <nav class="nav-section treks_nav table_tabs">
                        <ul class="treks_ul" id="myTab" role="tablist">
                            <li>
                                <button class="nav-link" data-bs-toggle="tab"
                                    data-bs-target="#teacher-tab-content" type="button" role="tab" aria-controls="teacher-tab-content"
                                    aria-selected="false">
                                    Teachers
                                </button>
                            </li>
                            <li>
                                <button class="nav-link" id="completed-tab" data-bs-toggle="tab"
                                    data-bs-target="#student-tab-content" type="button" role="tab"
                                    aria-controls="student-tab-content" aria-selected="true">
                                    Students
                                </button>
                            </li>
                            <li>
                                <button class="nav-link" id="to-tab" data-bs-toggle="tab"
                                    data-bs-target="#class-tab-content" type="button" role="tab"
                                    aria-controls="class-tab-content" aria-selected="false">
                                    Classes
                                </button>
                            </li>
                            <li>
                                <button class="nav-link" id="og-tab" data-bs-toggle="tab"
                                    data-bs-target="#other-group-tab-content" type="button" role="tab"
                                    aria-controls="other-group-tab-content" aria-selected="false">
                                    Group
                                </button>
                            </li>
                            <li>
                                <button class="nav-link" id="sg-tab" data-bs-toggle="tab"
                                    data-bs-target="#group-tab-content" type="button" role="tab"
                                    aria-controls="group-tab-content" aria-selected="false">
                                    Groups
                                </button>
                            </li>
                        </ul>
                    </nav>
                    <div class="tab-content">
                        <?php
                            $args['students'] = $students;
                            $args['teachers'] = $teachers;
                            $args['is_teacher_assignment_needed'] = $is_teacher_assignment_needed;
                            $args['classes'] = $classes;
                            $args['other_groups'] = $other_groups;
                            $args['groups'] = $groups;
                            $args['district_type'] = $district_type;

                            include $livePath.'/lxp/school-dashboard-teachers-tab.php';
                            include $livePath.'/lxp/school-dashboard-students-tab.php';
                            include $livePath.'/lxp/school-dashboard-classes-tab.php';
                            include $livePath.'/lxp/school-dashboard-other-groups-tab.php';
                            include $livePath.'/lxp/school-dashboard-groups-tab.php';
                        ?>

                    </div>
                </section>
            </section>
            <!-- Recent TREKs -->
            <section class="recent-treks-section" style="width: 100%;">
                <div class="recent-treks-section-div">
                    <!--  TREKs header-->
                    <div class="recent-treks-header section-div-header">
                        <h2>Top Courses</h2>
                        <div>
                            <a href="#">See All</a>
                        </div>
                    </div>
                </div>
                <!-- Assignment section -->
                <section class="recent-treks-section assignment-section">
                    <div class="recent-treks-section-div">
                        <!--  Assignment header-->
                        <div class="recent-treks-header section-div-header">
                            <h2>Assignments</h2>
                        </div>
                        <div class="section-div-header">
                            <h2 class="to-do">To Do</h2>
                        </div>
                        <div class="section-div-header">
                            <h2 class="progress-heading">In Progress</h2>
                        </div>
                </section>
            </section>
        </section>
    </section>

    <script src="https://code.jquery.com/jquery-3.6.3.js"
        integrity="sha256-nQLuAZGRRcILA+6dMBOvcRh5Pe310sBpanc6+QBmyVM=" crossorigin="anonymous"></script>
    <script
        src="<?php echo $treks_src; ?>/js/Animated-Circular-Progress-Bar-with-jQuery-Canvas-Circle-Progress/dist/circle-progress.js"></script>
    <script src="<?php echo $treks_src; ?>/js/custom.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4"
        crossorigin="anonymous"></script>
    
    <?php
        $args['school_post'] = $school_post;
        $args['teachers'] = $teachers;
        if (isset($district_type) && $district_type == 'edlink') {
            $args['district_post'] = $district_post;
            $args['role'] = 'school';
            include $livePath.'/lxp/edlink/teacher-modal.php';
            include $livePath.'/lxp/edlink/student-modal.php';
        } else {
            include $livePath.'/lxp/school-teacher-modal.php';
            include $livePath.'/lxp/school-student-modal.php';
            include $livePath.'/lxp/admin-student-assign-modal.php';
        }
    ?>
    
    <script type="text/javascript">
        jQuery(document).ready(function() {
            jQuery('.nav-link').on('show.bs.tab', function (event) {
                localStorage.setItem("school_dashboard_tab", jQuery(event.target).attr('data-bs-target'));
            });

            let current_tab = localStorage.getItem("school_dashboard_tab");
            if (current_tab) {
                let tabEl = jQuery('button.nav-link[data-bs-target="' + current_tab + '"]');
                var tab = new bootstrap.Tab(tabEl);
                tab.show();
            } else {
                let tabEl = jQuery('button.nav-link[data-bs-target="' + '#teacher-tab-content' + '"]');
                var tab = new bootstrap.Tab(tabEl);
                tab.show();
            }

            $('#teacher-drop-down').change(function() {
                var teacher_id = $(this).val();
                var url = new URL(window.location.href);
                url.searchParams.set('teacher_id', teacher_id);
                if (teacher_id == 0) {
                    url.searchParams.delete('teacher_id');
                }
                window.location = url.href;
            });
        });
    </script>

    
    <input type="hidden" name="school_admin_id_imp" id="school_admin_id_imp" value="<?php echo get_post_meta( $school_post->ID, 'lxp_school_admin_id', true ); ?>">
    <input type="hidden" name="teacher_school_id_imp" id="teacher_school_id_imp" value="<?php echo $school_post->ID; ?>">
    <script type="text/javascript">
        let host = window.location.hostname === 'localhost' ? window.location.origin + '<?php echo WORDPRESS_HOST; ?>' : window.location.origin;
        let apiUrl = host + '/wp-json/lms/v1/';

        jQuery("#import-teacher").on("change", function(e) {
            let formData = new FormData();
            formData.append('teacher_school_id', jQuery("#teacher_school_id_imp").val());
            formData.append('school_admin_id', jQuery("#school_admin_id_imp").val());
            formData.append('teachers', e.target.files[0]);
            $.ajax({
                method: "POST",
                enctype: 'multipart/form-data',
                url: apiUrl + "teachers/import",
                data: formData,
                processData: false,
                contentType: false,
                cache: false,
            }).done(function( response ) {
                jQuery("#import-teacher").val("");
                window.location.reload();
            }).fail(function (response) {
                jQuery("#import-teacher").val("");
                if (response.responseJSON) {
                    alert(response.responseJSON.data);
                }
            });
        });
    </script>
</body>

</html>