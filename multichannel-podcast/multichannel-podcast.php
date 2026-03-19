<?php
/*
Plugin Name: Multichannel Podcast
Description: A modern plugin to publish multiple podcast channels using Custom Post Types and Taxonomies.
Version: 1.0.0
Author: Jules
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Podcast Episode Post Type and Podcast Channel Taxonomy
 */
function mc_podcast_init() {
	// Register Custom Taxonomy: Podcast Channel
	$taxonomy_labels = array(
		'name'              => 'Podcast Channels',
		'singular_name'     => 'Podcast Channel',
		'search_items'      => 'Search Channels',
		'all_items'         => 'All Channels',
		'parent_item'       => 'Parent Channel',
		'parent_item_colon' => 'Parent Channel:',
		'edit_item'         => 'Edit Channel',
		'update_item'       => 'Update Channel',
		'add_new_item'      => 'Add New Channel',
		'new_item_name'     => 'New Channel Name',
		'menu_name'         => 'Channels',
	);

	$taxonomy_args = array(
		'hierarchical'      => true,
		'labels'            => $taxonomy_labels,
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'channel' ),
	);

	register_taxonomy( 'podcast_channel', array( 'podcast_episode' ), $taxonomy_args );

	// Register Custom Post Type: Podcast Episode
	$cpt_labels = array(
		'name'               => 'Podcast Episodes',
		'singular_name'      => 'Episode',
		'menu_name'          => 'Episodes',
		'name_admin_bar'     => 'Episode',
		'add_new'            => 'Add New',
		'add_new_item'       => 'Add New Episode',
		'new_item'           => 'New Episode',
		'edit_item'          => 'Edit Episode',
		'view_item'          => 'View Episode',
		'all_items'          => 'All Episodes',
		'search_items'       => 'Search Episodes',
		'parent_item_colon'  => 'Parent Episodes:',
		'not_found'          => 'No episodes found.',
		'not_found_in_trash' => 'No episodes found in Trash.',
	);

	$cpt_args = array(
		'labels'             => $cpt_labels,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'episode' ),
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => 5,
		'menu_icon'          => 'dashicons-microphone',
		'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
		'taxonomies'         => array( 'podcast_channel' ),
	);

	register_post_type( 'podcast_episode', $cpt_args );
}
add_action( 'init', 'mc_podcast_init' );

/**
 * Add Meta Boxes for Podcast Episode
 */
function mc_podcast_add_meta_boxes() {
	add_meta_box(
		'mc_podcast_media_meta',
		'Episode Media Info',
		'mc_podcast_media_meta_callback',
		'podcast_episode',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'mc_podcast_add_meta_boxes' );

function mc_podcast_media_meta_callback( $post ) {
	wp_nonce_field( 'mc_podcast_save_media_meta', 'mc_podcast_nonce' );

	$audio_url = get_post_meta( $post->ID, '_mc_audio_url', true );
	$duration  = get_post_meta( $post->ID, '_mc_duration', true );
	$filesize  = get_post_meta( $post->ID, '_mc_filesize', true );

	echo '<p><label for="mc_audio_url">Audio File URL:</label><br />';
	echo '<input type="text" id="mc_audio_url" name="mc_audio_url" value="' . esc_attr( $audio_url ) . '" size="50" /></p>';

	echo '<p><label for="mc_duration">Duration (e.g. 00:30:00):</label><br />';
	echo '<input type="text" id="mc_duration" name="mc_duration" value="' . esc_attr( $duration ) . '" size="20" /></p>';

	echo '<p><label for="mc_filesize">File Size (bytes):</label><br />';
	echo '<input type="text" id="mc_filesize" name="mc_filesize" value="' . esc_attr( $filesize ) . '" size="20" /></p>';
}

/**
 * Save Meta Box Data
 */
function mc_podcast_save_meta_box_data( $post_id ) {
	if ( ! isset( $_POST['mc_podcast_nonce'] ) || ! wp_verify_nonce( $_POST['mc_podcast_nonce'], 'mc_podcast_save_media_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['mc_audio_url'] ) ) {
		update_post_meta( $post_id, '_mc_audio_url', esc_url_raw( $_POST['mc_audio_url'] ) );
	}

	if ( isset( $_POST['mc_duration'] ) ) {
		update_post_meta( $post_id, '_mc_duration', sanitize_text_field( $_POST['mc_duration'] ) );
	}

	if ( isset( $_POST['mc_filesize'] ) ) {
		update_post_meta( $post_id, '_mc_filesize', sanitize_text_field( $_POST['mc_filesize'] ) );
	}
}
add_action( 'save_post', 'mc_podcast_save_meta_box_data' );

/**
 * Add Custom Fields to Podcast Channel Taxonomy
 */
function mc_podcast_channel_add_form_fields() {
	wp_nonce_field( 'mc_podcast_save_channel_meta', 'mc_podcast_channel_nonce' );
	?>
	<div class="form-field">
		<label for="mc_itunes_image">iTunes Image URL</label>
		<input type="text" name="mc_itunes_image" id="mc_itunes_image" value="">
		<p>The image for the podcast channel (1400x1400px recommended).</p>
	</div>
	<div class="form-field">
		<label for="mc_itunes_category">iTunes Category</label>
		<input type="text" name="mc_itunes_category" id="mc_itunes_category" value="">
		<p>Primary category for iTunes (e.g., Technology).</p>
	</div>
	<div class="form-field">
		<label for="mc_itunes_summary">iTunes Summary</label>
		<textarea name="mc_itunes_summary" id="mc_itunes_summary"></textarea>
		<p>A description of your podcast channel.</p>
	</div>
	<?php
}
add_action( 'podcast_channel_add_form_fields', 'mc_podcast_channel_add_form_fields' );

function mc_podcast_channel_edit_form_fields( $term ) {
	wp_nonce_field( 'mc_podcast_save_channel_meta', 'mc_podcast_channel_nonce' );
	$itunes_image    = get_term_meta( $term->term_id, 'mc_itunes_image', true );
	$itunes_category = get_term_meta( $term->term_id, 'mc_itunes_category', true );
	$itunes_summary  = get_term_meta( $term->term_id, 'mc_itunes_summary', true );
	?>
	<tr class="form-field">
		<th scope="row"><label for="mc_itunes_image">iTunes Image URL</label></th>
		<td>
			<input type="text" name="mc_itunes_image" id="mc_itunes_image" value="<?php echo esc_attr( $itunes_image ); ?>">
			<p class="description">The image for the podcast channel (1400x1400px recommended).</p>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="mc_itunes_category">iTunes Category</label></th>
		<td>
			<input type="text" name="mc_itunes_category" id="mc_itunes_category" value="<?php echo esc_attr( $itunes_category ); ?>">
			<p class="description">Primary category for iTunes (e.g., Technology).</p>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="mc_itunes_summary">iTunes Summary</label></th>
		<td>
			<textarea name="mc_itunes_summary" id="mc_itunes_summary"><?php echo esc_textarea( $itunes_summary ); ?></textarea>
			<p class="description">A description of your podcast channel.</p>
		</td>
	</tr>
	<?php
}
add_action( 'podcast_channel_edit_form_fields', 'mc_podcast_channel_edit_form_fields' );

/**
 * Save Taxonomy Custom Fields
 */
function mc_podcast_save_channel_fields( $term_id ) {
	if ( ! isset( $_POST['mc_podcast_channel_nonce'] ) || ! wp_verify_nonce( $_POST['mc_podcast_channel_nonce'], 'mc_podcast_save_channel_meta' ) ) {
		return;
	}

	if ( isset( $_POST['mc_itunes_image'] ) ) {
		update_term_meta( $term_id, 'mc_itunes_image', esc_url_raw( $_POST['mc_itunes_image'] ) );
	}
	if ( isset( $_POST['mc_itunes_category'] ) ) {
		update_term_meta( $term_id, 'mc_itunes_category', sanitize_text_field( $_POST['mc_itunes_category'] ) );
	}
	if ( isset( $_POST['mc_itunes_summary'] ) ) {
		update_term_meta( $term_id, 'mc_itunes_summary', sanitize_textarea_field( $_POST['mc_itunes_summary'] ) );
	}
}
add_action( 'edited_podcast_channel', 'mc_podcast_save_channel_fields' );
add_action( 'create_podcast_channel', 'mc_podcast_save_channel_fields' );

/**
 * Customize RSS Feeds for Podcast Channels
 */
function mc_podcast_rss_namespaces() {
	echo 'xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" ';
	echo 'xmlns:media="http://search.yahoo.com/mrss/" ';
}
add_action( 'rss2_ns', 'mc_podcast_rss_namespaces' );

function mc_podcast_rss_head() {
	if ( is_tax( 'podcast_channel' ) ) {
		$term = get_queried_object();
		$itunes_image    = get_term_meta( $term->term_id, 'mc_itunes_image', true );
		$itunes_category = get_term_meta( $term->term_id, 'mc_itunes_category', true );
		$itunes_summary  = get_term_meta( $term->term_id, 'mc_itunes_summary', true );

		if ( $itunes_summary ) {
			echo '<itunes:summary>' . esc_html( $itunes_summary ) . '</itunes:summary>' . "\n";
		}
		if ( $itunes_image ) {
			echo '<itunes:image href="' . esc_url( $itunes_image ) . '" />' . "\n";
		}
		if ( $itunes_category ) {
			echo '<itunes:category text="' . esc_attr( $itunes_category ) . '" />' . "\n";
		}
		echo '<itunes:author>' . esc_html( get_bloginfo( 'name' ) ) . '</itunes:author>' . "\n";
	}
}
add_action( 'rss2_head', 'mc_podcast_rss_head' );

function mc_podcast_rss_item() {
	global $post;
	if ( get_post_type( $post ) === 'podcast_episode' ) {
		$audio_url = get_post_meta( $post->ID, '_mc_audio_url', true );
		$duration  = get_post_meta( $post->ID, '_mc_duration', true );
		$filesize  = get_post_meta( $post->ID, '_mc_filesize', true );

		if ( $audio_url ) {
			echo '<enclosure url="' . esc_url( $audio_url ) . '" length="' . esc_attr( $filesize ) . '" type="audio/mpeg" />' . "\n";
			echo '<itunes:duration>' . esc_html( $duration ) . '</itunes:duration>' . "\n";
		}
	}
}
add_action( 'rss2_item', 'mc_podcast_rss_item' );

/**
 * Add Audio Player to Episode Content
 */
function mc_podcast_add_player_to_content( $content ) {
	if ( is_singular( 'podcast_episode' ) ) {
		$audio_url = get_post_meta( get_the_ID(), '_mc_audio_url', true );
		if ( $audio_url ) {
			$player = '<div class="mc-podcast-player" style="margin-bottom: 20px;">';
			$player .= '<h3>Listen to this Episode</h3>';
			$player .= '<audio controls style="width: 100%;">';
			$player .= '<source src="' . esc_url( $audio_url ) . '" type="audio/mpeg">';
			$player .= 'Your browser does not support the audio element.';
			$player .= '</audio>';
			$player .= '</div>';
			$content = $player . $content;
		}
	}
	return $content;
}
add_filter( 'the_content', 'mc_podcast_add_player_to_content' );

/**
 * Metadata auto-detection using getID3
 */
function mc_podcast_get_media_info( $file_path ) {
	if ( ! file_exists( $file_path ) ) {
		return false;
	}

	$getid3_path = dirname( dirname( __FILE__ ) ) . '/getid3/getid3.php';
	if ( ! file_exists( $getid3_path ) ) {
		return false;
	}

	require_once $getid3_path;
	$getID3 = new getID3;
	$file_info = $getID3->analyze( $file_path );

	if ( isset( $file_info['error'] ) ) {
		return false;
	}

	return array(
		'duration' => isset( $file_info['playtime_string'] ) ? $file_info['playtime_string'] : '00:00',
		'filesize' => isset( $file_info['filesize'] ) ? $file_info['filesize'] : 0,
	);
}
