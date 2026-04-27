<?php
if (!isset($_GET['filter'])) {
    $filterDefaultParams = array('filter' => 'all', 'strand' => 'none', 'search' => 'none', 'sort' => 'none', 'tekversion' => '2017');
    wp_redirect( get_permalink($post->ID) . '?' . build_query($filterDefaultParams) );
    die();
}

global $post;
global $treks_src;
$teacher_post = lxp_get_teacher_post( get_userdata(get_current_user_id())->ID );
$treks_assigned = get_post_meta($teacher_post->ID, 'treks_assigned');
$treks_assigned = is_array($treks_assigned) && count($treks_assigned) > 0 ? $treks_assigned : array(0);

$treks_filtered = array();
$treks_saved = get_post_meta($teacher_post->ID, 'treks_saved');
// filter $treks_saved to only include treks that are in $treks_assigned
$treks_saved = array_filter($treks_saved, function ($trek) use ($treks_assigned) { return in_array($trek, $treks_assigned); });

if ($_GET['filter'] == 'saved') {
    $treks_filtered = lxp_get_teacher_saved_treks($teacher_post->ID, $treks_saved, urldecode($_GET['strand']), urldecode($_GET['sort']), urldecode($_GET['search']));
} else if ($_GET['filter'] == 'recent'){
    $lxp_visited_treks = get_post_meta($teacher_post->ID, 'lxp_visited_treks');
    $lxp_visited_treks_to_show = is_array($lxp_visited_treks) && count($lxp_visited_treks) > 0 ? array_reverse($lxp_visited_treks) : array();
    // filter $lxp_visited_treks_to_show to only include treks that are in $treks_assigned
    $lxp_visited_treks_to_show = array_filter($lxp_visited_treks_to_show, function ($trek) use ($treks_assigned) { return in_array($trek, $treks_assigned); });
    $recent_query_args = array( 'post_type' => TL_COURSE_CPT , 'posts_per_page'   => -1, 'post_status' => array( 'publish' ), 'post__in' => $lxp_visited_treks_to_show, 'orderby' => 'post__in' );
    $searchVal = urldecode($_GET['search']);
    if(!($searchVal === '' || $searchVal === 'none')) {
        $recent_query_args['s'] = $searchVal;
    }
    $recent_query = new WP_Query( $recent_query_args );
    $treks_filtered = $recent_query->get_posts();
    if(!(urldecode($_GET['strand']) === '' || urldecode($_GET['strand']) === 'none')) {
        $treks_filtered = array_filter( $treks_filtered, function ($trek) { return in_array(urldecode($_GET['strand']), get_post_meta($trek->ID, 'strands')); });
    }
}
$treks_filtered_ids = array_map(function ($trek) { return $trek->ID; }, $treks_filtered);

$args = array(
    'posts_per_page'   => -1,
    'post_type'        => 'tl_trek',
    'meta_key'        => 'sort',
    'orderby'        => 'meta_value_num',
    'order' => 'asc'
);

if (count($treks_filtered_ids) > 0) {
    //$args['post__not_in'] = $treks_filtered_ids;
}

$strandVal = urldecode($_GET['strand']);
if(!($strandVal === '' || $strandVal === 'none')) {
    $args['meta_query'] = array('key' => 'strands', 'value' => $strandVal, 'compare' => '=');
}

$sortVal = urldecode($_GET['sort']);
if(!($sortVal === '' || $sortVal === 'none')) {
    $args['order'] = $sortVal;
}


$searchVal = urldecode($_GET['search']);
if(!($searchVal === '' || $searchVal === 'none')) {
    $args['s'] = $searchVal;
}

if ( get_userdata(get_current_user_id())->user_email === "guest@rpatreks.com" ) {
    $args = array(
        'include' => '15',
        'post_type'        => 'tl_trek',
        'order' => 'post__in'
    );
}

$args['include'] = $treks_assigned;
$treks = get_posts($args);

$tekversion = isset($_GET['tekversion']) ? $_GET['tekversion'] : '2017';
// filter $treks to only include treks that has tekversion meta value equal to $tekversion
$treks = array_filter($treks, function ($trek) use ($tekversion) { return get_post_meta($trek->ID, 'tekversion', true) == $tekversion; });
// filter $treks_filtered to only include treks that has tekversion meta value equal to $tekversion
$treks_filtered = array_filter($treks_filtered, function ($trek) use ($tekversion) { return get_post_meta($trek->ID, 'tekversion', true) == $tekversion; });

// Start the loop.
while (have_posts()) : the_post();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Document</title>
    <link href="<?php echo $treks_src; ?>/style/main.css" rel="stylesheet" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/header-section.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous" />
    <link href="<?php echo $treks_src; ?>/style/treksstyle.css" rel="stylesheet" />
    <style type="text/css">
        .treks-card {
            width: 300px !important;
            position: relative !important;
        }
        .treks-card-link {
            text-decoration: none !important;
        }
        /* .treks-card-saved with icon element in it in top right absolute position */
      .treks-card-saved {
        position: absolute;
        top: 0;
        right: 0;
        width: 35px;
        height: 38px;
        z-index: 2;
        margin-top: 10px;
        margin-right: 8px;
      }
      .treks-card-saved-back {
        position: absolute;
        top: 0;
        right: 0;
        width: 20px;
        height: 20px;
        z-index: 1;
        margin-top: 15px;
        margin-right: 15px;
        background-color: #ffffff;
      }

      .btn-check:checked + .btn, .btn.active, .btn.show, .btn:first-child:active, :not(.btn-check) + .btn:active {
        background-color: #0b5d7a !important;
      }

      .btn {
        border: var(--bs-btn-border-width) solid #0b5d7a !important;
      }
      .btn-outline-primary {
        --bs-btn-color: #0b5d7a !important;
      }
    </style>
</head>

<body>
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
    <!-- Basic Container -->
    <section class="main-container treks_main_container">
        <!-- Nav Section -->
        <div class="main-container nav_container">
            <nav class="nav-section nav_section_treks">
                <?php include $livePath.'/trek/navigation.php'; ?>
            </nav>
        </div>
        <!-- Recent Filters & TREKs flex -->
        <div class="filter_treks_flx">
            <!-- 
            <section class="recent-treks-section filter_treks_section">
                <div class="recent-treks-section-div recent_filter_treks_div">
                    
                    <div class="section-div-header filter_section">
                        <div class="filter_bx">
                            <img src="<?php // echo $treks_src; ?>/assets/img/filter-logo.svg" />
                            <h2>Filters</h2>
                        </div>
                        <div>
                            <a href="#">See All</a>
                        </div>
                    </div>
                    <div class="treks_bottom_border">
                        <h3 class="filter_treks_heading">TREKs</h3>
                    </div>
                    <div class="treks_bottom_border">
                        <h3 class="filter_treks_heading">Strand</h3>
                    </div>
                </div>
            </section>
             -->
            <!-- Recent TREKs -->
            <section class="recent-treks-section filter_treks_section filter_my_treks_sec" style="width: 80%; margin: 0 auto;">
                <div class="recent-treks-section-div">
                    <!--  TREKs header-->
                    <div class="section-div-header">
                        <div class="container">
                            <div class="row">
                                <div class="col col-md-2">
                                    <h2 style="margin-top: 8px;">My Courses</h2>
                                </div>  
                                <div class="col col-md-3">
                                    <select id="tekversion-drop-down" class="form-select" aria-label="Default select example">
                                        <option value="2017" <?php echo isset($_GET['tekversion']) && $_GET['tekversion'] == '2017' ? 'selected=selected' : ''; ?>>2017 TEKS (Beta)</option>
                                        <option value="2021" <?php echo isset($_GET['tekversion']) && $_GET['tekversion'] == '2021' ? 'selected=selected' : ''; ?>>2021 TEKS (New TEKS)</option>
                                    </select>
                                </div>
                                <div class="col col-md-3"></div>
                                <div class="col col-md-4"></div>
                            </div>
                            <hr />
                        </div>
                    </div>
                    <nav class="nav-section treks_nav" style="padding-top: 10px;">
                        <!-- make bootstrap row with 5 columns -->
                        <div class="row">
                            <div class="col col-md-3">
                                <label for="filterBtns" class="form-label">Filter</label>
                                <div id="filterBtns" class="form-control" style="padding: 0px; border: 0px;">
                                    <div class="btn-group" role="group" aria-label="Filter by">
                                        <input type="radio" class="btn-check" name="trekFilter" id="trekFilterAll" autocomplete="off" onclick="apply_filter('all');" <?php echo isset($_GET['filter']) && $_GET['filter'] === 'all' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-primary" for="trekFilterAll">All</label>

                                        <input type="radio" class="btn-check" name="trekFilter" id="trekFilterRecent" autocomplete="off" onclick="apply_filter('recent');" <?php echo isset($_GET['filter']) && $_GET['filter'] === 'recent' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-primary" for="trekFilterRecent">Recent</label>

                                        <input type="radio" class="btn-check" name="trekFilter" id="trekFilterSaved" autocomplete="off" onclick="apply_filter('saved');" <?php echo isset($_GET['filter']) && $_GET['filter'] === 'saved' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-primary" for="trekFilterSaved">Saved</label>
                                    </div>
                                </div>
                                
                            </div>
                            <div class="col col-md-3">
                                <label for="strandSelect" class="form-label">Strand</label>
                                <select id="strandSelect" class="form-select" aria-label="Default select example" onchange="apply_strand()">
                                    <option value="none">--- Select ---</option>
                                    <!-- add option tags for 'Matter and Energy Strand', 'Force, Motion, and Energy Strand', 'Earth and Space Strand', 'Organisms and Environments Strand' as name and value -->
                                    <option value="Matter and Energy" <?php echo isset($_GET['strand']) && urldecode($_GET['strand']) === 'Matter and Energy' ? 'selected' : '' ?>>Matter and Energy</option>
                                    <option value="Force, Motion, and Energy" <?php echo isset($_GET['strand']) && urldecode($_GET['strand']) === 'Force, Motion, and Energy' ? 'selected' : '' ?>>Force, Motion, and Energy</option>
                                    <option value="Earth and Space" <?php echo isset($_GET['strand']) && urldecode($_GET['strand']) === 'Earth and Space' ? 'selected' : '' ?>>Earth and Space</option>
                                    <option value="Organisms and Environments" <?php echo isset($_GET['strand']) && urldecode($_GET['strand']) === 'Organisms and Environments' ? 'selected' : '' ?>>Organisms and Environments</option>
                                </select>
                            </div>
                            <div class="col col-md-3">
                                <label for="standardInput" class="form-label">Standard</label>
                                <input type="text" class="form-control" placeholder="i.e 5.12A" id="standardInput" value="<?php echo isset($_GET['search']) && $_GET['search'] !== 'none' ? urldecode($_GET['search']) : '' ?>">
                            </div>
                            <div class="col col-md-2">
                                <label for="sortBtn" class="form-label">Sort</label>
                                <div class="treks_inner_flx">
                                    <!-- <img src="<?php // echo $treks_src; ?>/assets/img/filter-right-logo.svg" /> -->
                                    <a href="#" id="sortLink" style="text-decoration: none;">
                                        <div class="sort_flex_bx">
                                            <img src="<?php echo $treks_src;?>/assets/img/filter-sort-logo.svg" />
                                            <p>Sort by <?php echo isset($_GET['sort']) && ($_GET['sort'] === 'asc' || $_GET['sort'] === 'none') ? 'A-Z' : 'Z-A'; ?></p>
                                        </div>
                                    </a>
                                </div>
                            </div>
                            <div class="col col-md-1">
                                <p style="text-align: right;">
                                    <a href="<?php echo get_permalink($post->ID); ?>">Clear</a>
                                </p>
                            </div>
                        </div>  

                        <!-- <ul class="treks_ul" id="myTab" role="tablist">
                            <li>
                                <button class="nav-link active" id="all-tab" data-bs-toggle="tab"
                                    data-bs-target="#all-tab-pane" type="button" role="tab" aria-controls="all-tab-pane"
                                    aria-selected="true">All</button>
                            </li>
                        </ul> -->
                    </nav>
                    <!-- TREKs cards -->
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active recent-treks-cards-list treks_card_list" id="all-tab-pane"
                            role="tabpanel" aria-labelledby="all-tab" tabindex="0">
                            <!-- each cards  -->
                            <?php
                            if ($_GET['filter'] == 'saved' || $_GET['filter'] == 'recent') {
                                foreach($treks_filtered as $trek) {
                            ?>
                                <a href="<?php echo get_post_permalink($trek->ID); ?>" class="treks-card-link">
                                    <div class="recent-treks-card-body treks-card">
                                        <?php if (in_array($trek->ID, $treks_saved)) { ?>
                                            <div class="treks-card-saved"><img width="35" height="35" src="<?php echo $treks_src; ?>/assets/img/trek-save-filled-icon.svg" alt="svg" /></div>
                                            <div class="treks-card-saved-back"></div>
                                        <?php } ?>
                                        <div>
                                            <?php echo get_the_post_thumbnail($trek->ID, "medium", array( 'class' => 'rounded' )); ?>
                                        </div>
                                        <div>
                                        <h3><?php echo get_the_title($trek->ID); ?></h3>
                                        <!-- <span>Due date: May 17, 2023</span> -->
                                        </div>
                                    </div>
                                </a>
                            <?php
                                }
                            } else {
                            ?>
                            <?php
                                foreach($treks as $trek) {
                            ?>
                                <a href="<?php echo get_post_permalink($trek->ID); ?>" class="treks-card-link">
                                    <div class="recent-treks-card-body treks-card">
                                        <?php if (in_array($trek->ID, $treks_saved)) { ?>
                                            <div class="treks-card-saved"><img width="35" height="35" src="<?php echo $treks_src; ?>/assets/img/trek-save-filled-icon.svg" alt="svg" /></div>
                                            <div class="treks-card-saved-back"></div>
                                        <?php } ?>
                                        <div>
                                            <?php echo get_the_post_thumbnail($trek->ID, "medium", array( 'class' => 'rounded' )); ?>
                                        </div>
                                        <div>
                                        <h3><?php echo get_the_title($trek->ID); ?></h3>
                                        <!-- <span>Due date: May 17, 2023</span> -->
                                        </div>
                                    </div>
                                </a>
                            <?php
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </section>

    <script src="https://code.jquery.com/jquery-3.6.3.js"
        integrity="sha256-nQLuAZGRRcILA+6dMBOvcRh5Pe310sBpanc6+QBmyVM=" crossorigin="anonymous"></script>
    <script
        src="<?php echo $treks_src; ?>/js/Animated-Circular-Progress-Bar-with-jQuery-Canvas-Circle-Progress/dist/circle-progress.js"></script>
    <script src="<?php echo $treks_src; ?>/js/custom.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4"
        crossorigin="anonymous"></script>

    <script type="text/javascript">
        var urlQueryParams = {filter: "<?php echo $_GET['filter']; ?>", strand: "<?php echo $_GET['strand']; ?>", search: "<?php echo $_GET['search'] ?>", sort: "<?php echo $_GET['sort']; ?>", tekversion: "<?php echo $_GET['tekversion']; ?>"};
        function apply_filter(filter) {
            urlQueryParams = {...urlQueryParams, filter};
            window.location = window.filterUrl + "?" + jQuery.param(urlQueryParams);
        }

        function apply_strand() {
            urlQueryParams = {...urlQueryParams, strand: encodeURI(jQuery('#strandSelect').val())};
            window.location = window.filterUrl + "?" + jQuery.param(urlQueryParams);
        }

        function search(el) {
            console.log('EL >>> ', el.key);
        }

        jQuery(document).ready(function() {
            window.filterUrl = '<?php echo get_permalink($post->ID); ?>';

            jQuery("#standardInput").on('keyup', function (event) {
                if (event.which === 13) {
                    urlQueryParams = {...urlQueryParams, search: encodeURI(jQuery(event.target).val())};
                    window.location = window.filterUrl + "?" + jQuery.param(urlQueryParams);
                }
            });

            jQuery("#sortLink").on('click', function(event) {
                event.preventDefault();
                let sortVal = 'none';
                if (urlQueryParams.sort === 'none' || urlQueryParams.sort === 'desc') {
                    sortVal = 'asc';
                } else if (urlQueryParams.sort === 'asc') {
                    sortVal = 'desc';
                }

                urlQueryParams = {...urlQueryParams, sort: sortVal};
                window.location = window.filterUrl + "?" + jQuery.param(urlQueryParams);
            });

            $('#tekversion-drop-down').change(function () {
                var tekversion = $(this).val();
                var url = new URL(window.location.href);
                url.searchParams.set('tekversion', tekversion);
                window.location.href = url.href;
            });
        })
    </script>
</body>

</html>
<?php endwhile; ?>