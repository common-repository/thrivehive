<?php

// Regex constants for finding and replacing unique IDs and GUIDs
define('TH_IDS_WITH_GUIDS_PATTERN', "/((?!id=)[^'\"{#\n\t\r\s]*-([a-f]|[0-9]){8}-([a-f]|[0-9]){4}-([a-f]|[0-9]){4}-([a-f]|[0-9]){4}-([a-f]|[0-9]){12})/i");
define('TH_GUID_PATTERN', "/([a-f]|[0-9]){8}-([a-f]|[0-9]){4}-([a-f]|[0-9]){4}-([a-f]|[0-9]){4}-([a-f]|[0-9]){12}/i");
define('TH_SNIPPET_PATTERN', "/(\\[th_snippet id=[\"'])[0-9]+([\"']\\])/i");
define('TH_ID_PATTERN', "/(\\d+)/");

// Standard CRUD data-access methods for HTML snippets
// Used both in the json api snippets controller and in rendering the snippet shortcode
function get_thrivehive_snippets() {
    global $wpdb;
    $query = "SELECT * FROM " . thrivehive_snippets_table_name() . ";";
    $res = $wpdb->get_results($query, ARRAY_A);
    return array_map("convert_from_db_snippet", $res);
}

function get_thrivehive_snippet($id) {
    global $wpdb;
    $query_template = "SELECT * FROM " . thrivehive_snippets_table_name() . " WHERE id = %d;";
    $query = $wpdb->prepare($query_template, $id);
    $snippet = $wpdb->get_row($query, ARRAY_A);
    return convert_from_db_snippet($snippet);
}

function create_thrivehive_snippet($snippet_data) {
    global $wpdb;
    $table_name = thrivehive_snippets_table_name();
    $success = $wpdb->insert($table_name, $snippet_data);
    if ($success === FALSE) {
        return FALSE;
    } else {
        return convert_from_db_snippet(get_thrivehive_snippet($wpdb->insert_id));
    }
}

function update_thrivehive_snippet($snippet_data) {
    global $wpdb;
    $table_name = thrivehive_snippets_table_name();
    $id = $snippet_data["id"];
    $where = array("id" => $id);
    $success = $wpdb->update($table_name, $snippet_data, $where);
    if ($success === FALSE) {
        return FALSE;
    } else {
        return convert_from_db_snippet(get_thrivehive_snippet($id));
    }
}

function delete_thrivehive_snippet($id) {
    global $wpdb;
    $table_name = thrivehive_snippets_table_name();
    $where = array("id" => $id);
    return $wpdb->delete($table_name, $where);
}

function thrivehive_snippets_table_name(){
	global $wpdb;
	return $wpdb->prefix . "TH_snippets";
}

function convert_from_db_snippet($snippet) {
    if ($snippet === NULL) {
        return NULL;
    } else {
        // The mysql driver yields all values as strings, so we need to convert some of them to fit the desired API
        $snippet['locked'] = string_to_boolean($snippet['locked']);
        $snippet['previewable'] = string_to_boolean($snippet['previewable']);
        $snippet['id'] = intval($snippet['id']);
        return $snippet;
    }
}

function string_to_boolean($val) {
    return $val == "1" ? TRUE : FALSE;
}

/**
 * Duplicates a thrivehive snippet of the form ([th_snippet id="x"])
 *
 * @param int $id_to_duplicate The ID of a snippet to duplicate
 * @return snippet or an error in case of failure
 */
function duplicate_thrivehive_snippet($id_to_duplicate) {
  $existing_snippet = get_thrivehive_snippet($id_to_duplicate);
  unset($existing_snippet['id']);
  $unique_snippet_data = generate_unique_content_ids($existing_snippet);
  $duplicated_snippet = create_thrivehive_snippet($unique_snippet_data);
  
  return $duplicated_snippet;
}

// Our GUIDs are formed by 4 random groups of numbers/strings, joined by a dash
function create_th_id_guid() {
  return 
    bin2hex(openssl_random_pseudo_bytes(4)) .
    '-' .
    bin2hex(openssl_random_pseudo_bytes(2)) .
    '-' .
    bin2hex(openssl_random_pseudo_bytes(2)) .
    '-' .
    bin2hex(openssl_random_pseudo_bytes(6));
}

function replace_snippet_content($content, $content_to_replace, $replacement_content) {
  return str_replace($content_to_replace, $replacement_content, $content);
}

/**
 * Finds and replaces all GUIDs within a snippet with new ones
 *
 * @param array $snippet Associative array of a snippet, including its rendered HTML content
 * @return array The associative array of the snippet with new GUIDs
 */
function generate_unique_content_ids($snippet) {
  $ids_with_guids_matches = array();
  preg_match_all(TH_IDS_WITH_GUIDS_PATTERN, $snippet['html'], $ids_with_guids_matches);
  $ids_with_guids_matches = array_unique($ids_with_guids_matches);

  foreach ($ids_with_guids_matches[0] as $id_to_replace) {
    $guid = create_th_id_guid();
    $scrubbed_id = preg_replace(TH_GUID_PATTERN, '', $id_to_replace);
    $new_id = $scrubbed_id . $guid;

    $snippet['html'] = replace_snippet_content($snippet['html'], $id_to_replace, $new_id);
    $snippet['css'] = replace_snippet_content($snippet['css'], $id_to_replace, $new_id);
    $snippet['javascript'] = replace_snippet_content($snippet['javascript'], $id_to_replace, $new_id);
    $snippet['token_values'] = replace_snippet_content($snippet['token_values'], $id_to_replace, $new_id);
    $snippet['rendered_source'] = replace_snippet_content($snippet['rendered_source'], $id_to_replace, $new_id);
  }

  return $snippet;
}

?>
