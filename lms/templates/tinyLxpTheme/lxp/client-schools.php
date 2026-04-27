<?php
$district_post = lxp_get_user_district_post();
$district_type = get_post_meta($district_post->ID, 'lxp_district_type', true);

//$schools = $district_post ? lxp_get_district_schools($district_post->ID) : [];
$schools = [];
if (isset($_GET['inactive']) && $_GET['inactive'] === 'true') {
    $schools = lxp_get_district_schools_inactive($district_post->ID, 'inactive');
} else {
    $schools = lxp_get_district_schools_active($district_post->ID, 'active');
}

while (have_posts()) : the_post();
?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title><?php the_title(); ?></title>
        
        <link href="<?php echo $treks_src; ?>/style/main.css" rel="stylesheet" />
        <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/header-section.css" />
        <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/schoolAdminTeachers.css" />
        <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/addNewTeacherModal.css" />
        <!-- <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/adminInternalTeacherView.css" /> -->
        <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/schoolDashboard.css" />
        <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/calendar.css" />
        <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/adminSchools.css" />
        <link href="<?php echo $treks_src; ?>/style/treksstyle.css" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
            integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous" />
            
        <!-- <style type="text/css">
            .treks-card {
                width: 300px !important;
            }

            .fc-content {
                padding: 7px;
            }

            .eventCloseBtn {
                padding: 3px;
                font-size: 18px;
            }

            .treks-card-link {
                text-decoration: none !important;
            }
        </style> -->
    </head>

    <body>
        <nav class="navbar navbar-expand-lg bg-light">
            <div class="container-fluid">
                <?php include $livePath.'/trek/header-logo.php'; ?>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
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

        
    <!-- School: section-->
    <section class="welcome-section">
        <!-- School: heading-->
        <div class="welcome-content">
            <h2 class="welcome-heading">Schools</h2>
            <p class="welcome-text">Manage your school operations from one central location</p>
        </div>

        <!-- Schools: section-->
        <section class="school-section">
            <section class="school_teacher_cards">
                <div class="add-teacher-box">
                    
                    <div class="search-filter-box">
                        <div class="search_box">
                            <label class="search-label">Search</label>
                            <input type="text" name="text" placeholder="School, ID, admin" />
                        </div>
                        <!-- <div class="filter-box">
                            <img src="<?php //echo $treks_src; ?>/assets/img/filter-alt.svg" alt="filter logo" />
                            <p class="filter-heading">Filter</p>
                        </div> -->
                    </div>
                    <?php
                        if (isset($district_type) && $district_type == 'edlink') {
                            $model_id = 'addEdlinkSchoolModal';
                        } else {
                            $model_id = 'addSchoolModal';
                        }
                    ?>
                    <button class="add-heading" type="button" id="<?php echo $model_id; ?>" class="primary-btn">Add New School</button>
                    <!-- End Modal -->
                </div>

                <!-- Admin School Table Section -->
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
                        <div class="school-box">
                            <div class="showing-row-box">
                                <!-- <p class="showing-row-text">Showing 1 - 5 of 25</p> -->
                                <!-- 
                                <div class="row-box">
                                    <p class="showing-row-text">Rows per page</p>
                                    <div class="show-page">
                                        <button class="show-page-button" type="button" id="dropdownMenu2"
                                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <span class="showing-row-text">5</span>
                                        </button>
                                        <img id="dropdownMenu2" data-bs-toggle="dropdown" aria-haspopup="true"
                                            aria-expanded="false" src="<?php // echo $treks_src; ?>/assets/img//show-down-page.svg" alt="logo" />
                                        <div class="dropdown-menu" aria-labelledby="dropdownMenu2">
                                            <button class="dropdown-item dropdown-class">
                                                <p class="page-row-para">1</p>
                                            </button>
                                            <button class="dropdown-item dropdown-class" type="button">
                                                <p class="page-row-para">2</p>
                                            </button>
                                        </div>
                                    </div>
                                </div> -->

                            </div>
                            <!-- <div class="row-box">
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
                            </div> -->
                        </div>
                        <table class="table teacher_table">
                            <thead>
                                <tr>
                                    <th class="">
                                        <div class="th1">
                                            School
                                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                                        </div>
                                    </th>
                                    <th>
                                        <div class="th1 th2">
                                            Administrator
                                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                                        </div>
                                    </th>
                                    <th>
                                        <div class="th1 th3">
                                            ID
                                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                                        </div>
                                    </th>
                                    <th>
                                        <div class="th1 th4">
                                            Teachers
                                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                                        </div>
                                    </th>
                                    <th>
                                        <div class="th1 th5">
                                            Students
                                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                                        </div>
                                    </th>
                                    <th>
                                        <div class="th1 th5">
                                            Region / District
                                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    foreach ($schools as $school) {
                                        $edlink_school_id = get_post_meta($school->ID, 'lxp_edlink_school_id', true);
                                ?>
                                    <tr>
                                        <td class="user-box">
                                            <div class="table-user">
                                                <img src="<?php echo $treks_src; ?>/assets/img/profile-icon.png" alt="school" />
                                                <div class="user-about">
                                                    <h5><?php echo $school->post_title ?></h5>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="table-status"><?php echo get_userdata(get_post_meta($school->ID, 'lxp_school_admin_id', true))->display_name; ?></div>
                                        </td>
                                        <td><?php echo $school->ID; ?></td>
                                        <td><?php echo count(lxp_get_school_teachers($school->ID)); ?></td>
                                        <td><?php echo count(lxp_get_school_students($school->ID)); ?></td>
                                        <td>Texas</td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="dropdown_btn" type="button" id="dropdownMenu2"
                                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <img src="<?php echo $treks_src; ?>/assets/img/dots.svg" alt="logo" />
                                                </button>
                                                <div class="dropdown-menu" aria-labelledby="dropdownMenu2">
                                                    <button class="dropdown-item" type="button" onclick="onSchoolEdit(<?php echo $school->ID; ?>, '<?php echo $edlink_school_id; ?>')"><img src="<?php echo $treks_src; ?>/assets/img/edit.svg" alt="logo" />Edit</button>
                                                    <!-- <button class="dropdown-item" type="button"><img src="<?php // echo $treks_src; ?>/assets/img/delete.svg" alt="logo" />Delete</button> -->
                                                    <button class="dropdown-item" type="button" onclick="onSettingsClick(<?php echo $school->ID; ?>, 'school')">
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
                        <div class="school-box">
                            <!-- <div class="showing-row-box">
                                <p class="showing-row-text">Showing 1 - 5 of 25</p>
                            </div> -->
                            <!-- <div class="row-box">
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
                            </div> -->
                        </div>
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


        <!-- <form name="seach_form" method="get" action="">
            <input type="text" name="search_param" value="<?php // echo isset($_GET['search_param']) ? $_GET['search_param'] : '' ?>" />
            <input type="submit">
        </form> -->
        
        <?php
            $args['district_post'] = $district_post;
            include $livePath.'/lxp/admin-settings-modal.php';
            include $livePath.'/lxp/client-school-modal.php';
            $args['edlink_district_post'] = $district_post;
            $args['role'] = 'client';
            include $livePath.'/lxp/edlink/school-modal.php';
        ?>

        <script type="text/javascript">
            function onSchoolEdit(school_id, edlink_school_id) {
                jQuery("#school_post_id").val(school_id);
                var district_type = '<?php echo $district_type; ?>';
                
                if (school_id > 0 && district_type == 'edlink') {
                    jQuery("#edlink_school_post_id").val(school_id);
                    jQuery("#inputEdlinkSchoolId").val(edlink_school_id);
                    jQuery('.edlink-school-action').text("Update");
                } else {
                    jQuery(".school-action").text("Update");
                }
                let host = window.location.hostname === 'localhost' ? window.location.origin + '<?php echo WORDPRESS_HOST; ?>' : window.location.origin;
                let apiUrl = host + '/wp-json/lms/v1/';

                $.ajax({
                    method: "POST",
                    enctype: 'multipart/form-data',
                    url: apiUrl + "schools",
                    data: {school_id}
                }).done(function( response ) {
                    let school = response.data.school;
                    let admin = response.data.admin.data;
                    if (school_id > 0 && district_type == 'edlink') {                        
                        jQuery('#edlinkSchoolForm .form-control').removeClass('is-invalid');
                        var school_name_html = '<input class="brief_info id_info form-control" type="text" id="edlinkInputSchoolName" name="edlink_school_name" placeholder="Enter Schools name here" value="'+school.post_title+'" readonly="readonly"/>';
                        jQuery('#edlink_school_container').html(school_name_html);
                        jQuery('#edlinkInputAbout').val(school.post_content);
                        jQuery('#edlinkInputFirstName').val(admin.first_name);
                        jQuery('#edlinkInputLastName').val(admin.last_name);
                        getEdlinkPeoples(jQuery("#inputEdlinkProviderAccessToken").val(), admin.user_email);
                        edlinkSchoolModalObj.show();
                    } else {
                        jQuery('#schoolForm .form-control').removeClass('is-invalid');
                        jQuery('#inputSchoolName').val(school.post_title);
                        jQuery('#inputAbout').val(school.post_content);
                        jQuery('#inputFirstName').val(admin.first_name);
                        jQuery('#inputLastName').val(admin.last_name);
                        jQuery('#inputEmail').val(admin.user_email);
                        jQuery('#inputEmailDefault').val(admin.user_email);
                        schoolModalObj.show();
                    }
                }).fail(function (response) {
                    console.error("Can not load school");
                });
            }

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

            function getEdlinkPeoples(access_token, user_email) {
                jQuery("#people_loader").html('<i class="fa fa-spinner fa-spin" style="font-size:25px"></i> Loading ...');
                $.ajax({
                    method: "POST",            
                    url: apiUrl + "edlink/provider/people/edit",
                    data: {access_token, user_email}
                }).done(function( response ) {
                    // Set People Data
                    if (typeof response.people === 'object' && response.people !== null && !response['people']['error']) {
                        var html = '';
                        html += '';
                        Object.entries(response.people).forEach(([key, person]) => {
                            html += '<option value="'+person["email"]+'" id="'+person["id"]+'" firstName="'+person["first_name"]+'" lastName="'+person["last_name"]+'">'+person["first_name"]+' '+person["last_name"]+' ('+person["email"]+')</option>';
                        });
                        jQuery("#edlinkInputPeopleName").html(html);
                        jQuery('#edlinkInputPeopleName').val(user_email);
                        jQuery("#saveEdlinkSchoolBtn").attr("disabled", false);
                    } else {
                        jQuery("#saveEdlinkSchoolBtn").attr("disabled", true);                        
                        jQuery("#edlink_error").html(response['people']['error']);
                        jQuery("#edlinkInputPeopleName").html('<option value="0"> Choose...</option>');
                        jQuery("#inputEdlinkUserId").val('');
                        jQuery("#edlinkInputFirstName").val('');
                        jQuery("#edlinkInputLastName").val('');
                    }
                    jQuery("#people_loader").html('');
                });
            }
        </script>
    </body>
    </html>
<?php endwhile;
ob_end_flush(); ?>