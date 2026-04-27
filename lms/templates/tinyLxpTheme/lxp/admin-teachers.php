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
  'post_type' => TL_DISTRICT_CPT,
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
//$district_schools_teachers = lxp_get_all_schools_teachers( isset($_GET['school_id']) ? [$_GET['school_id']] : $district_schools_ids );
$district_schools_teachers = [];
if (isset($_GET['inactive']) && $_GET['inactive'] == 'true') {
    $district_schools_teachers = lxp_get_all_schools_inactive_teachers( isset($_GET['school_id']) ? [$_GET['school_id']] : $district_schools_ids );
} else {
    $district_schools_teachers = lxp_get_all_schools_active_teachers( isset($_GET['school_id']) ? [$_GET['school_id']] : $district_schools_ids );
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
    <title>District / Teachers</title>
    <link href="<?php echo $treks_src; ?>/style/main.css" rel="stylesheet" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/header-section.css" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/schoolAdminTeachers.css" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/schoolDashboard.css" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/schoolAdminStudents.css" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/adminTeacher.css" />
    <link href="<?php echo $treks_src; ?>/style/treksstyle.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/addNewTeacherModal.css" />
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

    <!-- Teachers: section-->
    <section class="welcome-section">
        <!-- Teachers: heading-->
        <div class="welcome-content">
            <h2 class="welcome-heading">Teachers</h2>
            <p class="welcome-text">Comprehensive teacher database and records management</p>
            <br />
            <!-- bootstrap form with Dstricts drop down component displaying using $district_posts -->
            <form class="row g-3 recent-treks-section-div">
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
                    <select id="district-drop-down" class="form-select">
                        <option value="0">Choose...</option>
                        <?php foreach ($district_posts as $district_post) { ?>
                            <option value="<?php echo $district_post->ID; ?>"<?php echo isset($_GET['district_id']) && $_GET['district_id'] == $district_post->ID ? ' selected=selected' : '' ?>><?php echo $district_post->post_title; ?></option>
                        <?php } ?>
                    </select>                    
                </div>
                <div class="col-md-3">
                    <!-- School drop down using $district_schools -->
                    <label for="school-drop-down" class="form-label">School</label>
                    <select id="school-drop-down" class="form-select">
                        <option value="0">Choose...</option>
                        <?php foreach ($district_schools as $district_school) { ?>
                            <option value="<?php echo $district_school->ID; ?>"<?php echo isset($_GET['school_id']) && $_GET['school_id'] == $district_school->ID ? ' selected=selected' : '' ?>><?php echo $district_school->post_title; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <?php 
                        if (isset($_GET['district_type']) && $_GET['district_type'] == 'edlink') {
                            $model_id = 'edlinkTeacherModal';
                            $add_btn = 'addEdlinkTeachers';
                        } else {
                            $model_id = 'teacherModal';
                            $add_btn = 'addTeachers';
                        }
                    ?>
                    <button id="<?php echo $add_btn; ?>" class="add-heading" type="button" type="button" data-bs-toggle="modal"
                        data-bs-target="#<?php echo $model_id; ?>" class="primary-btn" style="margin-top: 25px;">
                        Add New Teacher
                    </button>
                    <?php 
                        if (!isset($_GET['district_type']) || $_GET['district_type'] != 'edlink') {
                    ?>
                            <label for="import-teacher" class="primary-btn add-heading">
                                Import Teachers (CSV)
                            </label >
                            <input type="file" id="import-teacher" hidden />
                    <?php    
                        }
                    ?>
                </div>
            </form>
        </div>

        <!-- Teachers: section-->
        <section class="school-section">
            <section class="school_teacher_cards">

                    <!-- Admin Teacher Table Section -->
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
                                <?php 
                                    foreach ($district_schools_teachers as $teacher) {
                                        $teacher_admin = get_userdata(get_post_meta($teacher->ID, 'lxp_teacher_admin_id', true));
                                        $teacher_school = null;
                                        $teacher_district = null;
                                        $teacher_school_id = get_post_meta($teacher->ID, 'lxp_teacher_school_id', true);
                                        if ($teacher_school_id) {
                                            $teacher_school = get_post($teacher_school_id);
                                            $teacher_district_id = get_post_meta($teacher_school->ID, 'lxp_school_district_id', true);
                                            if ($teacher_district_id) {
                                                $teacher_district = get_post($teacher_district_id);
                                            }
                                        }
                                        $teacher_students = array();
                                        if ($teacher_district && $teacher_school) {
                                            $teacher_students = lxp_get_school_teacher_students($teacher->ID);
                                        }
                                ?>
                                    <tr>
                                        <td class="user-box">
                                            <div class="table-user">
                                                <img src="<?php echo $treks_src; ?>/assets/img/profile-icon.png" alt="teacher" />
                                                <div class="user-about">
                                                    <h5><?php echo $teacher->post_title?></h5>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="table-status"><?php echo $teacher_admin ? $teacher_admin->user_email : '(No Admin Login Found)'; ?></div>
                                        </td>
                                        <td><?php echo $teacher_district ? $teacher_district->post_title : '---'; ?></td>
                                        <td><?php echo $teacher_school ? $teacher_school->post_title : '---' ?></td>
                                        <td><?php echo $teacher_students && is_array($teacher_students) ? count($teacher_students) : 0 ?></td>
                                        <td><?php echo $teacher->ID; ?></td>
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
                                                        <button class="dropdown-item" type="button" onclick="onEdlinkTeacherEdit(<?php echo $teacher->ID; ?>)">
                                                    <?php        
                                                        } else {
                                                    ?>
                                                        <button class="dropdown-item" type="button" onclick="onTeacherEdit(<?php echo $teacher->ID; ?>)">
                                                    <?php        
                                                        }
                                                    ?>                                                    
                                                    <img src="<?php echo $treks_src; ?>/assets/img/edit.svg" alt="logo" />
                                                    Edit</button>
                                                    <button class="dropdown-item" type="button" onclick="onTeacherRestrictCoursesClick(<?php echo $teacher->ID; ?>)">
                                                        <img src="<?php echo $treks_src; ?>/assets/img/edit.svg" alt="logo" />
                                                        Restrict TREKs</button>
                                                    <!-- <button class="dropdown-item" type="button">
                                                        <img src="<?php // echo $treks_src; ?>/assets/img/delete.svg" alt="logo" />
                                                        Delete</button> -->
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
    
    <script>
        $(document).ready(function () {
            $('#district-drop-down').change(function () {
                var district_id = $(this).val();
                var url = new URL(window.location.href);
                url.searchParams.set('district_id', district_id);
                // unset school_id url param
                url.searchParams.delete('school_id');
                if (district_id == 0) {
                    url.searchParams.delete('district_id');
                }
                window.location.href = url.href;
            });

            $('#school-drop-down').change(function() {
                var school_id = $(this).val();
                var url = new URL(window.location.href);
                url.searchParams.set('school_id', school_id);
                if (school_id == 0) {
                    url.searchParams.delete('school_id');
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

    <?php 
        include $livePath.'/lxp/admin-teacher-assign-treks-modal.php';
        include $livePath.'/lxp/admin-settings-modal.php';        
        // check if district_id and school_id GET set
        if (isset($_GET['district_id']) && isset($_GET['school_id'])) {
            if (isset($_GET['district_type']) && $_GET['district_type'] == 'edlink') {
                include $livePath.'/lxp/edlink/teacher-modal.php';
            } else {
                include $livePath.'/lxp/admin-teacher-modal.php';
            }
    ?>
            <input type="hidden" name="school_admin_id_imp" id="school_admin_id_imp" value="<?php echo get_post_meta( $_GET['school_id'], 'lxp_school_admin_id', true ); ?>">
            <input type="hidden" name="teacher_school_id_imp" id="teacher_school_id_imp" value="<?php echo $_GET['school_id']; ?>">
            
            <script type="text/javascript">
                let host = window.location.hostname === 'localhost' ? window.location.origin + '<?php echo WORDPRESS_HOST; ?>' : window.location.origin;
                let apiUrl = host + '/wp-json/lms/v1/';
                
                jQuery("#import-teacher").on("change", function(e) {
                    let formData = new FormData();
                    formData.append("teacher_school_id", jQuery("#teacher_school_id_imp").val());
                    formData.append("school_admin_id", jQuery("#school_admin_id_imp").val());
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
    <?php
        } else {
    ?>
            <div class="modal fade teachers-modal" id="<?php echo (isset($_GET['district_type']) && $_GET['district_type'] == 'edlink') ? 'edlinkTeacherModal' : 'teacherModal'; ?>" tabindex="-1" aria-labelledby="<?php echo (isset($_GET['district_type']) && $_GET['district_type'] == 'edlink') ? 'edlinkTeacherModalLabel' : 'teacherModalLabel'; ?>" aria-hidden="true">
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
                            <!-- Bootstrap alert with text: Please select District and School to add new teacher. -->
                            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                Please select <strong>District</strong> and <strong>School</strong> to add/edit a teacher.</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <script type="text/javascript">
                function onTeacherEdit(x) {
                    $('#teacherModal').modal('show');
                }
                function onEdlinkTeacherEdit(x) {
                    $('#edlinkTeacherModal').modal('show');
                }

                jQuery(document).ready(function() {
                    jQuery("#import-teacher").on("change", function(e) {
                        $('#teacherModal').modal('show');
                        jQuery("#import-teacher").val("");
                    });
                });
            </script>
    <?php
        }    
    ?>

</body>

</html>