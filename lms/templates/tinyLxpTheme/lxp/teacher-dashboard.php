<?php
  global $userdata;
  global $treks_src;
  global $livePath;

  $teacher_post = lxp_get_teacher_post( get_userdata(get_current_user_id())->ID );
  if (is_null($teacher_post)) {
    die("This account is inactive. Please contact site administrator. ". '<a href="' . wp_logout_url("login") . '">Logout</a>');
  }
  // Start the loop.
  $courseId =  isset($_GET['courseid']) ? $_GET['courseid'] : get_post_meta($post->ID, 'tl_course_id', true);
  $courses_count = 6;
  $args = array(
    'posts_per_page'   => 3,
    'post_type'        => LP_COURSE_CPT
  );

  $courses_saved = get_post_meta($teacher_post->ID, 'courses_saved');
  if ($courses_saved) {
    $args['post__not_in'] = $courses_saved;
    if (is_array($courses_saved) && count($courses_saved) < $courses_count) {
      $courses_count = $courses_count - count($courses_saved);
      $args['posts_per_page']   = $courses_count;
    } else {
      $courses_count = 1;
    }
  }

  $lxp_visited_courses = get_post_meta($teacher_post->ID, 'lxp_visited_courses');
  $lxp_visited_courses_to_show = is_array($lxp_visited_courses) && count($lxp_visited_courses) > 0 ? array_diff(array_reverse($lxp_visited_courses), $courses_saved) : array();
  if (count($lxp_visited_courses_to_show) > 0) {
    $args['post__in'] = $lxp_visited_courses_to_show;
    $args['orderby'] = 'post__in';
  } else {
    /* $args['meta_key'] = 'sort';
    $args['orderby'] = 'meta_value_num';
    $args['order'] = 'ASC'; */
  }

  if ( get_userdata(get_current_user_id())->user_email === "guest@rpatreks.com" ) {
    $args = array(
      'include' => '15',
      'post_type'        => LP_COURSE_CPT,
      'order' => 'post__in'
    );
  }

  $courses = get_posts($args);
  while (have_posts()) : the_post();

  if (is_null($teacher_post)) {
    die("Teacher is not associated with any school. contact admin. <a href='". wp_logout_url("login") ."'>Logout</a>");
  }
  $assignments = lxp_get_teacher_assignments($teacher_post->ID);
  $teacher_saved_courses = lxp_get_teacher_saved_courses($teacher_post->ID, $courses_saved);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php the_title(); ?></title>
  <link href="<?= $treks_src; ?>/style/main.css" rel="stylesheet" />
  <link rel="stylesheet" href="<?= $treks_src; ?>/style/header-section.css" />
  <link href="<?= $treks_src; ?>/style/treksstyle.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
    integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous" />
  <link href="<?= $treks_src; ?>/style/treksstyle.css" rel="stylesheet" />
  <link rel="stylesheet" href="<?= $treks_src; ?>/style/newAssignment.css" />
  <script src="https://code.jquery.com/jquery-3.6.3.js" integrity="sha256-nQLuAZGRRcILA+6dMBOvcRh5Pe310sBpanc6+QBmyVM="
    crossorigin="anonymous"></script>

    <style type="text/css">
      .treks-card {
        width: 300px !important;
        position: relative;
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
      .student-stats-link {
        padding-left: 10px !important;
      }
      .student-progress-link {
        color: #000000 !important;
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
      .bg-gray {
          background: #757575 !important;
      }
      .bg-orange {
          background: #de6c03 !important;
      }
      .bg-green {
          background: #6dc200 !important;
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
              <img src="<?= $treks_src; ?>/assets/img/header_search.svg" alt="svg" />
              <form action="<?= site_url("search"); ?>">
                  <input placeholder="Search" id="q" name="q" value="<?= isset($_GET["q"]) ? $_GET["q"]:''; ?>" />
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

    <!-- Recent Courses -->
    <section class="recent-treks-section">
      <div class="recent-treks-section-div">
        <!--  Courses header-->
        <div class="recent-treks-header section-div-header">
          <h2>My Courses</h2>
          <div>
            <a href="<?= site_url('courses'); ?>">See All</a>
          </div>
        </div>
        <!-- Courses cards -->
        <div class="recent-treks-cards-list">
          <!-- Saved Courses cards -->
          <?php
            foreach($teacher_saved_courses as $course) {
          ?>
            <a href="<?= get_post_permalink($course->ID); ?>" class="treks-card-link">
              <div class="recent-treks-card-body treks-card">
                  <div class="treks-card-saved"><img width="35" height="35" src="<?= $treks_src; ?>/assets/img/trek-save-filled-icon.svg" alt="svg" /></div>
                  <div class="treks-card-saved-back"></div>
                  <div>
                    <?php
                      if ( has_post_thumbnail( $course->ID ) ) {
                          echo get_the_post_thumbnail($course->ID, "medium", array( 'class' => 'rounded' )); 
                      } else {
                    ?>
                      <img width="300" height="180" src="<?= $treks_src; ?>/assets/img/tr_main.jpg" class="rounded wp-post-image" /> 
                    <?php        
                        }
                    ?>
                  </div>
                  <div>
                    <h3><?= get_the_title($course->ID); ?></h3>
                    <!-- <span>Due date: May 17, 2023</span> -->
                  </div>
                </div>
            </a>
          <?php } ?>
          <!-- Visited Courses cards -->
          <?php
            foreach($courses as $course) {
          ?>
              <a href="<?= get_post_permalink($course->ID); ?>" class="treks-card-link">
                <div class="recent-treks-card-body treks-card">
                    <div>
                      <?php
                        if ( has_post_thumbnail( $course->ID ) ) {
                            echo get_the_post_thumbnail($course->ID, "medium", array( 'class' => 'rounded' )); 
                        } else {
                      ?>
                        <img width="300" height="180" src="<?= $treks_src; ?>/assets/img/tr_main.jpg" class="rounded wp-post-image" /> 
                      <?php        
                        }
                      ?>
                    </div>
                    <div>
                      <h3><?= get_the_title($course->ID); ?></h3>
                      <!-- <span>Due date: May 17, 2023</span> -->
                    </div>
                  </div>
              </a>
            <?php } ?>       
        </div>
      </div>
    </section>

    <!--Pending Assignments  -->
    <section class="pending-assignments-section">
      <div class="pending-assignments-section-div">
        <!--  header -->
        <div class="pending-assignments-header section-div-header">
          <h2>Pending Assignments</h2>
          <div>
            <a href="<?= site_url('calendar');?>">See All</a>
          </div>
        </div>
        <!--  table -->
        <div class="pending-assignments-table">
          <table>
            <thead>
              <tr>
                <th>Class</th>
                <th>Course</th>
                <th>Lesson</th>
                <th>Date</th>
                <th>Student Progress</th>
                <th>Students Submitted</th>
                <th>Students Graded</th>
              </tr>
            </thead>
            <tbody>  
              <?php 
                foreach ($assignments as $assignment) { 
                  $course = get_post(get_post_meta($assignment->ID, 'course_id', true));
                  if (is_object($course)) {
                  $calendar_selection_info = json_decode(get_post_meta($assignment->ID, 'calendar_selection_info', true));
                  $start = '';
                  if (!is_null($calendar_selection_info) && property_exists($calendar_selection_info, 'start') && gettype($calendar_selection_info->start) === 'string') {
                    $start = $calendar_selection_info->start;
                  } elseif (!is_null($calendar_selection_info) && property_exists($calendar_selection_info, 'start') && gettype($calendar_selection_info->start) === 'object') {
                    $start = $calendar_selection_info->start->date;
                  }
                  $end = '';
                  if (!is_null($calendar_selection_info) && property_exists($calendar_selection_info, 'end') && gettype($calendar_selection_info->end) === 'string') {
                    $end = $calendar_selection_info->end;
                  } elseif (!is_null($calendar_selection_info) && property_exists($calendar_selection_info, 'end') && gettype($calendar_selection_info->end) === 'object') {
                    $end = $calendar_selection_info->end->date;
                  }
                  $class_id = intval(get_post_meta($assignment->ID, 'class_id', true));
                  $class_post = get_post(get_post_meta($assignment->ID, 'class_id', true));
                  $lxp_lesson_post = get_post(get_post_meta($assignment->ID, 'lxp_lesson_id', true));
                  $section_repository = new TL_LearnPress_Section_Repository();
                  $section_name = $section_repository->get_section_name_by_item_id($lxp_lesson_post->ID);
                  
                  $student_stats = lxp_assignment_stats($assignment->ID);
                  $statuses = array("To Do", "In Progress");
                  $students_in_progress = array_filter($student_stats, function($studentStat) use ($statuses) {
                    return in_array($studentStat["status"], $statuses);
                  });
                  $statuses = array("Completed");
                  $students_graded = 0;
                  $students_completed = array_filter($student_stats, function($studentStat) use ($statuses, $assignment, &$students_graded) {
                    $ok = false;
                    $ok = in_array($studentStat["status"], $statuses);
                    if ($ok) {
                      $assignment_submission_item = lxp_get_assignment_submissions($assignment->ID, $studentStat["ID"]);
                      if (count($assignment_submission_item) > 0 && get_post_meta($assignment_submission_item['ID'], 'mark_as_graded', true) === 'true') {
                        $students_graded++;
                        $ok = false;
                      }
                    }
                    return $ok;
                  });
              ?>
                <tr>
                <td><?= $class_id > 0 && $class_post ? $class_post->post_title : '---'; ?></td>
                  <td>
                    <?php 
                      $course_title = str_replace("'", "`", $course->post_title);
                      $lxp_lesson_post->post_title = str_replace('"', "`", $lxp_lesson_post->post_title);
                      echo $course_title; 
                      $thumbnail = has_post_thumbnail( $course->ID ) ? get_the_post_thumbnail_url($course->ID) : $treks_src.'/assets/img/tr_main.jpg';
                    ?>
                  </td>
                  <td>
                    <div class="assignments-table-cs-td-poly">
                      <div class="polygon-shap">
                        <span>L</span>
                      </div>
                      <div>
                        <span><?= $lxp_lesson_post->post_title; ?></span>
                      </div>
                    </div>
                  </td>
                  <td>
                    <?php
                      $start_date = get_post_meta($assignment->ID, "start_date", true);
                      $start_date = date("M d, Y", strtotime($start_date));
                      echo $start_date;
                    ?>
                  </td>
                  <td>
                    <div class="student-stats-link"><a href="#" onclick="fetch_assignment_stats(<?= $assignment->ID; ?>, '<?= $thumbnail; ?>', '<?= $course_title; ?>', '<?= $section_name; ?>', '<?= $lxp_lesson_post->post_title; ?>', ['To Do', 'In Progress'], '<?= $start; ?>', '<?= $end; ?>')"><?= count($students_in_progress); ?>/<?= count($student_stats); ?></a></div>
                  </td>
                  <td>
                    <div class="student-stats-link"><a href="#" onclick="fetch_assignment_stats(<?= $assignment->ID; ?>, '<?= $thumbnail; ?>', '<?= $course_title; ?>', '<?= $section_name; ?>', '<?= $lxp_lesson_post->post_title; ?>', ['Completed'], '<?= $start; ?>', '<?= $end; ?>')"><?= count($students_completed); ?>/<?= count($student_stats); ?></a></div>
                  </td>
                  <td>
                    <div class="student-stats-link"><a href="#" onclick="fetch_assignment_stats(<?= $assignment->ID; ?>, '<?= $thumbnail; ?>', '<?= $course_title; ?>', '<?= $section_name; ?>', '<?= $lxp_lesson_post->post_title; ?>', ['Graded'], '<?= $start; ?>', '<?= $end; ?>')"><?= $students_graded; ?>/<?= count($student_stats); ?></a></div>
                  </td>
                </tr>  
              <?php } } ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </section>
  <?php include $livePath.'/lxp/assignment-stats-modal.php'; ?>

  <script src="<?= $treks_src; ?>/js/Animated-Circular-Progress-Bar-with-jQuery-Canvas-Circle-Progress/dist/circle-progress.js"></script>
  <script src="<?= $treks_src; ?>/js/custom.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4"
    crossorigin="anonymous"></script>
  
  <script type="text/javascript">
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
          student.status = 'Submitted';
          break;
        case 'Graded':
          statusClass = 'bg-blue';
          break;
      }
      return `
          <tr>
              <td>
              <div class="table-user">
                  <img src="<?= $treks_src; ?>/assets/img/profile-icon.png" alt="user" />
                  <div class="user-about">
                  <h5><a class='student-progress-link' href='<?= site_url("grade-assignment"); ?>?assignment=` + assignment_id + `&student=` + student.ID + `'>` + student.name + `</a></h5>
                  </div>
              </div>
              </td>
              <td>
              <div class="table-status ` + statusClass + `">` + student.status + `</div>
              </td>
              <td><a class='student-progress-link' href='<?= site_url("grade-assignment"); ?>?assignment=` + assignment_id + `&student=` + student.ID + `'>` + student.progress + `</a></td>
              <td>` + student.score + `</td>
              <td><a href='<?= site_url("grade-assignment"); ?>/?assignment=` + assignment_id + `&student=` + student.ID + `'><img src="<?= $treks_src; ?>/assets/img/review-icon.svg" alt="svg" width="30" /></a></td>
          </tr>
      `;
    }
  </script>
</body>

</html>
<?php endwhile; ?>