<?php
/**
 *Summary
 *Controller name: Snippets
 *Controller description: Data manipulation methods for snippets
 */

include_once( ABSPATH . 'wp-content/plugins/thrivehive/lib/thrivehive_snippets.php');
include_once( ABSPATH . 'wp-content/plugins/thrivehive/lib/thrivehive_parse_query_params.php');
include_once( ABSPATH . 'wp-content/plugins/thrivehive/lib/snippet_display.php');

/**
 *Class related to reusable snippets CRUD
 *@package Controllers\Snippets
 */
class JSON_API_Snippets_Controller {
    private static $model_param_types = array(
        "name" => array("type" => "string"),
        "locked" => array("type" => "boolean"),
        "previewable" => array("type" => "boolean"),
        "html" => array("type" => "string"),
        "css" => array("type" => "string"),
        "javascript" => array("type" => "string"),
        "token_values" => array("type" => "string", "required" => false),
        "rendered_source" => array("type" => "string", "required" => false)
    );

	/**
     *Gets existing snippets
     *@example URL - /api/snippets/get_snippets
     *@api
     *@return array("snippets" => array_of_snippets)
     **/
    public function get_snippets() {
        nocache_headers();
        return array("snippets" => get_thrivehive_snippets());
    }

	/**
     *Gets a single snippet for the specified id
     *@example URL - /api/snippets/get_snippet?id=20
     *@api
     *@return array("snippet" => snippet) or an error if it doesn't exist
     **/
    public function get_snippet() {
        global $json_api;
        nocache_headers();
        if (!isset($_REQUEST["id"])) {
            $json_api->error("The `id` parameter is required");
        }
        $id = $_REQUEST["id"];
        $snippet = get_thrivehive_snippet($id);
        if ($snippet === NULL) {
            $json_api->error("There is no snippet with id $id");
        }
        return array("snippet" => $snippet);
    }

	/**
     *Creates a new snippet
     *@example URL - /api/snippets/create_snippet?name=my_snippet[...]
     *@api
     *@return array("snippet" => snippet) or an error in case of failure
     **/
	public function create_snippet() {
		global $json_api;
        nocache_headers();
		if(!current_user_can('edit_pages')){
			$json_api->error("You need to login with a user that has 'edit_pages' capacity.",'**auth**');
		}
        extract(parse_params(self::$model_param_types)); // get $data and $errors
        if (!empty($errors)) {
            $json_api->error(implode(" ", $errors));
        }
        $snippet = create_thrivehive_snippet($data);
        if (!$snippet) {
            $json_api->error("There was an error trying to create the snippet");
        }
        return array(
          "snippet" => $snippet
        );
    }

	/**
     *Updates an existing snippet (identified by the id parameter)
     *@example URL - /api/snippets/update_snippet?id=12&name=my_snippet[...]
     *@api
     *@return array("snippet" => snippet) or an error in case of failure
     **/
	public function update_snippet() {
		global $json_api;
        nocache_headers();
		if(!current_user_can('edit_pages')){
            $json_api->error("You need to login with a user that has 'edit_pages' capacity.",'**auth**');
		}
        $param_types = array_merge(self::$model_param_types, array("id" => array("type" => "integer")));
        extract(parse_params($param_types)); // get $data and $errors
        if (!empty($errors)) {
            $json_api->error(implode(" ", $errors));
        }
        $snippet = update_thrivehive_snippet($data);
        if (!$snippet) {
            $json_api->error("There was an error trying to update the snippet");
        }
        return array("snippet" => $snippet);
    }

	/**
     *Deletes an existing snippet (identified by the id parameter)
     *@example URL - /api/snippets/delete_snippet?id=12
     *@api
     *@return nothing in case of success, error in case of failure
     **/
    public function delete_snippet() {
        global $json_api;
        nocache_headers();
		if(!current_user_can('edit_pages')){
            $json_api->error("You need to login with a user that has 'edit_pages' capacity.",'**auth**');
		}
        if(!isset($_REQUEST["id"])){
            $json_api->error("Parameter `id` is required to delete a snippet");
        }
        $id = $_REQUEST["id"];
        $success = delete_thrivehive_snippet($_REQUEST["id"]);
        if ($success) {
            return array();
        } else {
            $json_api->error("There was an error or the snippet does not exist");
        }
    }

    /**
     * Gets the HTML for a given shortcode_name and atts
     * @return array containing "html"
     */
    public function get_rendered_html()
    {
        global $json_api;
        nocache_headers();
        $shortcode_name = $_REQUEST["shortcode_name"];
        if(!isset($shortcode_name)) {
            $json_api->error("Parameter `shortcode_name` is required to get rendered html");
        }

        if (isset($_REQUEST["atts"])) {
            $atts = json_decode(stripcslashes($_REQUEST["atts"]), true);
        }

        $html = "";

        switch ($shortcode_name) {
            case "th_form":
                $html = th_display_form($atts);
                break;
            case "th_phone":
                $html = th_display_phone($atts);
                break;
            case "th_button":
                $html = th_display_button($atts);
                break;
            case "th_wysiwyg_button":
                $html = th_display_wysiwyg_button($atts);
                break;
            case "th_address":
                $html = th_display_address($atts);
                break;
            case "th_map":
                $html = th_map();
                break;
            case "th_gallery":
                $html = th_display_gallery($atts);
                break;
            case "th_youtube":
                $html = th_display_youtube($atts);
                break;
            case "th_pdf":
                $html = th_display_pdf($atts);
                break;
            case "th_snippet":
                $html = th_display_snippet($atts);
                break;
            default:
                $json_api->error("No shortcode is configured with the name $shortcode_name");
        }

        return array("html" => $html);
    }

  }
?>
