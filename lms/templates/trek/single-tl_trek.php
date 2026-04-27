<?php
$treks_src = plugin_dir_url( __FILE__ ) . 'treks-src';
// Start the loop.
$courseId =  isset($_GET['courseid']) ? $_GET['courseid'] : get_post_meta($post->ID, 'tl_course_id', true);
$args = array(
	'posts_per_page'   => -1,
	'post_type'        => TL_LESSON_CPT,
	'meta_query' => array(
		array(
			'key'   => 'tl_course_id',
			'value' =>  $courseId
		)
	)
);
$lessons = get_posts($args);
$button_styles = array();
while (have_posts()) : the_post();
global $wpdb;
$trek_sections = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}trek_sections WHERE trek_id={$post->ID}");
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php the_title(); ?></title>
    <link href="<?php echo $treks_src; ?>/style/common.css" rel="stylesheet" />
    <link href="<?php echo $treks_src; ?>/style/treksstyle.css" rel="stylesheet" />
    <link href="<?php echo $treks_src; ?>/style/style-trek-section.css" rel="stylesheet" />

    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
      integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65"
      crossorigin="anonymous"
    />

    <style type="text/css">
      .trek-section-hide {
        display: none;
      }
      .trek-section-nav-anchor {
        text-decoration: none;
      }
      .trek-main-heading {
        font-size: 1.5rem;
      }

      .digital-student-journal-section {
        justify-content: end;
      }
      .digital-student-journal-btn {
        width: 110% !important;
      }
      .trek-main-heading-wrapper {
        display: flex;
        width: 100%;
        justify-content: space-between;
        margin-bottom: 10px;
      }
      .trek-main-heading-top-link {
        margin-left: auto;
        background-color: #eaedf1;
        color: #979797;
        border: 1.5px solid #979797;
        padding: 6px;
        text-decoration: auto;
        font-size: 0.85rem;
      }
      .central-cncpt-section h1 {
        font-size: 1.6rem;
      }
      /* .central-cncpt-section h2 {
        font-size: 1.4rem;
      } */
      .central-cncpt-section h3 {
        font-size: 1.3rem;
      }
      .copy-anchor-icon-img {
        margin-left: 5px;
      }
      
      a:target {
        background-color: yellow !important;
      }
      
      a {
        color: #434343 !important;
      }
      
      ul {
        padding-left: 2rem !important;
      }
      table tr td {
        padding-top: 0.8rem !important;
        padding-left: 0.5rem !important;
      }
    </style>
  </head>
  <body>
    <!-- Menu -->
    <nav class="navbar navbar-expand-lg treks-nav">
      <div class="container-fluid">
        <a class="navbar-brand" href="#">
          <div class="header-logo-search">
            <!-- logo -->
            <div class="header-logo">
              <img src="<?php echo $treks_src; ?>/assets/img/header_logo.svg" alt="svg" />
            </div>
          </div>
        </a>
        <button
          class="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#navbarSupportedContent"
          aria-controls="navbarSupportedContent"
          aria-expanded="false"
          aria-label="Toggle navigation"
        >
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
              <!-- notification -->
              <div class="header-notification">
                <img
                  src="<?php echo $treks_src; ?>/assets/img/header_bell-notification.svg"
                  alt="svg"
                />
              </div>
              <!-- user detail & Image  -->
              <div class="header-user">
                <!-- User Avatar -->
                <div class="user-avatar">
                  <img src="<?php echo $treks_src; ?>/assets/img/header_avatar.svg" alt="svg" />
                </div>
                <!-- User short detail -->
                <div class="user-detail">
                  <span class="user-detail-name">Kristin Watson</span>
                  <span>Science teacher</span>
                </div>
                <!-- Arrow for open menu -->
                <div class="user-options">
                  <img src="<?php echo $treks_src; ?>/assets/img/header_arrow open.svg" alt="svg" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Basic Container -->
    <section class="main-container">
      <!-- Nav Section -->
      <nav class="nav-section">
        <ul>
          <li>
            <img src="<?php echo $treks_src; ?>/assets/img/nav_dashboard-dots_gray.svg" />
            <a href="<?php echo site_url("dashboard") ?>">Dashboard</a>
          </li>
          <li class="nav-section-selected">
            <img src="<?php echo $treks_src; ?>/assets/img/nav_treks_selected.svg" />
            <a href="/">TREKs</a>
          </li>
          <li>
            <img src="<?php echo $treks_src; ?>/assets/img/nav_students.svg" />
            <a href="/">Students</a>
          </li>
          <li>
            <img src="<?php echo $treks_src; ?>/assets/img/nav_reports.svg" />
            <a href="/">Reports</a>
          </li>
        </ul>
      </nav>

      <!-- My TREKs breadcrumbs -->
      <section class="my-trk-bc-section">
        <div class="my-trk-bc-section-div">
          <!-- breadcrumbs -->
          <img class="bc-img-1" src="<?php echo $treks_src; ?>/assets/img/bc_img.svg" />
          <p>My TREKs</p>
          <img class="bc-img-2" src="<?php echo $treks_src; ?>/assets/img/bc_arrow_right.svg" />
          <p><?php the_title(); ?></p>
        </div>
      </section>
      <!-- My TREKs Detail -->
      <section class="my-trk-detail-section">
        <div class="my-trk-detail-section-div">
          <!-- TREKs image  -->
          <div class="my-trk-detail-img">
            <!-- <img src="<?php //echo $treks_src; ?>/assets/img/tr_main.png" /> -->
            <?php the_post_thumbnail('medium', array( 'class' => 'rounded' )); ?>
          </div>
          <!-- TREKs detail -->
          <div class="my-trk-detail-prep">
            <!-- Title -->
            <div class="detail-prep-title">
              <h2><?php the_title(); ?></h2>
            </div>
            <!-- Description -->
            <div class="detail-prep-desc">
				      <p><?php echo $post->post_content; ?></p>
            </div>

            <!-- Navigation -->
            <div class="detail-prep-tags">
              <?php
                if ( $trek_sections ) {
                  foreach ( $trek_sections as $trek_section ) {
                    $button_style = strtolower($trek_section->title);
                    $defined_button_styles = ['overview', 'recall', 'apply'];
                    $button_style = in_array($button_style, $defined_button_styles) ? $button_style : 'pa';
                    $button_styles[trim($trek_section->title)] = "$button_style-poly-body";
              ?>
                  <!-- Navigation Button -->
                  <a href="#<?php echo implode('_', explode(' ', $trek_section->title));?>" class="trek-section-nav-anchor"> 
                    <div class="tags-body <?php echo $button_style; ?>-poly-body">
                      <div class="tags-body-polygon">
                        <span><?php echo substr($trek_section->title, 0, 1);?></span>
                      </div>
                      <div class="tags-body-detail">
                        <span><?php echo $trek_section->title;?></span>
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

      <!-- TREKs assigned to -->
      <section class="trk-assign-section">
        <div class="trk-assign-section-div">
          <p>TREKs assigned to</p>
          <a href="#">0 Students</a>
        </div>
      </section>
      <?php 
        if ( $trek_sections ) {
          foreach ( $trek_sections as $trek_section ) {
            $trek_section_hide = strtolower(trim($trek_section->title)) !== 'overview' ? 'trek-section-hide' : ''; 
      ?>
            <section class="central-cncpt-section <?php echo $trek_section_hide; ?> <?php echo 'trek-section-'.implode('_', explode(' ', $trek_section->title));?>">
              <!-- section heading -->
              <div class="trek-main-heading-wrapper">
                <h1 class="trek-main-heading" id="<?php echo implode('_', explode(' ', $trek_section->title));?>"><?php echo $trek_section->title;?></h1>                
              </div>
              
              <!-- digital journal link -->
              <?php
                $digital_journal_link = null;
                foreach($lessons as $lesson){ if (trim($trek_section->title) === trim($lesson->post_title)) { $digital_journal_link = get_permalink($lesson->ID); }; }
              ?>

              <div class="digital-student-journal-section my-trk-detail-section-div">
                <div class="detail-prep-tags">
                  <a href="<?php echo $digital_journal_link ?>" target="_blank" class="trek-section-nav-anchor"> 
                    <div class="digital-student-journal-btn tags-body <?php echo $button_styles[trim($trek_section->title)]; ?>">
                      <div class="tags-body-detail">
                        <span>Digital Student Journal</span>
                      </div>
                    </div>
                  </a>
                </div>
              </div>

              <div class="trek-main-body-wrapper">
                <?php echo stripslashes($trek_section->content);?>
              </div>
            </section>    
      <?php
          }
        }
      ?>
      
    </section>

    <script
      src="https://code.jquery.com/jquery-3.6.3.js"
      integrity="sha256-nQLuAZGRRcILA+6dMBOvcRh5Pe310sBpanc6+QBmyVM="
      crossorigin="anonymous"
    ></script>
    <script src="<?php echo $treks_src; ?>/js/Animated-Circular-Progress-Bar-with-jQuery-Canvas-Circle-Progress/dist/circle-progress.js"></script>
    <script src="<?php echo $treks_src; ?>/js/custom.js"></script>
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4"
      crossorigin="anonymous"
    ></script>
    
    <script type="text/javascript">

      jQuery(document).ready(function() {
        
        var trekUrl = "<?php echo get_permalink($post->ID); ?>";

        // set copy icons to links
        jQuery("a").each(function() {
          if ($(this).attr("id")) {
            // prevent link action from navigation if it is #
            if ($(this).attr("href") === "#") {
              $(this).on('click', function(e) {
                e.preventDefault();
              });
            }

            $( '<a class="copy-anchor" href="#' + $(this).attr("id") + '" id="' + $(this).attr("id") + '_Copy_Link"><img class="copy-anchor-icon-img" src="<?php echo $treks_src; ?>/assets/img/link_icon.png" width="20" height="20" /></a>' ).insertAfter( $(this) );
            jQuery('#' + jQuery(this).attr("id") + '_Copy_Link').on('click', function(e) {
              e.preventDefault();

              var targetLinkArr = jQuery(this).attr("href").split('#');
              targetLinkId = targetLinkArr.length === 2 ? targetLinkArr[1] : null;
              if (targetLinkId) {
                var trekTargetUrl = trekUrl + "#" + targetLinkId;
                console.log("copy -- ", trekTargetUrl);

                document.addEventListener('copy', function(e) {
                    e.clipboardData.setData('text/plain', trekTargetUrl);
                    e.preventDefault();
                }, true);

                document.execCommand('copy');  
              }
            })
          }
        });

        // ************ Navigation Tabs ****************
        jQuery('a.trek-section-nav-anchor').on('click', function(e) {
          e.preventDefault();
          const navHref = jQuery(this).attr('href').split('#');
          if (navHref.length > 1) {
            jQuery('.central-cncpt-section').addClass('trek-section-hide');
            jQuery('.trek-section-' + navHref[1]).removeClass('trek-section-hide');
          }
        });

        // ********* Execute the bookmark link ****************
        if (location.hash && jQuery('a' + location.hash).length > 0) {
          jQuery('section.central-cncpt-section').get().forEach(function(section) {            
            if (!jQuery(section).hasClass('trek-section-hide')) {
              jQuery(section).addClass('trek-section-hide');
            }
          });
          jQuery('a' + location.hash).parents('section.central-cncpt-section').removeClass('trek-section-hide');
          const bookmarkPosition = jQuery('a' + location.hash).position();
          if (bookmarkPosition) {
            window.scrollTo(bookmarkPosition.left, bookmarkPosition.top);
          }
        }
      });


    </script>
  </body>
</html>
<?php endwhile; ?>