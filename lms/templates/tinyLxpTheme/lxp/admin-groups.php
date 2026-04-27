<?php
    global $treks_src;
    global $userdata;
    
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

    // $teacher_post = lxp_get_teacher_post($userdata->data->ID);
    $teacher_post =  isset($_GET['teacher_id']) ? get_post($_GET['teacher_id']) : null;
    // $teacher_school_id = $teacher_post ? get_post_meta($teacher_post->ID, 'lxp_teacher_school_id', true) : 0;
    $default_classes = $teacher_post ? lxp_get_teacher_default_classes($teacher_post->ID) : [];
    $classes = $teacher_post ? lxp_get_teacher_group_by_type($teacher_post->ID, 'classes') : [];
    $classes = array_merge($default_classes, $classes);
    $other_groups = $teacher_post ? lxp_get_teacher_group_by_type($teacher_post->ID, 'other_group') : [];
    $small_groups = $teacher_post ? lxp_get_teacher_groups($teacher_post->ID) : [];

    // Get the Edlink API Settings
    $edlink_options = get_option('edlink_options');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Groups</title>
    <link href="<?php echo $treks_src; ?>/style/main.css" rel="stylesheet" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/header-section.css" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/schoolAdminTeachers.css" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/addNewTeacherModal.css" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/schoolDashboard.css" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/schoolAdminStudents.css" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/adminInternalTeacherView.css" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/teacherStudentsClasses.css" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/newAssignment.css" />
    <link href="<?php echo $treks_src; ?>/style/treksstyle.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <style type="text/css">
        .heading-wrapper {
            border: 0px solid red;
            height: 145px;
        }

        .heading-left {
            float: left;
        }
        .heading-right {
            padding-top: 70px;
            padding-right: 20px;
            float: right;
        }

        .students-table .table tbody tr td .dropdown .dropdown-menu.show {
            width: 200px;
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
            <?php include $livePath.'/trek/navigation.php'; ?>
        </nav>
    </section>
    <!-- Welcome: section-->
    <section class="welcome-section">
        <!-- Welcome: heading-->
        <div class="heading-wrapper">
            <div class="heading-left">
                <div class="welcome-content">
                    <h2 class="welcome-heading">Groups</h2>
                </div>
            </div>

            <div class="heading-right">
                <!-- <a href="<?php //echo site_url("students"); ?>" type="button" class="btn btn-outline-secondary btn-lg">Students</a>
                <a href="<?php //echo site_url("classes"); ?>" type="button" class="btn btn-outline-secondary btn-lg">Classes & Groups</a>
                <a href="<?php //echo site_url("groups"); ?>" type="button" class="btn btn-secondary btn-lg">Groups</a> -->
            </div>
        </div>

        <!-- Total Schools: section-->
        <section class="school-section">
            <section class="school_teacher_cards">
                <div class="add-teacher-box">
                    <div class="row" style="width: 100%;">
                        <div class="col-md-9">
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
                        <div class="col-md-3">                   
                            <button id="groupModalBtn" class="add-heading" type="button" data-bs-toggle="modal" data-bs-target="#groupModal" class="primary-btn" style="margin-top: 25px;">
                                Add New Group
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Groups Section -->
                <section class="recent-treks-section-div table-school-section">

                    <div class="students-table">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="">
                                        <div class="th1">
                                            Groups
                                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                                        </div>
                                    </th>
                                    <th>
                                        <div class="th1 th2">
                                            Class/Group
                                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                                        </div>
                                    </th>
                                    <th>
                                        <div class="th1 th3">
                                            Students
                                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    foreach ($small_groups as $small_group) {
                                ?>
                                    <tr>
                                        <td class="user-box">
                                            <div class="table-user">
                                                <img src="<?php echo $treks_src; ?>/assets/img/profile-icon.png" alt="student" />
                                                <div class="user-about">
                                                    <h5><?php echo $small_group->post_title?></h5>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo get_post_meta($small_group->ID, 'lxp_classe_group_name', true); ?>
                                        </td>
                                        <td>
                                            <?php 
                                                echo count(get_post_meta($small_group->ID, 'lxp_group_student_ids'));
                                            ?>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="dropdown_btn" type="button" id="dropdownMenu2"
                                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <img src="<?php echo $treks_src; ?>/assets/img/dots.svg" alt="logo" />
                                                </button>
                                                <div class="dropdown-menu" aria-labelledby="dropdownMenu2">
                                                    <button class="dropdown-item" type="button" onclick="onGroupEdit(<?php echo $small_group->ID; ?>)">
                                                        <img src="<?php echo $treks_src; ?>/assets/img/edit.svg" alt="logo" />
                                                        Edit</button>
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
        if(isset($_GET['teacher_id'])) {
            $args['classes'] = $classes;
            $args['other_groups'] = $other_groups;
            $args['teacher_post'] = $teacher_post;
            include $livePath.'/lxp/admin-group-modal.php';
        } else {
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
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            Please select <strong>Teacher</strong> to add/edit a Classe & Group</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php
        }
     ?>

     
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