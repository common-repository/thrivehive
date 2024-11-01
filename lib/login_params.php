<?php

// Allow login using params
// See https://wordpress.stackexchange.com/questions/32209/login-to-wordpress-with-get-variables-instead-of-post

add_action('init', 'Allow_Login_Via_Params');

function Allow_Login_Via_Params() {
  //Check that we are on the log-in page
  if(in_array($GLOBALS['pagenow'], array('wp-login.php'))):

    //Check that log and pwd are set
    if(isset($_GET['username']) && isset($_GET['password'])):
      $creds = array();
      $creds['user_login'] = $_GET['username'];
      $creds['user_password'] = $_GET['password'];
      $creds['remember'] = true; //Do you want the log-in details to be remembered?

      //Where do we go after log-in?
      $redirect_to = home_url();

      //Try logging in
      $user = wp_signon( $creds, false );

      if ( is_wp_error($user) ){
        //Log-in failed
      } else {
        //Logged in, now redirect
        $redirect_to = home_url();
        wp_safe_redirect($redirect_to);
        exit();
      }
    endif;
  endif;
  //If we are not on the log-in page or credentials are not set, carry on as normal
}

?>
