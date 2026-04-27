<?php

class Rest_Lxp_Class
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

		register_rest_route('lms/v1', '/class/students', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array('Rest_Lxp_Class', 'get_students'),
				'permission_callback' => '__return_true'
			)
		));

		register_rest_route('lms/v1', '/classes', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array('Rest_Lxp_Class', 'get_one'),
				'permission_callback' => '__return_true'
			)
		));
		
		register_rest_route('lms/v1', '/classes/save', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array('Rest_Lxp_Class', 'create'),
				'permission_callback' => '__return_true',
				'args' => array(
					'class_name' => array(
						'required' => true,
						'type' => 'string',
						'description' => 'class name',
						'validate_callback' => function($param, $request, $key) {
							return strlen( $param ) > 1;
						}
					),					
					// 'class_description' => array(
					// 	'required' => true,
					// 	'type' => 'string',
					// 	'description' => 'class description',
					// 	'validate_callback' => function($param, $request, $key) {
					// 		return strlen( $param ) > 1;
					// 	}
					// ),					
					// 'schedule' => array(
					// 	'required' => true,
					// 	'description' => 'class schedule',
					// 	'validate_callback' => function($param, $request, $key) {
					// 		$ok = true;
					// 		if (count( $param ) === 0) {
					// 			$ok = false;
					// 		}
					// 		foreach ($param as $day) {
					// 			$start = $request->get_param($day . '-sd');
					// 			$end = $request->get_param($day . '-ed');
					// 			if ( !(boolval(strlen($start)) || boolval(strlen($end))) )
					// 			{
					// 				$ok = false;
					// 			}
					// 		}
					// 		return $ok;
					// 	}
					// ),
					// 'grade' => array(
					// 	'required' => true,
					// 	'type' => 'string',
					// 	'description' => 'class grade',
					// 	'validate_callback' => function($param, $request, $key) {
					// 		return strlen( $param ) > 1;
					// 	}
					// ),
					'student_ids' => array(
						'required' => true,
						'description' => 'class students',
						'validate_callback' => function($param, $request, $key) {
							if (count( $param ) > 0) {
								return true;
							} else {
								return false;
							}
						}
					),
					'class_teacher_id' => array(
						'required' => true,
						'type' => 'integer',
						'description' => 'class teacher id',
						'validate_callback' => function($param, $request, $key) {
							return intval( $param ) > 0;
						}
					),
					'class_post_id' => array(
						'required' => true,
						'type' => 'integer',
						'description' => 'class post id',
						'validate_callback' => function($param, $request, $key) {
							return strlen( $param ) > 0;
						}
					)
			   )
			),
		));

		register_rest_route('lms/v1', '/update/class', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array('Rest_Lxp_Class', 'update_class'),
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
		// ============= Class Post =================================
		$class_teacher_id = $request->get_param('class_teacher_id');
		$class_post_id = intval($request->get_param('class_post_id'));
		$class_name = trim($request->get_param('class_name'));
		$class_description = trim($request->get_param('class_description'));
		$edlink_class_sec_id = trim($request->get_param('edlink_class_sec_id') ?? '');
		
		$class_post_arg = array(
			'post_title'    => wp_strip_all_tags($class_name),
			'post_content'  => $class_description,
			'post_status'   => 'publish',
			'post_author'   => $class_teacher_id,
			'post_type'   => TL_CLASS_CPT
		);
		if (intval($class_post_id) > 0) {
			$class_post_arg['ID'] = "$class_post_id";
		}
		
		// Insert / Update
		$class_post_id = wp_insert_post($class_post_arg);
		$grade = $request->get_param('grade') && $request->get_param('grade') != '0' ? $request->get_param('grade') : '';
		update_post_meta($class_post_id, 'grade', $grade);

		if(get_post_meta($class_post_id, 'lxp_class_teacher_id', true)) {
			update_post_meta($class_post_id, 'lxp_class_teacher_id', $class_teacher_id);
		} else {
			add_post_meta($class_post_id, 'lxp_class_teacher_id', $class_teacher_id, true);
		}

		if(get_post_meta($class_post_id, 'edlink_class_sec_id', true)) {
			update_post_meta($class_post_id, 'edlink_class_sec_id', $edlink_class_sec_id);
		} else {
			add_post_meta($class_post_id, 'edlink_class_sec_id', $edlink_class_sec_id, true);
		}
		delete_post_meta($class_post_id, 'lxp_student_ids');
		$student_ids = $request->get_param('student_ids');
		foreach ($student_ids as $student_id) {
			add_post_meta($class_post_id, 'lxp_student_ids', $student_id);
		}

		$schedule = array();
		if (is_array($request->get_param('schedule'))) {
			foreach ($request->get_param('schedule') as $day) {
				$start = $request->get_param($day . '-sd');
				$end = $request->get_param($day . '-ed');
				$schedule[$day] = array("start" => $start, "end" => $end);
			}
		}
		
		if(get_post_meta($class_post_id, 'schedule', true)) {
			update_post_meta($class_post_id, 'schedule', json_encode($schedule));
		} else {
			add_post_meta($class_post_id, 'schedule', json_encode($schedule), true);
		}

		if(get_post_meta($class_post_id, 'lxp_class_type', true)) {
			update_post_meta($class_post_id, 'lxp_class_type', $request->get_param('type'));
		} else {
			add_post_meta($class_post_id, 'lxp_class_type', $request->get_param('type'), true);
		}

        return wp_send_json_success("Class Saved!");
    }

    public static function get_students($request) {
		$class_id = $request->get_param('class_id');
		$lxp_student_ids = get_post_meta($class_id, 'lxp_student_ids');
		// fetch student posts using WP_Query which includes ids form $lxp_student_ids
		$students = new WP_Query(array(
			'post_type' => TL_STUDENT_CPT,
			'post__in' => $lxp_student_ids,
			'posts_per_page' => -1,
			'orderby' => 'title',
			'order' => 'ASC'
		));
		$student_posts = $students->posts;
		$student_posts_result = array_map(function($post) { 
			$user_data = get_userdata(get_post_meta($post->ID, 'lxp_student_admin_id', true))->data;
			$user = ["ID" => $user_data->ID, "display_name" => $user_data->display_name, "user_email" => $user_data->user_email, "user_login" => $user_data->user_login];
			return array('post' => $post, 'user' => $user);
		} , $student_posts);

		return wp_send_json_success(array("students" => $student_posts_result));
	}

    public static function get_one($request) {
		$class_id = $request->get_param('class_id');
		$class = get_post($class_id);
		$class->grade = get_post_meta($class_id, 'grade', true);
		$class->lxp_class_teacher_id = get_post_meta($class_id, 'lxp_class_teacher_id', true);
		$class->lxp_student_ids = get_post_meta($class_id, 'lxp_student_ids');
		$class->schedule = json_decode(get_post_meta($class_id, 'schedule', true));
		$class->lxp_class_type = get_post_meta($class_id, 'lxp_class_type', true);
		$class->edlink_class_sec_id = get_post_meta($class_id, 'edlink_class_sec_id', true);
		return wp_send_json_success(array("class" => $class));
	}

    public static function update_class()
	{
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

}
