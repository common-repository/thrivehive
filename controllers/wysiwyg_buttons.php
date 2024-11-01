<?php
/**
*Summary
*Controller name: WysiwygButtons
*Controller description: Data manipulation methods for wysiwyg buttons
*/
/**
*/
include_once( TH_PLUGIN_ROOT . '/lib/thrivehive_wysiwyg_buttons.php');

/**
*Class related to controlling menu and setting options
*@package Controllers\WysiwygButtons
*/
class JSON_API_Wysiwyg_Buttons_Controller {
    /**
	*Gets custom wysiwyg buttons
	*@example URL - /wysiwyg_buttons/get_wysiwyg_buttons
	*@api
	*@return array of button objects and their properties
	**/
	public function get_wysiwyg_buttons() {
		global $json_api;

		$buttons = get_wysiwyg_buttons();

		return array('buttons' => $buttons);
	}

	/**
	*Gets a single wysiwyg button
	*@example URL - /wysiwyg_buttons/get_wysiwyg_button
	*@api
	*@return array containing the button and its properties
	**/
	public function get_wysiwyg_button() {
		global $json_api;

		if(!isset($_REQUEST['id'])){
			$json_api->error("You must include the `id` of the button to retrieve");
		}
		$button = get_wysiwyg_button($_REQUEST['id']);

		return array('button' => $button);
	}

	/**
	*Sets the button's values and updates it in the database
	*@example URL - /wysiwyg_buttons/set_wysiwyg_button
	*@api
	*@return array containing the button with its new values
	**/
	public function set_wysiwyg_button() {
		global $json_api;

		if(!isset($_REQUEST['id'])){
			$json_api->error("You must include the `id` of the button to edit");
		}
		if(!isset($_REQUEST['text'])){
			$json_api->error("You must include the `text` value");
		}
		if(!isset($_REQUEST['url'])){
			$json_api->error("You must include the redirect `url` value");
		}
		if(!isset($_REQUEST['target'])){
			$json_api->error("You must include the window `target` value");
    }
    if(!isset($_REQUEST['classes'])){
			$json_api->error("You must include the `classes` value");
		}

		$nonce_id = $json_api->get_nonce_id('menus', 'set_button');

		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
		}

		$data = array(
			'text'=>stripcslashes($_REQUEST['text']),
			'url'=>$_REQUEST['url'],
            'target'=>$_REQUEST['target'],
            'classes'=>$_REQUEST['classes']
			);

		$button = set_wysiwyg_button($_REQUEST['id'], $data);

		return array($button);
    }
    
	/**
	*Create a new wysiwyg button with all the specified values
	*@example URL - /wysiwyg_buttons/create_wysiwyg_button
	*@api
	*@return array containing the button created
	**/
	public function create_wysiwyg_button() {
		global $json_api;

		if(!isset($_REQUEST['text'])){
			$json_api->error("You must include the `text` value");
		}
		if(!isset($_REQUEST['url'])){
			$json_api->error("You must include the redirect `url` value");
		}
		if(!isset($_REQUEST['target'])){
			$json_api->error("You must include the window `target` value");
		}
		if(!isset($_REQUEST['classes'])){
			$json_api->error("You must include the `classes` value");
		}

		$nonce_id = $json_api->get_nonce_id('menus', 'create_button');

		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
		}
		$data = array(
			'text'=>stripcslashes($_REQUEST['text']),
			'url'=>$_REQUEST['url'],
			'target'=>$_REQUEST['target'],
			'classes'=>$_REQUEST['classes']
			);
		$button = create_wysiwyg_button($data);
		return array('button' => $button);
    }
    
    public function delete_wysiwyg_button() {
		global $json_api;

		if(!isset($_REQUEST['id'])){
			$json_api->error("You must include the `id` of the button to edit");
		}

		$nonce_id = $json_api->get_nonce_id('menus', 'delete_button');

		$nonce = wp_create_nonce($nonce_id);

		if(!wp_verify_nonce($nonce, $nonce_id)){
			$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
		}

		delete_wysiwyg_button($_REQUEST['id']);

		return array();
	}
}
