<?php
$district_post = lxp_get_user_district_post();
$district_schools = lxp_get_district_schools($district_post->ID);
$district_schools_ids = array_map(function ($school) { return $school->ID; },  $district_schools);
$district_schools_teachers = lxp_get_all_schools_teachers($district_schools_ids);
$district_schools_students = lxp_get_all_schools_students($district_schools_ids);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>District Dashboard</title>
    <link href="<?php echo $treks_src; ?>/style/main.css" rel="stylesheet" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/header-section.css" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/adminDashboard.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous" />
    <link href="<?php echo $treks_src; ?>/style/treksstyle.css" rel="stylesheet" />
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
    <div class="welcome-section">
        <h2 class="welcome-heading">Welcome <?php echo $district_post->post_title; ?>!</h2>
        <p class="welcome-text">Here's how your academic system looks like</p>
    </div>

    <!-- Total Schools: section-->
    <section class="school-section">
        <div class="cards-box">
            <div class="card">
                <img src="<?php echo $treks_src; ?>/assets/img/home-logo.svg" alt="logo" />
                <h3 class="numbers-heading"><?php echo count( $district_schools ); ?></h3>
                <p class="name-text">Schools</p>
            </div>
            <div class="card">
                <img src="<?php echo $treks_src; ?>/assets/img/verified-user.svg" alt="logo" />
                <h3 class="numbers-heading"><?php echo count($district_schools_teachers); ?></h3>
                <p class="name-text">Teachers</p>
            </div>
            <div class="card">
                <img src="<?php echo $treks_src; ?>/assets/img/user.svg" alt="logo" />
                <h3 class="numbers-heading"><?php echo count($district_schools_students); ?></h3>
                <p class="name-text">Students</p>
            </div>
        </div>
    </section>

    <!-- Recent TREKs -->
    <!-- <section class="recent-treks-section">
        <div class="recent-treks-section-div">
            
            <div class="recent-treks-header section-div-header">
                <h2>Top TREKs</h2>
                <div>
                    <a href="#">See All</a>
                </div>
            </div>
            
            <div class="recent-treks-cards-list">
                
                
                <div class="recent-treks-card-body">
                    <div>
                        <img src="<?php // echo $treks_src; ?>/assets/img/admin_rec_tre_img1.svg" />
                    </div>
                    <div>
                        <h3>5.12A Interdependence</h3>
                        <span>Due date: May 17, 2023</span>
                    </div>
                </div>

                
                <div class="recent-treks-card-body">
                    <div>
                        <img src="<?php // echo $treks_src; ?>/assets/img/admin_rec_tre_img2.svg" />
                    </div>
                    <div>
                        <h3>5.7B Forces & Experimental Design</h3>
                        <span>Due date: May 17, 2023</span>
                    </div>
                </div>

                
                <div class="recent-treks-card-body">
                    <div>
                        <img src="<?php // echo $treks_src; ?>/assets/img/admin_rec_tre_img3.svg" />
                    </div>
                    <div>
                        <h3>5.6A Physical Properties</h3>
                        <span>Due date: May 17, 2023</span>
                    </div>
                </div>
            </div>
        </div>
    </section> -->



    <script src="https://code.jquery.com/jquery-3.6.3.js"
        integrity="sha256-nQLuAZGRRcILA+6dMBOvcRh5Pe310sBpanc6+QBmyVM=" crossorigin="anonymous"></script>
    <script
        src="<?php echo $treks_src; ?>/js/Animated-Circular-Progress-Bar-with-jQuery-Canvas-Circle-Progress/dist/circle-progress.js"></script>
    <script src="<?php echo $treks_src; ?>/js/custom.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4"
        crossorigin="anonymous"></script>
</body>

</html>