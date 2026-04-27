<?php
global $treks_src;

// Start the loop.
$courseId =  isset($_GET['courseid']) ? $_GET['courseid'] : get_post_meta($post->ID, 'tl_course_id', true);
$args = array(
	'posts_per_page'   => 3,
	'post_type'        => LP_COURSE_CPT,
  'order' => 'asc',
);

if ( get_userdata(get_current_user_id())->user_email === "guest@rpatreks.com" ) {
  $args = array(
    'include' => '15',
    'post_type'        => LP_COURSE_CPT,
    'order' => 'post__in'
  );
}

$treks = get_posts($args);
while (have_posts()) : the_post();

$teacher_post = lxp_get_teacher_post( get_userdata(get_current_user_id())->ID );
if (is_null($teacher_post)) {
  die("Teacher is not associated with any school. contact admin. <a href='". wp_logout_url("login") ."/'>Logout</a>");
}
$assignments = lxp_get_teacher_assignments($teacher_post->ID);
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
  <link href="<?php echo $treks_src; ?>/style/treksstyle.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
    integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous" />
  <link href="<?php echo $treks_src; ?>/style/treksstyle.css" rel="stylesheet" />
  <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/newAssignment.css" />

  <script src="https://code.jquery.com/jquery-3.6.3.js" integrity="sha256-nQLuAZGRRcILA+6dMBOvcRh5Pe310sBpanc6+QBmyVM="
    crossorigin="anonymous"></script>

    <style type="text/css">
      .treks-card {
        width: 300px !important;
      }
      .treks-card-link {
        text-decoration: none !important;
      }
      .polygon-shap {
        padding-top: 12px !important;
      }
      .student-stats-link {
        padding-left: 10px !important;
      }
      .student-progress-link {
        color: #000000 !important;
        text-decoration: none !important;
      }
      .bg-gray {
          background: #757575 !important;
      }
      .bg-orange {
          background: #de6c03 !important;
      }
      .bg-blue {
          background: #1fa5d4 !important;
      }
    </style>
</head>

<body>
  <nav class="navbar navbar-expand-lg bg-light">
    <div class="container-fluid">
      <?php include $livePath.'/trek/header-logo.php'; ?>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
        aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
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
  <section class="main-container">
    <!-- Nav Section -->
    <nav class="nav-section">
      <?php include $livePath.'/trek/navigation.php'; ?>
    </nav>

    <!--Pending Assignments  -->
    <section class="pending-assignments-section">
      <div class="pending-assignments-section-div">
        <!--  header -->
        <div class="pending-assignments-header section-div-header">
          <!-- <h2>Pending</h2> -->
                <!-- Table Section -->
          <section class="recent-treks-section-div table-section">            
              <nav class="nav-section treks_nav table_tabs">                
                  <ul class="treks_ul" id="myTab" role="tablist">
                      <li>
                          <button class="nav-link" data-bs-toggle="tab"
                              data-bs-target="#pending-tab-content" type="button" role="tab" aria-controls="pending-tab-content"
                              aria-selected="false">
                              Pending
                          </button>
                      </li>
                      <li>
                          <button class="nav-link" data-bs-toggle="tab"
                              data-bs-target="#completed-tab-content" type="button" role="tab"
                              aria-controls="completed-tab-content" aria-selected="true">
                              Completed
                          </button>
                      </li>
                  </ul>
                  
                  <a href="<?php echo site_url("assignment/?course=0&section=0") ?>" title="Add Assignment" class='btn btn-success' style="color:#FFFFFF;" role='button'> Add Assignment
                  </a>
                  <div>                    
                    <img src="<?php echo $treks_src; ?>/assets/img/calendar<?php echo $post->post_name === "calendar" ? "-selected" : ""; ?>.svg" />
                    <a href="<?php echo site_url("calendar") ?>">Calendar</a>
                  </div>
              </nav>              
              <div class="tab-content">
                  <?php
                    $args['assignments'] = $assignments;
                    include $livePath.'/lxp/teacher-pending-assignment-tab.php';
                    include $livePath.'/lxp/teacher-completed-assignment-tab.php';
                  ?>
              </div>
          </section>
        </div>
        
      </div>
    </section>
  </section>
  <?php include $livePath.'/lxp/assignment-stats-modal.php'; ?>

  <script src="<?php echo $treks_src; ?>/js/Animated-Circular-Progress-Bar-with-jQuery-Canvas-Circle-Progress/dist/circle-progress.js"></script>
  <script src="<?php echo $treks_src; ?>/js/custom.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4"
    crossorigin="anonymous"></script>
  
  <script type="text/javascript">
    jQuery(document).ready(function(){
      jQuery('.nav-link').on('show.bs.tab', function (event) {
          localStorage.setItem("teacher_assignments_tab", jQuery(event.target).attr('data-bs-target'));
      });

      let current_tab = localStorage.getItem("teacher_assignments_tab");
      if (current_tab) {
          let tabEl = jQuery('button.nav-link[data-bs-target="' + current_tab + '"]');
          var tab = new bootstrap.Tab(tabEl);
          tab.show();
      } else {
          let tabEl = jQuery('button.nav-link[data-bs-target="' + '#pending-tab-content' + '"]');
          var tab = new bootstrap.Tab(tabEl);
          tab.show();
      }
    });

    function fetch_assignment_stats(assignment_id, course_thumbnail, course_title, section_title, lesson_title, statuses, start, end) {

      jQuery('#student-course-thumbnail').html(`<img width="40" height="40" style="border-radius: 5px;" src="`+course_thumbnail+`"><h3 class="inter-user-name" id="student-course-title"></h3>`);
      jQuery('#student-course-title').text(course_title);
      jQuery('#student-section-title').text(section_title);
      jQuery('#student-lesson-title').text(lesson_title);

      // starting date and time
      let start_date = new Date(start);
      let start_date_string = start_date.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric', year: 'numeric' });
      let start_time_string = start_date.toLocaleTimeString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true });
      jQuery('#student-progress-trek-start-time').text(start_date_string + ' ' + start_time_string);
      // ending date and time
      let end_date = new Date(end);
      let end_date_string = end_date.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric', year: 'numeric' });
      let end_time_string = end_date.toLocaleTimeString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true });
      jQuery('#student-progress-trek-end-time').text(end_date_string + ' ' + end_time_string);

      var segmentColor = "#1fa5d4";
      jQuery('.students-modal .modal-content .modal-body .students-breadcrumb .interdependence-tab .inter-tab-polygon, .assignment-modal .modal-content .modal-body .assignment-modal-left .recall-user .inter-tab-polygon').css('background-color', segmentColor);
      jQuery('.students-modal .modal-content .modal-body .students-breadcrumb .interdependence-tab .inter-tab-polygon-name, .assignment-modal .modal-content .modal-body .assignment-modal-left .recall-user .inter-user-name').css('color', segmentColor);
      
      window.assignmentStatsModalObj.show();
      jQuery("#student-modal-loader").show();
      jQuery("#student-modal-table").hide();
      let host = window.location.hostname === 'localhost' ? window.location.origin + '<?php echo WORDPRESS_HOST; ?>' : window.location.origin;
      let apiUrl = host + '/wp-json/lms/v1/';
      jQuery.ajax({
          method: "POST",
          enctype: 'multipart/form-data',
          url: apiUrl + "assignment/stats",
          data: {assignment_id}
      }).done(function( response ) {
          const in_progress_students = response.data.filter(student => statuses.includes(student.status));
          jQuery("#student-modal-table tbody").html( in_progress_students.map(student => student_assignment_stat_row_html(student, assignment_id)).join('\n') );
          jQuery("#student-modal-loader").hide();
          jQuery("#student-modal-table").show();
      }).fail(function (response) {
          console.error("Can not load teacher");
      });
    }

    function student_assignment_stat_row_html(student, assignment_id) {
      let statusClass = '';
      switch (student.status) {
        case 'To Do':
          statusClass = 'bg-gray';
          break;
        case 'In Progress':
          statusClass = 'bg-orange';
          break;
        case 'Completed':
          statusClass = 'bg-green';
          break;
        case 'Graded':
          statusClass = 'bg-blue';
          break;
      }
      return `
          <tr>
              <td>
              <div class="table-user">
                  <img src="<?php echo $treks_src; ?>/assets/img/profile-icon.png" alt="user" />
                  <div class="user-about">
                  <h5><a class='student-progress-link' href='<?php echo site_url("grade-assignment"); ?>/?assignment=` + assignment_id + `&student=` + student.ID + `'>` + student.name + `</a></h5>
                  </div>
              </div>
              </td>
              <td>
              <div class="table-status ` + statusClass + `">` + (student.status === 'Completed' ? 'Submitted' : student.status) + `</div>
              </td>
              <td><a class='student-progress-link' href='<?php echo site_url("grade-assignment"); ?>/?assignment=` + assignment_id + `&student=` + student.ID + `'>` + student.progress + `</a></td>
              <td>` + student.score + `</td>
              <td><a href='<?php echo site_url("grade-assignment"); ?>/?assignment=` + assignment_id + `&student=` + student.ID + `'><img src="<?php echo $treks_src; ?>/assets/img/review-icon.svg" alt="svg" width="30" /></a></td>
          </tr>
      `;
    }
  </script>
</body>

</html>
<?php endwhile; ?>