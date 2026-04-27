<?php
$livePath = dirname( __FILE__ );
$treks_src = content_url().'/plugins/TinyLxp-wp-plugin/lms/templates/tinyLxpTheme/treks-src/';
// Get the Edlink API Settings
$edlink_options = get_option('edlink_options');
$edlink_sso_link = '';
if (isset($edlink_options['edlink_application_id']) && $edlink_options['edlink_application_id'] != '' && isset($edlink_options['edlink_application_secrets']) && $edlink_options['edlink_application_secrets'] != '' && isset($edlink_options['edlink_sso_enable']) && $edlink_options['edlink_sso_enable'] == 1
  ) {
      $edlink_sso_link = 'https://ed.link/sso/login?client_id='.$edlink_options['edlink_application_id'].'&redirect_uri='.site_url().'/edlink-integration'.'&response_type=code';
}
while (have_posts()) : the_post();
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

    <style type="text/css">
      .treks-card {
        width: 300px !important;
      }
      .treks-card-link {
        text-decoration: none !important;
      }
      .lxp-login-container {
        width: 100%;
      }
      .recent-treks-section-div .recent-treks-cards-list {
        justify-content: center;
      }
      .login-submit input#wp-submit {
        padding: 6px 9px 6px 8px;
      }
      .custom-login-button {
          background-color: #0073aa;
          color: white;
          padding: 8px 12px;
          border-radius: 5px;
          text-decoration: none;
          margin-left: 10px;
      }

      .custom-login-button:hover {
          background-color: #005a8c;
          color: white;
      }
      #loginform{
        margin-top: 20px;
      }

      .edlink-container {
            text-align: center;
            background: white;
            padding: 20px;
            border-radius: 10px;
            /*box-shadow: 0px 1px 0px rgba(0, 0, 0, 0.1);*/
            width: 90%;
            max-width: 31%;
            margin: 0% 0% -4% 37%;
        }
        .edlink-btn a {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            border: 1px solid;
            text-decoration: none;
            font-size: 16px;
            background: #fff;
            border-radius: 5px;
        }
        .edlink-btn img {
            height: 20px;
            margin-right: 8px;
        }
        .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }
        .divider::before,
        .divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #000;
        }
        .divider:not(:empty)::before {
            margin-right: 10px;
        }
        .divider:not(:empty)::after {
            margin-left: 10px;
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
            <!-- <div class="header-search">
              <img src="<?php //echo $treks_src; ?>/assets/img/header_search.svg" alt="svg" />
              <input placeholder="Search" />
            </div> -->
          </div>
        </div>
        <div class="d-flex" role="search">
          <div class="header-notification-user">
            <?php //include $livePath.'/trek/user-profile-block.php'; ?>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <!-- Basic Container -->
  <section class="main-container">
    
    <!-- Recent TREKs -->
    <section class="recent-treks-section">
      <div class="recent-treks-section-div">
        <!--  TREKs header-->
        <div class="recent-treks-header section-div-header">
          <h2>Sign in to TinyLXP</h2>
          
        </div>
        <div class="lxp-login-container">
          <!-- TREKs cards -->
          <?php    
            if (!is_user_logged_in() && isset($edlink_sso_link) && $edlink_sso_link != '') { 
          ?>
          <div class="edlink-container">
              <div class="edlink-btn">
                  <a href="<?php echo $edlink_sso_link ?>" class="btn btn-lg">
                      <img src="<?php echo $treks_src; ?>/assets/img/edlink_img.png" alt="Edlink Logo" />
                      Sign In With Edlink
                  </a>
              </div>
              <div class="divider">OR</div>
          </div>
          <?php
            }
          ?>
          <div class="recent-treks-cards-list">
            
            <?php   if (is_user_logged_in()) { ?>
              You are already logged in
              <a class="btn btn-xs btn-secondary" href="<?php echo wp_logout_url("login"); ?>">Logout</a>
            <?php   } ?>

            <?php
              if (!is_user_logged_in()) {
                $redirect_to = isset($_GET['redirect']) ? urldecode($_GET['redirect']) : site_url("/dashboard/");
                $args = array(
                  'echo' => true,
                  'label_log_in' => 'Sign In With Email', // Change the button text
                  'label_username' => 'Email &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
                  'redirect' => $redirect_to
                );
                wp_login_form($args); 
              }
            ?>
          </div>
        </div>
      </div>
    </section>
  </section>

  <script src="https://code.jquery.com/jquery-3.6.3.js" integrity="sha256-nQLuAZGRRcILA+6dMBOvcRh5Pe310sBpanc6+QBmyVM="
    crossorigin="anonymous"></script>
  <script src="<?php echo $treks_src; ?>/js/Animated-Circular-Progress-Bar-with-jQuery-Canvas-Circle-Progress/dist/circle-progress.js"></script>
  <script src="<?php echo $treks_src; ?>/js/custom.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4"
    crossorigin="anonymous"></script>
</body>

</html>
<?php endwhile; ?>