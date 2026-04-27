<?php

class Rest_Lxp_Assignment
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

		register_rest_route('lms/v1', '/assignment/attempted', array(
			array(
				'methods' => WP_REST_Server::ALLMETHODS,
				'callback' => array('Rest_Lxp_Assignment', 'assignment_attempted'),
				'permission_callback' => '__return_true'
			)
		));

		register_rest_route('lms/v1', '/assignment/stats', array(
			array(
				'methods' => WP_REST_Server::ALLMETHODS,
				'callback' => array('Rest_Lxp_Assignment', 'assignment_stats'),
				'permission_callback' => '__return_true'
			)
		));

		register_rest_route('lms/v1', '/assignments/calendar/events', array(
			array(
				'methods' => WP_REST_Server::ALLMETHODS,
				'callback' => array('Rest_Lxp_Assignment', 'calendar_events'),
				'permission_callback' => '__return_true'
			)
		));

		register_rest_route('lms/v1', '/assignment/students', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array('Rest_Lxp_Assignment', 'get_students'),
				'permission_callback' => '__return_true'
			)
		));

		register_rest_route('lms/v1', '/assignments', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array('Rest_Lxp_Assignment', 'get_one'),
				'permission_callback' => '__return_true'
			)
		));

		register_rest_route('lms/v1', 'assignment/interactions', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array('Rest_Lxp_Assignment', 'get_interactions_by_assignment'),
				'permission_callback' => '__return_true'
			)
		));
		
		register_rest_route('lms/v1', '/assignments/save', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array('Rest_Lxp_Assignment', 'create'),
				'permission_callback' => '__return_true',
				'args' => array(
					'course_id' => array(
						'required' => true,
						'type' => 'integer',
						'description' => 'assignment course id',
						'validate_callback' => function($param, $request, $key) {
							return intval( $param ) > 0;
						}
					),
					'lesson_ids' => array(
						'required' => true,
						'description' => 'assignment course lessons',
						'validate_callback' => function($param, $request, $key) {
							$param = json_decode($param);
							if (count( $param ) > 0) {
								return true;
							} else {
								return false;
							}
						}
					),
					'student_ids' => array(
						'required' => true,
						'description' => 'assignment students',
						'validate_callback' => function($param, $request, $key) {
							$param = json_decode($param);
							if (count( $param ) > 0) {
								return true;
							} else {
								return false;
							}
						}
					),
					'teacher_id' => array(
						'required' => true,
						'type' => 'integer',
						'description' => 'assignment teacher id',
						'validate_callback' => function($param, $request, $key) {
							return intval( $param ) > 0;
						}
					),
					'assignment_post_id' => array(
						'required' => true,
						'type' => 'string',
						'description' => 'assignment post id',
						'validate_callback' => function($param, $request, $key) {
							return strlen( $param ) > 0;
						}
					),
					'calendar_selection_info' => array(
						'required' => true,
						'type' => 'string',
						'description' => 'assignment calendar info id',
						'validate_callback' => function($param, $request, $key) {
							return strlen( $param ) > 0;
						}
					)
			   )
			),
		));
		
		register_rest_route('lms/v1', '/update/assignment', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array('Rest_Lxp_Assignment', 'update_assignment'),
				'permission_callback' => '__return_true',
				'args' => array(
					'user_email' => array(
					   'required' => true,
					   'type' => 'string',
					   'description' => 'user login name',  
					   'format' => 'email'
				   ),
				   'login_name' => array(
						'required' => true,
						'type' => 'string',
						'description' => 'user login name name'
					),
					'first_name' => array(
						'required' => true,
						'type' => 'string',
						'description' => 'user first name',
					),
					'last_name' => array(
						'required' => true,
						'type' => 'string',
						'description' => 'user last name',
					),
					'id' => array(
						'required' => true,
						'type' => 'integer',
						'description' => 'user account id',
					),
				   
			   )
			),
		));
		
	}

	public static function create($request) {		
		
		$course_id = $request->get_param('course_id');
		$course_post = get_post($course_id);
		$lesson_ids = json_decode($request->get_param('lesson_ids'));
		$class_id = $request->get_param('class_id');
		$group_id = $request->get_param('group_id');
		$calendar_selection_info = json_decode($request->get_param('calendar_selection_info'));
		$assignment_teacher_id = $request->get_param('teacher_id');
		$assignment_post_id = intval($request->get_param('assignment_post_id'));
		require_once plugin_dir_path(dirname( __FILE__ )). 'templates/tinyLxpTheme/lxp/functions.php';
		$start = new DateTime($calendar_selection_info->start);
		$end = new DateTime($calendar_selection_info->end);
		
		$start_date = $start->format('Y-m-d');
		$start_time = $start->format('H:i:s');
		

		$end_date = $end->format('Y-m-d');
		$end_time = $end->format('H:i:s');

		global $wpdb;		
		foreach ($lesson_ids as $lesson_id) {
			$lesson_post = get_post($lesson_id);
			// ============= Assignment Post =================================
			$assignment_name = $lesson_post->post_title . ' - ' . $course_post->post_title;
			$assignment_teacher_id = $request->get_param('teacher_id');
			$assignment_post_id = intval($request->get_param('assignment_post_id'));
			
			$assignment_post_arg = array(
				'post_title'    => wp_strip_all_tags($assignment_name),
				'post_content'  => $assignment_name,
				'post_status'   => 'publish',
				'post_author'   => $assignment_teacher_id,
				'post_type'   => TL_ASSIGNMENT_CPT
			);
			if (intval($assignment_post_id) > 0) {
				$assignment_post_arg['ID'] = "$assignment_post_id";
			}
			
			// Insert / Update
			$assignment_post_id = wp_insert_post($assignment_post_arg);

			if(get_post_meta($assignment_post_id, 'lxp_assignment_teacher_id', true)) {
				update_post_meta($assignment_post_id, 'lxp_assignment_teacher_id', $assignment_teacher_id);
			} else {
				add_post_meta($assignment_post_id, 'lxp_assignment_teacher_id', $assignment_teacher_id, true);
			}
			
			delete_post_meta($assignment_post_id, 'lxp_student_ids');
			$student_ids = json_decode($request->get_param('student_ids'));
			foreach ($student_ids as $student_id) {
				add_post_meta($assignment_post_id, 'lxp_student_ids', $student_id);
			}

			if(get_post_meta($assignment_post_id, 'lxp_lesson_id', true)) {
				update_post_meta($assignment_post_id, 'lxp_lesson_id', $lesson_id);
			} else {
				add_post_meta($assignment_post_id, 'lxp_lesson_id', $lesson_id, true);
			}

			if(get_post_meta($assignment_post_id, 'course_id', true)) {
				update_post_meta($assignment_post_id, 'course_id', $course_id);
			} else {
				add_post_meta($assignment_post_id, 'course_id', $course_id, true);
			}

			if(get_post_meta($assignment_post_id, 'class_id', true)) {
				update_post_meta($assignment_post_id, 'class_id', $class_id);
			} else {
				add_post_meta($assignment_post_id, 'class_id', $class_id, true);
			}

			if(get_post_meta($assignment_post_id, 'group_id', true)) {
				update_post_meta($assignment_post_id, 'group_id', $group_id);
			} else {
				add_post_meta($assignment_post_id, 'group_id', $group_id, true);
			}

			if(get_post_meta($assignment_post_id, 'calendar_selection_info', true)) {
				update_post_meta($assignment_post_id, 'calendar_selection_info', json_encode($calendar_selection_info));
			} else {
				add_post_meta($assignment_post_id, 'calendar_selection_info', json_encode($calendar_selection_info), true);
			}

			if(get_post_meta($assignment_post_id, 'start_date', true)) {
				update_post_meta($assignment_post_id, 'start_date', $start_date);
			} else {
				add_post_meta($assignment_post_id, 'start_date', $start_date, true);
			}

			if(get_post_meta($assignment_post_id, 'start_time', true)) {
				update_post_meta($assignment_post_id, 'start_time', $start_time);
			} else {
				add_post_meta($assignment_post_id, 'start_time', $start_time, true);
			}

			if(get_post_meta($assignment_post_id, 'end_date', true)) {
				update_post_meta($assignment_post_id, 'end_date', $end_date);
			} else {
				add_post_meta($assignment_post_id, 'end_date', $end_date, true);
			}

			if(get_post_meta($assignment_post_id, 'end_time', true)) {
				update_post_meta($assignment_post_id, 'end_time', $end_time);
			} else {
				add_post_meta($assignment_post_id, 'end_time', $end_time, true);
			}

			if(is_object(get_assignment_lesson_slides( $assignment_post_id ))) {
				add_post_meta($assignment_post_id, 'assignment_type', 'slides_activity');
			} else {
				add_post_meta($assignment_post_id, 'assignment_type', 'video_activity');
			}
		}
		
        return wp_send_json_success("Assignments Created!");
    }

	public static function assignment_attempted($request) {
		$assignment_id = $request->get_param('assignmentId');
		$student_user_id = $request->get_param('userId');
		$student_posts = get_posts(array(
			'post_type' => TL_STUDENT_CPT,
			'meta_query' => array(
				array(
					'key' => 'lxp_student_admin_id',
					'value' => $student_user_id,
					'compare' => '='
				)
			)
		));
		$student_post = $student_posts[0];
		
		if ($student_post) {
			$ok = false;
			$student_id = $student_post->ID;
			// add student_id as a 'attempted' metadata to assignment post
			if ( !in_array($student_id, get_post_meta($assignment_id, 'attempted_students')) ) {
				$ok = add_post_meta($assignment_id, 'attempted_students', $student_id);
			}
			$message = $ok ? "Assignment Attempted!" : "Attemp record not created!";
			return wp_send_json_success($message);
		} else {
			return wp_send_json_error("Assignment Attempt Failed!");
		}
	}

	public static function assignment_stats($request) {
		$assignment_id = $request->get_param('assignment_id');
		$students_ids = get_post_meta($assignment_id, 'lxp_student_ids');
		$q = new WP_Query( array( "post_type" => TL_STUDENT_CPT, 'posts_per_page'   => -1, "post__in" => $students_ids ) );
		$students_posts = $q->get_posts();
		$students = array_map(function ($student) use ($assignment_id) {
			$attempted = self::lxp_user_assignment_attempted($assignment_id, $student->ID);
			$submission = self::lxp_get_assignment_submissions($assignment_id, $student->ID);

			$status = 'To Do';
			if ($attempted && !is_null($submission) && !$submission['lti_user_id'] && !$submission['submission_id']) {
				$status = 'In Progress';
			} else if ($attempted && !is_null($submission) && $submission['lti_user_id'] && $submission['submission_id']) {
				$status = 'Completed';
				if (get_post_meta($submission['ID'], 'mark_as_graded', true) === 'true') {
				$status = 'Graded';
				}
			}
			$lxp_student_admin_id = get_post_meta($student->ID, 'lxp_student_admin_id', true);
			$userdata = get_userdata($lxp_student_admin_id);

			$progress = $submission && isset($submission['score_raw']) && ($submission['score_raw'] != '') ? $submission['score_raw'] .'/'. $submission['score_max'] : '---';
			$score = $submission && $submission['score_scaled'] ? round(($submission['score_scaled'] * 100), 2) . '%' : '---';
			$data = array("ID" => $student->ID, "name" => $userdata->data->display_name, "status" => $status, "progress" => $progress, "score" => $score);
			return $data;
		} , $students_posts);
		return wp_send_json_success($students);
	}

	public static function lxp_get_assignment_submissions($assignment_id, $student_post_id) {
		$query = new WP_Query( array( 'post_type' => TL_ASSIGNMENT_SUBMISSION_CPT , 'posts_per_page'   => -1, 'post_status' => array( 'publish' ), 
									'meta_query' => array(
										array('key' => 'lxp_assignment_id', 'value' => $assignment_id, 'compare' => '='),
										array('key' => 'lxp_student_id', 'value' => $student_post_id, 'compare' => '=')
									)
								)
							);
		$assignment_submission_posts = $query->get_posts();
	
		if ($assignment_submission_posts) {
			$lesson_id = get_post_meta($assignment_id, 'lxp_lesson_id', true);
			$assignment_submission_post = $assignment_submission_posts[0];
			$assignment_submission_post_data = array(
				'ID' => $assignment_submission_post->ID,
				'lxp_assignment_id' => get_post_meta($assignment_submission_post->ID, 'lxp_assignment_id', true),
				'lxp_student_id' => get_post_meta($assignment_submission_post->ID, 'lxp_student_id', true),
				'lti_user_id' => get_post_meta($assignment_submission_post->ID, 'lti_user_id', true),
				'h5p_content_id' => get_post_meta($lesson_id, 'h5p_content_id', true),
            	'activity_id' => get_post_meta($lesson_id, 'lti_custom_attr', true),
				'submission_id' => get_post_meta($assignment_submission_post->ID, 'submission_id', true),
				'score_min' => get_post_meta($assignment_submission_post->ID, 'score_min', true),
				'score_max' => get_post_meta($assignment_submission_post->ID, 'score_max', true),
				'score_raw' => get_post_meta($assignment_submission_post->ID, 'score_raw', true),
				'score_scaled' => get_post_meta($assignment_submission_post->ID, 'score_scaled', true),
				'completion' => boolval(get_post_meta($assignment_submission_post->ID, 'completion', true)),
				'duration' => get_post_meta($assignment_submission_post->ID, 'duration', true)
			);
			return $assignment_submission_post_data;
		} else {
			return null;
		}
	}

	public static function lxp_user_assignment_attempted($assignment_id, $user_id) {
		$query = new WP_Query( array( 
			'post_type' => TL_ASSIGNMENT_CPT ,
			'posts_per_page'   => -1, 
			'post_status' => array( 'publish' ), 
			'p' => $assignment_id,
			'meta_query' => array( 
				array('key' => 'attempted_students', 'value' => $user_id, 'compare' => 'IN') 
			)
		) );
		$assignment_posts = $query->get_posts();
		return count($assignment_posts) > 0 ? true : false;
	}

    public static function get_teacher_assignments_calendar_events($teacher_id) {
		$assignment_query = new WP_Query( array( 
			'post_type' => TL_ASSIGNMENT_CPT, 
			'post_status' => array( 'publish' ),
			'posts_per_page'   => -1,        
			'meta_query' => array(
				array('key' => 'lxp_assignment_teacher_id', 'value' => $teacher_id, 'compare' => 'IN')
			)
		) );
		
		return array_map(function($assignment) {
			$calendar_selection_info = json_decode(get_post_meta($assignment->ID, 'calendar_selection_info', true));
			$lxp_lesson_post = get_post(get_post_meta($assignment->ID, 'lxp_lesson_id', true));
			$course = get_post(get_post_meta($assignment->ID, 'course_id', true));
			$event = array();
			if ($lxp_lesson_post && $course) {
				$event["id"] = $assignment->ID;
				$event["start"] = $calendar_selection_info->start;
				$event["end"] = $calendar_selection_info->end;
				$event["allDay"] = $calendar_selection_info && property_exists($calendar_selection_info, 'allDay') ? $calendar_selection_info->allDay : false;
				$event["title"] = $lxp_lesson_post->post_title;
				$event["segment"] = implode("-", explode(" ", strtolower($lxp_lesson_post->post_title))) ;
				$event['course'] = $course ? $course->post_title : '';
				$event['course_post_image'] = get_the_post_thumbnail_url($course ? $course->ID : ''); 
				$event["calendar_selection_info"] = json_encode($calendar_selection_info);
			}
			return $event;
		}, $assignment_query->get_posts());
	}

	public static function get_student_assignments_calendar_events($student_id) {
		$assignment_query = new WP_Query( array( 
			'post_type' => TL_ASSIGNMENT_CPT, 
			'post_status' => array( 'publish' ),
			'posts_per_page'   => -1,        
			'meta_query' => array(
				array('key' => 'lxp_student_ids', 'value' => $student_id, 'compare' => 'IN')
			)
		) );
		
		return array_map(function($assignment) {
			$calendar_selection_info = json_decode(get_post_meta($assignment->ID, 'calendar_selection_info', true));
			$lxp_lesson_post = get_post(get_post_meta($assignment->ID, 'lxp_lesson_id', true));
			$course = get_post(get_post_meta($assignment->ID, 'course_id', true));
			// $args = array( 'posts_per_page' => -1, 'post_type' => LP_LESSON_CPT, 'meta_query' => array(array('key'   => 'tl_course_id', 'value' =>  $course ? $course->ID : '')));
			// $lessons = get_posts($args);
			$digital_journal_link = $lxp_lesson_post->guid;
			// foreach($lessons as $lesson){ 
			// 	if ( $lxp_lesson_post->ID === $lesson->ID ) {
			// 		 $digital_journal_link = get_permalink($lesson->ID); 
			// 	}; 
			// }
			$digital_journal_link = $digital_journal_link ? $digital_journal_link . '?assignment_id=' . $assignment->ID : '';
			$event = array();
			if ($lxp_lesson_post && $course) {
				$event["id"] = $assignment->ID;
				$event["start"] = $calendar_selection_info->start;
				$event["end"] = $calendar_selection_info->end;
				$event["allDay"] = $calendar_selection_info && property_exists($calendar_selection_info, 'allDay') ? $calendar_selection_info->allDay : false;
				$event["title"] = $lxp_lesson_post->post_title;
				$event["segment"] = implode("-", explode(" ", strtolower($lxp_lesson_post->post_title))) ;
				$event['course'] = $course ? $course->post_title : '';
				$event["calendar_selection_info"] = json_encode($calendar_selection_info);
				$event["digital_journal_link"] = $digital_journal_link;
			}
			return $event;
		}, $assignment_query->get_posts());
	}

    public static function calendar_events($request) {
		$userdata = get_userdata($request->get_param('user_id'));
		$user_roles = $userdata ? (array) $userdata->roles : array();
		$userRole = in_array('lp_teacher', $user_roles, true) ? 'lp_teacher' : (count($user_roles) > 0 ? array_values($user_roles)[0] : '');
		if ($userRole === 'lxp_student') {
			$student_post = self::lxp_get_student_post($userdata->data->ID);
			return self::get_student_assignments_calendar_events($student_post->ID);
		} else if ($userRole === 'lp_teacher') {
			$teacher_post = self::lxp_get_teacher_post($userdata->data->ID);
			return self::get_teacher_assignments_calendar_events($teacher_post->ID);
		} else {
			return [];
		}
	}

	public static function lxp_get_student_post($student_id) {
		$school_query = new WP_Query( array( 
			'post_type' => TL_STUDENT_CPT, 
			'post_status' => array( 'publish' ),
			'posts_per_page'   => -1,        
			'meta_query' => array(
				array('key' => 'lxp_student_admin_id', 'value' => $student_id, 'compare' => '=')
			)
		) );
		
		$posts = $school_query->get_posts();
		return count($posts) > 0 ? $posts[0] : null;
	}
	
	public static function lxp_get_teacher_post($lxp_teacher_admin_id) {
		$teacher_query = new WP_Query( array( 
			'post_type' => TL_TEACHER_CPT, 
			'post_status' => array( 'publish' ),
			'posts_per_page'   => -1,        
			'meta_query' => array(
				array('key' => 'lxp_teacher_admin_id', 'value' => $lxp_teacher_admin_id, 'compare' => '=')
			)
		) );
		
		$posts = $teacher_query->get_posts();
		return ( count($posts) > 0 ? $posts[0] : null );
	}

    public static function get_students($request) {
		$assignment_id = $request->get_param('assignment_id');
		$lxp_student_ids = get_post_meta($assignment_id, 'lxp_student_ids');
		$students = array_map(function($student_id) { 
			$post = get_post($student_id); 
			$user = get_userdata(get_post_meta($student_id, 'lxp_student_admin_id', true))->data;
			return array('post' => $post, 'user' => $user);
		} , $lxp_student_ids);

		return wp_send_json_success(array("students" => $students));
	}

    public static function get_one($request) {
		$assignment_id = $request->get_param('assignment_id');
		$assignment = get_post($assignment_id);
		$assignment->grade = get_post_meta($assignment_id, 'grade', true);
		$assignment->lxp_assignment_teacher_id = get_post_meta($assignment_id, 'lxp_assignment_teacher_id', true);
		$assignment->lxp_student_ids = get_post_meta($assignment_id, 'lxp_student_ids');
		$assignment->schedule = json_decode(get_post_meta($assignment_id, 'schedule', true));
		return wp_send_json_success(array("assignment" => $assignment));
	}

    public static function update_assignment() {
        $user_data = array(
            'ID' => $_POST['id'],
            'user_login' => $_POST['login_name'],
            'first_name' => $_POST['first_name'],
            'last_name' =>$_POST['last_name'],
            'user_email' =>$_POST['user_email'],
            'display_name' =>$_POST['first_name'] . ' ' .$_POST['last_name'],
            'user_pass' =>$_POST['login_pass']
         );
         wp_send_json_success (wp_update_user($user_data));
	}

	// /**
	//  * @param   submission_id
	//  */
	public static function get_interactions_by_assignment($request) {
		$data = json_decode(file_get_contents('php://input'), true);
		$assignment_submission['assignment_submission_id'] = $data['assignment_submission_id'];
		$assignment_submission['lti_user_id'] = $data['lti_user_id'];
		$assignment_submission['h5p_content_id'] = $data['h5p_content_id'];
		$api_data = $data['interactions_data'];
		$interaction_type = $data['interaction_type'];
		require_once plugin_dir_path(dirname( __FILE__ )). 'templates/tinyLxpTheme/lxp/functions.php';
		// $interaction_xapis = [];
		$interaction_xapis = get_submitted_xapi_data( $assignment_submission );
		// var_dump(count($interaction_xapis)); die;
		$main_interactions = [];
		if ($interaction_type == 'CP') {
			foreach ($api_data as $slide_num => $single_slide) {
				$score_raw = 0;
				$score_max = 0;
				if (isset($single_slide['elements'])) { // this if for empty slide or slide have data
					foreach ($single_slide['elements'] as $quest_num => $question_per_slide) {
						$slide_firt_question_type = $single_slide['elements'][0]['action']['metadata']['contentType'];
						$intrect_type_name = $question_per_slide['action']['metadata']['contentType'];
						$sub_content_id_array = []; // do not change the place of this code
						if ( in_array($intrect_type_name, Allowed_Activity_types) ) {
							if ($intrect_type_name == 'Essay') {
								$sub_content_id_array[] = $question_per_slide['action']['subContentId'];
								$scores_array = getScoresByGivenXapiStatements($sub_content_id_array, $interaction_xapis);

								$scores = 1;
								$i_grade = get_post_meta($assignment_submission['assignment_submission_id'], 'slide_'.$sub_content_id_array[0].'_grade', true);

								$score_raw = $i_grade == '' ? 0 : $i_grade;
								$score_max = '7';
							} elseif ($intrect_type_name == 'Interactive Video') {
								$iv_data = $question_per_slide['action']['params']['interactiveVideo'];
								$result = get_iv_interactions_score( $assignment_submission, $iv_data, $interaction_xapis);
								$sub_content_id_array = $result['sub_content_id_array'];
								$score_raw = $result['score_raw'];
								$score_max = $result['score_max'];
								$scores_array = 1;
							} else {
								if ( $intrect_type_name == 'Statements' || $intrect_type_name == 'Summary' ) {
									$sub_content_id_array = array_column( $question_per_slide['action']['params']['summaries'], 'subContentId' );
								} elseif ($intrect_type_name == 'Single Choice Set') {
									$sub_content_id_array = array_column( $question_per_slide['action']['params']['choices'], 'subContentId');
								} else {
									$sub_content_id_array[] = $question_per_slide['action']['subContentId'];
								}
								$scores_array = getScoresByGivenXapiStatements($sub_content_id_array, $interaction_xapis);
								// var_dump($scores_array); 
								$scores = array_column($scores_array, 'score');
								$score_raw += array_sum(array_column($scores, 'raw'));
								$score_max += array_sum(array_column($scores, 'max'));
							}
						}
					}
					// var_dump($sub_content_id_array); 
					if (count($sub_content_id_array) > 0) {
						$main_interactions[] = [
							'slide_num' => $slide_num,
							'slide_question_num' => 0,
							'title' => $question_per_slide['action']['metadata']['title'],
							'sub_content_id' => $sub_content_id_array[0],
							'attempted' => $scores,
							'score' => $score_max != 0 ? $score_raw.'/'.$score_max : ($slide_firt_question_type == 'Interactive Video' ? '' : 'Not Attempted'),
							'auto_grade'=> $slide_firt_question_type == 'Essay' ? 0 : 1,
							'interactive_video'=> $slide_firt_question_type == 'Interactive Video' ? 1 : 0
						];
					}
				}
			}
		} elseif ($interaction_type == 'IV') {
			$iv_questions = filterInteractiveVideoInteractions($api_data);
			foreach ($iv_questions['data'] as $question_per_second) {
				$intrect_type_name = $question_per_second['action']['metadata']['contentType'];
				if ( isset($intrect_type_name) && in_array($intrect_type_name, Allowed_Activity_types) ) {
					$sub_content_id_array = []; // do not change the place of this code
					if ($intrect_type_name == 'Free Text Question') {
						$sub_content_id_array[] = $question_per_second['action']['subContentId'];

						$scores_array = getScoresByGivenXapiStatements($sub_content_id_array, $interaction_xapis);
						
						$i_grade = get_post_meta($assignment_submission['assignment_submission_id'], 'slide_'.$sub_content_id_array[0].'_grade', true);
						$score_raw = ($scores_array ? ($i_grade == '' ? '' : $i_grade) : '');
						$score_max = '7';
					} else {
						if ( $intrect_type_name == 'Statements' || $intrect_type_name == 'Summary' ) {
							$sub_content_id_array = array_column( $question_per_second['action']['params']['summaries'], 'subContentId' );
						} elseif ($intrect_type_name == 'Single Choice Set') {
							$sub_content_id_array = array_column( $question_per_second['action']['params']['choices'], 'subContentId');
						} else {
							$sub_content_id_array[] = $question_per_second['action']['subContentId'];
						}
						$scores_array = getScoresByGivenXapiStatements($sub_content_id_array, $interaction_xapis);
						$scores = array_column($scores_array, 'score');
						$score_raw = array_sum(array_column($scores, 'raw'));
						$score_max = array_sum(array_column($scores, 'max'));
					}
					$main_interactions[] = [
						'title' => $question_per_second['action']['metadata']['title'],
						'sub_content_id' => $sub_content_id_array[0],
						'score' => ($scores_array && $score_raw == '' && $intrect_type_name == 'Free Text Question' ? 'Not Gradded' : ($scores_array && $score_max != 0 ? $score_raw.'/'.$score_max : 'Not Attempted')),
						'auto_grade'=> $intrect_type_name == 'Free Text Question' ? 1 : 0,
						'attempted' => $scores_array
					];
				}
			}
		} elseif ($interaction_type == 'IB' || $interaction_type == 'QS') {
			foreach ($api_data as $section_per_activity) {
				$section_per_activity = $interaction_type == 'QS' ? $section_per_activity : $section_per_activity['content'];

				$intrect_type_name = $section_per_activity['metadata']['contentType'];
				if ( isset($intrect_type_name) && in_array($intrect_type_name, Allowed_Activity_types) ) {
					$sub_content_id_array = []; // do not change the place of this code
					if ($intrect_type_name == 'Essay') {
						$sub_content_id_array[] = $section_per_activity['subContentId'];
						$scores_array = getScoresByGivenXapiStatements($sub_content_id_array, $interaction_xapis);
						$i_grade = get_post_meta($assignment_submission['assignment_submission_id'], 'slide_'.$sub_content_id_array[0].'_grade', true);
						$score_raw = ($scores_array ? ($i_grade == '' ? '' : $i_grade) : '');
						$score_max = '7';
					} else {
						if ( $intrect_type_name == 'Statements' || $intrect_type_name == 'Summary' ) {
							$sub_content_id_array = array_column( $section_per_activity['params']['summaries'], 'subContentId' );
							$scores_array = getScoresByGivenXapiStatements($sub_content_id_array, $interaction_xapis);
							$scores = array_column($scores_array, 'score');
							$score_raw = array_sum(array_column($scores, 'raw'));
							$score_max = array_sum(array_column($scores, 'max'));
						} elseif ($intrect_type_name == 'Single Choice Set') {
							$sub_content_id_array = array_column( $section_per_activity['params']['choices'], 'subContentId');
							$scores_array = getScoresByGivenXapiStatements($sub_content_id_array, $interaction_xapis);
							$scores = array_column($scores_array, 'score');
							$score_raw = array_sum(array_column($scores, 'raw'));
							$score_max = array_sum(array_column($scores, 'max'));
						} elseif ($intrect_type_name == 'Course Presentation') {
							$slides = $section_per_activity['params']['presentation']['slides'];
							$result = get_cp_interactions_score($assignment_submission, $slides, $interaction_xapis);
							$sub_content_id_array = $result['sub_content_id_array'];
							$score_raw 			  = $result['score_raw'];
							$score_max 			  = $result['score_max'];
							$scores_array = 1;
						} elseif ($intrect_type_name == 'Interactive Video') {
							$iv_data = $section_per_activity['params']['interactiveVideo'];
							$result = get_iv_interactions_score( $assignment_submission, $iv_data, $interaction_xapis);
							$sub_content_id_array = $result['sub_content_id_array'];
							$score_raw 			  = $result['score_raw'];
							$score_max 			  = $result['score_max'];
							$scores_array 		  = 1;
						} elseif ($intrect_type_name == 'Question Set') {
							$qs_data = $section_per_activity['params']['questions'];
							$result = get_question_set_interactions_score($assignment_submission, $qs_data, $interaction_xapis);
							$sub_content_id_array = $result['sub_content_id_array'];
							$score_raw 			  = $result['score_raw'];
							$score_max 			  = $result['score_max'];
							$scores_array 		  = 1;
						} else {
							$sub_content_id_array[] = $section_per_activity['subContentId'];
							$scores_array = getScoresByGivenXapiStatements($sub_content_id_array, $interaction_xapis);
							$scores = array_column($scores_array, 'score');
							$score_raw = array_sum(array_column($scores, 'raw'));
							$score_max = array_sum(array_column($scores, 'max'));
						}
					}
					$main_interactions[] = [
						'title' => $section_per_activity['metadata']['title'],
						'sub_content_id' => $sub_content_id_array[0],
						'score' => ($scores_array && $score_raw == '' && ($intrect_type_name == 'Free Text Question' || $intrect_type_name == 'Essay') ? 'Not Gradded' : ($scores_array && $score_max != 0 ? $score_raw.'/'.$score_max : 'Not Attempted')),
						'auto_grade'=> $intrect_type_name == 'Free Text Question' || $intrect_type_name == 'Essay' ? 1 : 0,
						'interaction_type'=> $intrect_type_name,
						'attempted' => $scores_array
					];
				}
			}
		}
		return wp_send_json_success($main_interactions);
	}
}
