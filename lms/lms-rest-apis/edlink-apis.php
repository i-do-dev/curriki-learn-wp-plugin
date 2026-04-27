<?php

class Rest_Lxp_Edlink_API
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

		// Get auth token
		register_rest_route('lms/v1', '/edlink/authentication/token', array(
			array(
				'methods' => WP_REST_Server::ALLMETHODS,
				'callback' => array('Rest_Lxp_Edlink_API', 'exchange_code_for_access_token'),
				'permission_callback' => '__return_true'
			)
		));

		// Get Providers List
		register_rest_route('lms/v1', '/edlink/provider', array(
			array(
				'methods' => WP_REST_Server::ALLMETHODS,
				'callback' => array('Rest_Lxp_Edlink_API', 'get_provider_list'),
				'permission_callback' => '__return_true'
			)
		));	

		// Get Provider District List
		register_rest_route('lms/v1', '/edlink/provider/district', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array('Rest_Lxp_Edlink_API', 'get_provider_district_list'),
				'permission_callback' => '__return_true'
			)
		));
		
		// Get Provider District And People List
		register_rest_route('lms/v1', '/edlink/provider/people', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array('Rest_Lxp_Edlink_API', 'get_provider_people_list'),
				'permission_callback' => '__return_true'
			)
		));

		// Get Provider People List
		register_rest_route('lms/v1', '/edlink/provider/people/edit', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array('Rest_Lxp_Edlink_API', 'get_provider_edit_people_list'),
				'permission_callback' => '__return_true'
			)
		));

		// Get Provider People By Role List
		register_rest_route('lms/v1', '/edlink/provider/people-by-role', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array('Rest_Lxp_Edlink_API', 'get_provider_people_list_by_roles'),
				'permission_callback' => '__return_true'
			)
		));

		// Get Provider Class And Sections List
		register_rest_route('lms/v1', '/edlink/provider/class-sections', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array('Rest_Lxp_Edlink_API', 'get_classes_and_sections_list'),
				'permission_callback' => '__return_true'
			)
		));
	}

	public static function get_provider_list($request) {
		$edlink_options = get_option('edlink_options');
		$error = '';
		$data = [];
		if (isset($edlink_options['edlink_application_secrets']) && $edlink_options['edlink_application_secrets'] != '') {
			// Define the API endpoint
			$url = 'https://ed.link/api/v1/integrations';

			// Make the GET request using wp_remote_get
			$response = wp_remote_get($url, [
			    'headers' => [
			        'Authorization' => 'Bearer ' . $edlink_options['edlink_application_secrets']
			    ]
			]);

			// Check for errors
			if (is_wp_error($response)) {
			    //$error = 'Your request has been failed. Error Message: '.$response->get_error_message(). '. Please try again later!';
				$error = 'Failed to fetch data. Please try again!';
			} else {
			    // Retrieve and decode the response body
			    $response_body = wp_remote_retrieve_body($response);
				$data = json_decode($response_body, true);
			}
		} else {
			$error = 'Your Edlink application secret key has not been found!';
		}
		return wp_send_json_success(array('error' => $error, 'providers' => $data));
	}

	public static function get_provider_district_list($request) {
		$access_token = $request->get_param('access_token');
		$error = '';
		$data = [];
		if (isset($access_token) && $access_token != '') {
			// Define the API endpoint
			$url = 'https://ed.link/api/v2/graph/districts';

			// Make the GET request using wp_remote_get
			$response = wp_remote_get($url, [
			    'headers' => [
			        'Authorization' => 'Bearer ' . $access_token
			    ]
			]);

			// Check for errors
			if (is_wp_error($response)) {			    
				$error = 'Failed to fetch data. Please try again!';
			} else {
			    // Retrieve and decode the response body
			    $response_body = wp_remote_retrieve_body($response);
				$data = json_decode($response_body, true);
			}
		} else {
			$error = 'Your Edlink provider access token has not been found!';
		}
		return wp_send_json_success(array('error' => $error, 'edlink_district' => $data));
	}

	public static function get_provider_people_list($request) {
		$access_token = $request->get_param('access_token');
		$api_require = $request->get_param('api_require');
		$provider_name = $request->get_param('provider_name');
		// Define parameters for the people API
		$people_params = array(
            '$filter' => json_encode(array(
                'email' => array(
                    array(
                        'operator' => 'contains',
                        'value' => '@'
                    )
				)
			)),
			'$first' => 10000
        );

        // Build the query string for people API parameters
    	$people_query_string = http_build_query($people_params);
		// Define the API endpoints 
		$endpoints = [
			'people' => 'https://ed.link/api/v2/graph/people?' . $people_query_string,
			$api_require => 'https://ed.link/api/v2/graph/' . $api_require,
		];
		
		// Define headers, e.g., API key or token for authorization
		$headers = [
			'Authorization' => 'Bearer ' . $access_token
		];
	
		// Store request objects for parallel processing
		$requests = [];

		foreach ($endpoints as $key => $url) {
			$requests[$key] = [
				'url' => $url,
				'type' => 'GET',
				'headers' => $headers,
			];
		}
	
		// Use Requests::request_multiple for parallel requests
		$responses = \WpOrg\Requests\Requests::request_multiple($requests);
		$instance = new self();
	
		// Process responses
		$results = [];
		foreach ($responses as $key => $response) {
			if (!is_wp_error($response) && isset($response->status_code) && $response->status_code === 200) {
				$results[$key] = json_decode($response->body, true); // Decode JSON response to an associative array
				if ($key == 'people') {
					// Exclude user from the array, if user email has been matched
					$results[$key] = array_filter($results[$key]['$data'], function ($person) {
						return !get_user_by("email", $person['email']);
					});
				} else if ($key == 'districts') {
					$results[$key] = array_filter($results[$key]['$data'], function ($districts) use ($provider_name, $instance) {
						$districts['name'] = $provider_name . '-' . $districts['name'];
						return !$instance->get_post_by("name", $districts['name'], TL_DISTRICT_CPT);
					});
				} else if ($key == 'schools') {
					$results[$key] = array_filter($results[$key]['$data'], function ($schools) use ($instance) {
						return !$instance->get_post_by("name", $schools['name'], TL_SCHOOL_CPT);
					});
				}

			} else {
				$results[$key] = ['error' => 'Failed to fetch data. Please try agian!'];
			}
		}		
		return $results; 
	}

	public static function get_post_by($field, $value, $post_type) {
		// Define the query arguments
		$args = [
			$field           => $value,
			'post_status' => array( 'publish' ),
			'post_type'      => $post_type,
			'posts_per_page' => 1,
			
		];

		// Run the query
		return get_posts($args);
	}

	public static function get_provider_edit_people_list($request) {
		$access_token = $request->get_param('access_token');
		$user_email = $request->get_param('user_email');
		$role = $request->get_param('role');
		$school_id = $request->get_param('school_id');

		// Define parameters for the people API
		if (isset($role) && $role != '') {
			$people_params = array(
				'$filter' => json_encode(array(
					'email' => array(
						array(
							'operator' => 'contains',
							'value' => '@'
						)
					),
					'roles' => array(
						array(
							'operator' => 'equals',
							'value' => $role
						)
					),
					'school_ids' => array(
						array(
							'operator' => 'equals',
							'value' => $school_id
						)
					)
				)),
				'$first' => 10000
			);
		} else {
			$people_params = array(
				'$filter' => json_encode(array(
					'email' => array(
						array(
							'operator' => 'contains',
							'value' => '@'
						)
					)
				)),
				'$first' => 10000
			);
		}
		
        // Build the query string for people API parameters
    	$people_query_string = http_build_query($people_params);

		// Define the API endpoints for people and district
		$endpoints = [
			'people' => 'https://ed.link/api/v2/graph/people?' . $people_query_string
		];
	
		// Define headers, e.g., API key or token for authorization
		$headers = [
			'Authorization' => 'Bearer ' . $access_token
		];
	
		// Store request objects for parallel processing
		$requests = [];

		foreach ($endpoints as $key => $url) {
			$requests[$key] = [
				'url' => $url,
				'type' => 'GET',
				'headers' => $headers,
			];
		}
	
		// Use Requests::request_multiple for parallel requests
		$responses = \WpOrg\Requests\Requests::request_multiple($requests);
	
		// Process responses
		$results = [];
		foreach ($responses as $key => $response) {
			if (!is_wp_error($response) && isset($response->status_code) && $response->status_code === 200) {
				$results[$key] = json_decode($response->body, true); // Decode JSON response to an associative array
				if ($key == 'people') {
					// Exclude user from the array, if user email has been matched
					$results[$key] = array_filter($results[$key]['$data'], function ($person) use ($user_email) {						
						// Keep the person if their email matches $user_email
						// OR if the email is different and doesn't already exist in WordPress
						return $person['email'] === $user_email || !get_user_by("email", $person['email']);
					});
				}
			} else {
				$results[$key] = ['error' => 'Failed to fetch data. Please try agian!'];
			}
		}
		return $results;
	}

	public static function get_provider_people_list_by_roles($request) {
		$access_token = $request->get_param('access_token');
		$role = $request->get_param('role');
		$school_id = $request->get_param('school_id');
		
		// Define parameters for the people API
		$people_params = array(
            '$filter' => json_encode(array(
                'email' => array(
                    array(
                        'operator' => 'contains',
                        'value' => '@'
                    )
				),
				'roles' => array(
                    array(
                        'operator' => 'equals',
                        'value' => $role
                    )
				),
				'school_ids' => array(
                    array(
                        'operator' => 'equals',
                        'value' => $school_id
                    )
                )
			)),
			'$first' => 10000
        );

        // Build the query string for people API parameters
    	$people_query_string = http_build_query($people_params);
		// Define the API endpoints 
		$endpoints = [
			'people' => 'https://ed.link/api/v2/graph/people?' . $people_query_string
		];
		
		// Define headers, e.g., API key or token for authorization
		$headers = [
			'Authorization' => 'Bearer ' . $access_token
		];
	
		// Store request objects for parallel processing
		$requests = [];

		foreach ($endpoints as $key => $url) {
			$requests[$key] = [
				'url' => $url,
				'type' => 'GET',
				'headers' => $headers,
			];
		}
	
		// Use Requests::request_multiple for parallel requests
		$responses = \WpOrg\Requests\Requests::request_multiple($requests);
	
		// Process responses
		$results = [];
		foreach ($responses as $key => $response) {			
			if (!is_wp_error($response) && isset($response->status_code) && $response->status_code === 200) {
				$results[$key] = json_decode($response->body, true); // Decode JSON response to an associative array
				if ($key == 'people') {
					// Exclude user from the array, if user email has been matched
					$results[$key] = array_filter($results[$key]['$data'], function ($person) {						
						return !get_user_by("email", $person['email']);
					});
				}
			} else {
				$results[$key] = ['error' => 'Failed to fetch data. Please try again!'];
			}
		}
		return $results; 
	}

	public static function get_classes_and_sections_list($request) {
		$access_token = $request->get_param('access_token');
		$api_require = $request->get_param('api_require');
		$school_id = $request->get_param('school_id');

		// Define parameters for the people API
		$params = array(
            '$filter' => json_encode(array(
                'state' => array(
                    array(
                        'operator' => 'equals',
                        'value' => 'active'
                    )
				),
				'school_id' => array(
                    array(
                        'operator' => 'equals',
                        'value' => $school_id
                    )
                )
			)),
			'$first' => 10000
        );

        // Build the query string for people API parameters
    	$query_string = http_build_query($params);
		// Define the API endpoints 
		$endpoints = [
			'class_and_section' => 'https://ed.link/api/v2/graph/'. $api_require .'?' . $query_string
		];
		
		// Define headers, e.g., API key or token for authorization
		$headers = [
			'Authorization' => 'Bearer ' . $access_token
		];
	
		// Store request objects for parallel processing
		$requests = [];

		foreach ($endpoints as $key => $url) {
			$requests[$key] = [
				'url' => $url,
				'type' => 'GET',
				'headers' => $headers,
			];
		}
	
		// Use Requests::request_multiple for parallel requests
		$responses = \WpOrg\Requests\Requests::request_multiple($requests);
		$instance = new self();
		// Process responses
		$results = [];
		foreach ($responses as $key => $response) {			
			if (!is_wp_error($response) && isset($response->status_code) && $response->status_code === 200) {
				$results[$key] = json_decode($response->body, true); // Decode JSON response to an associative array
				$results[$key] = array_filter($results[$key]['$data'], function ($class) use ($instance) {
					return !$instance->get_post_by("name", $class['name'], TL_CLASS_CPT);
				});
			} else {
				$results[$key] = ['error' => 'Failed to fetch data. Please try again!'];
			}
		}
		return $results; 
	}

	public static function exchange_code_for_access_token($request) {
		// Get the authorization code from the query string
		$code = $request->get_param('code');
		echo $code; die;
		$edlink_options = get_option('edlink_options');
		$client_id = $edlink_options['edlink_application_id'];
		$client_secret = $edlink_options['edlink_application_secrets'];
		// Prepare the request data
		$requestData = [
		    'code' => $code,
		    'client_id' => $client_id,
		    'client_secret' => $client_secret,
		    'redirect_uri' => get_site_url().'/wp-json/lms/v1/edlink/authentication/token',
		    'grant_type' => 'authorization_code'
		];
		// Send the requestData to the API
		$response = wp_remote_post('https://ed.link/api/authentication/token', [
		    'method' => 'POST',
		    'body' => http_build_query($requestData),
		    'headers' => [
		        'Content-Type' => 'application/x-www-form-urlencoded',
		    ],
		]);

		// Check for errors
		if (is_wp_error($response)) {
		    error_log($response->get_error_message());
		    echo 'Request failed';
		} else {
		    // Convert the response to JSON
		    $response_body = wp_remote_retrieve_body($response);
		    $data = json_decode($response_body, true);

		    if (is_array($data) && isset($data['$data']['access_token'])) {
		    	// Define the access token
				$user_access_token = $data['$data']['access_token'];

				// Define the API endpoint
				$url = 'https://ed.link/api/v2/my/profile';

				// Make the GET request using wp_remote_get
				$response = wp_remote_get($url, [
				    'headers' => [
				        'Authorization' => 'Bearer ' . $user_access_token
				    ]
				]);

				// Check for errors
				if (is_wp_error($response)) {
				    error_log($response->get_error_message());
				    echo 'Request failed';
				} else {
				    // Retrieve and decode the response body
				    $response_body = wp_remote_retrieve_body($response);
				    $data = json_decode($response_body, true);
				    // Print the response data
				    echo '<pre>';
				    print_r($data);
				    echo '</pre>';
				}
		    } else {
		   		return wp_send_json_error($data['$error']); 	
		    }		    
		}
	}

}

?>