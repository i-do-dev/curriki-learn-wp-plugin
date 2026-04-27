<?php
global $treks_src;

// get all user with role lxp_client_admin
$lxp_client_admin_users = get_users(array('role' => 'lxp_client_admin'));
$lxp_client_admin_user_ids = array_map(function ($user) { return $user->ID; },  $lxp_client_admin_users);
// get post TL_DISTRICT_CPT based on multiple 'lxp_district_admin' meta values

$district_type_condition = (isset($_GET['district_type']) && $_GET['district_type'] == 'edlink') ? array(
    'key' => 'lxp_district_type',
    'value' => 'edlink',
    'compare' => '='
) : array(
    'relation' => 'OR',
    array(
        'key' => 'lxp_district_type',
        'compare' => 'NOT EXISTS'
    ),
    array(
        'key' => 'lxp_district_type',
        'value' => 'edlink',
        'compare' => '!='
    )
);

$district_posts = get_posts(array(
  'post_type' => 'tl_district',
  'meta_query' => array(
    array(
      'key' => 'lxp_district_admin',
      'value' => $lxp_client_admin_user_ids,
      'compare' => 'IN'
    ),
    $district_type_condition
  )
));

$district_post = lxp_get_user_district_post( (isset($_GET['district_id']) ? get_post_meta($_GET['district_id'], 'lxp_district_admin', true) : 0) );
$district_schools = !$district_post ? [] : lxp_get_district_schools($district_post->ID);
$district_schools_ids = array_map(function ($school) { return $school->ID; },  $district_schools);
$district_schools_teachers = lxp_get_all_schools_teachers( isset($_GET['school_id']) ? [$_GET['school_id']] : $district_schools_ids );

global $userdata;
$teacher_post =  isset($_GET['teacher_id']) ? get_post($_GET['teacher_id']) : null;
$teacher_school_id = $teacher_post ? get_post_meta($teacher_post->ID, 'lxp_teacher_school_id', true) : 0;
$school_post = $teacher_school_id > 0 ? get_post($teacher_school_id) : null;
$students = [];
if(isset($_GET['school_id']) && isset($_GET['teacher_id'])) {
    // $students = lxp_get_school_teacher_students($teacher_school_id, $teacher_post->ID);
    if (isset($_GET['inactive']) && $_GET['inactive'] == 'true') {
        $students = lxp_get_school_teacher_students_inactive($teacher_post->ID, true);
    } else {
        $students = lxp_get_school_teacher_students_active($teacher_post->ID);
    }
} else if(isset($_GET['school_id'])) {
    // $students = lxp_get_school_students($_GET['school_id']);
    if (isset($_GET['inactive']) && $_GET['inactive'] == 'true') {
        $students = lxp_get_school_students_inactive($_GET['school_id'], true);
    } else {
        $students = lxp_get_school_students_active($_GET['school_id']);
    }
    $school_post = get_post($_GET['school_id']);
}

// Get the Edlink API Settings
$edlink_options = get_option('edlink_options');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Students</title>
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
        .heading-wrapper {
            border: 0px solid red;
            height: 115px;
        }

        .heading-left {
            float: left;
        }
        .heading-right {
            padding-top: 70px;
            padding-right: 20px;
            float: right;
        }

        .welcome-content {
            padding: 20px 0;
        }

        .add-teacher-box {
            display: block !important;
        }

        .add-heading {
            margin-top: 20px !important;
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
                            <form action="<?php echo site_url("search"); ?>">
                                <input placeholder="Search" id="q" name="q" value="<?php echo isset($_GET["q"]) ? $_GET["q"]:''; ?>" />
                            </form>
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
        <div class="heading-wrapper">
            <div class="heading-left">
                <div class="welcome-content">
                    <h2 class="welcome-heading">Students</h2>
                    <p class="welcome-text">Student enrollment and registration management</p>
                </div>
            </div>

<!-- 
            <div class="heading-right">
                <a href="<?php //echo site_url("students"); ?>" type="button" class="btn btn-secondary btn-lg">Students</a>
                <a href="<?php //echo site_url("classes"); ?>" type="button" class="btn btn-outline-secondary btn-lg">Classes & Groups</a>
                <a href="<?php //echo site_url("groups"); ?>" type="button" class="btn btn-outline-secondary btn-lg">Groups</a>
            </div> -->
        </div>

        <!-- Total Schools: section-->
        <section class="school-section">
            <section class="school_teacher_cards">
                <div class="add-teacher-box">
                    <div class="row">
                        <div class="col-md-6">
                            <form class="row">
                                <?php 
                                    if (isset($edlink_options['edlink_application_id']) && $edlink_options['edlink_application_id'] != '' && isset($edlink_options['edlink_application_secrets']) && $edlink_options['edlink_application_secrets'] != '' && isset($edlink_options['edlink_sso_enable']) && $edlink_options['edlink_sso_enable'] == 1
                                    ) {
                                ?>
                                        <div class="col-md-2">
                                            <label for="district_type" class="form-label">Integration</label>
                                            <select id="district_type" name="district_type" class="form-select" onChange="javascript:onChangeDistrictType();">
                                                <?php 
                                                    if (isset($_GET['district_type']) && $_GET['district_type'] == 'edlink') {
                                                ?>
                                                        <option value="tinylxp">TinyLxp</option>
                                                        <option value="edlink" selected="selected">Edlink</option>
                                                <?php        

                                                    } else {
                                                ?>
                                                        <option value="tinylxp">TinyLxp</option>
                                                        <option value="edlink">Edlink</option>
                                                <?php
                                                    }
                                                ?>
                                            </select>                    
                                        </div>
                                <?php } ?>
                                <div class="col-md-3">
                                    <label for="district-drop-down" class="form-label">District</label>
                                    <select class="form-select" id="district-drop-down" name="district_id">
                                        <option value="0">Choose...</option>
                                        <?php foreach ($district_posts as $district_post) { ?>
                                            <option value="<?php echo $district_post->ID; ?>"<?php echo isset($_GET['district_id']) && $_GET['district_id'] == $district_post->ID ? ' selected=selected' : '' ?>><?php echo $district_post->post_title; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="district-drop-down" class="form-label">School</label>
                                    <select class="form-select" id="school-drop-down" name="school_id">
                                        <option value="0">Choose...</option>
                                        <?php foreach ($district_schools as $district_school) { ?>
                                            <option value="<?php echo $district_school->ID; ?>"<?php echo isset($_GET['school_id']) && $_GET['school_id'] == $district_school->ID ? ' selected=selected' : '' ?>><?php echo $district_school->post_title; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="district-drop-down" class="form-label">Teacher</label>
                                    <select class="form-select" id="teacher-drop-down" name="teacher_id">
                                        <option value="0">Choose...</option>
                                        <?php foreach ($district_schools_teachers as $district_school_teacher) { ?>
                                            <option value="<?php echo $district_school_teacher->ID; ?>"<?php echo isset($_GET['teacher_id']) && $_GET['teacher_id'] == $district_school_teacher->ID ? ' selected=selected' : '' ?>><?php echo $district_school_teacher->post_title; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <?php 
                                if (isset($_GET['district_type']) && $_GET['district_type'] == 'edlink') {
                                    $model_id = 'edlinkStudentModal';
                                    $add_btn = 'edlinkStudentModalBtn';
                                } else {
                                    $model_id = 'studentModal';
                                    $add_btn = 'studentModalBtn';

                                    $stdTakenModal = isset($_GET['school_id']) ? 'takenStudentModal' : $model_id;
                                    $stdTakenModalBtn = isset($_GET['school_id']) ? 'takenStudentModalBtn' : $add_btn;

                                    echo '<button id="'.$stdTakenModalBtn.'" class="add-heading" type="button" data-bs-toggle="modal" data-bs-target="#'.$stdTakenModal.'" class="primary-btn"> Add Lxp Students </button>';
                                }
                            ?>
                            
                                <button id="<?php echo $add_btn; ?>" class="add-heading" type="button" data-bs-toggle="modal" data-bs-target="#<?php echo $model_id; ?>" class="primary-btn">
                                    Add New Student
                                </button>
                                <?php 
                                    if (!isset($_GET['district_type']) || $_GET['district_type'] != 'edlink') {
                                ?>
                                        <label for="import-student" class="primary-btn add-heading">
                                            Import Students (CSV)
                                        </label >
                                        <input type="file" id="import-student" hidden />
                                <?php    
                                    }
                                ?>
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
                                                // echo count(lxp_get_student_all_classes($student->ID)); 
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
                                                        if (isset($_GET['district_type']) && $_GET['district_type'] == 'edlink') {
                                                    ?>
                                                        <button class="dropdown-item" type="button" onclick="onEdlinkStudentEdit(<?php echo $student->ID; ?>)">
                                                    <?php        
                                                        } else {
                                                    ?>
                                                        <button class="dropdown-item" type="button" onclick="onStudentEdit(<?php echo $student->ID; ?>)">
                                                    <?php        
                                                        }
                                                    ?>
                                                    <img src="<?php echo $treks_src; ?>/assets/img/edit.svg" alt="logo" />
                                                    Edit</button>
                                                    <!-- <button class="dropdown-item" type="button">
                                                        <img src="<?php // echo $treks_src; ?>/assets/img/delete.svg" alt="logo" />
                                                        Delete</button> -->
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
                        <!-- 
                        <div class="school-box">
                            <div class="showing-row-box">
                                <p class="showing-row-text">Showing 1 - 5 of 25</p>
                            </div>
                            <div class="row-box">
                                <p class="showing-row-text">First</p>
                                <img class="previous-slide-img" src="<?php // echo $treks_src; ?>/assets/img/previous-arrow.svg" alt="logo" />
                                <div class="slides-boxes">
                                    <div class="slide-box"><span class="showing-row-text slide-num">1</span></div>
                                    <div class="slide-box"><span class="showing-row-text slide-num slide-num2">2</span>
                                    </div>
                                    <div class="slide-box"><span class="showing-row-text slide-num slide-num2">3</span>
                                    </div>
                                </div>
                                <img class="last-slide-img" src="<?php // echo $treks_src; ?>/assets/img/last-slide.svg" alt="logo" />
                                <p class="showing-row-text">Last</p>
                            </div>
                        </div>
                         -->
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
        //if(isset($_GET['teacher_id'])) {
        if( $school_post ) {
            $args['school_post'] = $school_post;
            $args['teachers'] = $district_schools_teachers;
            if (isset($_GET['district_type']) && $_GET['district_type'] == 'edlink') {
                include $livePath.'/lxp/edlink/student-modal.php';
            } else {
                include $livePath.'/lxp/admin-student-modal.php';
                include $livePath.'/lxp/admin-student-assign-modal.php';
            }
        } else {

        //if( !isset($_GET['teacher_id']) ) {
    ?>
        <div class="modal fade students-modal" id="<?php echo (isset($_GET['district_type']) && $_GET['district_type'] == 'edlink') ? 'edlinkStudentModal' : 'studentModal'; ?>" tabindex="-1" aria-labelledby="<?php echo (isset($_GET['district_type']) && $_GET['district_type'] == 'edlink') ? 'edlinkTeacherModalLabel' : 'teacherModalLabel'; ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div class="modal-header-title">
                                <h2 class="modal-title" id="studentModalLabel"><span class="teacher-action-head">New</span> Student</h2>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Bootstrap alert with text: Please select District and School to add new teacher. -->
                            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                Please select <strong>Teacher</strong> to add/edit a student.</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        
        <script type="text/javascript">
            jQuery(document).ready(function() {
                jQuery("#import-student").on("change", function(e) {
                    $('#studentModal').modal('show');
                    jQuery("#import-student").val("");
                });
            });
        </script>
    <?php } ?>
    
    <?php if(isset($_GET['teacher_id'])) { ?>
        <input type="hidden" name="school_admin_id_imp" id="school_admin_id_imp" value="<?php echo get_post_meta( $school_post->ID, 'lxp_school_admin_id', true ); ?>">
        <input type="hidden" name="student_school_id_imp" id="student_school_id_imp" value="<?php echo $school_post->ID; ?>">
        <input type="hidden" name="teacher_id_imp" id="teacher_id_imp" value="<?php echo isset($_GET['teacher_id']) ? $_GET['teacher_id'] : 0; ?>">

        <script>
            jQuery(document).ready(function() {
                let host = window.location.hostname === 'localhost' ? window.location.origin + '<?php echo WORDPRESS_HOST; ?>' : window.location.origin;
                let apiUrl = host + '/wp-json/lms/v1/';
                
                jQuery("#import-student").on("change", function(e) {
                    let formData = new FormData();
                    formData.append('student_school_id', jQuery("#student_school_id_imp").val());
                    formData.append('school_admin_id', jQuery("#school_admin_id_imp").val());
                    formData.append('teacher_id', jQuery("#teacher_id_imp").val());
                    formData.append('students', e.target.files[0]);
                    $.ajax({
                        method: "POST",
                        enctype: 'multipart/form-data',
                        url: apiUrl + "students/import",
                        data: formData,
                        processData: false,
                        contentType: false,
                        cache: false,
                    }).done(function( response ) {
                        jQuery("#import-student").val("");
                        window.location.reload();
                    }).fail(function (response) {
                        jQuery("#import-student").val("");
                        if (response.responseJSON) {
                            alert(response.responseJSON.data);
                        }
                    });
                });
            });
        </script>
    <?php } ?>

    <script>
        $(document).ready(function () {
            $('#district-drop-down').change(function () {
                var district_id = $(this).val();
                var url = new URL(window.location.href);
                url.searchParams.set('district_id', district_id);

                // unset school_id url param
                url.searchParams.delete('school_id');
                // unset teacher_id url param if it exists
                url.searchParams.delete('teacher_id');

                if (district_id == 0) {
                    url.searchParams.delete('district_id');
                }

                window.location.href = url.href;
            });

            $('#school-drop-down').change(function() {
                var school_id = $(this).val();
                var url = new URL(window.location.href);
                url.searchParams.set('school_id', school_id);

                // unset teacher_id url param if it exists
                url.searchParams.delete('teacher_id');

                if (school_id == 0) {
                    url.searchParams.delete('school_id');
                }
                window.location = url.href;
            });

            $('#teacher-drop-down').change(function() {
                var teacher_id = $(this).val();
                var url = new URL(window.location.href);
                url.searchParams.set('teacher_id', teacher_id);
                if (teacher_id == 0) {
                    url.searchParams.delete('teacher_id');
                }
                window.location = url.href;
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

        function onChangeDistrictType() {
            var district_type = jQuery("#district_type option:selected").val();
            let newUrl = window.location.pathname + '?district_type=' + district_type;
            // Reload the page with the new URL
            window.location.href = newUrl;
        }
    </script>
</body>

</html>