<?php

class Rest_Lxp_Teacher
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

		// /teacher/settings/update
		register_rest_route('lms/v1', '/teacher/settings/update', array(
			array(
				'methods' => WP_REST_Server::ALLMETHODS,
				'callback' => array('Rest_Lxp_Teacher', 'update_settings'),
				'permission_callback' => '__return_true'
			)
		));

		// /teacher/settings
		register_rest_route('lms/v1', '/teacher/settings', array(
			array(
				'methods' => WP_REST_Server::ALLMETHODS,
				'callback' => array('Rest_Lxp_Teacher', 'get_settings'),
				'permission_callback' => '__return_true'
			)
		));

		register_rest_route('lms/v1', '/teacher/students', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array('Rest_Lxp_Teacher', 'teacher_students'),
				'permission_callback' => '__return_true'
			)
		));

		register_rest_route('lms/v1', '/teacher/treks/assign', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array('Rest_Lxp_Teacher', 'set_restricted_courses'),
				'permission_callback' => '__return_true'
			)
		));

		register_rest_route('lms/v1', '/teacher/treks/restricted', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array('Rest_Lxp_Teacher', 'get_restricted_courses'),
				'permission_callback' => '__return_true'
			)
		));

		// register_rest_route('lms/v1', '/teacher/treks/saved', array(
		// 	array(
		// 		'methods' => WP_REST_Server::EDITABLE,
		// 		'callback' => array('Rest_Lxp_Teacher', 'treks_saved'),
		// 		'permission_callback' => '__return_true'
		// 	)
		// ));

		register_rest_route('lms/v1', '/teachers', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array('Rest_Lxp_Teacher', 'get_one'),
				'permission_callback' => '__return_true'
			)
		));

		register_rest_route('lms/v1', '/teachers/import', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array('Rest_Lxp_Teacher', 'import'),
				'permission_callback' => '__return_true'
			),
		));
		
		register_rest_route('lms/v1', '/teachers/save', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array('Rest_Lxp_Teacher', 'create'),
				'permission_callback' => '__return_true',
				'args' => array(
					'lxp_user_email' => array(
					   'required' => true,
					   'type' => 'string',
					   'description' => 'user email name',  
					   'format' => 'email',
					   'validate_callback' => function($param, $request, $key) {
							if (strlen(trim($request->get_param('lxp_user_email'))) == 0) {
								return false;
							}
							
							$user_by_email = get_user_by("email", trim($request->get_param('lxp_user_email')));
							$user_by_login = get_user_by("login", trim($request->get_param('lxp_user_email')));
							if ( $user_by_email && intval($request->get_param('teacher_post_id')) > 0 && $user_by_email->data->user_email !== trim($request->get_param('lxp_user_email_default')) ) {
								return false;
							} else if ($request->get_param('teacher_post_id') == 0) {
								return ( !($user_by_email || $user_by_login) ? true : false );
							} if ( trim($request->get_param('lxp_user_email')) == '' ) {
								return false;
							} else {
								return true;
							}
						}
				  	),
					'lxp_first_name' => array(
						'required' => true,
						'type' => 'string',
						'description' => 'user first name',
						'validate_callback' => function($param, $request, $key) {
							return strlen( $param ) > 1;
						}
					),
					'lxp_last_name' => array(
						'required' => true,
						'type' => 'string',
						'description' => 'user last name',
						'validate_callback' => function($param, $request, $key) {
							return strlen( $param ) > 1;
						}
					),
					'lxp_user_password' => array(
						'required' => true,
						'type' => 'string',
						'description' => 'user login password',
						'validate_callback' => function($param, $request, $key) {
							$teacher_post_id = intval($request->get_param('teacher_post_id'));
							if ($teacher_post_id < 1) {
								return strlen( $param ) > 1;
							} else {
								return true;
							}
						}
					),
					'teacher_school_id' => array(
						'required' => true,
						'type' => 'integer',
						'description' => 'user school id',
						'validate_callback' => function($param, $request, $key) {
							return strlen( $param ) > 0;
						}
					)
			   )
			),
		));
		

		register_rest_route('lms/v1', '/edlink/teachers/save', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array('Rest_Lxp_Teacher', 'edlink_create'),
				'permission_callback' => '__return_true',
				'args' => array(
					'edlink_teachers' => array(
					   'required' => true,
					   'type' => 'array',
					   'description' => 'user email name',  
					   'format' => 'email',
					   'validate_callback' => function($param, $request, $key) {							
							return (!empty($param)) ? true : false;
						}
				  	),					
					'edlink_teacher_school_id' => array(
						'required' => true,
						'type' => 'integer',
						'description' => 'user school id',
						'validate_callback' => function($param, $request, $key) {
							return strlen( $param ) > 0;
						}
					)
			   )
			),
		));
		register_rest_route('lms/v1', '/update/teacher', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array('Rest_Lxp_Teacher', 'update_teacher'),
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

		register_rest_route('lms/v1', '/teacher/courses/saved', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array('Rest_Lxp_Teacher', 'courses_saved'),
				'permission_callback' => '__return_true'
			)
		));
	}

	public static function update_settings($request) {
		$entity_post_id = intval($request->get_param('entity_post_id'));		
		$active = $request->get_param('active');
		update_post_meta($entity_post_id, 'settings_active', $active);
		return wp_send_json_success( "Settings Saved!" );
	}

	public static function get_settings($request) {
		$entity_post_id = intval($request->get_param('entity_post_id'));
		// get 'settings_active' post metadata and return it as 'active' attribute in response
		$active = get_post_meta($entity_post_id, 'settings_active', true);
		$active = $active && $active === 'false' ? false : true;
		return wp_send_json_success( ["active" => $active] );
	}

	public static function lxp_get_school_students($school_id)
	{
		$school_query = new WP_Query( array( 
			'post_type' => TL_STUDENT_CPT, 
			'post_status' => array( 'publish' ),
			'posts_per_page'   => -1,        
			'meta_query' => array(
				array('key' => 'lxp_student_school_id', 'value' => $school_id, 'compare' => '=')
			)
		) );
		
		$posts = $school_query->get_posts();
		return $posts;
	}

	public static function teacher_students($request) {
		$teacher_post =  get_post(intval($request->get_param('teacher_id')));
		// $teacher_school_id = get_post_meta($teacher_post->ID, 'lxp_teacher_school_id', true);
		$students = self::lxp_get_school_teacher_students($teacher_post->ID);
		$students = array_map(function($student_post) {
			$student_id = $student_post->ID;
			$user_data = get_userdata(get_post_meta($student_id, 'lxp_student_admin_id', true))->data;
			$user = ["ID" => $user_data->ID, "display_name" => $user_data->display_name, "user_email" => $user_data->user_email, "user_login" => $user_data->user_login];
			return array('post' => $student_post, 'user' => $user);
		}, array_values($students));
		return wp_send_json_success( ["students" => ($students ? $students : array())] );
	}

	// get restricted courses
	public static function get_restricted_courses($request) {
		$teacher_post_id = intval($request->get_param('teacher_post_id'));
		$restricted_courses = get_post_meta($teacher_post_id, 'restricted_courses');
		return wp_send_json_success( ($restricted_courses ? $restricted_courses : array()) );
	}

	// save restricted courses
	public static function set_restricted_courses($request) {
		$teacher_post_id = intval($request->get_param('teacher_post_id'));
		$course_ids = $request->get_param('treks');
		$course_ids = $course_ids ? $course_ids : array();
		// add teacher 'restricted_course' post metadata and remove existing
		delete_post_meta($teacher_post_id, 'restricted_courses');
		foreach ($course_ids as $course_id) {
			add_post_meta($teacher_post_id, 'restricted_courses', $course_id);
		}
		return wp_send_json_success(get_post_meta($teacher_post_id, 'restricted_courses'));
	}

	// public static function treks_saved($request) {
	// 	$teacher_post_id = intval($request->get_param('teacher_post_id'));
	// 	$is_saved = boolval($request->get_param('is_saved'));
	// 	$course_id = intval($request->get_param('course_id'));
	// 	// add/delete teacher 'courses_saved' post metadata
	// 	if ($is_saved) {
	// 		add_post_meta($teacher_post_id, 'courses_saved', $course_id);
	// 	} else {
	// 		delete_post_meta($teacher_post_id, 'courses_saved', $course_id);
	// 	}

	// 	return wp_send_json_success(get_post_meta($teacher_post_id, 'courses_saved'));
	// }

	public static function create($request) {		
		
		// ============= Teacher Post =================================
		$school_admin_id = $request->get_param('school_admin_id');
		$teacher_post_id = intval($request->get_param('teacher_post_id'));
		$teacher_name = wp_strip_all_tags(trim($request->get_param('lxp_last_name')) . ', ' . trim($request->get_param('lxp_first_name')));
		$teacher_description = trim($request->get_param('about')) ? trim($request->get_param('about')) : '';
		
		$shool_post_arg = array(
			'post_title'    => wp_strip_all_tags($teacher_name),
			'post_content'  => $teacher_description,
			'post_status'   => 'publish',
			'post_author'   => $school_admin_id,
			'post_type'   => TL_TEACHER_CPT
		);
		if (intval($teacher_post_id) > 0) {
			$shool_post_arg['ID'] = "$teacher_post_id";
		}
		// Insert / Update
		$teacher_post_id = wp_insert_post($shool_post_arg);
		if(get_post_meta($teacher_post_id, 'grades', json_encode($request->get_param('grades')))) {
			update_post_meta($teacher_post_id, 'grades', json_encode($request->get_param('grades')));
		} else {
			add_post_meta($teacher_post_id, 'grades', json_encode($request->get_param('grades')), true);
		}
		// ============= Profile Picture =============================
		/* $file = $request->get_file_params();
		$profilePicture = isset($file['profile_picture']) ? $file['profile_picture'] : null;
		if ($profilePicture['size'] > 0) {
			$mimes = array(
				'bmp'  => 'image/bmp',
				'gif'  => 'image/gif',
				'jpe'  => 'image/jpeg',
				'jpeg' => 'image/jpeg',
				'jpg'  => 'image/jpeg',
				'png'  => 'image/png',
				'tif'  => 'image/tiff',
				'tiff' => 'image/tiff'
			);
			
			$overrides = array(
				'mimes'     => $mimes,
				'test_form' => false
			);
			 
			$upload = wp_handle_upload( $file['profile_picture'], $overrides );

			if ( $upload && !isset( $upload['error'] ) ) {
				// File uploaded successfully. 
				$uploadedFileURL = $upload['url'];
				$uploadedFileName = basename($upload['url']);
				
				// Add Featured Image to Post
				$image_url        = $uploadedFileURL; // Define the image URL here
				$image_name       = $uploadedFileName;
				$upload_dir       = wp_upload_dir(); // Set upload folder
				$image_data       = file_get_contents($image_url); // Get image data
				$unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); // Generate unique name
				$filename         = basename( $unique_file_name ); // Create image file name
	
				// Check folder permission and define file location
				if( wp_mkdir_p( $upload_dir['path'] ) ) {
					$file = $upload_dir['path'] . '/' . $filename;
				} else {
					$file = $upload_dir['basedir'] . '/' . $filename;
				}
				
				// Create the image  file on the server
				file_put_contents( $file, $image_data );
	
				// Check image file type
				$wp_filetype = wp_check_filetype( $filename, null );
				
				// Set attachment data
				$attachment = array(
					'post_mime_type' => $wp_filetype['type'],
					'post_title'     => sanitize_file_name( $filename ),
					'post_content'   => '',
					'post_status'    => 'inherit'
				);
				
				// Create the attachment
				$attach_id = wp_insert_attachment( $attachment, $file, $teacher_post_id );
	
				// Include image.php
				require_once(ABSPATH . 'wp-admin/includes/image.php');
	
				// Define attachment metadata
				$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
	
				// Assign metadata to attachment
				wp_update_attachment_metadata( $attach_id, $attach_data );
	
				// And finally assign featured image to post
				set_post_thumbnail( $teacher_post_id, $attach_id );	
			}	
		} */
		
		// ========== Teacher Admin ===========
		$teacher_admin_data = array(
			'user_login' => trim($request->get_param('lxp_user_email')),
			'user_email' => trim($request->get_param('lxp_user_email')),
			'first_name' => trim($request->get_param('lxp_first_name')),
			'last_name' => trim($request->get_param('lxp_last_name')),
			'display_name' =>  wp_strip_all_tags($teacher_name),
			'role' => 'lp_teacher'
		);
		
		if (trim($request->get_param('lxp_user_password'))) {
			$teacher_admin_data['user_pass'] = trim($request->get_param('lxp_user_password'));
		}

		$lxp_teacher_admin_id = get_post_meta($teacher_post_id, 'lxp_teacher_admin_id', true);
		if ($lxp_teacher_admin_id) {
			$teacher_admin_data["ID"] = $lxp_teacher_admin_id;
		}
		$teacher_admin_id  = wp_insert_user($teacher_admin_data);
		if (!is_wp_error($teacher_admin_id)) {
			// Use only 'lp_teacher' for teacher users.
		}
		if (trim($request->get_param('lxp_user_password'))) {
			wp_set_password( trim($request->get_param('lxp_user_password')), $teacher_admin_id );
		}

		if (!boolval($lxp_teacher_admin_id) && $teacher_admin_id) {
			if(get_post_meta($teacher_post_id, 'lxp_teacher_admin_id', $teacher_admin_id)) {
				update_post_meta($teacher_post_id, 'lxp_teacher_admin_id', $teacher_admin_id);
			} else {
				add_post_meta($teacher_post_id, 'lxp_teacher_admin_id', $teacher_admin_id, true);
			}
			
			if(get_post_meta($teacher_post_id, 'lxp_teacher_school_id', true)) {
				update_post_meta($teacher_post_id, 'lxp_teacher_school_id', trim($request->get_param('teacher_school_id')));
			} else {
				add_post_meta($teacher_post_id, 'lxp_teacher_school_id', trim($request->get_param('teacher_school_id')), true);
			}
		}

        return wp_send_json_success("Teacher Saved!");
    }

	public static function edlink_create($request) {
		
		// ============= Teacher Post =================================
		$school_admin_id = $request->get_param('edlink_school_admin_id');
		$teacher_post_id = intval($request->get_param('edlink_teacher_post_id'));		
		$edlink_teachers = $request->get_param('edlink_teachers');

		foreach ($edlink_teachers as $user) {
			$user = explode("|", $user);
			$first_name = trim($user[0]);
			$last_name = trim($user[1]);
			$email = trim($user[2]);

			$teacher_name = $first_name.', '.$last_name;
			$teacher_description = trim($request->get_param('about')) ? trim($request->get_param('about')) : $teacher_name;

			$shool_post_arg = array(
				'post_title'    => wp_strip_all_tags($teacher_name),
				'post_content'  => $teacher_description,
				'post_status'   => 'publish',
				'post_author'   => $school_admin_id,
				'post_type'   => TL_TEACHER_CPT
			);
			
			if (intval($teacher_post_id) > 0 && trim($request->get_param('about'))) {
				$shool_post_arg['ID'] = "$teacher_post_id";
			}
			// Insert / Update
			$teacher_post_id = wp_insert_post($shool_post_arg);
			if(get_post_meta($teacher_post_id, 'grades', json_encode($request->get_param('grades'))) && trim($request->get_param('about'))) {
				update_post_meta($teacher_post_id, 'grades', json_encode($request->get_param('grades')));
			} else {
				add_post_meta($teacher_post_id, 'grades', json_encode($request->get_param('grades')), true);
			}			

			$teacher_admin_data = array(
				'user_login' => $email,
				'user_email' => $email,
				'first_name' => $first_name,
				'last_name' => $last_name,
				'display_name' =>  wp_strip_all_tags($teacher_name),
				'role' => 'lp_teacher'
			);
			
			$teacher_admin_data['user_pass'] = $email;

			$lxp_teacher_admin_id = get_post_meta($teacher_post_id, 'lxp_teacher_admin_id', true);
			if ($lxp_teacher_admin_id && trim($request->get_param('about'))) {
				$teacher_admin_data["ID"] = $lxp_teacher_admin_id;
				global $wpdb;
				$result = $wpdb->update(
					$wpdb->users,
					array('user_login' => $email),
					array('ID' => $lxp_teacher_admin_id)
				);
			}
			$teacher_admin_id  = wp_insert_user($teacher_admin_data);			

			// Use only 'lp_teacher' for teacher users.
			if (!is_wp_error($teacher_admin_id)) {
				wp_set_password($email, $teacher_admin_id);
			}

			if (!boolval($lxp_teacher_admin_id) && $teacher_admin_id) {
				if(get_post_meta($teacher_post_id, 'lxp_teacher_admin_id', $teacher_admin_id) && trim($request->get_param('about'))) {
					update_post_meta($teacher_post_id, 'lxp_teacher_admin_id', $teacher_admin_id);
				} else {
					add_post_meta($teacher_post_id, 'lxp_teacher_admin_id', $teacher_admin_id, true);
				}
                
				if(get_post_meta($teacher_post_id, 'lxp_teacher_school_id', true) && trim($request->get_param('about'))) {
					update_post_meta($teacher_post_id, 'lxp_teacher_school_id', trim($request->get_param('edlink_teacher_school_id')));
				} else {
					add_post_meta($teacher_post_id, 'lxp_teacher_school_id', trim($request->get_param('edlink_teacher_school_id')), true);
				}
			}
		}

		return wp_send_json_success("Teacher Saved!");
	}

    public static function get_one($request) {
		$teacher_id = $request->get_param('teacher_id');
		$teacher = get_post($teacher_id);
		$teacher->grades = json_decode(get_post_meta($teacher_id, 'grades', true));
		$admin = get_userdata(get_post_meta($teacher_id, 'lxp_teacher_admin_id', true));
		$admin->data->first_name = get_user_meta($admin->ID, 'first_name', true);
		$admin->data->last_name = get_user_meta($admin->ID, 'last_name', true);
		return wp_send_json_success(array("teacher" => $teacher, "admin" => $admin));
	}

    public static function update_teacher()
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

	public static function import($request)
	{
		$school_admin_id = $request->get_param('school_admin_id');
		$file = $request->get_file_params();
		$teachers_csv = isset($file['teachers']) ? $file['teachers'] : null;
		if ($teachers_csv['size'] > 0 && $teachers_csv['type'] == 'text/csv') {
			
			$overrides = array('test_form' => false);
			$upload = wp_handle_upload( $teachers_csv, $overrides );
			if ( $upload && !isset( $upload['error'] ) ) {
				$csv_file_url = $upload["url"];
				
				if (($handle = fopen($csv_file_url, "r")) !== false) {
					while (($row = fgetcsv($handle, 1000, ",")) !== false) {
						if (count($row) >= 4) {
							$first_name = trim($row[0]);
							$last_name = trim($row[1]);
							$user_display_name = $last_name . ', ' . $first_name;
							$email = strtolower( trim($row[2]) );
							$password = trim($row[3]);
							$grades = explode('-', trim($row[4]));
							
							if (!get_user_by('email', $email)) {
								$teacher_post_arg = array(
									'post_title'    => wp_strip_all_tags($user_display_name),
									'post_content'  => '',
									'post_status'   => 'publish',
									'post_author'   => $school_admin_id,
									'post_type'   => TL_TEACHER_CPT
								);
								// Insert teacher post
								$teacher_post_id = wp_insert_post($teacher_post_arg);
	
								// ========== teacher Admin ===========
								$teacher_admin_data = array(
									'user_login' => $email,
									'user_email' => $email,
									'first_name' => $first_name,
									'last_name' => $last_name,
									'display_name' => $user_display_name,
									'user_pass' => $password,
									'role' => 'lp_teacher'
								);
									$teacher_admin_id  = wp_insert_user($teacher_admin_data);
									if (!is_wp_error($teacher_admin_id)) {
										wp_set_password( $password, $teacher_admin_id );
									add_post_meta($teacher_post_id, 'lxp_teacher_admin_id', $teacher_admin_id, true);
									add_post_meta($teacher_post_id, 'lxp_teacher_school_id', trim($request->get_param('teacher_school_id')), true);
									update_post_meta($teacher_post_id, 'grades', json_encode($grades));
								}
							}
						}		
					}
					fclose($handle);
				}
				return wp_send_json_success("teachers imported successfully.");
			} else {
				return  wp_send_json_error("File could not uploaded.", 400);
			} 

		} else {
			return  wp_send_json_error("Invalid file . Upload valid CSV file.", 400);
		}

		return wp_send_json_success("");
	}

	public static function lxp_get_school_teacher_students($teacher_id)
	{
		$all_students = get_posts([
			'post_type'      => TL_STUDENT_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query' => [
				'relation' => 'OR',
				['key' => 'lxp_teacher_id', 'compare' => 'EXISTS']
			]
		]);

		$filtered_posts = array_filter($all_students, function($post) use ($teacher_id) {
			$meta = get_post_meta($post->ID, 'lxp_teacher_id', true);
			
			if (is_numeric($meta) && (int)$meta === (int)$teacher_id) {
				return true;
			}

			if (is_array($meta) && in_array((int)$teacher_id, array_map('intval', $meta))) {
				return true;
			}

			return false;
		});
		return $filtered_posts;
	}

	public static function courses_saved($request) {
		$teacher_post_id = intval($request->get_param('teacher_post_id'));
		$is_saved = $request->get_param('is_saved');
		$course_id = intval($request->get_param('course_id'));
		// add/delete treacher 'courses_saved' post metadata
		if ($is_saved == 0) {
			add_post_meta($teacher_post_id, 'courses_saved', $course_id);
		} else {
			delete_post_meta($teacher_post_id, 'courses_saved', $course_id);
		}

		return wp_send_json_success(get_post_meta($teacher_post_id, 'courses_saved'));
	}
}
