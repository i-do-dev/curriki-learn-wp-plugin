<?php
//$userdata = isset($args['userdata']) ? $args['userdata'] : get_userdata(get_current_user_id());
$student_post = isset($args['student_post']) ? $args['student_post'] : lxp_get_student_post(get_current_user_id());
$assignments = isset($args['assignments']) ? $args['assignments'] : lxp_get_student_assignments($student_post->ID);

if (isset($args['course_id'])) {
    // $assignments filter by course id
    $assignments = array_filter($assignments, function ($assignment) use ($args) {
        return $assignment->course_id == $args['course_id'];
    });
}

foreach ($assignments as $assignment) {
    $course = get_post(get_post_meta($assignment->ID, 'course_id', true));
    $lxp_lesson_post = get_post(get_post_meta($assignment->ID, 'lxp_lesson_id', true));
    $segmentColor = "#1fa5d4";
    $args = array( 'posts_per_page' => -1, 'post_type' => TL_LESSON_CPT, 'meta_query' => array(array('key'   => 'tl_course_id', 'value' =>  $course->ID)));
    $lessons = get_posts($args);
    $digital_journal_link = null;
    foreach($lessons as $lesson){ if ( $lxp_lesson_post->ID === $lesson->ID ) { $digital_journal_link = get_permalink($lesson->ID); }; }
    $digital_journal_link = $digital_journal_link . '?assignment_id=' . $assignment->ID;
?>
<a href="<?php echo $digital_journal_link; ?>" class="student-assignment-block" target="_blank">
    <div>
        <div class="assig-label-card">
            <div class="header">
            <div class="tags-body-polygon bg-green" style="background-color: <?php echo $segmentColor; ?>">
                <span><?php echo 'L'; ?></span>
            </div>
            </div>
            <div class="tag-assig-tetaul">
            <h3 style="color: <?php echo $segmentColor; ?>"><?php echo $lxp_lesson_post->post_title; ?></h3>
            <p><?php echo $course->post_title; ?></p>
            </div>
        </div>
        <div class="progress" style="height: 4px">
            <div
            class="progress-bar"
            role="progressbar"
            style="width: 25%"
            aria-valuenow="25"
            aria-valuemin="0"
            aria-valuemax="100"
            ></div>
        </div>
    </div>
</a>
<?php } ?>
<?php if (count($assignments) == 0) { ?>
    <div style="color: gray;"><i><h6>No Assignment(s)</h6></i></div>
<?php } ?>