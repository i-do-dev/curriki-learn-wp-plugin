<?php
    lxp_login_check();
    $school_post = lxp_get_user_school_post();
    // $teachers = lxp_get_school_teachers($school_post->ID);
    $teachers = [];
    if (isset($_GET['inactive']) && $_GET['inactive'] == 'true') {
        $teachers = lxp_get_school_teachers_inactive($school_post->ID);
    } else {
        $teachers = lxp_get_school_teachers_active($school_post->ID);
    }
    $district_post = get_post(get_post_meta($school_post->ID, 'lxp_school_district_id', true));
    $district_type = get_post_meta($district_post->ID, 'lxp_district_type', true);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>School Admin / Teachers</title>
    <link href="<?php echo $treks_src; ?>/style/main.css" rel="stylesheet" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/header-section.css" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/schoolAdminTeachers.css" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/addNewTeacherModal.css" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/schoolDashboard.css" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/calendar.css" />
    <link href="<?php echo $treks_src; ?>/style/treksstyle.css" rel="stylesheet" />
    <!-- <link rel="stylesheet" href="<?php // echo $treks_src; ?>/style/adminInternalTeacherView.css" /> -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
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
            <h2 class="welcome-heading">Teachers</h2>
            <p class="welcome-text">Comprehensive teacher database and records management</p>
        </div>

        <!-- Total Schools: section-->
        <section class="school-section">
            <section class="school_teacher_cards">
                <div class="add-teacher-box" style="width: 33%">
                    <!-- <div class="search-filter-box">
                        <div class="search_box">
                            <label class="search-label">Search</label>
                            <input type="text" name="text" placeholder="School, ID, admin" />
                        </div>
                        <div class="filter-box">
                            <img src="<?php echo $treks_src; ?>/assets/img/filter-alt.svg" alt="filter logo" />
                            <p class="filter-heading">Filter</p>
                        </div>
                    </div> -->
                    <?php 
                        if (isset($district_type) && $district_type == 'edlink') {
                            $model_id = 'edlinkTeacherModal';
                            $add_btn = 'addEdlinkTeachers';
                        } else {
                            $model_id = 'teacherModal';
                            $add_btn = 'addTeachers';
                        }
                    ?>
                    <button id="<?php echo $add_btn; ?>" class="add-heading" type="button" type="button" data-bs-toggle="modal"
                        data-bs-target="#<?php echo $model_id; ?>" class="primary-btn">
                        Add New Teacher
                    </button>
                    <?php
                        if (empty($district_type) || $district_type != 'edlink') {
                    ?>
                            <label for="import-teacher" class="primary-btn add-heading">
                                Import Teachers (CSV)
                            </label >
                            <input type="file" id="import-teacher" hidden />
                    <?php        
                        }
                    ?>
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
                        <table class="table teacher_table">
                            <thead>
                                <tr>
                                    <th class="">
                                        <div class="th1">
                                            Teacher
                                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                                        </div>
                                    </th>
                                    <th>
                                        <div class="th1 th2">
                                            Email
                                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                                        </div>
                                    </th>
                                    <th>
                                        <div class="th1 th3">
                                            District
                                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                                        </div>
                                    </th>
                                    <th>
                                        <div class="th1 th3">
                                            School
                                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                                        </div>
                                    </th>
                                    <th>
                                        <div class="th1 th3">
                                            Students
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
                                <?php foreach ($teachers as $teacher) {
                                    $teacher_admin = get_userdata(get_post_meta($teacher->ID, 'lxp_teacher_admin_id', true));
                                    $lxp_teacher_school = null;
                                    $lxp_teacher_district = null;
                                    $lxp_teacher_school_id = get_post_meta($teacher->ID, 'lxp_teacher_school_id', true);
                                    if ($lxp_teacher_school_id) {
                                        $lxp_teacher_school = get_post($lxp_teacher_school_id);
                                        $lxp_teacher_district_id = get_post_meta($lxp_teacher_school->ID, 'lxp_school_district_id', true);
                                        if ($lxp_teacher_district_id) {
                                            $lxp_teacher_district = get_post($lxp_teacher_district_id);
                                        }
                                    }
                                    $lxp_teacher_students = array();
                                    if ($lxp_teacher_district && $lxp_teacher_school) {
                                        $lxp_teacher_students = lxp_get_school_teacher_students($teacher->ID);
                                    }
                                ?>
                                    <tr>
                                        <td class="user-box">
                                            <div class="table-user">
                                                <img src="<?php echo $treks_src; ?>/assets/img/profile-icon.png" alt="teacher" />
                                                <div class="user-about">
                                                    <h5><?php echo $teacher_admin->display_name; ?></h5>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="table-status"><?php echo $teacher_admin->user_email; ?></div>
                                        </td>
                                        <td><?php echo $lxp_teacher_district ? $lxp_teacher_district->post_title : '---'; ?></td>
                                        <td><?php echo $lxp_teacher_school ? $lxp_teacher_school->post_title : '---' ?></td>
                                        <td><?php echo $lxp_teacher_students && is_array($lxp_teacher_students) ? count($lxp_teacher_students) : 0 ?></td>
                                        <td><?php echo $teacher->ID; ?></td>
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
                                                            <button class="dropdown-item" type="button" onclick="onEdlinkTeacherEdit(<?php echo $teacher->ID; ?>)">
                                                            <img src="<?php echo $treks_src; ?>/assets/img/edit.svg" alt="logo" />
                                                            Edit</button>
                                                    <?php
                                                        } else {
                                                    ?>
                                                            <button class="dropdown-item" type="button" onclick="onTeacherEdit(<?php echo $teacher->ID; ?>)">
                                                            <img src="<?php echo $treks_src; ?>/assets/img/edit.svg" alt="logo" />
                                                            Edit</button>
                                                    <?php            
                                                        }
                                                    ?>
                                                    <button class="dropdown-item" type="button" onclick="onSettingsClick(<?php echo $teacher->ID; ?>, 'teacher')">
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
            if (isset($district_type) && $district_type == 'edlink') {
                $args['role'] = 'school';
                $args['district_post'] = $district_post;
                include $livePath.'/lxp/edlink/teacher-modal.php';
            } else {
                include $livePath.'/lxp/school-teacher-modal.php';
            }
            include $livePath.'/lxp/admin-settings-modal.php';
        ?>
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
    <script type="text/javascript">
        jQuery(document).ready(function() {
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