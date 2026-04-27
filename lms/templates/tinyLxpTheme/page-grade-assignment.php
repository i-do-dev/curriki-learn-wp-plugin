<?php
  $livePath = dirname( __FILE__ );
  // require_once $livePath.'/lxp/functions.php';
  lxp_login_check();
  $treks_src = content_url().'/plugins/TinyLxp-wp-plugin/lms/templates/tinyLxpTheme/treks-src/';

  $userdata = get_userdata(get_current_user_id());
  $userRole = lxp_get_active_role(get_current_user_id());
  if ($userRole !== 'lp_teacher') {
    wp_redirect(site_url("/dashboard"));
  }
  if ( !(isset($_GET['assignment']) && intval($_GET['assignment']) > 0) ) {
    wp_redirect(site_url("/calendar"));
  }

  $student_id = 0;
  if ( (isset($_GET['student']) && intval($_GET['student']) > 0) ) {
    $student_id = intval($_GET['student']);
  }

  $assignment = lxp_get_assignment($_GET['assignment']);
  $assignment_type = get_post_meta($_GET['assignment'], 'assignment_type', true);
  $assignment_type = (isset($assignment_type) && $assignment_type == 'video_activity') ? $assignment_type : '';
  $students = lxp_get_students($assignment->lxp_student_ids);
  $course = get_post(get_post_meta($assignment->ID, 'course_id', true));
  $lxp_lesson_post = get_post(get_post_meta($assignment->ID, 'lxp_lesson_id', true));
    $section_repository = new TL_LearnPress_Section_Repository();
    $section_name = $section_repository->get_section_name_by_item_id($lxp_lesson_post->ID);
  $slide_current = isset($_GET['slide']) ? $_GET['slide'] : 0;
  $assignment_submission = lxp_get_assignment_submissions($assignment->ID, $student_id);
  $grade = $assignment_submission ? get_post_meta($assignment_submission['ID'], "slide_" . $slide_current . "_grade", true) : '';
  $result = $assignment_submission ? get_post_meta($assignment_submission['ID'], "slide_" . $slide_current . "_result", true) : '';
  $submit_status = (isset($assignment_submission['submission_id']) && $assignment_submission['submission_id'] != 0);
  if ($submit_status && $assignment_type == 'video_activity') {
    $assignment_submission['lti_user_id'] = get_post_meta($student_id, 'lti_user_id', true);
    // get interactions of given activity
    $all_interactions = get_interactions_by_activity($assignment_submission);
    // var_dump(); die;
    // get all statements of given activity
    if (isset($_GET['action']) && $_GET['action'] == 'grade' ) {
      // this condition is use in teacher-iv-grade-single
    } else {
      // $interaction_xapis = [];
      $interaction_xapis = get_submitted_xapi_data( $assignment_submission );
      // var_dump(count($interaction_xapis)); die;
    }
  }
  $total_grades_str = $result ? '/' .json_decode($result)->score->max : '';
  $marks_float = 0;
  $interactions_float = 0;
  $progress_float = 0;
  $pg_color = '#1fa5d4'; // polygon color
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Grade Assignment</title>
    <link href="<?= $treks_src; ?>/style/common.css" rel="stylesheet" />
    <link href="<?= $treks_src; ?>/style/main.css" rel="stylesheet" />
    <link rel="stylesheet" href="<?= $treks_src; ?>/style/header-section.css" />
    <link rel="stylesheet" href="<?= $treks_src; ?>/style/assignments.css" />
    <link rel="stylesheet" href="<?= $treks_src; ?>/style/newAssignment.css" />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
      integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65"
      crossorigin="anonymous"
    />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css"
      crossorigin="anonymous"
    />
    
    <style type="text/css">
      .time-date-box {
        margin-left: 60px;
      }

      body {
        background-color: #f6f7fa !important;
      }

      .grade-box-slide {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 8px 24px;
        width: 100%;
        border-radius: 20px;
        font-family: 'Arial';
        font-style: normal;
        font-weight: 400;
        font-size: 16px;
        line-height: 24px;
        margin: 0 0 8px;
        color: #757575;
        background: #eaedf1;
      }

      .grade-box-btn {
          display: flex;
          justify-content: center;
          align-items: center;
          padding: 8px 40px;
          font-family: 'Nunito';
          font-style: normal;
          font-weight: 500;
          font-size: 16px;
          line-height: 24px;
          margin: 0 auto;
          color: #0b5d7a;
          background-color: transparent;
          border: 1px solid #0b5d7a;
          border-radius: 8px;
          margin-top: 10px;
      }

      .grade-box {
        margin-left: 50px;
        margin-right: 50px;
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

      .no-right-border {
          border-right: 0px !important;
      }

      .summary_link {
        text-decoration: none;
      }

      .summary_link:hover {
        color: #fff;
      }

      .add-feedback-btn {
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        font-size: 14px;
      }

      .feedback_input_box {
        margin-top: 10px;
      }

      .feedback-btn {
          display: inline-block;
          cursor: pointer;
      }

      .truncate {
        width: 190px; /* Adjust as needed */
        overflow: hidden;
        display: -webkit-box;
        line-clamp: 2; /* Show only 2 lines */
        -webkit-box-orient: vertical;
      }
    </style>
  </head>

  <body>
    <div id="loader" class="text-center" style="display: none;">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p>Loading...</p>
    </div>
    <nav class="navbar navbar-expand-lg bg-light">
      <div class="container-fluid">
        <?php include $livePath.'/trek/header-logo.php'; ?>
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
    <section class="main-container" style=" margin-bottom: 0px;">
        <nav class="nav-section">
            <?php // get_template_part('trek/navigation') ?>
        </nav>
    </section>

    <!-- main secton -->
    <section class="main_assignment_section">
      <!-- back button -->
      <section class="assigmint_back_button">
        <a href="<?= site_url("assignments"); ?>/">
          <span> <img src="<?= $treks_src; ?>/assets/img/back.svg" alt="" /> </span> Back
        </a>
      </section>

      <!-- heading section -->

      <section class="assig_heading">
        <h2>Grade Assignment</h2>

        <!-- student cat -->

        <div class="students-breadcrumb">
          <div class="interdependence-user">
            <?php
              if ( has_post_thumbnail( $course->ID ) ) {
                  echo get_the_post_thumbnail($course->ID, array(50,50), array( 'class' => 'rounded wp-post-image')); 
              } else {
            ?>
              <img width="50" height="50" src="<?= $treks_src; ?>/assets/img/tr_main.jpg" class="rounded wp-post-image" /> 
            <?php
              }
            ?>
            <h3 class="inter-user-name"><?= $course->post_title; ?></h3>
          </div>

          <img src="<?= $treks_src; ?>/assets/img/bc_arrow_right.svg" alt="user" class="students-breadcrumb-arrow" />
          <div class="interdependence-tab">
            <div class="inter-tab-polygon" style="background-color: <?= $pg_color; ?>">
              <h4>S</h4>
            </div>
            <h3 class="inter-tab-polygon-name" style="color: <?= $pg_color; ?>; width=90%"><?= $section_name; ?></h3>
          </div>
          <img src="<?= $treks_src; ?>/assets/img/bc_arrow_right.svg" alt="user" class="students-breadcrumb-arrow" />
          <div class="interdependence-tab">
            <div class="inter-tab-polygon" style="background-color: <?= $pg_color; ?>">
              <h4>L</h4>
            </div>
            <h3 class="inter-tab-polygon-name" style="color: <?= $pg_color; ?>; width=90%"><?= $lxp_lesson_post->post_title; ?></h3>
          </div>
          <?php
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
          ?>
          <div class="time-date-box">
            <input type="hidden" name="startDateTime" id="startDateTime" value="<?= $start; ?>" />
            <input type="hidden" name="endDateTime" id="endDateTime" value="<?= $end; ?>" />
            <p class="date-time" id="student-progress-trek-start-time"></p>
            <p class="date-time to-text">To</p>
            <p class="date-time" id="student-progress-trek-end-time"></p>
          </div>

        </div>
      </section>

      <!-- Table section -->
      <section class="student_assignment_tab">
        <!-- School nav tabs -->
        <nav class="assignment_tabs">
          <h2>Students</h2>
          <ul class="treks_ul" id="myTab" role="tablist">
            <?php 
              foreach ($students as  $student) { 
                $assignment_submissions = assignments_submissions([$assignment], $student);
                $status = count($assignment_submissions) > 0 ? $assignment_submissions[0][$assignment->ID]["status"] : 'not submitted';
                $statusClass= '';
                switch ($status) {
                  case 'To Do':
                    $statusClass = 'bg-gray';
                    break;
                  case 'In Progress':
                    $statusClass = 'bg-orange';
                    break;
                  case 'Completed':
                    $statusClass = 'bg-green';
                    break;
                }
              if ( isset($_GET['action']) && $student->ID == $student_id ) {

                ?>
                <li onclick="switch_student(<?= $student->ID; ?>)">
                  <div
                    class="nav-link tab_btn <?= $student->ID == $student_id ? 'active' : ''; ?>"
                    data-bs-toggle="tab"
                    type="button"
                    role="tab"
                    aria-selected="true"
                  >
                    <img src="<?= $treks_src; ?>/assets/img/check-g.svg" alt="" class="check-g" />

                    <div class="student_abouts_tab tab_bg_w">
                      <div class="student_about">
                        <img src="<?= $treks_src; ?>/assets/img/profile-icon.png" class="student_user_img" alt="user" />
                        <div class="student_names">
                          <h4><?= $student->name; ?></h4>
                          <p><?= is_array(json_decode($student->grades)) ? implode(', ', json_decode($student->grades)) : ""; ?></p>
                        </div>
                      </div>
                      <div class="stu_tag">
                        <?php 
                          if ($status && $status === 'Completed') {
                            $status = 'Submitted';
                          }
                          if ($status && $status === 'Submitted' && get_post_meta($assignment_submissions[0][$assignment->ID]['submission']['ID'], 'mark_as_graded', true) === 'true') {
                            $status = 'Graded';
                            $statusClass = 'bg-blue';
                          }

                          if (!$assignment_submission && $status && $status === 'Submitted') {
                            $assignment_submission_item = lxp_get_assignment_submissions($assignment->ID, $student->ID);
                            if (count($assignment_submission_item) > 0 && get_post_meta($assignment_submission_item['ID'], 'mark_as_graded', true) === 'true') {
                              $status = 'Graded';
                              $statusClass = 'bg-blue';
                            }
                          }
                        ?>
                        <span class="student_label label_red <?= $statusClass; ?>"><?= $status; ?></span>
                        <img src="<?= $treks_src; ?>/assets/img/select-arrow-up.svg" alt="" />
                      </div>
                    </div>
                  </div>
                </li>
                <?php
              } elseif ( !isset($_GET['action']) && !isset($_GET['subContentId']) ) {
                ?>
                <li onclick="switch_student(<?= $student->ID; ?>)">
                  <div
                    class="nav-link tab_btn <?= $student->ID == $student_id ? 'active' : ''; ?>"
                    data-bs-toggle="tab"
                    type="button"
                    role="tab"
                    aria-selected="true"
                  >
                    <img src="<?= $treks_src; ?>/assets/img/check-g.svg" alt="" class="check-g" />

                    <div class="student_abouts_tab tab_bg_w">
                      <div class="student_about">
                        <img src="<?= $treks_src; ?>/assets/img/profile-icon.png" class="student_user_img" alt="user" />
                        <div class="student_names">
                          <h4><?= $student->name; ?></h4>
                          <p><?= is_array(json_decode($student->grades)) ? implode(', ', json_decode($student->grades)) : ""; ?></p>
                        </div>
                      </div>
                      <div class="stu_tag">
                        <?php 
                          if ($status && $status === 'Completed') {
                            $status = 'Submitted';
                          }
                          if ($status && $status === 'Submitted' && get_post_meta($assignment_submissions[0][$assignment->ID]['submission']['ID'], 'mark_as_graded', true) === 'true') {
                            $status = 'Graded';
                            $statusClass = 'bg-blue';
                          }

                          if (!$assignment_submission && $status && $status === 'Submitted') {
                            $assignment_submission_item = lxp_get_assignment_submissions($assignment->ID, $student->ID);
                            if (count($assignment_submission_item) > 0 && get_post_meta($assignment_submission_item['ID'], 'mark_as_graded', true) === 'true') {
                              $status = 'Graded';
                              $statusClass = 'bg-blue';
                            }
                          }
                        ?>
                        <span class="student_label label_red <?= $statusClass; ?>"><?= $status; ?></span>
                        <img src="<?= $treks_src; ?>/assets/img/select-arrow-up.svg" alt="" />
                      </div>
                    </div>
                  </div>
                </li>
                <?php
              }
             } ?>
          </ul>
        </nav>
        <!-- End School nav tabs -->

        <!-- Tabs Table -->
        <?php 
          $slides = get_assignment_lesson_slides( intval($_GET['assignment']) );
          $args['slides'] = $slides;
          $lessons = lxp_get_course_digital_journals($course->ID);
          $course_lesson = null;
          # give grade for courese presentation Activity
          foreach($lessons as $lesson){
            $course_lesson = ($lxp_lesson_post->ID == $lesson->ID) ? $lesson : $course_lesson;
          }
          $lti_post_attr_id = get_post_meta($course_lesson->ID, 'lti_post_attr_id', true);
          $attrId = $lti_post_attr_id ? $lti_post_attr_id : 0;
          $queryParam = '';
          if (isset($_GET['slide'])) {
            $queryParam = "&slideNumber=" . $_GET['slide'];
          }
          $student_user_id = get_post_meta($_GET['student'], 'lxp_student_admin_id', true);
          $queryParam .= "&student=" . $student_user_id;
          $queryParam .= "&skipSave=1";

          if ( isset($_GET['action']) && $_GET['action'] == 'grade' ) {
            if ( $assignment_type == 'video_activity' ) {
              include $livePath.'/lxp/teacher-iv-grade-single.php';
            } else {
            $slidesData = $slides->data;
            $slides = $slides->slides;
            $slideIndex = intval($_GET['slide']);
            $slide_filtered_arr = array_filter($slides, function($slide) use($slideIndex) {
              return $slide->slide == $slideIndex;
            });
            ?>
              <div class="tab-content" id="myTabContent">
                <div class="container">
                  
                  <div class="row">
                    <div class="col col-md-8">
                      <iframe style="border: none; width: 100%; height: 395px;" src="<?= site_url() ?>?lti-platform&post=<?= $course_lesson->ID ?>&id=<?= $attrId ?><?= $queryParam ?>" allowfullscreen></iframe>
                    </div>
                      <?php 
                        if (count($slide_filtered_arr) > 0) {
                          $slide_filtered = array_values($slide_filtered_arr)[0];
                          $max_grades = $slide_filtered->gradedManually && $slide_filtered->totalGrades ? $slide_filtered->totalGrades : 10;
                      ?>
                        <div class="col col-md-4">
                            <div class="grade-box">
                                <span class="grade-box-slide"><?= $slide_filtered->title; ?></span>
                                <?php if ($slide_filtered->type == 'Essay') { ?>
                                  <div class="grade-select">
                                    <select name="grade" id="grade" class="form-select">
                                      <option value="">----</option>
                                      <?php foreach (range(0, intval($max_grades)) as $grade_number) { ?>
                                        <option value="<?= $grade_number; ?>"><?= $grade_number; ?></option>
                                      <?php } ?>
                                    </select>
                                    <button class="grade-box-btn" onclick="assign_grade(<?= $_GET['slide']; ?>)">&nbsp;&nbsp;&nbsp;Grade&nbsp;&nbsp;&nbsp;</button>
                                    <br />
                                    <button class="add-feedback-btn btn btn-outline-info btn-lg" id="addFeedbackModal">Add Feedback</button>
                                    <button class="grade-box-btn" onclick="back()">Back</button>
                                  </div>
                                <?php 
                                  } else { 
                                    $auto_score = lxp_assignment_submission_auto_score($assignment_submission['ID'], $slideIndex);
                                    $score = $auto_score['score'];
                                    $max = $auto_score['max'];
                                ?>
                                  <!-- <div class="alert alert-primary text-center" role="alert"> Auto-graded </div> -->
                                  <span class="grade-box-slide"><?= $max ? "Grade: $score/$max" : "Not Submitted"; ?></span>
                                  <button class="grade-box-btn" onclick="back()">Back</button>
                                <?php } ?>
                        </div>
                      <?php } else { ?>
                        <div class="col col-md-4">
                          <div class="alert alert-warning text-center" role="alert">
                            <i>Slide <?= $_GET['slide']; ?></i> is not gradable.
                          </div>
                        </div>
                      <?php } ?>
                  </div>

                </div>
              </div>
      <?php }
          } else if ( isset($_GET['action']) && $_GET['action'] == 'summary' ) {
            $args['assignment_submission'] = $assignment_submission;
            ( $assignment_type == 'video_activity' ) ? include $livePath.'/lxp/grade-book-video.php' 
              : include $livePath.'/lxp/grade-book.php';
          } else if ( isset($_GET['lesson_id']) && isset($_GET['student_id']) ) { ?>
            <iframe style="border: none;width: 100%;height: 400px;" class="" src="<?= site_url() ?>?lti-platform&post=<?= $_GET['lesson_id'] ?>&id=jcfvxikc&is_summary=1&student_id=<?= $_GET['student_id'] ?>"  allowfullscreen></iframe>
          <?php
          } else {
            // check for interactive-video/interactive-book   or   course-presentation
            ( $assignment_type == 'video_activity' ) ? include $livePath.'/lxp/teacher-ivib-grades.php' : include $livePath.'/lxp/teacher-grade.php';
          }
        ?>
        <!-- End Table -->
      </section>
    </section>

    <script
      src="https://code.jquery.com/jquery-3.6.3.js"
      integrity="sha256-nQLuAZGRRcILA+6dMBOvcRh5Pe310sBpanc6+QBmyVM="
      crossorigin="anonymous"
    ></script>
    <script src="<?= $treks_src; ?>/js/Animated-Circular-Progress-Bar-with-jQuery-Canvas-Circle-Progress/dist/circle-progress.js"></script>
    <script src="<?= $treks_src; ?>/js/custom.js"></script>
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4"
      crossorigin="anonymous"
    ></script>
    <script type="text/javascript">
      const marks_float = "<?= $marks_float; ?>";
      const interactions_float = "<?= $interactions_float; ?>";
      const progress_float = "<?= $progress_float; ?>";
      if (interactions_float !== null) {
        $('.score-progress').circleProgress({
          startAngle: -Math.PI / 4 * 1,
          value: marks_float,
          size: 50,
          thickness: 6,
          lineCap: 'round',
          fill: {gradient: ['#0e7c57']}
        });
        $('.book-progress').circleProgress({
          startAngle: -Math.PI / 4 * 1,
          value: progress_float,
          size: 50,
          thickness: 6,
          lineCap: 'round',
          fill: {gradient: ['#1768c4']}
        });
        $('.interaction-progress').circleProgress({
          startAngle: -Math.PI / 4 * 1,
          value: interactions_float,
          size: 50,
          thickness: 6,
          lineCap: 'round',
          fill: {gradient: ['#1768c4']}
        });
      }
      // function grade() redirect to grade page
      function grade(slide) {
        window.location.href = location.origin + location.pathname + "?assignment=<?= $_GET['assignment']; ?>&student=<?= $_GET['student']; ?>" + "&action=grade&slide=" + slide;
      }

      function video_grade(interactionUrl) {
        window.location.href = location.origin + location.pathname + "?assignment=<?= $_GET['assignment']; ?>&student=<?= $_GET['student']; ?>" + interactionUrl;
      }

      <?php if($assignment_submission) { ?>
      // function assign_grade() assign grade and selected grade to student
      function assign_grade(slide) {
        let grade = jQuery("#grade").val();
        let host = window.location.hostname === 'localhost' ? window.location.origin + '<?php echo WORDPRESS_HOST; ?>' : window.location.origin;
        let apiUrl = host + '/wp-json/lms/v1/';
        $.ajax({
          url: apiUrl + 'assignment/submission/grade',
          type: "POST",
          data: {
            "assignment_submission_id": "<?= $assignment_submission['ID']; ?>",
            "slide": slide,
            "grade": grade
          },
          success: function (response) {
            alert('Gradded successfully !');
          },
          error: function (error) {
            console.log(error);
          }
        });
      }

      <?php } ?>
      
      function back() {
        window.location.href = window.location.origin + window.location.pathname + "?assignment=<?= $_GET['assignment']; ?>&student=<?= $_GET['student']; ?>";
      }

      function switch_student(student_post_id) {
        window.location.href = window.location.origin + window.location.pathname + "?assignment=<?= $_GET['assignment']; ?>&student=" + student_post_id;
      }

      jQuery(document).ready(function () {
        var student_assignment_grade = "<?= $grade; ?>";
        if (student_assignment_grade) {
          jQuery("#grade").val(student_assignment_grade);
        }
        window.slideMessageReceivedCount = 0;
        window.slideMessageReceived = 0;
        window.addEventListener('message', function (event) {
          if (typeof event.data === 'object' && event.data.hasOwnProperty('currentSlide')) {
            window.slideMessageReceivedCount++;
            if (window.slideMessageReceivedCount > 1) {
              const params = new URLSearchParams(window.location.search);
              if (window.slideMessageReceived > 0 && window.slideMessageReceived !== parseInt(event.data.currentSlide)) {
                params.set('slide', event.data.currentSlide);
                window.location = window.location.origin + window.location.pathname + '?' + params.toString();  
              }
            } else if (window.slideMessageReceivedCount <= 1) {
              window.slideMessageReceived = parseInt(event.data.currentSlide);
            }
          }
        });

        // handle Mark as Graded check
        jQuery('#markGraded').on('change', function() {
          let host = window.location.hostname === 'localhost' ? window.location.origin + '<?php echo WORDPRESS_HOST; ?>' : window.location.origin;
          let apiUrl = host + '/wp-json/lms/v1/';
          let checked = jQuery(this).prop('checked');
          jQuery.ajax({
            url: apiUrl + 'assignment/submission/mark-as-graded',
            type: "POST",
            data: {
              "assignment_submission_id": "<?= $assignment_submission ? $assignment_submission['ID'] : 0; ?>",
              "checked": checked
            },
            success: function (response) {
              console.log(response);
              window.location.reload();
            },
            error: function (error) {
              console.log(error);
            }
          });
        });
        
        // starting date and time
        let start_date = new Date(jQuery('#startDateTime').val());
        let start_date_string = start_date.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric', year: 'numeric' });
        let start_time_string = start_date.toLocaleTimeString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true });
        jQuery('#student-progress-trek-start-time').text(start_date_string + ' ' + start_time_string);
        // ending date and time
        let end_date = new Date(jQuery('#endDateTime').val());
        let end_date_string = end_date.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric', year: 'numeric' });
        let end_time_string = end_date.toLocaleTimeString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true });
        jQuery('#student-progress-trek-end-time').text(end_date_string + ' ' + end_time_string);
        
        $('.modalclose').on('click', function(e) {
          $('#carouselModal').modal('hide');
        });
        $('.viewclose').on('click', function(e) {
          feedbackViewModalObj.hide();
        });
      });
    </script>
    <?php
      if ($assignment_submission) {

        // if (isset($_GET['assignment']) && (isset($_GET['slide']) || isset($_GET['ivInteraction']) || isset($_GET['question'])) && isset($_GET['student'])) {
        $args['assignment'] = intval($_GET['assignment']);
        $args['student'] = $_GET['student'];
        $args['assignment_submission_id'] = $assignment_submission['ID'];
        include $livePath.'/lxp/teacher-grading-feedback-modal.php';
        // }
        include $livePath.'/lxp/teacher-grading-feedback-view-modal.php';
      }
    ?>
  </body>
</html>
