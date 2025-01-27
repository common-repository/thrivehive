<?php

   /**
   *Plugin Name: ThriveHive
   *Plugin URI: http://thrivehive.com
   *Description: A plugin to include ThriveHive's tracking code
   *Version: 2.602
   *Author: ThriveHive
   *Author URI: http://thrivehive.com
   */

/* hack in a cover image instead of a static, single bg image */
/* if it's set to static, overwrite and set to cover */

define( 'TH_PLUGIN_ROOT', dirname(__FILE__) );
define( 'TH_WYSIWYG_THEME_NAME', 'Changeling' );
define( 'CHANGELING_DISPLAY_HERO_SETTING', 'changeling_display_hero_setting' );

@include_once TH_PLUGIN_ROOT . "/singletons/api.php";
@include_once TH_PLUGIN_ROOT . "/singletons/query.php";
@include_once TH_PLUGIN_ROOT . "/singletons/introspector.php";
@include_once TH_PLUGIN_ROOT . "/singletons/response.php";
@include_once TH_PLUGIN_ROOT . "/models/post.php";
@include_once TH_PLUGIN_ROOT . "/models/comment.php";
@include_once TH_PLUGIN_ROOT . "/models/category.php";
@include_once TH_PLUGIN_ROOT . "/models/tag.php";
@include_once TH_PLUGIN_ROOT . "/models/author.php";
@include_once TH_PLUGIN_ROOT . "/models/attachment.php";
@include_once TH_PLUGIN_ROOT . "/lib/thrivehive_buttons.php";
@include_once TH_PLUGIN_ROOT . "/lib/thrivehive_wysiwyg_buttons.php";
@include_once TH_PLUGIN_ROOT . "/lib/thrivehive_forms.php";
@include_once TH_PLUGIN_ROOT . "/lib/thrivehive_theme_options.php";
@include_once TH_PLUGIN_ROOT . "/lib/thrivehive_snippets.php";
@include_once TH_PLUGIN_ROOT . "/lib/snippet_display.php";
@include_once TH_PLUGIN_ROOT . "/lib/login_params.php";
@include_once TH_PLUGIN_ROOT . "/lib/duplicate_post.php";
@include_once TH_PLUGIN_ROOT . "/templates/form_generator.php";

add_action('init', 'version_check');

function version_check(){
  //UPDATE THIS WHEN WE MAKE VERSION CHANGES
  $db_version = '1.120';
  $update = null;

  $ver = get_option('thrivehive_vers');
  if($ver != $db_version){
    $update = $db_version;
  }

  if(!$ver || $update){
    //update_option('thrivehive_vers', $db_version);
    thrivehive_create_button_db($update);
    thrivehive_create_wysiwyg_button_db($update);
    thrivehive_create_theme_options_table($update);
    thrivehive_create_forms_db($update);
    thrivehive_create_snippets_db($update);
  }
}

add_filter('body_class', 'bg_repeat_body_class');

function bg_repeat_body_class($classes) {
    $bg_repeat = get_theme_mod('background_repeat');

    if ($bg_repeat === 'no-repeat' || $bg_repeat === 'False') {
    array_push($classes, 'use-cover-image-for-bg');
  }

  return $classes;
}

$has_th_environment = get_option('th_environment') ? true : false;
if ($has_th_environment && !(is_thrivehive_wysiwyg())) {
  add_action( 'wp_enqueue_scripts', 'DEPRECATED_thrivehive_enqueue_assets' );
}

// These should only be included if we are not using the WYSIWYG theme (Changeling)
function DEPRECATED_thrivehive_enqueue_assets(){
  wp_enqueue_style( 'custom-style', plugins_url('css/custom_style.css', __FILE__, false, 'v1') );
  wp_register_style( 'thrivehive-grid', plugins_url('css/minimal_foundation_grid.min.css', __FILE__), null, '1' );
  wp_enqueue_style( 'thrivehive-grid' );
  wp_enqueue_style( 'prefix-font-awesome', plugins_url("css/fontawesome/css/font-awesome.min.css", __FILE__), array(), null);
}

add_action('changeling_before_css_include', 'add_css_start_delimiter');
function add_css_start_delimiter() {
  echo "<!-- content:style -->" . PHP_EOL;
}

add_action('changeling_after_css_include', 'add_css_end_delimiter');
function add_css_end_delimiter() {
  echo "<!-- /content:style -->" . PHP_EOL;
}

// create menu
add_action('admin_menu', 'thrivehive_create_menu');
// Block direct requests
if ( !defined('ABSPATH') )
  die('-1');


// add page template plugin: https://github.com/tommcfarlin/page-template-example
require_once( plugin_dir_path( __FILE__ ) . 'class-page-template-example.php' );
add_action( 'plugins_loaded', array( 'Page_Template_Plugin', 'get_instance' ) );

/**
*Registers a widget to hold the logo, and another for the button
**/
function register_th_widgets(){
  register_widget('ThriveHiveLogo');
  register_widget('ThriveHiveButton');
  register_widget('ThriveHiveWysiwygButton');
  register_widget('ThriveHiveSocialButtons');
  register_widget('ThriveHivePhone');
}

add_action( 'widgets_init', 'register_th_widgets');


/**
*Creates the settings menu for the ThriveHive plugin
**/
function thrivehive_create_menu() {

  //create new top-level menu
  add_menu_page('ThriveHive Plugin Settings', 'ThriveHive', 'administrator', __FILE__, 'thrivehive_settings_page',plugins_url('/images/icon.png', __FILE__, '999'));

  //call register settings function
  add_action( 'admin_init', 'register_thrivehive_settings' );

}

/**
*Adds all of the setting values for the Thrivehive settings menu
**/
function register_thrivehive_settings() {
  global $pagenow;
  //register settings
  register_setting( 'thrivehive-settings-group', 'th_tracking_code' );
  register_setting( 'thrivehive-settings-group', 'th_phone_number' );
  register_setting( 'thrivehive-settings-group', 'th_form_html' );
  register_setting( 'thrivehive-settings-group', 'th_javascript_header');
  register_setting( 'thrivehive-settings-group', 'th_javascript');
  register_setting( 'thrivehive-settings-group', 'th_header_html');
  register_setting( 'thrivehive-settings-group', 'th_css');
  register_setting( 'thrivehive-settings-group', 'th_landingform_id' );
  register_setting( 'thrivehive-settings-group', 'th_contactform_id' );
  register_setting( 'thrivehive-settings-group', 'th_company_address');

  register_setting( 'thrivehive-settings-group', 'th_landingform_showfields' );
  register_setting( 'thrivehive-settings-group', 'th_site_logo');
  register_setting( 'thrivehive-settings-group', 'th_facebook');
  register_setting( 'thrivehive-settings-group', 'th_twitter');
  register_setting( 'thrivehive-settings-group', 'th_linkedin');
  register_setting( 'thrivehive-settings-group', 'th_yelp');
  register_setting( 'thrivehive-settings-group', 'th_googleplus');
  register_setting( 'thrivehive-settings-group', 'th_instagram');
  register_setting( 'thrivehive-settings-group', 'th_youtube');
  register_setting( 'thrivehive-settings-group', 'th_houzz');
  register_setting( 'thrivehive-settings-group', 'th_angieslist');
  register_setting( 'thrivehive-settings-group', 'th_pinterest');
  register_setting( 'thrivehive-settings-group', 'th_foursquare');
  register_setting( 'thrivehive-settings-group', 'th_tripadvisor');

  register_setting( 'thrivehive-settings-group', 'th_social_blogroll');
  register_setting( 'thrivehive-settings-group', 'th_social_blog');
  register_setting( 'thrivehive-settings-group', 'th_social_sidebar');

  add_settings_field('th_setting_logo', __('Logo', 'th'), 'th_setting_logo', $pagenow);

  th_settings_setup();

  th_redirect();
}

function th_setting_logo(){
  ?>
    <input id="upload_logo_button" type="button" class="button" value="<?php _e('Upload Logo', 'th');?>"/>
  <?php
}

function th_settings_enqueue_scripts(){
  wp_register_script('th-options', plugins_url('resources/js/th-options.js', __FILE__),plugins_url('resources/js/image-widget.js', __FILE__), array('jquery', 'media-upload', 'thickbox'));

  if(strpos(get_current_screen()->id, '/thrivehive') != ''){
    wp_enqueue_script('thickbox');
    wp_enqueue_style('thickbox');
    wp_enqueue_script('th-options');
  }
}

add_action('admin_enqueue_scripts', 'th_settings_enqueue_scripts');

/**
*Setup for media upload handling
**/
function th_settings_setup(){
  global $pagenow;
  if('media-upload.php' == $pagenow || 'async-upload.php' == $pagenow){
    add_filter('gettext', 'replace_thickbox_text', 1, 2);
  }
}
function replace_thickbox_text($translated_text, $text){
  if('Insert into Post' == $text){
    $referer = strpos(wp_get_referer(), 'th-settings');
    if($referer != ''){
      return __('Set Logo', 'th');
    }
  }
  return $translated_text;
}



/**
*Sets up the html form for the ThriveHive settings page
**/
function thrivehive_settings_page() {
?>

<div class="wrap">
<h2>ThriveHive Settings</h2>
<p>Please fill out the following information to set up your site with basic tracking assets.</p>

<form method="post" action="options.php">
    <?php settings_fields( 'thrivehive-settings-group' ); ?>
    <?php do_settings_fields( 'thrivehive-settings-group', 'thrivehive-settings-group' ); ?>

    <table class="form-table">
        <tr valign="top">
      <th scope="row">ThriveHive Account ID</th>
      <td>
        <input type="text" name="th_tracking_code" value="<?php echo get_option('th_tracking_code'); ?>" />
      </td>
        </tr>
        <tr valign="top">
      <th scope="row">ThriveHive Phone Number</th>
      <td>
        <input type="text" name="th_phone_number" value="<?php echo get_option('th_phone_number'); ?>" />
      </td>
      </tr>
        <tr valign="top">
        <tr valign="top">
      <th scope="row">ThriveHive Company Address</th>
      <td>
        <textarea rows="4" cols="75" name="th_company_address" /><?php echo get_option('th_company_address'); ?>  </textarea>
      </td>
    </tr>
        <tr valign="top">
        <th scope="row">ThriveHive Contact Us Form HTML</th>
        <td>
      <textarea rows="15" cols="100" name="th_form_html" /><?php echo htmlentities(get_option('th_form_html')); ?></textarea>
    </td>
        </tr>
    <tr valign="top">
      <th scope="row">ThriveHive Custom Javascript (Header)</th>
      <td>
        <textarea rows="15" cols="100" name="th_javascript_header" /><?php echo htmlentities(get_option('th_javascript_header')); ?></textarea>
      </td>
    </tr>
        <tr valign="top">
          <th scope="row">ThriveHive Custom Javascript (Footer)</th>
          <td>
        <textarea rows="15" cols="100" name="th_javascript" /><?php echo htmlentities(get_option('th_javascript')); ?></textarea>
      </td>
    </tr>
    <tr valign="top">
      <th scope="row">ThriveHive Custom Header HTML</th>
      <td>
        <textarea rows="15" cols="100" name="th_header_html" /><?php echo htmlentities(get_option('th_header_html')); ?></textarea>
      </td>
    </tr>

         <tr valign="top">
          <th scope="row">ThriveHive Custom CSS</th>
          <td>
        <textarea rows="15" cols="100" name="th_css" /><?php echo htmlentities(get_option('th_css')); ?></textarea>
      </td>
        </tr>
    <tr valign="top">
      <th scope="row">ThriveHive Contact Page Form ID</th>
      <td>
        <input type="text" name="th_contactform_id" value="<?php echo get_option('th_contactform_id'); ?>" />
      </td>
    </tr>
    <tr valign="top">
      <th scope="row">ThriveHive Landing Page Form ID</th>
      <td>
        <input type="text" name="th_landingform_id" value="<?php echo get_option('th_landingform_id'); ?>" />
      </td>
    </tr>
    <tr valign="top">
      <th scope="row">ThriveHive Facebook Username/Userid</th>
      <td>
        <input type="text" name="th_facebook" value="<?php echo get_option('th_facebook'); ?>" />
      </td>
    </tr>
    <tr valign="top">
      <th scope="row">ThriveHive twitter Username/Userid</th>
      <td>
        <input type="text" name="th_twitter" value="<?php echo get_option('th_twitter'); ?>" />
      </td>
    </tr>
    <tr valign="top">
      <th scope="row">ThriveHive LinkedIn Page URL</th>
      <td>
        <input type="text" name="th_linkedin" value="<?php echo get_option('th_linkedin'); ?>" />
      </td>
    </tr>
    <tr valign="top">
      <th scope="row">ThriveHive Yelp Username/Userid</th>
      <td>
        <input type="text" name="th_yelp" value="<?php echo get_option('th_yelp'); ?>" />
      </td>
    </tr>
    <tr valign="top">
      <th scope="row">ThriveHive Google Plus Page URL</th>
      <td>
        <input type="text" name="th_googleplus" value="<?php echo get_option('th_googleplus'); ?>" />
      </td>
    </tr>
    <tr valign="top">
      <th scope="row">ThriveHive Instagram Username/Userid</th>
      <td>
        <input type="text" name="th_instagram" value="<?php echo get_option('th_instagram'); ?>" />
      </td>
    </tr>
    <tr valign="top">
      <th scope="row">ThriveHive YouTube Username/Userid</th>
      <td>
        <input type="text" name="th_youtube" value="<?php echo get_option('th_youtube'); ?>" />
      </td>
    </tr>
    <tr valign="top">
      <th scope="row">ThriveHive Houzz Page URL</th>
      <td>
        <input type="text" name="th_houzz" value="<?php echo get_option('th_houzz'); ?>" />
      </td>
    </tr>
    <tr valign="top">
      <th scope="row">ThriveHive Angie's List Public URL</th>
      <td>
        <input type="text" name="th_angieslist" value="<?php echo get_option('th_angieslist'); ?>" />
      </td>
    </tr>
    <tr valign="top">
      <th scope="row">ThriveHive Pinterest Username/Userid</th>
      <td>
        <input type="text" name="th_pinterest" value="<?php echo get_option('th_pinterest'); ?>" />
      </td>
    </tr>
    <tr valign="top">
      <th scope="row">ThriveHive Foursquare Page URL</th>
      <td>
        <input type="text" name="th_foursquare" value="<?php echo get_option('th_foursquare'); ?>" />
      </td>
    </tr>
        <tr valign="top">
            <th scope="row">ThriveHive TripAdvisor Page URL</th>
            <td>
                <input type="text" name="th_tripadvisor" value="<?php echo get_option('th_tripadvisor'); ?>" />
            </td>
        </tr>
    <tr valign="top">
      <th scope="row">Show Social Buttons on Blogroll</th>
      <td>
        <input type="checkbox" value='True' name="th_social_blogroll" <?php if (get_option('th_social_blogroll') && get_option('th_social_blogroll') == "True"){?>checked<?php } ?> />
      </td>
    </tr>
    <tr valign="top">
      <th scope="row">Show Social Buttons on Blog Entry</th>
      <td>
        <input type="checkbox" value='True' name="th_social_blog" <?php if (get_option('th_social_blog') && get_option('th_social_blog') == "True"){?>checked<?php } ?> />
      </td>
    </tr>
    <tr valign="top">
      <th scope="row">Show Social Buttons on Sidebar</th>
      <td>
        <input type="checkbox" value='True' name="th_social_sidebar" <?php if (get_option('th_social_sidebar') && get_option('th_social_sidebar') == "True"){?>checked<?php } ?> />
      </td>
    </tr>
    <tr valign="top">
      <th scope="row">Page Logo Url</th>
      <td>
        <input type="text" name="th_site_logo" id='site_logo' size=100 value="<?php echo get_option('th_site_logo'); ?>" />
      </td>
    </tr>
    <tr valign="top">
      <th scope="row"></th>
      <td>
        <input id="upload_logo_button" type="button" class="button" value="<?php _e('Upload Logo', 'th');?>"/>
      </td>
    </tr>
    <tr valign="top">
      <th scope="row">Preview </th>
      <td>
        <img src="<?php echo get_option('th_site_logo'); ?>" alt="Logo" style="max-width: 300px" />
      </td>
    </tr>

        <!--<tr valign="top">
      <td>
        <div class="checkboxes">
          <input type="checkbox" name="th_landingform_showfields[0]" value="<?php echo get_option('th_landingform_showfields'); ?>" />
          <label>First Name</label>
        </div>
      </td>
    </tr>-->
    </table>
    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>
</form>
</div>

<?php } ?>
<?php

//shortcodes
//[th_form]
add_shortcode( 'th_form', 'th_display_form' );

//[th_phone]
add_shortcode( 'th_phone', 'th_display_phone' );

//[th_button]
add_shortcode( 'th_button', 'th_display_button' );

//[th_button]
add_shortcode( 'th_wysiwyg_button', 'th_display_wysiwyg_button' );

//[th_address]
add_shortcode( 'th_address', 'th_display_address');

//[th_map]
add_shortcode( 'th_map', 'th_map');

//[th_gallery]
add_shortcode( 'th_gallery', 'th_display_gallery');

//[th_youtube]
add_shortcode( 'th_youtube', 'th_display_youtube' );

//[th_pdf]
add_shortcode('th_pdf', 'th_display_pdf');

//[th_snippet]
add_shortcode('th_snippet', 'th_display_snippet');

//instrument site
function thrivehive_instrumentation() {
    $account_id = get_option('th_tracking_code');
    $env = get_option('th_environment');
    if($env == false){
      $env = "my.thrivehive.com";
    }
    if(isset($account_id) && !empty($account_id)){
  echo <<<END
  <script type="text/javascript">
    var scripturl = (("https:" == document.location.protocol) ? "https://" : "http://") + "$env/content/WebTrack/catracker.js";
    document.write(unescape("%3Cscript src='" + scripturl + "' type='text/javascript'%3E%3C/script%3E"));
  </script>
  <script type="text/javascript">
    try {
    var cat = new CATracker("$account_id");
    cat.Pause = true; cat.TrackOutboundLinks(); cat.PageView();
    } catch (err) {document.write("There has been an error initializing web tracking.");}
  </script>
  <noscript><img src='http://$env?noscript=1&aweid=$account_id&action=PageView'/></noscript>
END;
}

}

function add_theme_name_as_body_class() {
  $theme = get_stylesheet_directory();
  $theme_name = basename($theme);
  $class_name = 'theme-name-' . $theme_name;
  echo "<script type='text/javascript'>document.body.className += ' $class_name'</script>";
}

function thrivehive_custom_javascript_header(){
  $js = get_option('th_javascript_header');
  if($js){
    echo "<script type='text/javascript'>$js</script>";
  }
}

function thrivehive_custom_javascript(){
  $js = get_option('th_javascript');
  if($js){
    echo "<script type='text/javascript'>$js</script>";
  }
}

function thrivehive_header_html(){
  $html = get_option('th_header_html');
  if($html){
    echo "$html";
  }
}

function thrivehive_custom_css(){
  $css = get_option('th_css');
  if($css){
    echo "<style type='text/css'>$css</style>";
  }
}

function create_option_css(){
  $theme = basename(get_stylesheet_directory());
  $theme_options = get_theme_options_by_name($theme);
  $options = unserialize($theme_options['options']);
  if(!$theme_options){
    return '';
  }
  $css = "";
  $imports = "";
  foreach ($options as $opt) {
    if(isset($opt["Import"])){
      $imports .= "@import url(".$opt['Import']."); \n";
    }
    $name = $opt['Option'];
    $selector = $opt['Selector'];
    $value = $opt['Value'];
    $value_type = $opt['Type'];
    $css .= "
        /* $name */
        $selector {
          $value_type:$value;
        }
        ";
  }
  echo "<style type='text/css'>$css</style>";
}
register_activation_hook(__FILE__, 'th_activate');
register_activation_hook(__FILE__, 'th_permalinks');
register_activation_hook(__FILE__, 'thrivehive_create_button_db');
register_activation_hook(__FILE__, 'thrivehive_create_wysiwyg_button_db');
register_activation_hook(__FILE__, 'thrivehive_create_theme_options_table');
register_activation_hook(__FILE__, 'thrivehive_create_forms_db');
register_activation_hook(__FILE__, 'thrivehive_create_snippets_db');



function th_activate() {
    global $wp_rewrite;
    add_option('thrivehive_do_activation_redirect', true);

    //if(version_compare(PHP_VERSION, '5.4', '<'))
  //{
  //	deactivate_plugins(basename(__FILE__));

  //	wp_die('<p>The <strong>Thrivehive</strong> plugin requires PHP version 5.4 or greater. </br> For help please contact <strong><a href="mailto:support@thrivehive.com">support@thrivehive.com</a></strong></p>',
  //			'Plugin Activation Error',  array( 'response'=>200, 'back_link'=>TRUE ) );
  //}
    #add_filter('rewrite_rules_array', 'json_api_rewrites');

    #flush_rewrite_rules();
    #$wp_rewrite->generate_rewrite_rules();
    //add_option('thrivehive_do_activation_validation', true);
}
/**
*Sets up the permalink settins needed for the JSON api to function
**/
function th_permalinks(){
  global $wp_rewrite;
  $home_path = get_home_path();
  if(! file_exists($home_path . '.htaccess')){
    file_put_contents($home_path . '.htaccess',
      "
      #BEGIN WordPress
      <IfModule mod_rewrite.c>
      RewriteEngine On
      RewriteBase /
      RewriteRule ^index\.php$ - [L]
      RewriteCond %{REQUEST_FILENAME} !-f
      RewriteCond %{REQUEST_FILENAME} !-d
      RewriteRule . /index.php [L]
      </IfModule>
      #END WordPress
      ");
  }
  $perma = $wp_rewrite->permalink_structure;
    if($perma == ""){
    $wp_rewrite->set_permalink_structure('/%year%/%monthnum%/%day%/%postname%/');
  }
  else{
    $wp_rewrite->set_permalink_structure($perma);
  }
    add_filter('rewrite_rules_array', 'json_api_rewrites');
    $wp_rewrite->flush_rules();
}
function th_file_get_contents_curl($url) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
      curl_setopt($ch, CURLOPT_URL, $url);
      $data = curl_exec($ch);
      curl_close($ch);
      return $data;
}
/**
*Should execute a redirect to the thrivehive settings page upon activation
**/
function th_redirect() {
  if(get_option('thrivehive_do_activation_validation', false)){
    delete_option('thrivehive_do_activation_validation');
    $url = get_bloginfo('url').'/api';
    if(($html = th_file_get_contents_curl($url))){
      $json = json_decode($html);
    }


    if(!$json){
      $plugin = plugin_basename(__FILE__);
      if(is_plugin_active($plugin)){
        $html = '<div class="error">';
            $html .= '<p>';
             $html .= __( 'There was a problem activating the ThriveHive plugin. <br>
              This is usually caused by a redirect set up in the WordPress .htaccess file or a similar plugin.' );
             $html .= __( '<br> Please try to remedy this to ensure that the plugin works smoothly' );
            $html .= '</p>';
            $html .= '</div><!-- /.updated -->';
            echo $html;
            deactivate_plugins($plugin);
            return;
      }
    }

  }
    if (get_option('thrivehive_do_activation_redirect', false)) {
        delete_option('thrivehive_do_activation_redirect');
        if(defined(__DIR__)){
          wp_redirect(admin_url().'admin.php?page='.__DIR__.'/thrivehive.php');
      }
    }
}

function suppress_title( $title, $id = null ) {

  $meta = get_post_meta($id, "th_show_title", true);
  if($meta == "hide"){
    return "";
    }
    else{
      return $title;
  }
}
add_filter( 'the_title', 'suppress_title', 10, 2 );


// footer and header hooks
add_action('wp_footer', 'thrivehive_instrumentation');
add_action('wp_footer', 'thrivehive_custom_javascript');
add_action('wp_footer', 'add_theme_name_as_body_class');
add_action('wp_head', 'create_option_css');
add_action('wp_head','thrivehive_custom_css');
add_action('wp_head', 'thrivehive_header_html');
add_action('wp_head', 'thrivehive_custom_javascript_header');

// admin messages hook!
add_action('admin_notices', 'thrivehive_admin_msgs');



?>
<?php


 /**
 * Helper function for creating admin messages
 * src: http://www.wprecipes.com/how-to-show-an-urgent-message-in-the-wordpress-admin-area
 * found at: http://wp.tutsplus.com/tutorials/using-the-settings-api-part-1-create-a-theme-options-page/
 *
 * @param (string) $message The message to echo
 * @param (string) $msgclass The message class
 * @return echoes the message
 */

  function thrivehive_show_msg($message, $msgclass = 'info') {
  echo "<div id='message' class='$msgclass'>$message</div>";

}



 /**
 * Callback function for displaying admin messages
 *
 * @return calls thrivehive_show_msg()
 */

function thrivehive_admin_msgs() {

  // check for our settings page - need this in conditional further down
  if(isset($_GET["page"])){
    $thrivehive_settings_pg = strpos($_GET['page'], "thrivehive");
  }
  else{
    $thrivehive_settings_pg = FALSE;
  }
  // collect setting errors/notices: //http://codex.wordpress.org/Function_Reference/get_settings_errors
  $set_errors = get_settings_errors();

  //display admin message only for the admin to see, only on our settings page and only when setting errors/notices are returned!
  if(current_user_can ('manage_options') && $thrivehive_settings_pg !== FALSE && !empty($set_errors)){

    // have our settings succesfully been updated?
    if($set_errors[0]['code'] == 'settings_updated' && isset($_GET['settings-updated'])){
      thrivehive_show_msg("<p>" . $set_errors[0]['message'] . "</p>", 'updated');

    // have errors been found?
    }else{
      // there maybe more than one so run a foreach loop.
      foreach($set_errors as $set_error){
        // set the title attribute to match the error "setting title"
        thrivehive_show_msg("<p class='setting-error-message' title='" . $set_error['setting'] . "'>" . $set_error['message'] . "</p>", 'error');
      }
    }
  }
}

/**
*Sets up the database for the thrivehive buttons
**/
function thrivehive_create_button_db($version=null) {
  global $wpdb;
  $table_name = $wpdb->prefix . "TH_" . "buttons";
  $sql = "CREATE TABLE " . $table_name . " (
      id INT NOT NULL AUTO_INCREMENT,
      text VARCHAR(100) NULL,
      norm_gradient1 VARCHAR(10) NULL,
      norm_gradient2 VARCHAR(10) NULL,
      hover_gradient1 VARCHAR(10) NULL,
      hover_gradient2 VARCHAR(10) NULL,
      norm_border_color VARCHAR(10) NULL,
      hover_border_color VARCHAR(10) NULL,
      norm_text_color VARCHAR(10) NULL,
      hover_text_color VARCHAR(10) NULL,
      generated_css TEXT NULL,
      url TEXT NULL,
      target VARCHAR(10) NULL,
      PRIMARY KEY  (id)
      );";
  if(!thrivehive_table_exists($table_name) || $version) {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    update_option('thrivehive_vers', $version);
    dbDelta($sql);
  }

}
/**
*Sets up the database for the wysiwyg buttons
**/
function thrivehive_create_wysiwyg_button_db($version=null) {
  global $wpdb;
  $table_name = $wpdb->prefix . "TH_" . "wysiwyg_buttons";
  $sql = "CREATE TABLE " . $table_name . " (
      id INT NOT NULL AUTO_INCREMENT,
      text VARCHAR(100) NULL,
      url TEXT NULL,
      target VARCHAR(10) NULL,
      classes TEXT NULL,
      PRIMARY KEY  (id)
      );";
  if(!thrivehive_table_exists($table_name) || $version) {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    update_option('thrivehive_vers', $version);
    dbDelta($sql);
  }
}

function thrivehive_create_forms_db($version=null) {
  global $wpdb;
  $table_name = $wpdb->prefix . "TH_" . "forms";
  $sql = "CREATE TABLE " . $table_name . " (
      id INT NOT NULL AUTO_INCREMENT,
      th_id INT NOT NULL,
      html TEXT NULL,
      type VARCHAR(100) NULL,
      PRIMARY KEY  (id)
      );";
  if(!thrivehive_table_exists($table_name) || $version){
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
  }
}

function thrivehive_create_theme_options_table($version=null){
  global $wpdb;
  $table_name = $wpdb->prefix . "TH_" . "theme_options";
  $sql = "CREATE TABLE " . $table_name . " (
      id INT NOT NULL AUTO_INCREMENT,
      theme VARCHAR(100) NOT NULL,
      options TEXT NOT NULL,
      version INT(11) DEFAULT 0,
      PRIMARY KEY  (id)
      );";
  if(!thrivehive_table_exists($table_name) || $version){
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
  }
}

function thrivehive_create_snippets_db($version=null){
    global $wpdb;
    $table_name = $wpdb->prefix . "TH_" . "snippets";
    $sql = "CREATE TABLE " . $table_name . " (
      id INT NOT NULL AUTO_INCREMENT,
      name VARCHAR(100) NOT NULL,
      locked BOOLEAN DEFAULT 0,
      previewable BOOLEAN DEFAULT 0,
      html TEXT,
      css TEXT,
      javascript TEXT,
      rendered_source TEXT,
      token_values TEXT,
      PRIMARY KEY  (id)
      );";
  if(!thrivehive_table_exists($table_name) || $version){
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
  }
}

/**
*Checks to see if a thrivehive certain table already exists
*@return bool if the table was found or not
**/
function thrivehive_table_exists($table){
  global $wpdb;
  return strtolower($wpdb->get_var("SHOW TABLES LIKE '$table';")) == strtolower($table);
}

/***********************************************************************************
_________ _______  _______  _          _______  _______ _________   _______  _______  ______   _______
\__    _/(  ____ \(  ___  )( (    /|  (  ___  )(  ____ )\__   __/  (  ____ \(  ___  )(  __  \ (  ____ \
   )  (  | (    \/| (   ) ||  \  ( |  | (   ) || (    )|   ) (     | (    \/| (   ) || (  \  )| (    \/
   |  |  | (_____ | |   | ||   \ | |  | (___) || (____)|   | |     | |      | |   | || |   ) || (__
   |  |  (_____  )| |   | || (\ \) |  |  ___  ||  _____)   | |     | |      | |   | || |   | ||  __)
   |  |        ) || |   | || | \   |  | (   ) || (         | |     | |      | |   | || |   ) || (
|\_)  )  /\____) || (___) || )  \  |  | )   ( || )      ___) (___  | (____/\| (___) || (__/  )| (____/\
(____/   \_______)(_______)|/    )_)  |/     \||/       \_______/  (_______/(_______)(______/ (_______/

************************************************************************************/


function json_api_init() {
  global $json_api;
  if (phpversion() < 5) {
    add_action('admin_notices', 'json_api_php_version_warning');
    return;
  }
  if (!class_exists('JSON_API')) {
    add_action('admin_notices', 'json_api_class_warning');
    return;
  }
  add_filter('rewrite_rules_array', 'json_api_rewrites');
  $json_api = new JSON_API();
}

function json_api_php_version_warning() {
  echo "<div id=\"json-api-warning\" class=\"updated fade\"><p>Sorry, JSON API requires PHP version 5.0 or greater.</p></div>";
}

function json_api_class_warning() {
  echo "<div id=\"json-api-warning\" class=\"updated fade\"><p>Oops, JSON_API class not found. If you've defined a JSON_API_DIR constant, double check that the path is correct.</p></div>";
}

function json_api_activation() {
  // Add the rewrite rule on activation
  global $wp_rewrite;
  flush_rewrite_rules();
  add_filter('rewrite_rules_array', 'json_api_rewrites');
  $wp_rewrite->flush_rules();
}

function json_api_deactivation() {
  // Remove the rewrite rule on deactivation
  global $wp_rewrite;
  flush_rewrite_rules();
  $wp_rewrite->flush_rules();
}

function json_api_rewrites($wp_rules) {
  $base = get_option('json_api_base', 'api');
  if (empty($base)) {
    return $wp_rules;
  }
  $json_api_rules = array(
    "$base\$" => 'index.php?json=info',
    "$base/(.+)\$" => 'index.php?json=$matches[1]'
  );
  return array_merge($json_api_rules, $wp_rules);
}

function json_api_dir() {
    return dirname(__FILE__);
}
/**
 * Returns current plugin version.
 *
 * @return string Plugin version
 */
function  thrivehive_get_version() {
    if ( ! function_exists( 'get_plugins' ) )
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    $plugin_folder = get_plugins( '/' . plugin_basename( dirname( __FILE__ ) ) );
    $plugin_file = basename( ( __FILE__ ) );
    return $plugin_folder[$plugin_file]['Version'];
}


// Add initialization and activation hooks
// TODO: Remove references to $dir since these files don't exist
$dir = json_api_dir();
add_action('init', 'json_api_init');
register_activation_hook("$dir/json-api.php", 'json_api_activation');
register_deactivation_hook("$dir/json-api.php", 'json_api_deactivation');


/*****************************************************************************
// Logo widget
*/

class ThriveHiveLogo extends WP_Widget {

  public function __construct() {
    parent::__construct(
      'th_logo_widget', // Base ID
      'ThriveHive Page Logo', // Name
      array( 'description' => __( 'Displays your site logo in the widget area', 'text_domain' ), ));// Args
  }

  public function widget( $args, $instance ) {
    // outputs the content of the widget
    echo "<img src=";
    echo get_option('th_site_logo');
    echo ">";
  }

   public function form( $instance ) {
    // outputs the options form on admin
    echo "<img src=";
    echo get_option('th_site_logo');
    echo ">";
  }

  public function update( $new_instance, $old_instance ) {
    // processes widget options to be saved
  }

}
//Sidebar for Logo Widget
if(!function_exists('custom_logo_create')){
  add_action('genesis_site_title', 'custom_logo_create');
    function custom_logo_create(){
      genesis_widget_area('mycustom-logo', array(
      'before' => '<div class="mycustom-logo">'));
  }
}

/*****************************************************************************
// Button Widget
*/

class ThriveHiveButton extends WP_Widget {

  /**
   * Register widget with WordPress.
   */
  public function __construct() {
    parent::__construct(
      'th_button_widget', // Base ID
      'ThriveHive Button', // Name
      array( 'description' => __( 'Displays a button in the widget area', 'text_domain' ), ));// Args
  }
  /**
   * Register widget with WordPress.
   */
  public function widget( $args, $instance ) {

    echo $before_widget;
      $buttonId = empty($instance['buttonId']) ? ' ' : $instance['buttonId'];
      $buttonOptions = get_thrivehive_button( $buttonId );
      $css =  stripslashes($buttonOptions['generated_css']);
      $text = stripslashes($buttonOptions['text']);
      $url = stripslashes($buttonOptions['url']);
      $target = stripslashes($buttonOptions['target']);

      echo "<a class='thrivehive-button' target='$target' style='$css' href='$url'>$text</a>";

      echo $after_widget;
  }
  /**
   * Back-end widget form.
   *
   * @see WP_Widget::form()
   *
   * @param array $instance Previously saved values from database.
   */
   public function form( $instance ) {
    // outputs the options form on admin
    $defaults = array( 'buttonId' => '1' );
    $instance = wp_parse_args( $instance, $defaults );
      $buttonId = $instance['buttonId'];
  ?>
    <p><label for="<?php echo $this->get_field_id('buttonId'); ?>">Button ID: <input class="widefat" id="<?php echo $this->get_field_id('buttonId'); ?>" name="<?php echo $this->get_field_name('buttonId'); ?>" type="text" value="<?php echo attribute_escape($buttonId); ?>" /></label></p>
  <?php
    }
  /**
   * Sanitize widget form values as they are saved.
   *
   * @see WP_Widget::update()
   *
   * @param array $new_instance Values just sent to be saved.
   * @param array $old_instance Previously saved values from database.
   *
   * @return array Updated safe values to be saved.
   */
  public function update( $new_instance, $old_instance ) {
    // processes widget options to be saved
    $instance = $old_instance;
    $instance['buttonId'] = ( ! empty( $new_instance['buttonId'] ) ) ? strip_tags( $new_instance['buttonId'] ) : '';
    return $instance;
  }

}

/*****************************************************************************
// Wysiwyg Button Widget
*/

class ThriveHiveWysiwygButton extends WP_Widget {

  /**
   * Register widget with WordPress.
   */
  public function __construct() {
    parent::__construct(
      'th_wysiwyg_button_widget', // Base ID
      'ThriveHive Wysiwyg Button', // Name
      array( 'description' => __( 'Displays a wysiwyg button in the widget area', 'text_domain' ), ));// Args
  }
  /**
   * Register widget with WordPress.
   */
  public function widget( $args, $instance ) {

    echo $before_widget;
    $buttonId = empty($instance['buttonId']) ? ' ' : $instance['buttonId'];
    $buttonOptions = get_wysiwyg_button( $buttonId );
    $text = stripslashes($buttonOptions['text']);
    $url = stripslashes($buttonOptions['url']);
    $target = stripslashes($buttonOptions['target']);
    $classes =  stripslashes($buttonOptions['classes']);

    echo "<a class='$classes' target='$target' href='$url'>$text</a>";

    echo $after_widget;
  }
  /**
   * Back-end widget form.
   *
   * @see WP_Widget::form()
   *
   * @param array $instance Previously saved values from database.
   */
   public function form( $instance ) {
    // outputs the options form on admin
    $defaults = array( 'buttonId' => '1' );
    $instance = wp_parse_args( $instance, $defaults );
      $buttonId = $instance['buttonId'];
  ?>
    <p><label for="<?php echo $this->get_field_id('buttonId'); ?>">Button ID: <input class="widefat" id="<?php echo $this->get_field_id('buttonId'); ?>" name="<?php echo $this->get_field_name('buttonId'); ?>" type="text" value="<?php echo attribute_escape($buttonId); ?>" /></label></p>
  <?php
    }
  /**
   * Sanitize widget form values as they are saved.
   *
   * @see WP_Widget::update()
   *
   * @param array $new_instance Values just sent to be saved.
   * @param array $old_instance Previously saved values from database.
   *
   * @return array Updated safe values to be saved.
   */
  public function update( $new_instance, $old_instance ) {
    // processes widget options to be saved
    $instance = $old_instance;
    $instance['buttonId'] = ( ! empty( $new_instance['buttonId'] ) ) ? strip_tags( $new_instance['buttonId'] ) : '';
    return $instance;
  }

}

// /*****************************************************************************
// // PUBLIC PREVIEW CODE
// */

// class DS_Public_Post_Preview {

// 	/**
// 	 * Hooks into 'pre_get_posts' to handle public preview, only nn-admin
// 	 * Hooks into 'add_meta_boxes' to register the meta box.
// 	 * Hooks into 'save_post' to handle the values of the meta box.
// 	 * Hooks into 'admin_enqueue_scripts' to register JavaScript.
// 	 *
// 	 * @since 1.0.0
// 	 */
// 	public static function init() {
// 		add_action( 'init', array( __CLASS__, 'load_textdomain' ) );

// 		if ( ! is_admin() ) {
// 			add_filter( 'pre_get_posts', array( __CLASS__, 'show_public_preview' ) );

// 			add_filter( 'query_vars', array( __CLASS__, 'add_query_var' ) );
// 		} else {
// 			//add_action( 'post_submitbox_misc_actions', array( __CLASS__, 'post_submitbox_misc_actions' ) );

// 			add_action( 'save_post', array( __CLASS__, 'register_public_preview' ), 20, 2 );

// 			add_action( 'wp_ajax_public-post-preview', array( __CLASS__, 'ajax_register_public_preview' ) );

// 			//add_action( 'admin_enqueue_scripts' , array( __CLASS__, 'enqueue_script' ) );
// 		}
// 	}

// 	/**
// 	 * Registers the textdomain.
// 	 *
// 	 * @since 2.0.0
// 	 */
// 	public static function load_textdomain() {
// 		return load_plugin_textdomain(
// 			'ds-public-post-preview',
// 			false,
// 			dirname( plugin_basename( __FILE__ ) ) . '/lang'
// 		);
// 	}


// 	/**
// 	 * Returns the public preview link.
// 	 *
// 	 * The link is the permalink with these parameters:
// 	 *  - preview, always true (query var for core)
// 	 *  - _ppp, a custom nonce, see DS_Public_Post_Preview::create_nonce()
// 	 *
// 	 * @since  2.0.0
// 	 *
// 	 * @param  int    $post_id  The post id.
// 	 * @return string           The generated public preview link.
// 	 */
// 	public static function get_preview_link( $post_id ) {
// 		return add_query_arg(
// 			array(
// 				'preview' => true,
// 				'_ppp'    => self::create_nonce( 'public_post_preview_' . $post_id ),
// 			),
// 			get_permalink( $post_id )
// 		);
// 	}

// 	/**
// 	 * (Un)Registers a post for a public preview.
// 	 *
// 	 * Don't runs on an autosave and ignores post revisions.
// 	 *
// 	 * @since  2.0.0
// 	 *
// 	 * @param  int    $post_id The post id.
// 	 * @param  object $post    The post object.
// 	 * @return bool            Returns false on a failure, true on a success.
// 	 */
// 	public static function register_public_preview( $post_id, $post ) {
// 		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
// 			return false;

// 		if ( wp_is_post_revision( $post_id ) )
// 			return false;

// 		if ( empty( $_POST['public_post_preview_wpnonce'] ) || ! wp_verify_nonce( $_POST['public_post_preview_wpnonce'], 'public_post_preview' ) )
// 			return false;

// 		$preview_post_ids = self::get_preview_post_ids();
// 		$preview_post_id  = $post->ID;

// 		if ( empty( $_POST['public_post_preview'] ) && in_array( $preview_post_id, $preview_post_ids ) )
// 			$preview_post_ids = array_diff( $preview_post_ids, (array) $preview_post_id );
// 		elseif (
// 				! empty( $_POST['public_post_preview'] ) &&
// 				! empty( $_POST['original_post_status'] ) &&
// 				'publish' != $_POST['original_post_status'] &&
// 				'publish' == $post->post_status &&
// 				in_array( $preview_post_id, $preview_post_ids )
// 			)
// 			$preview_post_ids = array_diff( $preview_post_ids, (array) $preview_post_id );
// 		elseif ( ! empty( $_POST['public_post_preview'] ) && ! in_array( $preview_post_id, $preview_post_ids ) )
// 			$preview_post_ids = array_merge( $preview_post_ids, (array) $preview_post_id );
// 		else
// 			return false; // Nothing changed.

// 		return self::set_preview_post_ids( $preview_post_ids );
// 	}



// 	/**
// 	 * Registers the new query var `_ppp`.
// 	 *
// 	 * @since  2.1
// 	 *
// 	 * @return array List of query variables.
// 	 */
// 	public static function add_query_var( $qv ) {
// 		$qv[] = '_ppp';

// 		return $qv;
// 	}

// 	/**
// 	 * Registers the filter to handle a public preview.
// 	 *
// 	 * Filter will be set if it's the main query, a preview, a singular page
// 	 * and the query var `_ppp` exists.
// 	 *
// 	 * @since  2.0.0
// 	 *
// 	 * @param  object $query The WP_Query object.
// 	 * @return object        The WP_Query object, unchanged.
// 	 */
// 	public static function show_public_preview( $query ) {
// 		if (
// 			$query->is_main_query() &&
// 			$query->is_preview() &&
// 			$query->is_singular() &&
// 			$query->get( '_ppp' )
// 		)
// 			add_filter( 'posts_results', array( __CLASS__, 'set_post_to_publish' ), 10, 2 );

// 		return $query;
// 	}

// 	/**
// 	 * Checks if a public preview is available and allowed.
// 	 * Verifies the nonce and if the post id is registered for a public preview.
// 	 *
// 	 * @since  2.0.0
// 	 *
// 	 * @param  int   $post_id The post id.
// 	 * @return bool           True if a public preview is allowed, false on a failure.
// 	 */
// 	private static function public_preview_available( $post_id ) {
// 		if ( empty( $post_id ) )
// 			return false;

// 		if( ! self::verify_nonce( get_query_var( '_ppp' ), 'public_post_preview_' . $post_id ) )
// 			wp_die( __( 'The link has been expired!', 'ds-public-post-preview' ) );

// /*		if ( ! in_array( $post_id, get_option( 'public_post_preview', array() ) ) )
// 			wp_die( __( 'No Public Preview available!', 'ds-public-post-preview' ) );*/

// 		return true;
// 	}

// 	/**
// 	 * Sets the post status of the first post to publish, so we don't have to do anything
// 	 * *too* hacky to get it to load the preview.
// 	 *
// 	 * @since 2.0.0
// 	 *
// 	 * @param array $posts The post to preview.
// 	 */
// 	public static function set_post_to_publish( $posts ) {
// 		// Remove the filter again, otherwise it will be applied to other queries too.
// 		remove_filter( 'posts_results', array( __CLASS__, 'set_post_to_publish' ), 10, 2 );

// 		if ( empty( $posts ) )
// 			return;

// 		if ( self::public_preview_available( $posts[0]->ID ) )
// 			$posts[0]->post_status = 'publish';

// 		return $posts;
// 	}

// 	/**
// 	 * Get the time-dependent variable for nonce creation.
// 	 *
// 	 * @see    wp_nonce_tick()
// 	 *
// 	 * @since  2.1
// 	 *
// 	 * @return int The time-dependent variable
// 	 */
// 	private static function nonce_tick() {
// 		$nonce_life = apply_filters( 'ppp_nonce_life', 60 * 60 * 48 ); // 48 hours

// 		return ceil( time() / ( $nonce_life / 2 ) );
// 	}

// 	/**
// 	 * Verifies that correct nonce was used with time limit. Without an UID.
// 	 *
// 	 * @see    wp_verify_nonce()
// 	 *
// 	 * @since  1.0.0
// 	 *
// 	 * @param  string     $nonce  Nonce that was used in the form to verify
// 	 * @param  string|int $action Should give context to what is taking place and be the same when nonce was created.
// 	 * @return bool               Whether the nonce check passed or failed.
// 	 */
// 	private static function verify_nonce( $nonce, $action = -1 ) {
// 		$i = self::nonce_tick();

// 		// Nonce generated 0-12 hours ago
// 		if ( substr( wp_hash( $i . $action, 'nonce' ), -12, 10 ) == $nonce )
// 			return 1;

// 		// Nonce generated 12-24 hours ago
// 		if ( substr( wp_hash( ( $i - 1 ) . $action, 'nonce' ), -12, 10 ) == $nonce )
// 			return 2;

// 		// Invalid nonce
// 		return false;
// 	}

// 	/**
// 	 * Returns the post ids which are registered for a public preview.
// 	 *
// 	 * @since  2.0.0
// 	 *
// 	 * @return array The post ids. (Empty array if no ids are registered.)
// 	 */
// 	private static function get_preview_post_ids() {
// 		return get_option( 'public_post_preview', array() );
// 	}

// 	/**
// 	 * Saves the post ids which are registered for a public preview.
// 	 *
// 	 * @since  2.0.0
// 	 *
// 	 * @return array The post ids. (Empty array if no ids are registered.)
// 	 */
// 	private static function set_preview_post_ids( $post_ids = array( )) {
// 		return update_option( 'public_post_preview', $post_ids );
// 	}

// 	/**
// 	 * Small helper to get some plugin info.
// 	 *
// 	 * @since  2.0.0
// 	 *
// 	 * @param  string        $key The key to get the info from, see get_plugin_data().
// 	 * @return string|bool        Either the value, or if the key doesn't exists false.
// 	 */
// 	private static function get_plugin_info( $key = null ) {
// 		$plugin_data = get_plugin_data( __FILE__);
// 		if ( array_key_exists( $key, $plugin_data ) )
// 			return $plugin_data[ $key ];

// 		return false;
// 	}

// 	/**
// 	 * Delets the option 'public_post_preview' if the plugin will be uninstalled.
// 	 *
// 	 * @since 2.0.0
// 	 */
// 	public static function uninstall() {
// 		delete_option( 'public_post_preview' );
// 	}
// }

// add_action( 'plugins_loaded', array( 'DS_Public_Post_Preview', 'init' ) );

// register_uninstall_hook( __FILE__, array( 'DS_Public_Post_Preview', 'uninstall' ) );

class Simple_Preview {

  // Variable place holder for post ID for easy passing between functions
  var $id;

  // Plugin startup
  function Simple_Preview() {
    if ( ! is_admin() ) {
      add_action('init', array(&$this, 'show_preview'));
    } else {
      register_activation_hook(__FILE__, array(&$this, 'init'));
      add_action('admin_menu', array(&$this, 'meta_box'));
      add_action('save_post', array(&$this, 'save_post'));
    }
  }

  // Initialize plugin
  function init() {
    if ( ! get_option('simple_preview') )
      add_option('simple_preview', array());
  }

  // Content for meta box
  function preview_link($post) {
    $preview_posts = get_option('simple_preview');
    if ( ! in_array($post->post_status, array('publish')) ) {
?>
      <p>
        <label for="public_preview_status" class="selectit">
          <input type="checkbox" name="public_preview_status" id="public_preview_status" value="on"<?php if (isset($preview_posts[$post->ID]) ) echo ' checked="checked"'; ?>/>
          Allow Anonymous Preview
        </label>
      </p>
<?php
      if ( isset($preview_posts[$post->ID]) ) {
        $this->id = (int) $post->ID;
        $url = htmlentities(add_query_arg(array('p' => $this->id, 'preview' => 'true'), get_option('home') . '/'));
        echo "<p><a href='$url'>$url</a><br /><br />\r\n";
      }
    } else {
      echo '<p>This post is already public. Preview is not available.</p>';
    }
  }

  // Register meta box
  function meta_box() {
    add_meta_box('publicpostpreview', 'Preview', array(&$this, 'preview_link'), 'post', 'normal', 'high');
  }

  // Update options on post save
  function save_post($post) {
    $preview_posts = get_option('simple_preview');
    $post_id = $_POST['post_ID'];
    if ( $post != $post_id )
      return;
    if ( (isset($_POST['public_preview_status']) && $_POST['public_preview_status'] == 'on') && !in_array($_POST['post_status'], array('publish')) ) {
        $preview_posts[$post_id] = true;
      } else{
        unset($preview_posts[$post_id]);
    }
    update_option('simple_preview', $preview_posts);
  }

  // Show the post preview
  function show_preview() {
    if ( !is_admin() && isset($_GET['page_id']) && isset($_GET['preview']) ) {
      $this->id = (int) $_GET['page_id'];
      $preview_posts = get_option('simple_preview');

      add_action('pre_get_posts', array($this,'pages_filter'));
      add_filter('posts_results', array(&$this, 'fake_publish'));
    }
  }

  function pages_filter($query) {
    // In WYSIWYG, this call causes the menu to not show up in the draft preview
    // TODO: Investigate how this call impacts legacy previews and whether we need it at all
    if (!is_thrivehive_wysiwyg()) {
      $query->set('post_type', 'any');
    }
  }

  // Fake the post being published so we don't have to do anything *too* hacky to get it to load the preview
  function fake_publish($posts) {
    if($posts[0]){
      $posts[0]->post_status = 'publish';
    }
    return $posts;
  }
}

$Simple_Preview = new Simple_Preview();


/**
*Registers the post type `th_draft` which we use for previews
**/
function th_draft() {

  $labels = array(
    'name'                => _x( 'Drafts', 'Post Type General Name', 'text_domain' ),
    'singular_name'       => _x( 'Draft', 'Post Type Singular Name', 'text_domain' ),
    'menu_name'           => __( 'Product', 'text_domain' ),
    'parent_item_colon'   => __( 'Parent Product:', 'text_domain' ),
    'all_items'           => __( 'All Products', 'text_domain' ),
    'view_item'           => __( 'View Product', 'text_domain' ),
    'add_new_item'        => __( 'Add New Product', 'text_domain' ),
    'add_new'             => __( 'New Product', 'text_domain' ),
    'edit_item'           => __( 'Edit Product', 'text_domain' ),
    'update_item'         => __( 'Update Product', 'text_domain' ),
    'search_items'        => __( 'Search products', 'text_domain' ),
    'not_found'           => __( 'No products found', 'text_domain' ),
    'not_found_in_trash'  => __( 'No products found in Trash', 'text_domain' ),
  );
  $args = array(
    'label'               => __( 'th_draft', 'text_domain' ),
    'description'         => __( 'Product information pages', 'text_domain' ),
    'labels'              => $labels,
    'supports'            => array( ),
    'taxonomies'          => array( 'category', 'post_tag' ),
    'hierarchical'        => false,
    'public'              => false,
    'show_ui'             => false,
    'show_in_menu'        => false,
    'show_in_nav_menus'   => false,
    'show_in_admin_bar'   => false,
    'menu_position'       => 5,
    'menu_icon'           => '',
    'can_export'          => true,
    'has_archive'         => true,
    'exclude_from_search' => false,
    'publicly_queryable'  => true,
    'capability_type'     => 'page',
  );
  register_post_type( 'th_draft', $args );

}

// Hook into the 'init' action
add_action( 'init', 'th_draft', 0 );


class ThriveHivePhone extends WP_Widget {

    /**
     * Register widget with WordPress.
     */
    public function __construct() {
        parent::__construct(
            'th_phone_widget', // Base ID
            'ThriveHive Phone Number', // Name
            array( 'description' => __( 'Displays phone number in the widget area', 'text_domain' ), ));// Args
    }
    /**
     * Register widget with WordPress.
     */
    public function widget( $args, $instance ) {

       $raw_num = get_option('th_phone_number');
       $safe_num = ThriveHivePhone::esc_phone_num( $raw_num );
       $num = th_display_phone(null);

       echo $before_widget;
       $heading = $instance['heading'];
       echo "<div class='phone-number widget'>";
       echo "<div class='widget-wrap'>";
       if (!empty($heading)) {
        echo "<h4 class='heading'>$heading</h4>";
       }
       echo "<a class='phone-number-text' href='tel:+1-$safe_num'>$num</a>";
       echo "</div>";
       echo "</div>";
       echo $after_widget;
    }
    /**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     */
    public function form( $instance ) {
        $defaults = array( 'heading' => 'Get in touch:' );
        $instance = wp_parse_args( $instance, $defaults );
        $heading = $instance['heading'];
?>
  <p><label for="<?php echo $this->get_field_id('heading'); ?>">Heading: <input class="widefat" id="<?php echo $this->get_field_id('buttonId'); ?>" name="<?php echo $this->get_field_name('heading'); ?>" type="text" value="<?php echo attribute_escape($heading); ?>" /></label></p>
<?php
      }
    /**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update( $new_instance, $old_instance ) {
     $instance = $old_instance;
     $instance['heading'] = ( ! empty( $new_instance['heading'] ) ) ? strip_tags( $new_instance['heading'] ) : '';
     return $instance;
    }
    /**
     * Sanitize a given phone number
     *
     * @param string $number The phone number to sanitize and check
     *
     * @return string Updated safe phone number or '' if the given string did not contain a valid number
     */
    public static function esc_phone_num( $number ) {
     $safe_num = preg_replace('/[^\d]/','', $number);
     $trunc_num = substr($safe_num, 0, 10);
     $hyphen_num = substr_replace($trunc_num, '-', 3, 0);
     $hyphen_num = substr_replace($hyphen_num, '-', 7, 0);

     return $hyphen_num;
    }

}

class ThriveHiveSocialButtons extends WP_Widget {

  /**
   * Register widget with WordPress.
   */
  public function __construct() {
    parent::__construct(
      'th_social_buttons_widget', // Base ID
      'ThriveHive Social Buttons', // Name
      array( 'description' => __( 'Displays Facebook and Twitter links in the widget area', 'text_domain' ), ));// Args
  }
  /**
   * Register widget with WordPress.
   */
  public function widget( $args, $instance ) {
    $sidebar = get_option('th_social_sidebar');
    if($sidebar == "True"){
      $facebook = get_option('th_facebook');
      $twitter = get_option('th_twitter');
      $linkedin = get_option('th_linkedin');
      $yelp = get_option('th_yelp');
      $googleplus = get_option('th_googleplus');
      $instagram = get_option('th_instagram');
      $youtube = get_option('th_youtube');
      $houzz = get_option('th_houzz');
      $angieslist = get_option('th_angieslist');
      $pinterest = get_option('th_pinterest');
      $foursquare = get_option('th_foursquare');
      $tripadvisor = get_option('th_tripadvisor');

      $use_fontawesome = get_option("th_use_fontawesome");

      if(filter_var($use_fontawesome, FILTER_VALIDATE_BOOLEAN)){
        $this->render_fontawesome_icons($facebook, $twitter, $linkedin, $yelp, $googleplus,
          $instagram,$youtube, $houzz, $angieslist, $pinterest,
          $foursquare, $tripadvisor);
      }

      else if ($facebook || $twitter || $linkedin || $yelp || $googleplus || $instagram ||
        $youtube || $houzz || $angieslist || $pinterest || $foursquare || $tripadvisor) {
        echo $before_widget;
        echo "<div class='social-widgets widget'>";
        echo "<div class='widget-wrap'>";
            if($facebook){
              $facebook_icon = plugins_url('/images/icon-facebook-32.png', __FILE__);
              echo "<a target='_blank' href='https://facebook.com/$facebook'><img src='$facebook_icon' /></a>";
          }
          if($twitter){
            $twitter_icon = plugins_url('/images/icon-twitter-32.png', __FILE__);
            echo "<a target='_blank' href='https://twitter.com/$twitter' style='margin-left: 10px'><img src='$twitter_icon' /></a>";
          }
          if($linkedin){
            $linkedin_icon = plugins_url('/images/icon-linkedin-32.png', __FILE__);
            if (strpos($linkedin, 'http') === 0) {
              echo "<a target='_blank' href='$linkedin' style='margin-left: 10px'><img src='$linkedin_icon' /></a>";
            }
            else{
              echo "<a target='_blank' href='https://$linkedin' style='margin-left: 10px'><img src='$linkedin_icon' /></a>";
            }
          }
          if($yelp){
            $yelp_icon = plugins_url('/images/icon-yelp-32.png', __FILE__);
            echo "<a target='_blank' href='http://yelp.com/biz/$yelp' style='margin-left: 10px'><img src='$yelp_icon' /></a>";
          }
          if($googleplus){
            $googleplus_icon = plugins_url('/images/icon-gplus-32.png', __FILE__);
            if (strpos($googleplus, 'http') === 0) {
              echo "<a target='_blank' href='$googleplus' style='margin-left: 10px'><img src='$googleplus_icon' /></a>";
            }
            else{
              echo "<a target='_blank' href='https://$googleplus' style='margin-left: 10px'><img src='$googleplus_icon' /></a>";
            }
          }
          if($instagram){
            $instagram_icon = plugins_url('/images/icon-instagram-32.png', __FILE__);
            echo "<a target='_blank' href='http://instagram.com/$instagram' style='margin-left: 10px'><img src='$instagram_icon' /></a>";
          }
          if($youtube){
            $youtube_icon = plugins_url('/images/icon-youtube-32.png', __FILE__);
            if (strpos($youtube, 'http') === 0) {
              echo "<a target='_blank' href='$youtube' style='margin-left: 10px'><img src='$youtube_icon' /></a>";
            }
            else{
              echo "<a target='_blank' href='http://youtube.com/user/$youtube' style='margin-left: 10px'><img src='$youtube_icon' /></a>";
            }
          }
          if($houzz){
            $houzz_icon = plugins_url('/images/icon-houzz-32.png', __FILE__);

            if (strpos($houzz, 'http') === 0) {
              echo "<a target='_blank' href='$houzz' style='margin-left: 10px'><img src='$houzz_icon' /></a>";
            }
            else{
              echo "<a target='_blank' href='https://$houzz' style='margin-left: 10px'><img src='$houzz_icon' /></a>";
            }
          }
          if($angieslist){
            $angieslist_icon = plugins_url('/images/icon-angieslist-32.png', __FILE__);
            if (strpos($angieslist, 'http') === 0) {
              echo "<a target='_blank' href='$angieslist' style='margin-left: 10px'><img src='$angieslist_icon' /></a>";
            }
            else{
              echo "<a target='_blank' href='https://$angieslist' style='margin-left: 10px'><img src='$angieslist_icon' /></a>";
            }
          }
          if($pinterest){
            $pinterest_icon = plugins_url('/images/icon-pinterest-32.png', __FILE__);
            if (strpos($pinterest, 'http') === 0) {
              echo "<a target='_blank' href='$pinterest' style='margin-left: 10px'><img src='$pinterest_icon' /></a>";
            }
            else{
              echo "<a target='_blank' href='http://pinterest.com/$pinterest' style='margin-left: 10px'><img src='$pinterest_icon' /></a>";
            }
          }
          if($foursquare){
            $foursquare_icon = plugins_url('/images/icon-foursquare-32.png', __FILE__);
            if (strpos($foursquare, 'http') === 0) {
              echo "<a target='_blank' href='$foursquare' style='margin-left: 10px'><img src='$foursquare_icon' /></a>";
            }
            else{
              echo "<a target='_blank' href='https://$foursquare' style='margin-left: 10px'><img src='$foursquare_icon' /></a>";
            }
          }
                    if($tripadvisor){
                        $tripadvisor_icon = plugins_url('/images/icon-tripadvisor-32.png', __FILE__);
                         if (strpos($tripadvisor, 'http') === 0) {
              echo "<a target='_blank' href='$tripadvisor' style='margin-left: 10px'><img src='$tripadvisor_icon' /></a>";
            }
            else{
              echo "<a target='_blank' href='https://$tripadvisor' style='margin-left: 10px'><img src='$tripadvisor_icon' /></a>";
            }
                    }
        echo "</div>";
        echo "</div>";
          echo $after_widget;
      }
    }
  }

  public function render_fontawesome_icons($facebook, $twitter, $linkedin, $yelp, $googleplus,
                            $instagram,$youtube, $houzz, $angieslist, $pinterest,
                            $foursquare, $tripadvisor){

    echo $before_widget;
    echo "<div class='social-widgets widget'>";
    echo "<div class='widget-wrap'>";
    if($facebook){
        echo "<a target='_blank' href='https://facebook.com/$facebook' title='Facebook'><i class='fa th-social-icon fa-facebook'></i></a>";
    }

    if($twitter){
        echo "<a target='_blank' href='https://twitter.com/$twitter' style='margin-left: 10px' title='Twitter'><i class='fa th-social-icon fa-twitter'></i></a>";
    }
    if($linkedin){
      if (strpos($linkedin, 'http') === 0) {
        echo "<a target='_blank' href='$linkedin' style='margin-left: 10px' title='LinkedIn'><i class='fa th-social-icon fa-linkedin'></i></a>";
      }
      else{
        echo "<a target='_blank' href='https://$linkedin' style='margin-left: 10px' title='LinkedIn'><i class='fa th-social-icon fa-linkedin'></i></a>";
      }
    }
    if($yelp){
      if (strpos($yelp, 'http') === 0) {
        echo "<a target='_blank' href='$yelp' style='margin-left: 10px' title='Yelp'><i class='fa th-social-icon fa-yelp'></i></a>";
      }
      else{
        echo "<a target='_blank' href='http://yelp.com/biz/$yelp' style='margin-left: 10px' title='Yelp'><i class='fa th-social-icon fa-yelp'></i></a>";
      }
    }
    if($googleplus){
      if (strpos($googleplus, 'http') === 0) {
        echo "<a target='_blank' href='$googleplus' style='margin-left: 10px' title='Google Plus'><i class='fa th-social-icon fa-google-plus'></i></a>";
      }
      else{
        echo "<a target='_blank' href='https://$googleplus' style='margin-left: 10px' title='Google Plus'><i class='fa th-social-icon fa-google-plus'></i></a>";
      }
    }
    if($instagram){
      echo "<a target='_blank' href='http://instagram.com/$instagram' style='margin-left: 10px' title='Instagram'><i class='fa th-social-icon fa-instagram'></i></a>";
    }
    if($youtube){
      if (strpos($youtube, 'http') === 0) {
        echo "<a target='_blank' href='$youtube' style='margin-left: 10px' title='YouTube'><i class='fa th-social-icon fa-youtube'></i></a>";
      }
      else{
        echo "<a target='_blank' href='http://youtube.com/user/$youtube' style='margin-left: 10px' title='YouTube'><i class='fa th-social-icon fa-youtube'></i></a>";
      }
    }
    if($houzz){
      if (strpos($houzz, 'http') === 0) {
        echo "<a target='_blank' href='$houzz' style='margin-left: 10px' title='Houzz'><i class='fa th-social-icon fa-houzz'></i></a>";
      }
      else{
        echo "<a target='_blank' href='https://$houzz' style='margin-left: 10px' title='Houzz'><i class='fa th-social-icon fa-houzz'></i></a>";
      }
    }
    if($angieslist){
      if (strpos($angieslist, 'http') === 0) {
        echo "<a target='_blank' href='$angieslist' style='margin-left: 10px' title='Angies List'><i class='fa th-social-icon fa-angies-list'></i></a>";
      }
      else{
        echo "<a target='_blank' href='https://$angieslist' style='margin-left: 10px' title='Angies List'><i class='fa th-social-icon fa-angies-list'></i></a>";
      }
    }
    if($pinterest){
      if (strpos($pinterest, 'http') === 0) {
        echo "<a target='_blank' href='$pinterest' style='margin-left: 10px' title='Pinterest'><i class='fa th-social-icon fa-pinterest'></i></a>";
      }
      else{
        echo "<a target='_blank' href='http://pinterest.com/$pinterest' style='margin-left: 10px' title='Pinterest'><i class='fa th-social-icon fa-pinterest'></i></a>";
      }
    }
    if($foursquare){
      if (strpos($foursquare, 'http') === 0) {
        echo "<a target='_blank' href='$foursquare' style='margin-left: 10px' title='Foursquare'><i class='fa th-social-icon fa-foursquare'></i></a>";
      }
      else{
        echo "<a target='_blank' href='https://$foursquare' style='margin-left: 10px' title='Foursquare'><i class='fa th-social-icon fa-foursquare'></i></a>";
      }
    }

    echo "</div>";
    echo "</div>";
      echo $after_widget;
  }


  /**
   * Back-end widget form.
   *
   * @see WP_Widget::form()
   *
   * @param array $instance Previously saved values from database.
   */
   public function form( $instance ) {

    }
  /**
   * Sanitize widget form values as they are saved.
   *
   * @see WP_Widget::update()
   *
   * @param array $new_instance Values just sent to be saved.
   * @param array $old_instance Previously saved values from database.
   *
   * @return array Updated safe values to be saved.
   */
  public function update( $new_instance, $old_instance ) {
          $instance = $old_instance;
     return $instance;
  }

}
add_filter ('the_content', 'renderSocialStuff');
function renderSocialStuff($content){
    // Based on //kikolani.com/social-sharing-buttons-in-single-post-templates.html
    if(!is_page()){
        $twitter = get_option("th_twitter");
        $permalink = get_permalink();
        $title = get_the_title();
        $encodedPermalink = urlencode($permalink);
        $image = wp_get_attachment_url( get_post_thumbnail_id(get_the_ID()));
        if($image == ""){
            $image = get_header_image();
            if($image != ""){
              list($width, $height, $type, $attr) = getimagesize($image);
              if($width < 84 || $height < 84){
                $image = "";
              }
            }
        }
        if($image == ""){
            $image = includes_url("images/wlw/wp-watermark.png");
        }
        $image = urlencode($image);
        wp_enqueue_script( "twitter", "//platform.twitter.com/widgets.js");
        wp_enqueue_script( "facebook", "//static.ak.fbcdn.net/connect.php/js/FB.Share");
        wp_enqueue_script( "linkedin", '//platform.linkedin.com/in.js');
        wp_enqueue_script( "pinterest", '//assets.pinterest.com/js/pinit.js');
        $blog_roll = get_option("th_social_blogroll");
        $single = get_option("th_social_blog");
        $show_blogroll = $blog_roll == "True" && !is_single();
        $show_single = $single == "True" && is_single();
        $desc = $title." | ".get_bloginfo();
        if($show_blogroll || $show_single)
        {
            echo  "";
            echo "<div class='social-buttons' style='margin:5px 0'>";
            echo "  <div id='twitterbutton' style='float:left'>";
            echo "      <div>";
            echo "          <a href='//twitter.com/share' class='twitter-share-button' data-url='$permalink' data-counturl='$permalink' data-text='$title' data-via='$twitter' data-related='$twitter'>Tweet</a>";
            echo "      </div>";
            echo "  </div>";
            echo "  <div id='likebutton' style='float:left'>";
            echo "      <iframe src='//www.facebook.com/plugins/like.php?href=$encodedPermalink&amp;layout=button_count&amp;show_faces=false&amp;width=100&amp;action=like&amp;font=verdana&amp;colorscheme=light&amp;height=21' scrolling='no' frameborder='0' style='border:none; overflow:hidden; width:100px; height:21px;' allowTransparency='true'>";
            echo "      </iframe>";
            echo "  </div>";
            echo "  <div id='linkedinshare' style='float:left'>";
            echo "      <script type='IN/Share' data-url='$permalink' data-counter='right'></script>";
            echo "  </div>";
            echo "  <div id='pinit' style='float:left; margin-left:10px'>";
            echo "      <a href='//pinterest.com/pin/create/button/?url=$permalink&media=$image&class=pin-it-button&description=$desc' class='pin-it-button' count-layout='horizontal'><img border='0' src='//assets.pinterest.com/images/PinExt.png' title='Pin It' /></a>";
            echo "  </div>";
            echo "  <div id='sharebutton' style='padding-top:1px;float:left;'>";
            echo "  </div>";
            echo "  <div style='clear: both;'></div>";
            echo "</div>";
        }
    }
    return $content;
}
if($has_th_environment){
  add_action( 'genesis_meta', 'thrivehive_custom_header_override' );
}

function thrivehive_custom_header_override(){
  // replace genesis header func with our own
  remove_action( 'wp_head', 'genesis_custom_header_style' );
  add_action( 'wp_head', 'genesis_custom_header_style_override' );

}


// copied from genesis/lib/structure/header.php:754ish
/**
 * Custom header callback.
 *
 * It outputs special CSS to the document head, modifying the look of the header based on user input.
 *
 * @since 1.6.0
 *
 * @uses genesis_html() Check for HTML5 support.
 *
 * @return null Return null on if custom header not supported, user specified own callback, or no options set.
 */
function genesis_custom_header_style_override() {

  //* Do nothing if custom header not supported
  if ( ! current_theme_supports( 'custom-header' ) )
    return;

  //* Do nothing if user specifies their own callback
  if ( get_theme_support( 'custom-header', 'wp-head-callback' ) )
    return;

  $output = '';

  $header_image = get_header_image();
  $text_color   = get_header_textcolor();

  //* If no options set, don't waste the output. Do nothing.
  if ( empty( $header_image ) && ! display_header_text() && $text_color === get_theme_support( 'custom-header', 'default-text-color' ) )
    return;

  $header_selector = get_theme_support( 'custom-header', 'header-selector' );
  $title_selector  = genesis_html5() ? '.custom-header .site-title'       : '.custom-header #title';
  $desc_selector   = genesis_html5() ? '.custom-header .site-description' : '.custom-header #description';

  //* Header selector fallback
  if ( ! $header_selector )
    $header_selector = genesis_html5() ? '.custom-header .site-header' : '.custom-header #header';

  //* Header image CSS, if exists
  if ( $header_image )
    $output .= sprintf( '%s, %s:hover { background-image: url(%s); background-repeat: no-repeat; background-color: transparent; }', $header_selector, $header_selector, esc_url( $header_image ), $header_selector );

  //* Header text color CSS, if showing text
  if ( display_header_text() && $text_color !== get_theme_support( 'custom-header', 'default-text-color' ) )
    $output .= sprintf( '%2$s a, %2$s a:hover, %3$s { color: #%1$s !important; }', esc_html( $text_color ), esc_html( $title_selector ), esc_html( $desc_selector ) );

  if ( $output )
    printf( '<style type="text/css">%s</style>' . "\n", $output );

}
function thrivehive_add_content_delimiter($content) {
  // wraps content in delimiters for TH editor
  return "<!--editor:content -->$content<!-- /editor:content -->";
}
add_filter( 'the_content', 'thrivehive_add_content_delimiter' );


add_action('wp_insert_comment', 'th_comment_inserted', 99, 500);
function th_comment_inserted($comment_id, $comment_object) {
  if($comment_object->user_id == 0 && $comment_object->comment_approved != "spam"){
    $post = get_post($comment_object->comment_post_ID);
    $comment_object->post_title = $post->post_title;
    $comment_json = json_encode($comment_object);
    $api_key = get_option("th_api_key");
    $env = get_option('th_environment');
    if(!$env){
      $env = "my.thrivehive.com";
    }
    if(!$api_key){
      $web_tracker = get_option("th_tracking_code");
      $api_resp = wp_remote_post("http://$env/blogwebhook/GenerateWordpressApiKey",
        array("body" =>
          array("webTracker" => $web_tracker)
      ));
      $api_decoded = json_decode($api_resp["body"],true);
      if($api_decoded["message"] != "error"){
        $api_key = $api_decoded["payload"];
        update_option("th_api_key", $api_key);
      }
    }
    wp_remote_post("http://$env/blogwebhook/insertblogcomment", array("body"
      => array("apiKey" => $api_key, "commentObject" => $comment_json)));
  }
}
