<?php

class Rest_Lxp_Assignment_Submission
{
	/**
	 * Register the REST API routes.
	 */
	public static function init()
	{
		if (!function_exists('register_rest_route')) {
			// The REST API wasn't integrated into core until 4.4, and we support 4.0+ (for now).
			return false;
		}

        register_rest_route('lms/v1', '/assignment/submission', array(
			array(
				'methods' => WP_REST_Server::ALLMETHODS,
				'callback' => array('Rest_Lxp_Assignment_Submission', 'assignment_submission'),
				'permission_callback' => '__return_true'
			)
		));

        register_rest_route('lms/v1', '/assignment/submission/feedback/view', array(
			array(
				'methods' => WP_REST_Server::ALLMETHODS,
				'callback' => array('Rest_Lxp_Assignment_Submission', 'assignment_submission_feedback_view'),
				'permission_callback' => '__return_true'
			)
		));

        register_rest_route('lms/v1', '/assignment/submission/feedback', array(
			array(
				'methods' => WP_REST_Server::ALLMETHODS,
				'callback' => array('Rest_Lxp_Assignment_Submission', 'assignment_submission_feedback'),
				'permission_callback' => '__return_true'
			)
		));

        register_rest_route('lms/v1', '/assignment/submission/grade', array(
			array(
				'methods' => WP_REST_Server::ALLMETHODS,
				'callback' => array('Rest_Lxp_Assignment_Submission', 'assignment_submission_grade'),
				'permission_callback' => '__return_true'
			)
		));

        register_rest_route('lms/v1', '/assignment/submission/gradeByStudent', array(
			array(
				'methods' => WP_REST_Server::ALLMETHODS,
				'callback' => array('Rest_Lxp_Assignment_Submission', 'assignment_submission_grade_by_student'),
				'permission_callback' => '__return_true'
			)
		));

        // register rest route for assignment/submission/mark-as-graded
        register_rest_route('lms/v1', '/assignment/submission/mark-as-graded', array(
            array(
                'methods' => WP_REST_Server::ALLMETHODS,
                'callback' => array('Rest_Lxp_Assignment_Submission', 'assignment_submission_mark_as_graded'),
                'permission_callback' => '__return_true'
            )
        ));
    }

    // mark public static function 'assignment_submission_mark_as_graded' which set the assignment submission 'mark as graded' status
    public static function assignment_submission_mark_as_graded($request) {
        $assignment_submission_id = $request->get_param('assignment_submission_id');
        $mark_as_graded = $request->get_param('checked');
        update_post_meta($assignment_submission_id, "mark_as_graded", $mark_as_graded);
        return wp_send_json_success("Assignment Submission Marked as {$mark_as_graded} Graded!");
    }

    public static function assignment_submission_grade_by_student($request) {

        $xapiData = $request->get_param('xapiData');

        $h5pType = '';
        if (isset($xapiData['context']['contextActivities']['category'])) {
            $h5pTypeParts = explode('/', $xapiData['context']['contextActivities']['category'][0]['id']);
            $h5pTypeParts = $h5pTypeParts[count($h5pTypeParts) - 1];
            $h5pType = explode('-', $h5pTypeParts)[0];
        }
        // only course presentation activity will use below code -> ok
        if ($h5pType == 'H5P.Essay') {
            return wp_send_json_success("Grading Skipped for {$h5pType}!");
        }

        $xapiObjectId = null;
        parse_str(parse_url($xapiData['object']['id'], PHP_URL_QUERY), $xapiObjectId);
        $subContentId = $xapiObjectId['subContentId'];

        $student_user_id = $request->get_param('student_user_id');
        $assignment_id = $request->get_param('assignment_id');

        $student_post_query = new WP_Query( array( 
            'post_type' => TL_STUDENT_CPT, 
            'post_status' => array( 'publish' ),
            'posts_per_page'   => -1,        
            'meta_query' => array(
                array('key' => 'lxp_student_admin_id', 'value' => $student_user_id, 'compare' => '=')
            )
        ) );
        $student_posts = $student_post_query->get_posts();
        if (count($student_posts) > 0) {
            $student_post = $student_posts[0];
            $assignment_submission_get_query = new WP_Query( array( 'post_type' => TL_ASSIGNMENT_SUBMISSION_CPT , 'posts_per_page'   => -1, 'post_status' => array( 'publish' ), 
                        'meta_query' => array(
                            array('key' => 'lxp_assignment_id', 'value' => $assignment_id, 'compare' => '='),
                            array('key' => 'lxp_student_id', 'value' => $student_post->ID, 'compare' => '=')
                        )
                    )
                );
            $assignment_submission_posts = $assignment_submission_get_query->get_posts();
            if (count($assignment_submission_posts) > 0) {
                $assignment_submission_id = $assignment_submission_posts[0]->ID;
                // video activity case start
                $activity_type = get_post_meta($assignment_id, 'assignment_type', true);
                if ($activity_type == 'video_activity') {
                    // get lti user id from xapi object
                    $lti_user_id = $xapiData['actor']['account']['name'];
                    $raw = isset($xapiData['result']['score']['raw']) ? $xapiData['result']['score']['raw'] : 0;
                    $max = $xapiData['result']['score']['max'];
                    $url_path = parse_url($xapiData['object']['id'], PHP_URL_PATH);
                    preg_match('/\/h5p\/embed\/(\d+)/', $url_path, $matches);
                    $lesson_id = get_post_meta($assignment_id, "lxp_lesson_id", true);
                    update_post_meta($student_post->ID, 'lti_user_id', $lti_user_id);
                    update_post_meta($lesson_id, "h5p_content_id", $matches[1]);
                    //start change status here
                    update_post_meta($assignment_submission_posts[0]->ID, "submission_id", '1');
                    update_post_meta($assignment_submission_posts[0]->ID, "lti_user_id", $lti_user_id);
                    update_post_meta($assignment_submission_id, "iv_subContentId_{$subContentId}_raw", $raw);
                    update_post_meta($assignment_submission_id, "iv_subContentId_{$subContentId}_max", $max);
                    // end change status here
                    // update score start
                    $all_meta = get_post_meta($assignment_submission_id);
                    $raw = 0; $max = 0;
                    foreach ($all_meta as $key => $value) {
                        if (strpos($key, 'iv_') === 0 && substr($key, -4) === '_raw') {
                            $raw += $value[0];
                        } elseif (strpos($key, 'iv_') === 0 && substr($key, -4) === '_max') {
                            $max += $value[0];
                        }
                    }
                    update_post_meta($assignment_submission_id, "score_raw", $raw);
                    update_post_meta($assignment_submission_id, "score_max", $max);
                    update_post_meta($assignment_submission_id, "score_scaled", $raw/$max);
                    // update score end
                    return wp_send_json_success("IV Graded for subContentId {$subContentId}!");
                }
                // video activity case end
                $result = $request->get_param('result');
                if (is_array($result) && array_key_exists('score', $result)) {
                    $grade = $result['score']['raw'];
                    $slide = $request->get_param('slide');
                    if (!in_array($subContentId, get_post_meta($assignment_submission_id, "subContentIds"))) {
                        add_post_meta($assignment_submission_id, "subContentIds", $subContentId);
                    }
                    update_post_meta($assignment_submission_id, "slide_{$slide}_subContentId_{$subContentId}_grade", $grade);
                    update_post_meta($assignment_submission_id, "slide_{$slide}_subContentId_{$subContentId}_result", json_encode($result));
                    return wp_send_json_success("Assignment Submission Graded for Slide: {$slide} and content: {$subContentId}!");
                } else {
                    return wp_send_json_success("No Assignment Submission saved!");
                }
            } else {
                return wp_send_json_success("No Assignment Submission saved!");
            }
        } else {
            return wp_send_json_success("Student not found!");
        }
    }

    public static function assignment_submission_feedback_view($request) {
        $assignment_submission_id = $request->get_param('assignment_submission_id');
        $slide = $request->get_param('slide');
        $feedback['feedback'] = get_post_meta($assignment_submission_id, "slide_{$slide}_feedback", true);
        $feedback['grade_num'] = get_post_meta($assignment_submission_id, "slide_{$slide}_grade", true);
        return wp_send_json_success($feedback);
    }

    public static function assignment_submission_feedback($request) {
        $assignment_submission_id = $request->get_param('assignment_submission_id');
        $slide = $request->get_param('slide');
        $feedback = $request->get_param('feedback');
        update_post_meta($assignment_submission_id, "slide_{$slide}_feedback", $feedback);
        return wp_send_json_success("Assignment Submission Feedback Saved for Slide {$slide}!");
    }

    public static function assignment_submission_grade($request) {
        $assignment_submission_id = $request->get_param('assignment_submission_id');
        $slide = $request->get_param('slide');
        $grade = $request->get_param('grade');
        update_post_meta($assignment_submission_id, "slide_{$slide}_grade", $grade);
        return wp_send_json_success("Assignment Submission Graded for Slide {$slide}!");
    }

    public static function assignment_submission($request) {
        $assignmentId = $request->get_param('assignmentId');
        $assignment_post = get_post($assignmentId);

        $userId = $request->get_param('userId');
        $user_post_query = new WP_Query( array( 
            'post_type' => TL_STUDENT_CPT, 
            'post_status' => array( 'publish' ),
            'posts_per_page'   => -1,        
            'meta_query' => array(
                array('key' => 'lxp_student_admin_id', 'value' => $userId, 'compare' => '=')
            )
        ) );
        $user_posts = $user_post_query->get_posts();

        if ($user_posts) {   
            $user_post = $user_posts[0];
            $assignment_submission_post_title = $user_post->post_title . ' | ' . $assignment_post->post_title;
            
            $assignment_submission_post_arg = array(
				'post_title'    => wp_strip_all_tags($assignment_submission_post_title),
				'post_content'  => $assignment_submission_post_title,
				'post_status'   => 'publish',
				'post_author'   => $userId,
				'post_type'   => TL_ASSIGNMENT_SUBMISSION_CPT
			);
            
            $assignment_submission_get_query = new WP_Query( array( 'post_type' => TL_ASSIGNMENT_SUBMISSION_CPT , 'posts_per_page'   => -1, 'post_status' => array( 'publish' ), 
                        'meta_query' => array(
                            array('key' => 'lxp_assignment_id', 'value' => $assignment_post->ID, 'compare' => '='),
                            array('key' => 'lxp_student_id', 'value' => $user_post->ID, 'compare' => '=')
                        )
                    )
                );
            $assignment_submission_posts = $assignment_submission_get_query->get_posts();
            if (count($assignment_submission_posts) > 0) {
                $assignment_submission_post_arg['ID'] = $assignment_submission_posts[0]->ID;
            }
            // var_dump($assignment_submission_post_arg); die;
            $assignment_submission_post_id = wp_insert_post($assignment_submission_post_arg);
            if ($assignment_submission_post_id) {
                // add assignment submission post meta data for $assignmentId and $user_post
                update_post_meta($assignment_submission_post_id, 'lxp_assignment_id', $assignmentId);
                update_post_meta($assignment_submission_post_id, 'lxp_student_id', $user_post->ID);

                // get 'ltiUserId', 'submissionId from $request and add as post meta data
                $ltiUserId = $request->get_param('ltiUserId');
                $submissionId = $request->get_param('submissionId');
                update_post_meta($assignment_submission_post_id, 'lti_user_id', $ltiUserId);
                update_post_meta($assignment_submission_post_id, 'submission_id', $submissionId);

                $activity_type = get_post_meta($assignment_post->ID, 'assignment_type', true);
                if ( $activity_type == 'slides_activity' ) {
                    // get array values for 'min', 'max', 'raw' and 'scaled' from 'score' array key of $request paramter 'result' and add as assignment submission post meta data
                    $score = $request->get_param('result')['score'];
                    update_post_meta($assignment_submission_post_id, 'score_min', $score['min']);
                    update_post_meta($assignment_submission_post_id, 'score_max', $score['max']);
                    update_post_meta($assignment_submission_post_id, 'score_raw', $score['raw']);
                    update_post_meta($assignment_submission_post_id, 'score_scaled', $score['scaled']);
                }

                // get 'completion' and 'duration' key values from 'result' $request parameter and add as assignment submission post meta data
                $completion = $request->get_param('result')['completion'];
                $duration = $request->get_param('result')['duration'];
                update_post_meta($assignment_submission_post_id, 'completion', intval($completion));
                update_post_meta($assignment_submission_post_id, 'duration', $duration);
                return wp_send_json_success("Assignment Submission Created!");
            } else {
                return wp_send_json_error("Assignment Submission Creation Failed!");
            }
        }
    }

    public static function grades_score_video_activity($assignment_id, $student_id, $score) {
        $lesson_id = absint(get_post_meta($assignment_id, 'lxp_lesson_id', true));
        $student_id = absint($student_id);
        $score = floatval($score);

        $grades_repository = new TL_Grades_Repository();
        $grades_repository->upsert_score($lesson_id, $student_id, $score);
    }
}
