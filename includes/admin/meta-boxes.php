<?php
/**
 * Delightful Downloads Metaboxes
 *
 * @package     Delightful Downloads
 * @subpackage  Admin/Metaboxes
 * @since       1.0
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Register Meta Boxes
 *
 * @since  1.0
 */
function dedo_register_meta_boxes() {
	add_meta_box( 'dedo_download', __( 'Download', 'delightful-downloads' ), 'dedo_meta_box_download', 'dedo_download', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'dedo_register_meta_boxes' );

/**
 * Add Correct Enc Type
 *
 * @since  1.0
 */
function dedo_form_enctype() {
	if ( get_post_type() == 'dedo_download' ) {
		echo ' enctype="multipart/form-data"';
	}
}
add_action( 'post_edit_form_tag', 'dedo_form_enctype' );

/**
 * Add post type custom update messages
 *
 * @since  1.3.8
 */
function dedo_update_messages( $messages ) {
	global $post, $post_ID;

	$messages['dedo_download'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => sprintf( __('Download updated. Use the %s shortcode to include it in posts or pages.', 'delightful-downloads'), '<code>[ddownload id="' . $post_ID . '"]</code>' ),
		2 => __('Custom field updated.', 'delightful-downloads'),
		3 => __('Custom field deleted.', 'delightful-downloads'),
		4 => sprintf( __('Download updated. Use the %s shortcode to include it in posts or pages.', 'delightful-downloads'), '<code>[ddownload id="' . $post_ID . '"]</code>' ),
		5 => isset($_GET['revision']) ? sprintf( __('Download restored to revision from %s', 'delightful-downloads'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => sprintf( __('Download published. Use the %s shortcode to include it in posts or pages.', 'delightful-downloads'), '<code>[ddownload id="' . $post_ID . '"]</code>' ),
		7 => __('Download saved.', 'delightful-downloads'),
		8 => __('Download submitted.', 'delightful-downloads'),
		9 => sprintf( __('Download scheduled for: <strong>%1$s</strong>.', 'delightful-downloads'),
		  date_i18n( __( 'M j, Y @ G:i', 'delightful-downloads' ), strtotime( $post->post_date ) ) ),
		10 => __('Download draft updated.', 'delightful-downloads'),
	);

	return $messages;
}
add_action( 'post_updated_messages', 'dedo_update_messages' );

/**
 * Render Download Metabox
 *
 * @since  1.5
 */
function dedo_meta_box_download( $post ) {
	$file = get_post_meta( $post->ID, '_dedo_file', true );

	$args = array(
		'ajaxURL'	=> admin_url( 'admin-ajax.php', isset( $_SERVER['HTTPS'] ) ? 'https://' : 'http://' ),
		'nonce' 	=> wp_create_nonce( 'dedo_download_update_status' ),
		'action'    => 'dedo_download_update_status'
	);

	?>

	<script type="text/javascript">
		var updateStatusArgs = <?php echo json_encode( $args ); ?>;
	</script>

	<?php wp_nonce_field( 'ddownload_file_save', 'ddownload_file_save_nonce' ); ?>
	
	<div id="dedo-new-download" style="<?php echo ( !isset( $file['download_url'] ) || empty( $file['download_url'] ) ) ? 'display: block;' : 'display: none;'; ?>">		
		<a href="#dedo-upload-modal" class="button dedo-modal-action"><?php _e( 'Upload File', 'delightful-downloads' ); ?></a>
		<a href="#dedo-select-modal" class="button dedo-modal-action select-existing"><?php _e( 'Existing File', 'delightful-downloads' ); ?></a>
	</div>

	<div id="dedo-existing-download" style="<?php echo ( isset( $file['download_url'] ) && !empty( $file['download_url'] ) ) ? 'display: block;' : 'display: none;'; ?>">		
		<div class="file-icon">	
			<img src="<?php echo DEDO_PLUGIN_URL . './assets/icons/zip.png'; ?>" />
		</div>
		<div class="file-name">delightful-downloads.zip</div>
		<div class="file-size">1.0 MB</div>
		<div class="file-actions">
			<a href="#" id="dedo-edit" title="<?php _e( 'Edit', 'delightful-downloads' ); ?>"></a>
			<a href="#" id="dedo-delete" title="<?php _e( 'Delete', 'delightful-downloads' ); ?>"></a>
			<input type="text" name="dedo-file-url" id="dedo-file-url" class="large-text" value="" placeholder="<?php _e( 'File URL or path...', 'delightful-downloads' ); ?>" style="display: none;" />
		</div>
	</div>

	<?php
	
}

/**
 * Render Upload Modal
 *
 * @since  1.5
 */
function dedo_render_part_upload() {

	global $post;

	// Ensure only added on add/edit screen
	$screen = get_current_screen();

	if ( 'post' !== $screen->base || 'dedo_download' !== $screen->post_type ) {
		return;
	}

	$plupload_init = array(
		'runtimes'            => 'html5, silverlight, flash, html4',
		'browse_button'       => 'dedo-upload-button',
		'container'           => 'dedo-upload-container',
		'drop_element'		  => 'dedo-drag-drop-area',
		'file_data_name'      => 'async-upload',            
		'multiple_queues'     => false,
		'multi_selection'	  => false,
		'max_file_size'       => wp_max_upload_size() . 'b',
		'url'                 => admin_url( 'admin-ajax.php' ),
		'flash_swf_url'       => includes_url( 'js/plupload/plupload.flash.swf' ),
		'silverlight_xap_url' => includes_url( 'js/plupload/plupload.silverlight.xap' ),
		'filters'             => array( array( 'title' => __( 'Allowed Files' ), 'extensions' => '*' ) ),
		'multipart'           => true,
		'urlstream_upload'    => true,

		// additional post data to send to our ajax hook
		'multipart_params'    => array(
			'_ajax_nonce' 		=> wp_create_nonce( 'dedo_download_upload' ),
			'action'      		=> 'dedo_download_upload',
			'post_id'			=> $post->ID
		)
	);

	?>

	<script type="text/javascript">
		var plupload_args = <?php echo json_encode( $plupload_init ); ?>;
	</script>

	<div id="dedo-upload-modal" class="dedo-modal" style="display: none; width: 40%; left: 50%; margin-left: -20%;">
		<a href="#" class="dedo-modal-close" title="Close"><span class="media-modal-icon"></span></a>
		<div id="dedo-upload-container" class="dedo-modal-content">
			<h1><?php _e( 'Upload File', 'delightful-downloads' ); ?></h1>
			<div id="dedo-drag-drop-area">
				<p class="drag-drop-info"><?php _e( 'Drop file here', 'delightful-downloads' ); ?></p>
				<p><?php _e( 'or', 'delightful-downloads' ); ?></p>
				<p class="drag-drop-button"><input id="dedo-upload-button" type="button" value="<?php _e( 'Select File', 'delightful-downloads' ); ?>" class="button" />
			</div>
			<p><?php printf( __( 'Maximum upload file size: %s.', 'delightful-downloads' ), size_format( wp_max_upload_size(), 1 ) ); ?></p>
			<div id="dedo-progress-bar" style="display: none">
				<div id="dedo-progress-percent" style="width: 0%;"></div>
				<div id="dedo-progress-text">0%</div>
			</div>
			<div id="dedo-progress-error" style="display: none"></div>
		</div>
	</div>

	<?php

}
add_action( 'admin_footer', 'dedo_render_part_upload' );

/**
 * Render Select Modal
 *
 * @since  1.5
 */
function dedo_render_part_select() {

	// Ensure only added on add/edit screen
	$screen = get_current_screen();

	if ( 'post' !== $screen->base || 'dedo_download' !== $screen->post_type ) {
		return;
	}

	// File browser args
	$filebrowser_init = array(
		'root'			=> dedo_get_upload_dir( 'basedir' ) . '/',
		'url'			=> dedo_get_upload_dir( 'baseurl' ) . '/',
		'script'		=> DEDO_PLUGIN_URL . 'assets/js/jqueryFileTree/connectors/jqueryFileTree.php'
	);

	?>

	<script type="text/javascript">
		var filebrowser_args = <?php echo json_encode( $filebrowser_init ); ?>;
	</script>

	<div id="dedo-select-modal" class="dedo-modal" style="display: none; width: 40%; left: 50%; margin-left: -20%;">
		<a href="#" class="dedo-modal-close" title="Close"><span class="media-modal-icon"></span></a>
		<div class="dedo-modal-content">
			<h1><?php _e( 'Existing File', 'delightful-downloads' ); ?></h1>
			<p><?php _e( 'Manaully enter a file URL, or use the file browser.', 'delightful-downloads' ); ?></p>
			<p>	
				<input name="dedo-select-url" id="dedo-select-url" type="url" class="large-text" placeholder="<?php _e( 'File URL or path...', 'delightful-downloads' ); ?>" />
			</p>
			<p>
				<div id="dedo-file-browser"><p><?php _e( 'Loading...', 'delightful-downloads' ); ?></p></div>
			</p>
			<p>
				<a href="#" id="dedo-select-done" class="button button-primary"><?php _e( 'Confirm', 'delightful-downloads' ); ?></a>
			</p>
		</div>
	</div>

	<?php

}
add_action( 'admin_footer', 'dedo_render_part_select' );

/**
 * Update Status Ajax
 *
 * @since  1.5
*/
function dedo_download_update_status_ajax() {

	global $dedo_statistics;

	// Check for nonce and permission
	if ( !check_ajax_referer( 'dedo_download_update_status', 'nonce', false ) || !current_user_can( apply_filters( 'dedo_cap_add_new', 'edit_pages' ) ) ) {
		echo json_encode( array(
			'status'	=> 'error',
			'content'	=> __( 'Failed security check!', 'delightful-downloads' )
		) );

		die();
	}

	if( $result = dedo_get_file_status( trim( $_REQUEST['url'] ) ) ) {
		// Cache remote file sizes, for 15 mins
		if ( 'remote' === $result['type'] ) {
			$cached_remotes = get_transient( 'dedo_remote_file_sizes' );

			if ( false === $cached_remotes || !isset( $cached_remotes[esc_url_raw( $_REQUEST['url'] )] ) ) {
				$cached_remotes[esc_url_raw( $_REQUEST['url'] )] = $result['size'];
				set_transient( 'dedo_remote_file_sizes', $cached_remotes, 900 );
			}
		}

		// Format filesize
		$result['size'] = size_format( $result['size'], 1 );

		// Exists
		echo json_encode( array (
			'status'	=> 'success',
			'content'	=> $result
		) );
	}
	else {
		echo json_encode( array (
			'status'	=> 'error',
			'content'	=> __( 'File not accessible!', 'delightful-downloads' )
		) );
	}

	die();
}
add_action( 'wp_ajax_dedo_download_update_status', 'dedo_download_update_status_ajax' );

/**
 * Save Meta Boxes
 *
 * @since  1.0
 */
function dedo_meta_boxes_save( $post_id ) {
	// Check for autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	
	// Check for other post types
	if ( isset( $post->post_type ) && $post->post_type != 'dedo_download' ) {
		return;
	}

	// Check user has permission
	if ( !current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	
	// Check for file nonce
	if ( isset( $_POST['ddownload_file_save_nonce'] ) && wp_verify_nonce( $_POST['ddownload_file_save_nonce'], 'ddownload_file_save' ) ) {	
		// Get original meta
		$file = get_post_meta( $post_id, '_dedo_file', true );

		// Get cached remote file sizes
		$cached_remotes = get_transient( 'dedo_remote_file_sizes' );

		// Save file url
		$file['files'] = array();

		foreach ( $_POST['dedo-file-url'] as $file_url ) {
			$file_url = trim( $file_url );
			
			// Check for cached or get size
			if ( false === $cached_remotes || !isset( $cached_remotes[esc_url_raw( $file_url )] ) ) {
				$file_status = dedo_get_file_status( $file_url );
			}
			else {
				$file_status['size'] = $cached_remotes[esc_url_raw( $file_url )];
				$file_status['type'] = 'remote';
			}

			// Build files array
			$file['files'][] = array(
				'url'	=> $file_url,
				'size'	=> $file_status['size'],
				'type'	=> $file_status['type']
			);
		}

		$download = array();

		// Set correct download URL
		if ( count( $file['files'] > 0 ) ) {
			// Defaults to first item
			$download = apply_filters( 'dedo_save_download_url', array(
				'download_url' 	=> $file['files'][0]['url'],
				'download_size' => $file['files'][0]['size'],
				'download_type' => $file['files'][0]['type'],
			), $file );
		}

		// Join arrays and save
		update_post_meta( $post_id, '_dedo_file', $download + $file );
	}
}
add_action( 'save_post', 'dedo_meta_boxes_save' );