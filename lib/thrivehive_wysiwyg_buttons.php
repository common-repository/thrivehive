<?php
	/**
	*Retrieves the desired wysiwyg button from the database
	*@param int $id id of the button to fetch
	*@return array containing row data for the button
	**/
	function get_wysiwyg_button($id){
		global $wpdb;
		$table_name = wysiwyg_table_name();
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $table_name . " WHERE id = %d;", $id), ARRAY_A);
	}

	/**
	*Retrieves all wysiwyg buttons from the database
	*@return array containing row data for the buttons
	**/
	function get_wysiwyg_buttons(){
		global $wpdb;
		$table_name = wysiwyg_table_name();
		return $wpdb->get_results($wpdb->prepare("SELECT * FROM " . $table_name), ARRAY_A);
	}

	/**
	*Sets the data for an existing wysiwyg button to the specified values
	*@param int $id id of the button to update
	*@param mixed[] $data the date to set the button's values to
	*@return array containing row data for the button
	**/
	function set_wysiwyg_button($id, $data){
		global $wpdb;
		$table_name = wysiwyg_table_name();
		$wpdb->query($wpdb->prepare("UPDATE " . $table_name . " SET text = %s,
																url = %s,
																target = %s,
																classes = %s WHERE id = %d ", 
																$data['text'],
																$data['url'],
																$data['target'],
																$data['classes'], $id));
		return get_wysiwyg_button($id);
	}

	/**
	*Create a new wysiwyg button
	*@param mixed[] $data the date to set the button's values to
	*@return array containing row data for the button
	**/
	function create_wysiwyg_button($data){
		global $wpdb;
		$table_name = wysiwyg_table_name();
		$wpdb->insert($table_name, $data);
		return get_wysiwyg_button($wpdb->insert_id);
	}

	function delete_wysiwyg_button($id){
		global $wpdb;
		$table_name = wysiwyg_table_name();
		$wpdb->delete($table_name, array('id' => $id));
	}

	/**
	*Gets the string for the button table name
	*@return string the table name
	**/
	function wysiwyg_table_name(){
		global $wpdb;
		return $wpdb->prefix . "TH_" . "wysiwyg_buttons";
	}