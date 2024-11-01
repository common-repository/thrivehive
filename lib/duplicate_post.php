<?php

define('POST_COPY_PREPEND_TEXT', 'COPY - ');

/**
 * Processes a post's content for proper duplication, such that any snippets within the post will
 * be duplicated (including generating new IDs and GUIDs) to diverge from the snippets in the original post.
 *
 * @param string $post_content The HTML of the post
 * @return string The updated HTML of the post, with unique GUIDs and IDs where appropriate
 */
function thrivehive_process_post_content_for_duplication($post_content) {
	$new_content = $post_content;
	$guids_outside_of_snippets_tokens = array();
	$snippet_tokens = array();

	preg_match_all(TH_GUID_PATTERN, $new_content, $guids_outside_of_snippets_tokens);
	$guids_outside_of_snippets_tokens = array_unique($guids_outside_of_snippets_tokens[0]);

	preg_match_all(TH_SNIPPET_PATTERN, $new_content, $snippet_tokens);
	$snippet_tokens = array_unique($snippet_tokens[0]);

	// Row (and potentially other) GUIDs are not stored as shortcodes in the post content,
	// so we can just replace them inline here
	foreach($guids_outside_of_snippets_tokens as $guid) {
		$new_guid = create_th_id_guid();
		$new_content = str_replace($guid, $new_guid, $new_content);
	}

	// Snippets are stored as shortcodes in the post content, so this loop results in each
	// snippet being rendered so GUID and ID replacement can take place
	foreach($snippet_tokens as $token) {
		$id = array();
		preg_match(TH_ID_PATTERN, $token, $id);

		$duplicated_snippet = duplicate_thrivehive_snippet($id[0]);
		$new_token = str_replace($id[0], $duplicated_snippet['id'], $token);
		$new_content = str_replace($token, $new_token, $new_content);
	}

	return $new_content;
}

// Adapted from code at: https://www.hostinger.com/tutorials/how-to-duplicate-wordpress-page-post
function duplicate_post_as_draft($post_id) {
	global $wpdb;

	$post = get_post($post_id);
 
	// From the hostinger implementation...we can either carry over the original post author,
	// or make the user who does the duplicating action be the author for the duplicated post

	// $current_user = wp_get_current_user();
	// $new_post_author = $current_user->ID;
	$new_post_author = $post->post_author;

	if (isset($post) && $post != null) {
		// See https://developer.wordpress.org/reference/functions/wp_insert_post/
		$args = array(
			'comment_status' => $post->comment_status,
			'ping_status'    => $post->ping_status,
			'post_author'    => $new_post_author,
			'post_content'   => thrivehive_process_post_content_for_duplication($post->post_content),
			'post_excerpt'   => $post->post_excerpt,
			'post_name'      => urlencode(POST_COPY_PREPEND_TEXT) . $post->post_name, // refers to the URL slug of the post
			'post_parent'    => $post->post_parent,
			'post_password'  => $post->post_password,
			'post_status'    => 'draft',
			'post_title'     => POST_COPY_PREPEND_TEXT . $post->post_title, // refers to the page title the user sees in the UI
			'post_type'      => $post->post_type,
			'to_ping'        => $post->to_ping,
			'menu_order'     => $post->menu_order
		);

		// Moment post is added to database
		$new_post_id = wp_insert_post($args);

		// Get all current post terms and set them to the new post draft
		$taxonomies = get_object_taxonomies($post->post_type); // returns array of taxonomy names for post type, ex array("category", "post_tag");
		foreach ($taxonomies as $taxonomy) {
			$post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
			wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
		}

		// Duplicate all post meta just in two SQL queries
		$post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id");
		if (count($post_meta_infos)!=0) {
			$sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
			foreach ($post_meta_infos as $meta_info) {
				$meta_key = $meta_info->meta_key;
				if( $meta_key == '_wp_old_slug' ) continue;
				$meta_value = addslashes($meta_info->meta_value);
				$sql_query_sel[]= "SELECT $new_post_id, '$meta_key', '$meta_value'";
			}
			$sql_query.= implode(" UNION ALL ", $sql_query_sel);
			$wpdb->query($sql_query);
		}

		return $new_post_id;
	}
	return false;
}

add_action( 'admin_action_duplicate_post_as_draft', 'duplicate_post_as_draft_from_admin' );
function duplicate_post_as_draft_from_admin() {
	if (!(isset( $_GET['post']) || isset($_POST['post']) || (isset($_REQUEST['action']) && 'duplicate_post_as_draft' == $_REQUEST['action']))) {
		wp_die('No post to duplicate has been supplied!');
	}

	// Nonce verification
	if (!isset( $_GET['duplicate_nonce'] ) || !wp_verify_nonce( $_GET['duplicate_nonce'], basename( __FILE__ ) )) {
		return;
	}

	// Get the original post id
	$post_id = (isset($_GET['post']) ? absint( $_GET['post'] ) : absint( $_POST['post'] ) );
	$new_post_id = duplicate_post_as_draft($post_id);

	if ($new_post_id) {
		// Redirect to the edit post screen for the new draft
		wp_redirect(admin_url('post.php?action=edit&post=' . $new_post_id));
		exit;
	} else {
		wp_die('Post creation failed, could not find original post: ' . $post_id);
	}
}

// Add a link to duplicate to each post in the list of posts in the WordPress admin
function add_duplicate_post_link( $actions, $post ) {
	if (current_user_can('edit_posts')) {
		$actions['duplicate'] = '<a href="' . wp_nonce_url('admin.php?action=duplicate_post_as_draft&post=' . $post->ID, basename(__FILE__), 'duplicate_nonce' ) . '" title="Duplicate this item" rel="permalink">Duplicate</a>';
	}
	return $actions;
}

add_filter( 'post_row_actions', 'add_duplicate_post_link', 10, 2 );
add_filter( 'page_row_actions', 'add_duplicate_post_link', 10, 2 );
?>
