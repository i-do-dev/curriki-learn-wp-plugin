<?php	
	$treks_src = content_url().'/plugins/TinyLxp-wp-plugin/lms/templates/tinyLxpTheme/treks-src/';
	$livePath = dirname( __FILE__ );
	$error = '';		
	// Get the authorization code from the query string
	$edlink_options = get_option('edlink_options');
	if (isset($edlink_options['edlink_application_id']) && $edlink_options['edlink_application_id'] != ''	&& isset($edlink_options['edlink_application_secrets']) && $edlink_options['edlink_application_secrets'] != '' && isset($edlink_options['edlink_sso_enable']) && $edlink_options['edlink_sso_enable'] == 1
	) { 
		$client_id = $edlink_options['edlink_application_id'];
		$client_secret = $edlink_options['edlink_application_secrets'];
		// Prepare the request data
		$requestData = [
		    'code' => $_GET['code'],
		    'client_id' => $client_id,
		    'client_secret' => $client_secret,
		    'redirect_uri' => get_site_url().'/edlink-integration',
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
		    $error = '1-Your request has been failed. Error Message:<br/>&nbsp;&nbsp;'.$response->get_error_message(). '. <br/>Please try again later!';
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

				$response_body = wp_remote_retrieve_body($response);
				$data = json_decode($response_body, true);		    

				// Check for errors
				if (is_wp_error($response)) {
				    $error = '2-Your request has been failed. Error Message:<br/>&nbsp;&nbsp;'.$response->get_error_message(). '. <br/>Please try again later!';
				} else {
				    // Retrieve and decode the response body
				    $response_body = wp_remote_retrieve_body($response);
				    $data = json_decode($response_body, true);
				    $user = get_user_by("email", trim($data['$data']['email']));
					if ( $user ) {
						// Log the user in
						wp_set_current_user($user->ID);  // Set the current user
						wp_set_auth_cookie($user->ID);   // Set the authentication cookies
						
						// Optional: Redirect the user after login
						wp_redirect('dashboard');  // Redirect to the homepage (or any URL you prefer)
						exit;
					}
				}
		    } else {
		    	$error = 'Your request has been failed. Error Message:<br/>&nbsp;&nbsp;'.$data['$error']. '. <br/>Please try again later!';
		    }		    
		}
	} else {
		$error = 'Your Edlink has not been configured properly. Please check Edlink API settings!';
	}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edlink Integration</title>
    <link href="<?php echo $treks_src; ?>/style/main.css" rel="stylesheet" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/header-section.css" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/schoolAdminTeachers.css" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/addNewTeacherModal.css" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/schoolDashboard.css" />
    <link rel="stylesheet" href="<?php echo $treks_src; ?>/style/schoolAdminStudents.css" />
    <link href="<?php echo $treks_src; ?>/style/treksstyle.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous" />
    
    <style type="text/css">
        .heading-wrapper {
            border: 0px solid red;
            height: 115px;
        }

        .heading-left {
            float: left;
        }
        .heading-right {
            padding-top: 70px;
            padding-right: 20px;
            float: right;
        }

        .welcome-content {
            padding: 20px 0;
        }

        .add-teacher-box {
            display: block !important;
        }

        .add-heading {
            margin-top: 20px !important;
        }
    </style>
</head>

<body>

    <!-- Header Section -->
    <nav class="navbar navbar-expand-lg bg-light">
        <div class="container-fluid">
			<?php include $livePath.'/trek/header-logo.php'; ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
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
                        <?php 
                        	//get_template_part('trek/user-profile-block'); 
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Nav Section -->
    <section class="main-container">
        <nav class="nav-section">
            <?php 
            	//get_template_part('lxp/admin-nav'); 
            ?>
        </nav>
    </section>

    <!-- Welcome: section-->
    <section class="welcome-section">
        <!-- Welcome: heading-->
        <div class="heading-wrapper">
            <div class="heading-left">
                <div class="welcome-content">
                	<!-- <h2 class="welcome-heading">In-Progress Edlink Integration</h2> -->
                	<p class="welcome-text">
                		<?php 
	                		if (isset($error) && $error != '') {
	                			echo '<br/><h4 style="color:red;">'.$error.'</h4>';
	                		} else {
	                			echo '<br/><h4 style="color:red;">Sorry No Record Found. Please contact TinyLxp Admin!</h4>';
	                		}
	                	?>
                	</p>
                </div>
            </div>
        </div>
        <?php
        	// if ($error == '') {
        ?>
	        <!-- Total Schools: section-->
	        <section class="school-section">
	            <section class="school_teacher_cards">
	                <!-- Table Section -->
	                <section class="recent-treks-section-div table-school-section">
	                    <!-- bootstrap Active and Inactive tabs -->
	                    <div class="students-table">
	                        <!-- <table class="table">
	                            <thead>
	                                <tr>
	                                    <th class="">
	                                        <div class="th1">
	                                            First Name
	                                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
	                                        </div>
	                                    </th>
	                                    <th>
	                                        <div class="th1 th2">
	                                            Last Name
	                                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
	                                        </div>
	                                    </th>
	                                    <th>
	                                        <div class="th1 th3">
	                                            Display Name
	                                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
	                                        </div>
	                                    </th>
	                                    <th>
	                                        <div class="th1 th3">
	                                            Email
	                                            <img src="<?php echo $treks_src; ?>/assets/img/showing.svg" alt="logo" />
	                                        </div>
	                                    </th>
	                                </tr>
	                            </thead>
	                            <tbody>
	                                
	                                    <tr>
	                                        <td class="user-box">
	                                            <div class="table-user">
	                                                <img src="<?php echo $treks_src; ?>/assets/img/profile-icon.png" alt="student" />
	                                                <div class="user-about">
	                                                    <h5>
	                                                    	<?php 
	                                                    		echo $data['$data']['first_name']; 
	                                                    	?>
	                                                    </h5>
	                                                </div>
	                                            </div>
	                                        </td>
	                                        <td>
	                                            <div class="table-status">
	                                            	<h5>
	                                                	<?php 
	                                                		echo $data['$data']['last_name']; 
	                                                	?>
	                                                </h5>
	                                            </div>
	                                        </td>
	                                        <td>
	                                            <h5>
	                                            	<?php 
	                                            		echo $data['$data']['display_name']; 
	                                            	?>
	                                            </h5>
	                                        </td>
	                                        <td>
	                                            <h5>
	                                            	<?php 
	                                            		echo $data['$data']['email']; 
	                                            	?>
	                                            </h5>
	                                        </td>
	                                    </tr>
	    
	                            </tbody>
	                        </table> -->
	                    </div>
	                </section>
	            </section>
	        </section>
    	<?php 
    		// }
    	?>
    </section>

    
    
    
</body>

</html>