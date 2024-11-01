<?php
/**
*Controller name: Changeling
*Controller description: Interactions with Changeling theme
*/

/**
*Class for WP Changeling theme options
*@package Controllers\Changeling
*/
class JSON_API_Changeling_Controller {
	
  private function config_key_indices() {
    $NAME_KEY_INDEX = 0;
    $CATEGORY_KEY_INDEX = 1;
    $NICE_NAME_KEY_INDEX = 2;
    $ACCESS_LEVEL_KEY_INDEX = 3;
	  
    return array("name" => $NAME_KEY_INDEX, "category" => $CATEGORY_KEY_INDEX, "nice_name" => $NICE_NAME_KEY_INDEX, "access_level" => $ACCESS_LEVEL_KEY_INDEX);
  }

  /**
  *@api
  **/
  public function get_bootstrap_sass_settings() {
    global $json_api;
    $user_defined_bootstrap_sass_settings_option = 'user_defined_bootstrap_sass_settings';
    $sass_settings = get_option($user_defined_bootstrap_sass_settings_option);
    $sass_settings = $sass_settings ?: "";

    return array('settings' => $sass_settings);
  }

  /**
  *@api
  * WARNING: This could fail silently if the user has permission issues
  **/
  public function set_bootstrap_sass_settings() {
    global $json_api;
    if(isset($_REQUEST['settings'])) {
      $settings = stripslashes($_REQUEST['settings']);
      $resp = update_option('user_defined_bootstrap_sass_settings', $settings);
      return array('settings' => $settings);
    } else {
      return $json_api->error('No settings were passed!');
    }
  }

  /**
  *@api
  **/
  public function get_custom_styling_settings() {
    global $json_api;
    $user_defined_custom_styling_settings_option = 'user_defined_custom_styling_settings';
    $sass_settings = get_option($user_defined_custom_styling_settings_option);
    $sass_settings = $sass_settings ?: "";

    return array('settings' => $sass_settings);
  }

  /**
  *@api
  **/
  public function set_custom_styling_settings() {
    global $json_api;
    if(isset($_REQUEST['settings'])) {
      $settings = stripslashes($_REQUEST['settings']);
      update_option('user_defined_custom_styling_settings', $settings);
      return array('settings' => $settings);
    } else {
      return $json_api->error('No settings were passed!');
    }
  }

  /**
  *@api
  **/
  public function save_compiled_bootstrap_css() {
    global $json_api;
    if(isset($_REQUEST['css'])) {
      // We must save custom CSS files to the uploads directory, otherwise they would be destroyed by theme updates
      $upload_dir = wp_upload_dir();
      $changeling_css_dir = $upload_dir['basedir'] . '/changeling-css';
      $compiled_bootstrap_css_file_location = $changeling_css_dir .'/theme-user-custom.css';
      $compiled_bootstrap_minified_css_file_location = $changeling_css_dir .'/theme-user-custom.min.css';
      $compiled_bootstrap_sourcemap_file_location = $changeling_css_dir .'/theme-user-custom.css.map';

      // Add BOM (byte order mark) to correct interpretation of files as UTF-8, necessary for Font Awesome glyphs
      // Also remove slashes added to escape quotes when CSS was sent as JSON
      // See https://stackoverflow.com/a/9047876
      $utf8Bom = "\xEF\xBB\xBF";
      $css = $utf8Bom . stripslashes($_REQUEST['css']);
      $minified = $utf8Bom . stripslashes($_REQUEST['minified']);
      $map = $utf8Bom . stripslashes($_REQUEST['sourceMap']);

      if (!file_exists($changeling_css_dir)) {
        wp_mkdir_p($changeling_css_dir);
      }

      file_put_contents($compiled_bootstrap_css_file_location, $css);
      file_put_contents($compiled_bootstrap_minified_css_file_location, $minified);
      file_put_contents($compiled_bootstrap_sourcemap_file_location, $map);

      // Clear cache plugin's combined minified CSS file, reducing the likelihood of the end user seeing outdated CSS
      // after the updated CSS is saved via the Global Styling editor in Zergling
      if( isset($GLOBALS['wp_fastest_cache']) && method_exists($GLOBALS['wp_fastest_cache'], 'deleteCache') ) {
        $GLOBALS['wp_fastest_cache']->deleteCache(true);
      }

      return array(
        'css' => $css,
        'minified' => $minified,
        'sourceMap' => $map
      );
    } else {
      return $json_api->error('No css was passed!');
    }
  }

  /**
  *@api
  **/
  public function get_compiled_minified_css() {
    global $json_api;

    $stylesheet_dir = get_stylesheet_directory();
    $upload_dir = wp_upload_dir();
    $changeling_css_dir = $upload_dir['basedir'] . '/changeling-css';
    $user_css = $changeling_css_dir . '/theme-user-custom.min.css';
    $default_css = $stylesheet_dir . '/css/theme-default.min.css';

    $compiled_minified_css_path = (file_exists($user_css) ? $user_css : $default_css);
    $compiled_minified_css = file_get_contents($compiled_minified_css_path);

    return array(
      'css' => $compiled_minified_css
    );
  }

  /**
  *@api
  **/
  public function get_bootstrap_assets(){
    global $json_api;
    $theme_dir_uri = get_stylesheet_directory();

    $bootstrap_files = $theme_dir_uri . '/config/virtual-file-system.json';
    $file_contents = file_get_contents($bootstrap_files);

    $theme_template = $theme_dir_uri . '/templates/bootstrap-styles.handlebars';
    $template_contents = file_get_contents($theme_template);

    return array(
      'staticFiles' => $file_contents,
      'themeTemplate' => $template_contents
    );
  }

  /**
  *@api
  **/
  public function get_bootstrap_theme_config(){
    global $json_api;
    $theme_dir_uri = get_stylesheet_directory();
    $category = $_REQUEST['category'];
    $query = $_REQUEST['query'];

    if (isset($category)) {
      $theme_config_path = '/config/categories/' . $category . '.json';
    } else {
      $theme_config_path = '/config/theme-config.json';
    }

    $theme_config_full_path = $theme_dir_uri . $theme_config_path;
    $theme_config_contents = file_get_contents($theme_config_full_path);

    if (isset($query)) {
      $theme_config_data = json_decode($theme_config_contents);
      $filtered_data = array('keys' => $theme_config_data -> keys);
      $filtered_vars = array();
      $keys = $this -> config_key_indices();
      foreach ($theme_config_data -> vars as $var) {
        if ($this -> data_contains_query($var, $query)) {
          $filtered_vars[] = $var;
        }
      }
      $filtered_data['vars'] = $filtered_vars;

      return array(
        'themeConfig' => $filtered_data,
        'category' => $category
      );
    }

    return array(
      'themeConfig' => $theme_config_contents,
      'category' => $category
    );
  }

  /**
  *@api
  **/
  public function get_bootstrap_theme_config_settings(){
    global $json_api;
    $theme_dir_uri = get_stylesheet_directory();

    $theme_config_settings_full_path = $theme_dir_uri . '/config/theme-config-settings.json';
    $theme_config_settings_contents = file_get_contents($theme_config_settings_full_path);

    return array(
      'themeConfigSettings' => $theme_config_settings_contents
    );
  }
	
  /**
  *@api
  **/
  public function filter_bootstrap_categories(){
    global $json_api;
    $theme_dir_uri = get_stylesheet_directory();
    $theme_config_path = '/config/theme-config.json';
    $query = $_REQUEST['query'];
    
    if (!isset($query)) {
      $query = '';
    }
    $theme_config_full_path = $theme_dir_uri . $theme_config_path;
    $theme_config_contents = file_get_contents($theme_config_full_path);
    $theme_config_data = json_decode($theme_config_contents);

    $filtered_categories = array();
	  $keys = $this -> config_key_indices();
    foreach ($theme_config_data -> vars as $var) {
      if ($this -> data_contains_query($var, $query)) {
        $cur_category = $var[$keys['category']];
        $new_access_level = $var[$keys['access_level']];

        // If the category does not yet exist or we find a variable within it that has
        // a higher access level, set the category's access level to the current variable
        if (!array_key_exists($cur_category, $filtered_categories) || 
        $new_access_level > ($filtered_categories[$cur_category]['accessLevel'])) {
          $filtered_categories[$cur_category] = array('accessLevel' => $new_access_level);
        };
      }
    }
    return array(
      'filteredCategories' => $filtered_categories
    );
  }

  private function data_contains_query($var, $query){
    $keys = $this -> config_key_indices();
    if ($query === '') {
      return true;
    }
    $result = false;

    $keys_to_check = array('name', 'nice_name', 'category');
    foreach ($keys_to_check as $key) {
      if (stripos($var[$keys[$key]], $query) !== false) {
        $result = true;
      }
    }
      
    return $result;
  }
}

?>
