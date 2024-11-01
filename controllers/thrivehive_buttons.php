<?php
/**
*Summary
*Controller name: ThrivehiveButtons
*Controller description: Data manipulation methods for thrivehive buttons
*/
/**
*/
include_once( TH_PLUGIN_ROOT . '/lib/thrivehive_buttons.php');

/**
*Class related to controlling menu and setting options
*@package Controllers\ThrivehiveButtons
*/
class JSON_API_Thrivehive_Buttons_Controller {
	/**
	*Gets custom thrivehive buttons
	*@example URL - /menus/get_buttons
	*@api
	*@return array of button objects and their properties
	**/
	public function get_buttons() {
		global $json_api;

		$buttons = get_thrivehive_buttons();

		return array('buttons' => $buttons);
	}

	/**
	*Gets a single thrivehive button
	*@example URL - /menus/get_button
	*@api
	*@return array containing the button and its properties
	**/
	public function get_button() {
		global $json_api;

		if(!isset($_REQUEST['id'])){
			$json_api->error("You must include the `id` of the button to retrieve");
		}
		$button = get_thrivehive_button($_REQUEST['id']);

		return array('button' => $button);
	}

	/**
	*Sets the button's values and updates it in the database
	*@example URL - /menus/set_button
	*@api
	*@return array containing the button with its new values
	**/
	public function set_button() {
		global $json_api;

		if(!isset($_REQUEST['id'])){
			$json_api->error("You must include the `id` of the button to edit");
		}
		if(!isset($_REQUEST['text'])){
			$json_api->error("You must include the `text` value");
		}
		if(!isset($_REQUEST['norm_gradient1'])){
			$json_api->error("You must include the `norm_gradient1` value");
		}
		if(!isset($_REQUEST['norm_gradient2'])){
			$json_api->error("You must include the `norm_gradient2` value");
		}
		if(!isset($_REQUEST['hover_gradient1'])){
			$json_api->error("You must include the `hover_gradient1` value");
		}
		if(!isset($_REQUEST['hover_gradient2'])){
			$json_api->error("You must include the `hover_gradient2` value");
		}
		if(!isset($_REQUEST['norm_border_color'])){
			$json_api->error("You must include the `norm_border_color` value");
		}
		if(!isset($_REQUEST['hover_border_color'])){
			$json_api->error("You must include the `hover_border_color` value");
		}
		if(!isset($_REQUEST['norm_text_color'])){
			$json_api->error("You must include the `norm_text_color` value");
		}
		if(!isset($_REQUEST['hover_text_color'])){
			$json_api->error("You must include the `hover_text_color` value");
		}
		if(!isset($_REQUEST['generated_css'])){
			$json_api->error("You must include the `generated_css` value");
		}
		if(!isset($_REQUEST['url'])){
			$json_api->error("You must include the redirect `url` value");
		}
		if(!isset($_REQUEST['target'])){
			$json_api->error("You must include the window `target` value");
		}
		/*if(!isset($_REQUEST['nonce'])){
			$json_api->error("You must include the `nonce` value");
		}*/

		$nonce_id = $json_api->get_nonce_id('menus', 'set_button');

		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
		}

		$data = array(
			'text'=>stripcslashes($_REQUEST['text']),
			'norm_gradient1'=>$_REQUEST['norm_gradient1'],
			'norm_gradient2'=>$_REQUEST['norm_gradient2'],
			'hover_gradient1'=>$_REQUEST['hover_gradient1'],
			'hover_gradient2'=>$_REQUEST['hover_gradient2'],
			'norm_border_color'=>$_REQUEST['norm_border_color'],
			'hover_border_color'=>$_REQUEST['hover_border_color'],
			'norm_text_color'=>$_REQUEST['norm_text_color'],
			'hover_text_color'=>$_REQUEST['hover_text_color'],
			'generated_css'=>$_REQUEST['generated_css'],
			'url'=>$_REQUEST['url'],
			'target'=>$_REQUEST['target']
			);

		$button = set_thrivehive_button($_REQUEST['id'], $data);

		return array($button);
	}

	/**
	*Create a new thrivehive button with all the specified values
	*@example URL - /menus/create_button
	*@api
	*@return array containing the button created
	**/
	public function create_button() {
		global $json_api;

		if(!isset($_REQUEST['text'])){
			$json_api->error("You must include the `text` value");
		}
		if(!isset($_REQUEST['norm_gradient1'])){
			$json_api->error("You must include the `norm_gradient1` value");
		}
		if(!isset($_REQUEST['norm_gradient2'])){
			$json_api->error("You must include the `norm_gradient2` value");
		}
		if(!isset($_REQUEST['hover_gradient1'])){
			$json_api->error("You must include the `hover_gradient1` value");
		}
		if(!isset($_REQUEST['hover_gradient2'])){
			$json_api->error("You must include the `hover_gradient2` value");
		}
		if(!isset($_REQUEST['norm_border_color'])){
			$json_api->error("You must include the `norm_border_color` value");
		}
		if(!isset($_REQUEST['hover_border_color'])){
			$json_api->error("You must include the `hover_border_color` value");
		}
		if(!isset($_REQUEST['norm_text_color'])){
			$json_api->error("You must include the `norm_text_color` value");
		}
		if(!isset($_REQUEST['hover_text_color'])){
			$json_api->error("You must include the `hover_text_color` value");
		}
		if(!isset($_REQUEST['generated_css'])){
			$json_api->error("You must include the `generated_css` value");
		}
		if(!isset($_REQUEST['url'])){
			$json_api->error("You must include the redirect `url` value");
		}
		if(!isset($_REQUEST['target'])){
			$json_api->error("You must include the window `target` value");
		}
		/*if(!isset($_REQUEST['nonce'])){
			$json_api->error("You must include the `nonce` value");
		}*/

		$nonce_id = $json_api->get_nonce_id('menus', 'create_button');

		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
		}
		$data = array(
			'text'=>stripcslashes($_REQUEST['text']),
			'norm_gradient1'=>$_REQUEST['norm_gradient1'],
			'norm_gradient2'=>$_REQUEST['norm_gradient2'],
			'hover_gradient1'=>$_REQUEST['hover_gradient1'],
			'hover_gradient2'=>$_REQUEST['hover_gradient2'],
			'norm_border_color'=>$_REQUEST['norm_border_color'],
			'hover_border_color'=>$_REQUEST['hover_border_color'],
			'norm_text_color'=>$_REQUEST['norm_text_color'],
			'hover_text_color'=>$_REQUEST['hover_text_color'],
			'generated_css'=>$_REQUEST['generated_css'],
			'url'=>$_REQUEST['url'],
			'target'=>$_REQUEST['target']
			);
		$button = create_thrivehive_button($data);
		return array('button' => $button);
	}

	public function delete_button() {
		global $json_api;

		if(!isset($_REQUEST['id'])){
			$json_api->error("You must include the `id` of the button to edit");
		}
		/*if(!isset($_REQUEST['nonce'])){
			$json_api->error("You must include the `nonce` value");
		}*/

		$nonce_id = $json_api->get_nonce_id('menus', 'delete_button');

		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
		}

		delete_thrivehive_button($_REQUEST['id']);

		return array();
	}
}
