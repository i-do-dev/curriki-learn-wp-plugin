<?php
    $school_post = lxp_get_user_school_post();
    $teachers = lxp_get_school_teachers($school_post->ID);

    $students = array();
    $is_teacher_assignment_needed = false;
    if (isset($_GET['teacher_id']) && $_GET['teacher_id'] != 0) {
        $teacher_id = $_GET['teacher_id'];
        // $students = lxp_get_school_teacher_students($school_post->ID, $teacher_id);
        if (isset($_GET['inactive']) && $_GET['inactive'] == 'true') {
            $students = lxp_get_school_teacher_students_inactive($teacher_id);
        } else {
            $students = lxp_get_school_teacher_students_active($teacher_id);
        }
    } else {
        //$school_students = lxp_get_school_students($school_post->ID);
        if (isset($_GET['inactive']) && $_GET['inactive'] == 'true') {
            $school_students = lxp_get_school_students_inactive($school_post->ID);
        } else {
            $school_students = lxp_get_school_students_active($school_post->ID);
        }
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

    $district_post = get_post(get_post_meta($school_post->ID, 'lxp_school_district_id', true));
    $district_type = get_post_meta($district_post->ID, 'lxp_district_type', true);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" conte nt="width=device-width, initial-scale=1.0" />
    <title>School Admin / Students</title>
    <link href="<?php echo $treks_src; ?>/style/main.css" rel="stylesheet" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/header-section.css" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/schoolAdminTeachers.css" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/addNewTeacherModal.css" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/schoolDashboard.css" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/schoolAdminStudents.css" />

    <link href="<?php echo $treks_src; ?>/style/treksstyle.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous" />
    <style type="text/css">
        .add-teacher-box {
            display: block;
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
            <h2 class="welcome-heading">Students</h2>
            <p class="welcome-text">Student enrollment and registration management</p>
        </div>

        <!-- Total Schools: section-->
        <section class="school-section">
            <section class="school_teacher_cards">
                <div class="add-teacher-box">
                    <div class="row">
                        <div class="col-md-4">
                            <form class="row">
                                <div class="col-md-12">
                                    <label for="district-drop-down" class="form-label">Teacher</label>
                                    <select class="form-select" id="teacher-drop-down" name="teacher_id">
                                        <option value="0">Choose...</option>
                                        <?php foreach ($teachers as $teacher) { ?>
                                            <option value="<?php echo $teacher->ID; ?>"<?php echo isset($_GET['teacher_id']) && $_GET['teacher_id'] == $teacher->ID ? ' selected=selected' : '' ?>><?php echo $teacher->post_title; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-2"></div>
                        <div class="col-md-6">
                            <?php if (isset($_GET['teacher_id']) && $_GET['teacher_id'] > 0) { ?>
                                <div>
                                <?php 
                                    if (isset($district_type) && $district_type == 'edlink') {
                                        $model_id = 'edlinkStudentModal';
                                        $add_btn = 'edlinkStudentModalBtn';
                                    } else {
                                        $model_id = 'studentModal';
                                        $add_btn = 'studentModalBtn';

                                        echo '<button id="takenStudentModalBtn" class="add-heading" type="button" data-bs-toggle="modal" data-bs-target="#takenStudentModal" class="primary-btn"> Add Lxp Students </button>';
                                    }
                                ?>
                                <button id="<?php echo $add_btn; ?>" class="add-heading" type="button" data-bs-toggle="modal" data-bs-target="#<?php echo $model_id; ?>" class="primary-btn">
                                    Add New Student
                                </button>
                                <?php
                                    if (empty($district_type) || $district_type != 'edlink') {
                                ?>
                                        <label for="import-student" class="primary-btn add-heading">
                                            Import Students (CSV)
                                        </label >
                                        <input type="file" id="import-student" hidden />
                                <?php        
                                    }
                                ?>
                                </div>
                        <?php } else { ?>
                                <div class="alert alert-info" role="alert" style="margin-top: 10px;">
                                    Please select a `Teacher` to add or import `Students`.
                                </div>
                        <?php } ?>
                
                        </div>
                    </div>
                </div>

                <!-- Table Section -->
                <section class="recent-treks-section-div table-school-section">
                    <!-- bootstrap Active and Inactive tabs -->
                    <ul class="nav nav-tabs mb-3" id="settingsTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link<?php echo !isset($_GET['inactive']) ? ' active':''; ?>" id="active-tab" data-bs-toggle="tab" href="#active" role="tab" aria-controls="active" aria-selected="true">Active</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link<?php echo isset($_GET['inactive']) ? ' active' : ''; ?>" id="inactive-tab" data-bs-toggle="tab" href="#inactive" role="tab" aria-controls="inactive" aria-selected="false">Inactive</a>
                        </li>
                    </ul>

                    <div class="students-table">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="">
                                        <div class="th1">
                                            Student
                                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                                        </div>
                                    </th>
                                    <th>
                                        <div class="th1 th2">
                                            Username
                                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                                        </div>
                                    </th>
                                    <th>
                                        <div class="th1 th3">
                                            Classes
                                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                                        </div>
                                    </th>
                                    <th>
                                        <div class="th1 th3">
                                            Group
                                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                                        </div>
                                    </th>
                                    <th>
                                        <div class="th1 th4">
                                            Grades
                                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                                        </div>
                                    </th>
                                    <th>
                                        <div class="th1 th5">
                                            ID
                                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    foreach ($students as $student) {
                                        $student_admin = get_userdata(get_post_meta($student->ID, 'lxp_student_admin_id', true));
                                        $student_id = get_post_meta($student->ID, 'student_id', true);
                                ?>
                                    <tr>
                                        <td class="user-box">
                                            <div class="table-user">
                                                <img src="<?php echo $treks_src; ?>/assets/img/profile-icon.png" alt="student" />
                                                <div class="user-about">
                                                    <h5><?php echo $student->post_title; ?></h5>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="table-status"><?php echo $student_admin->user_login; ?></div>
                                        </td>
                                        <td>
                                            <?php
                                                echo count(lxp_get_student_class_group_by_type($student->ID, 'classes'));
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                                echo count(lxp_get_student_class_group_by_type($student->ID, 'other_group'));
                                            ?>
                                        </td>
                                        <td class="grade">
                                            <?php 
                                                $student_grades = json_decode(get_post_meta($student->ID, 'grades', true));
                                                $student_grades = $student_grades ? $student_grades : array();
                                                foreach ($student_grades as $grade) {
                                            ?>
                                                <span><?php echo $grade; ?></span>
                                            <?php        
                                                }
                                            ?>
                                        </td>
                                        <td><?php echo $student_id ? $student_id : '--'; ?></td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="dropdown_btn" type="button" id="dropdownMenu2"
                                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <img src="<?php echo $treks_src; ?>/assets/img/dots.svg" alt="logo" />
                                                </button>
                                                <div class="dropdown-menu" aria-labelledby="dropdownMenu2">
                                                    <?php
                                                        if (isset($district_type) && $district_type == 'edlink') {
                                                    ?>
                                                            <button class="dropdown-item" type="button" onclick="onEdlinkStudentEdit(<?php echo $student->ID; ?>)">
                                                            <img src="<?php echo $treks_src; ?>/assets/img/edit.svg" alt="logo" />
                                                            Edit</button>
                                                    <?php
                                                        } else {
                                                    ?>
                                                            <button class="dropdown-item" type="button" onclick="onStudentEdit(<?php echo $student->ID; ?>)">
                                                            <img src="<?php echo $treks_src; ?>/assets/img/edit.svg" alt="logo" />
                                                            Edit</button>
                                                    <?php            
                                                        }
                                                    ?>
                                                    <button class="dropdown-item" type="button" onclick="onSettingsClick(<?php echo $student->ID; ?>, 'student')">
                                                        <img src="<?php echo $treks_src; ?>/assets/img/edit.svg" alt="logo" />
                                                        Settings
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
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
        include $livePath.'/lxp/admin-settings-modal.php';
        $args['school_post'] = $school_post;
        $args['teachers'] = $teachers;
        if (isset($district_type) && $district_type == 'edlink') {
            $args['role'] = 'school';
            $args['district_post'] = $district_post;
            include $livePath.'/lxp/edlink/student-modal.php';
        } else {
            include $livePath.'/lxp/admin-student-assign-modal.php';
            include $livePath.'/lxp/school-student-modal.php';
        }
    ?>

    <script>
        // document ready
        $(document).ready(function () {
            // on change teacher drop down
            $('#teacher-drop-down').on('change', function () {
                var teacher_id = $(this).val();
                if (teacher_id > 0) {
                    window.location.href = '<?php echo get_permalink(); ?>?teacher_id=' + teacher_id;
                } else {
                    window.location.href = '<?php echo get_permalink(); ?>';
                }
            });
            // Get the tabs
            let activeTab = document.querySelector('#active-tab');
            let inactiveTab = document.querySelector('#inactive-tab');
            // Add event listener for 'shown.bs.tab' event
            activeTab.addEventListener('shown.bs.tab', function (e) {
                // Create a URLSearchParams object
                let params = new URLSearchParams(window.location.search);
                // Remove 'inactive' parameter
                params.delete('inactive');
                // Create the new URL
                let newUrl = window.location.pathname + '?' + params.toString();
                // Reload the page with the new URL
                window.location.href = newUrl;
            });

            inactiveTab.addEventListener('shown.bs.tab', function (e) {
                // Create a URLSearchParams object
                let params = new URLSearchParams(window.location.search);
                // Add 'inactive' parameter
                params.set('inactive', 'true');
                // Create the new URL
                let newUrl = window.location.pathname + '?' + params.toString();
                // Reload the page with the new URL
                window.location.href = newUrl;
            });
        });
    </script>
</body>

</html>