<?php
global $post;
global $treks_src;
$student_post = lxp_get_student_post( get_userdata(get_current_user_id())->ID );
$treks_filtered = array();
$treks_saved = get_post_meta($student_post->ID, 'treks_saved');

$treks = array();
if ( isset($_GET['q']) && strlen($_GET['q']) > 0 ) {
    $args = array(
        'posts_per_page'   => -1,
        'post_type'        => TL_COURSE_CPT,
        // 'meta_key'        => 'sort',
        'orderby'        => 'meta_value_num',
        'order' => 'asc'
    );
    $args['s'] = urldecode($_GET['q']);  
    $treks = get_posts($args);
    
    unset($args['s']);
    $args['meta_query'] = array('key' => 'strands', 'value' => urldecode($_GET['q']), 'compare' => 'LIKE');
    $treks_with_strands = get_posts($args);
    $treks_with_strands = array_map(function ($trek) { $trek->strands = implode(', ', get_post_meta($trek->ID, 'strands')); return $trek; }, $treks_with_strands );    
    $treks = array_merge($treks, $treks_with_strands);

    unset($args['s']);
    unset($args['meta_query']);
    $trek_ids = implode(',', array_map(function($trek_post) { return $trek_post->ID; }, get_posts($args)));
    $trek_sections_data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}trek_sections WHERE trek_id IN (" . $trek_ids . ") AND content LIKE '%" . urldecode($_GET['q']) . "%'");
    $trek_sections = array_map(function($trek_section) { 
        $content_string = strip_tags( html_entity_decode($trek_section->content) );
        $content = null;
        $q = urldecode($_GET['q']);
        if (strpos($content_string, $q)) {
            $content = '<strong class="searched-term">'. $q .'</strong>' . substr($content_string, (strpos($content_string, $q) + strlen($q)), 100) . '...';
        }
        return ['trek_id' => $trek_section->trek_id, 'title' => $trek_section->title, 'content' => $content]; 
    }, $trek_sections_data);
    
    $treks_with_segments =  array_map(function($trek_section) { 
        $trek_post = get_post($trek_section['trek_id']);
        $trek_post->segment = $trek_section; 
        return $trek_post;
    }, $trek_sections);
    
    $treks = array_merge($treks, $treks_with_segments);
} else if(isset($_GET['q']) && strlen($_GET['q']) === 0) {
    wp_redirect(site_url('search'));
    exit;
}

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
      .underline {
        text-decoration: underline;
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
        <section class="main-container">
            <!-- Nav Section -->
            <nav class="nav-section">
                <?php include $livePath.'/trek/navigation-student.php'; ?>
            </nav>
        </section>
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
            <section id="search-results-container" class="recent-treks-section filter_treks_section filter_my_treks_sec" style="width: 80%; margin: 0 auto;">
                <p>Results found<?php echo isset($_GET['q']) ? ' for "' . $_GET['q'] . '":' : ':' ?></p>
                <?php if (count($treks) > 0) { ?>
                    <?php foreach ($treks as $trek) { ?>
                        <?php if (property_exists($trek, 'strands')) { ?>
                            <div class="recent-treks-section-div">
                                <h4><a href="<?php echo get_permalink($trek->ID); ?>" target="_blank"><?php echo $trek->post_title; ?></a></h4>
                                <p><?php echo $trek->post_content; ?></p>
                                <p><i class="underline">Strand</i>: <?php echo $trek->strands; ?></p>
                            </div>
                            <br />
                        <?php } else if (property_exists($trek, 'segment') && !is_null($trek->segment['content'])) { ?>
                            <div class="recent-treks-section-div">
                                <h4><a href="<?php echo get_permalink($trek->ID); ?>" target="_blank"><?php echo $trek->post_title; ?></a></h4>
                                <p><?php echo $trek->post_content; ?></p>
                                <p><i class="underline">Segment > <?php echo $trek->segment['title']; ?></i> : ..... <?php echo $trek->segment['content']; ?>...
                            </div>
                            <br />
                        <?php } else if( !property_exists($trek, 'strands') && !property_exists($trek, 'segment') ) { ?>
                            <div class="recent-treks-section-div">
                                <h4><a href="<?php echo get_permalink($trek->ID); ?>" target="_blank"><?php echo $trek->post_title; ?></a></h4>
                                <p><?php echo $trek->post_content; ?></p>
                            </div>
                            <br />
                        <?php } ?>
                    <?php } ?>
                <?php } else { ?>
                    <div class="recent-treks-section-div">
                        No results found<?php echo isset($_GET['q']) ? ' for "' . $_GET['q'] . '".' : '.' ?>
                    </div>
                    <br />
                <?php } ?>
                 
                <div class="recent-treks-section-div" id="search-loading">
                    Loading...
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
        jQuery(document).ready(function() {
            // fetch search results
            let host = window.location.hostname === 'localhost' ? window.location.origin + '<?php echo WORDPRESS_HOST; ?>' : window.location.origin;
            let apiUrl = host + '/wp-json/lms/v1/';
            jQuery.ajax({
                url: apiUrl + 'trek/search',
                method: 'POST',
                data: {
                    search: '<?php echo $_GET['q']; ?>',
                    user_post_id: '<?php echo $student_post->ID; ?>',
                    user_role: 'student'
                }
            }).done(function( response ) {
                let html = response.data.map(result => {
                    let assignment_id_param = result.hasOwnProperty('assignment_id') ? '&assignment_id=' + result.assignment_id : '';
                    let slides = result.slides.map(slide => `<a href="` + result.lesson_link + `?slide=` + (slide + 1) + assignment_id_param + `" target="_blank">Slide ` + (slide + 1) + `</a>`);
                    let slides_html = slides.join(', ');
                    return `<div class='recent-treks-section-div'>
                        <h4><a href="` + result.trek_link + `" target="_blank">` + result.trek_title + `</a></h4>
                        <p class="underline"><i>Digital Student Journal > ` + result.lesson_title + `</i> :</p>
                        <p>` + slides_html + `</p>
                    </div>`;
                });
                jQuery("#search-loading").remove();
                jQuery('#search-results-container').append(html.join( "<br />" ));
            }).fail(function (response) {
                console.error("Can not load teacher");
            });
            
        })
    </script>
</body>

</html>
<?php endwhile; ?>