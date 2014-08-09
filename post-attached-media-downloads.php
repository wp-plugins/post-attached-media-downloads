<?php
/**
 * Plugin Name: Post Attached Media Downloads
 * Plugin URI: http://wordpress.org/plugins/post-attached-media-downloads/
 * Description: Create simple download lists for use in your posts or theme
 * Version: 1.2
 * Author: Clorith
 * Text Domain: post-attached-media-downloads
 * Author URI: http://www.clorith.net
 * License: GPL2
 *
 * Copyright 2014 Marius Jensen (email : marius@jits.no)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Class pamd
 *
 * Post Attached Media Downloads parent class
 */
class pamd {
	/**
	 * Class construct
	 *
	 * Set up WordPress hooks used by the plugin
	 *
	 * @since 1.0.0
	 */
	function __construct() {
		/**
		 * Shortcode hook
		 */
		add_shortcode( 'pamd', array( $this, 'pamd_shortcode' ) );

		/**
		 * TinyMCE hooks
		 */
		add_action( 'admin_head', array( $this, 'pamd_tinymce' ) );

		/**
		 * JavaScript and enqueue hooks
		 */
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'admin_print_footer_scripts', array( $this, 'pamd_quicktag' ) );

		/**
		 * Meta hooks
		 */
		add_action( 'load-post.php', array( $this, 'meta_init' ) );
		add_action( 'load-post-new.php', array( $this, 'meta_init' ) );

		/**
		 * Ajax hooks
		 */
		add_action( 'wp_ajax_pamd_append_list', array( $this, 'ajax_update_list' ) );
		add_action( 'wp_ajax_pamd_remove_entry', array( $this, 'ajax_remove_entry' ) );
		add_action( 'wp_ajax_pamd_update_list_order', array( $this, 'ajax_update_list_order' ) );
		add_action( 'wp_ajax_pamd_update_label', array( $this, 'ajax_update_label' ) );
	}

	/**
	 * Hooks dependant on a parent hook to load at the appropriate time
	 *
	 * @since 1.0.0
	 */
	function meta_init() {
		add_action( 'add_meta_boxes', array( $this, 'meta_add' ) );
	}

	/**
	 * Register and enqueue scripts and styles
	 *
	 * @since 1.0.0
	 */
	function admin_scripts() {
		wp_register_style( 'pamd-editor', plugin_dir_url( __FILE__ ) . '/resources/css/editor.css', array(), '1.0.1' );
		wp_register_script( 'pamd-editor', plugin_dir_url( __FILE__ ) . '/resources/js/editor.js', array( 'jquery', 'jquery-ui-sortable' ), '1.0.1' );

		/**
		 * Add javascript localization
		 */
		$translations = array(
			'edit_link'      => __( 'Edit label', 'post-attached-media-downloads' ),
			'delete_link'    => __( 'Remove', 'post-attached-media-downloads' ),
			'edit_help_text' => __( 'Enter the new download label below:', 'post-attached-media-downloads' )
		);
		wp_localize_script( 'pamd-editor', 'pamd', $translations );

		wp_enqueue_style( 'pamd-editor' );
		wp_enqueue_script( 'pamd-editor' );
	}

	/**
	 * Include buttons for the TinyMCE editor if the user has the appropriate capabilities
	 *
	 * @since 1.2
	 */
	function pamd_tinymce() {
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
			return;
		}

		/**
		 *
		 */
		if ( 'true' == get_user_option( 'rich_editing' ) ) {
			add_filter( 'mce_external_plugins', array( $this, 'pamd_add_tinymce_plugin' ) );
			add_filter( 'mce_buttons', array( $this, 'pamd_register_mce_button' ) );
		}
	}

	/**
	 * Filter for queuing our TinyMCE plugin code through core
	 *
	 * @param array $plugins
	 *
	 * @since 1.2
	 *
	 * @return array
	 */
	function pamd_add_tinymce_plugin( $plugins ) {
		$plugins['pamd_mce_button'] = plugin_dir_url( __FILE__ ) . '/resources/js/tinymce.plugin.js';

		return $plugins;
	}

	/**
	 * Filter used to include our button with the rest of the TinyMCE buttons
	 *
	 * @param array $buttons
	 *
	 * @since 1.2
	 *
	 * @return array
	 */
	function pamd_register_mce_button( $buttons ) {
		array_push( $buttons, 'pamd_mce_button' );

		return $buttons;
	}

	/**
	 * Ajax call handler used when updating a download label
	 *
	 * @return int|void
	 */
	function ajax_update_label() {
		/**
		 * Check if the user is allowed to edit the post in question
		 */
		if ( ! current_user_can( 'edit_post', $_POST['post_id'] ) ) { return -1; }

		/**
		 * Fetch the post meta holding our download list
		 */
		$media = get_post_meta( $_POST['post_id'], '_pamd_files', true );
		if ( empty( $media ) ) {
			/**
			 * If the meta is empty, we set our variable as an array
			 */
			$media = array();
		}

		if ( isset( $media[$_POST['pamd_id']] ) ) {
			$media[$_POST['pamd_id']]['label'] = $_POST['new_label'];
		}

		/**
		 * Update the post meta with the relabeled media entry
		 */
		update_post_meta( $_POST['post_id'], '_pamd_files', $media );

		die();
	}

	/**
	 * Ajax call handler used to receive the new order of downloads after drag and drop events
	 *
	 * @since 1.1.0
	 *
	 * return int|void
	 */
	function ajax_update_list_order() {
		/**
		 * Check if the user is allowed to edit the post in question
		 */
		if ( ! current_user_can( 'edit_post', $_POST['post_id'] ) ) { return -1; }

		/**
		 * Fetch the post meta holding our download list
		 */
		$media = get_post_meta( $_POST['post_id'], '_pamd_files', true );
		if ( empty( $media ) ) {
			/**
			 * If the meta is empty, we set our variable as an array
			 */
			$media = array();
		}

		/**
		 * Prepare the array for our new ordered list
		 */
		$new_media = array();

		/**
		 * Explode the list, we sent a comma separated list via JS
		 */
		$new_order = explode( ",", $_POST['pamd_ids'] );

		/**
		 * Loop over the new order and set the new media array accordingly
		 */
		foreach( $new_order AS $onum ) {
			/**
			 * Skip any non-existent entries (could happen if multiple users are editing at once)
			 */
			if ( isset( $media[$onum] ) ) {
				$new_media[] = $media[$onum];
			}
		}

		/**
		 * Update the post meta with the new media list
		 */
		update_post_meta( $_POST['post_id'], '_pamd_files', $new_media );

		/**
		 * Output a JSON encoded string of the media downloads returned to our ajax call
		 */
		echo json_encode( $new_media );
		die();
	}

	/**
	 * Ajax call handler for removing a download entry from a post
	 *
	 * @since 1.0.0
	 *
	 * @return int|void
	 */
	function ajax_remove_entry() {
		/**
		 * Check if the user is allowed to edit the post in question
		 */
		if ( ! current_user_can( 'edit_post', $_POST['post_id'] ) ) { return -1; }

		/**
		 * Fetch the post meta holding our download list
		 */
		$media = get_post_meta( $_POST['post_id'], '_pamd_files', true );
		if ( empty( $media ) ) {
			/**
			 * If the meta is empty, we set our variable as an array
			 */
			$media = array();
		}

		/**
		 * Remove the entry
		 */
		unset( $media[$_POST['entry_id']] );

		/**
		 * We reorder the array to keep it nice and tidy
		 */
		$media = array_values( $media );

		/**
		 * Update the post meta with the new media list
		 */
		update_post_meta( $_POST['post_id'], '_pamd_files', $media );

		/**
		 * Output a JSON encoded string of the media downloads returned to our ajax call
		 */
		echo json_encode( $media );
		die();
	}

	/**
	 * Ajax call handler for adding download entries to a post
	 *
	 * @since 1.0.0
	 *
	 * @return int|void
	 */
	function ajax_update_list() {
		/**
		 * Check if the user is allowed to edit the post in question
		 */
		if ( ! current_user_can( 'edit_post', $_POST['post_id'] ) ) { return -1; }

		/**
		 * Fetch the post meta holding our download list
		 */
		$media = get_post_meta( $_POST['post_id'], '_pamd_files', true );
		if ( empty( $media ) ) {
			/**
			 * If the meta is empty, we set our variable as an array
			 */
			$media = array();
		}

		/**
		 * Merge our current media list with the array sent form the ajax call
		 */
		$media = array_merge( $media, $_POST['pamd_ids'] );
		/**
		 * Reorder the array to ensure IDs are ordered
		 */
		$media = array_values( $media );

		/**
		 * Uodate the post meta with the new media list
		 */
		update_post_meta( $_POST['post_id'], '_pamd_files', $media );

		/**
		 * Output a JSON encoded string of the media downloads returned to our ajax call
		 */
		echo json_encode( $media );
		die();
	}

	/**
	 * Add a quicktag to the post editor
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function pamd_quicktag() {

		if ( wp_script_is( 'quicktags' ) ) {
			?>
			<script type="text/javascript">
				QTags.addButton( 'pamd', 'PAMD', '[pamd]', '', '', 'Post Attached Media Downloads', 15  );
			</script>
		<?php
		}

	}

	/**
	 * Function for displaying shortcode content
	 *
	 * @param $atts
	 *
	 * @since 1.2
	 *
	 * @return mixed
	 */
	function pamd_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'target' => '_self'
		), $atts );

		return $this->get_downloads( '', false, 'pamd', $atts['target'] );
	}

	/**
	 * Get a list of downloadable media for a post
	 *
	 * @param int $postid The post id we want to get a PAMD list for
	 * @param bool $echo Should the list be returned or echoed directly
	 * @param string $return_format What format should the content be returned in
	 * @param string $target The download target to be output if we build the links
	 *
	 * @since 1.0.0
	 *
	 * @return mixed
	 */
	function get_downloads( $postid = 0, $echo = false, $return_format = 'pamd', $target = '_self' ) {
		/**
		 * If no post id is provided, we get the current one from The Loop
		 */
		if ( empty( $postid ) ) {
			global $post;
			$postid = $post->ID;
		}
		$pam = "";

		/**
		 * Get the post meta containing our downloads
		 */
		$entries = get_post_meta( $postid, '_pamd_files', true );

		/**
		 * If the $entries variable does not contain any array it is safe to presume
		 * there are no downloads for this post, and we return false
		 */
		if ( ! is_array( $entries ) ) {
			return false;
		}

		/**
		 * Output the data in the requested manner
		 */
		if ( $return_format == 'array' ) {
			/**
			 * Don't modify the values fetched from the post meta, as a direct array is desired
			 */
			return $entries;
		}
		elseif ( $return_format == 'table' ) {
			/**
			 * Output as a table
			 */
			$pam .= '
				<table class="post-attached-media">
					<thead>
						<tr>
							<th>' . __( 'Label', 'post-attached-media-downloads' ) . '</th>
							<th>' . __( 'Download link', 'post-attached-media-downloads' ) . '</th>
						</tr>
					</thead>
					<tbody>
			';

			foreach( $entries AS $entry ) {
				$pam .= '
					<tr>
						<td>' . $entry['label'] . '</td>
						<td>
							<a href="' . $entry['url'] . '" target="' . $target . '">
								' . $entry['url'] . '
							</a>
						</td>
					</tr>
				';
			}

			$pam .= '
					</tbody>
				</table>
			';
		}
		else {
			/**
			 * Default behavior
			 *
			 * Output in an unordered list
			 */
			$pam .= '<ul class="post-attached-media">';

			foreach( $entries AS $entry ) {
				$pam .= '
					<li>
						<a href="' . $entry['url'] . '" target="' . $target . '">
							' . $entry['label'] . '
						</a>
					</li>
				';
			}

			$pam .= '</ul>';
		}

		if ( $echo ) {
			echo $pam;
		}
		else {
			return $pam;
		}
	}

	/**
	 * Add a meta box to the post editor screen
	 * We add it to every post as it's a feature that would be handy in many cases
	 *
	 * @param string $post_type The currently active post type
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function meta_add( $post_type ) {
		/**
		 * Always add our meta box to the active post type
		 * This ensures it's available everywhere
		 */
		add_meta_box( 'post-attached-media-downloads', __( 'Media Downloads', 'post-attached-media-downloads' ), array( $this, 'meta_html' ), $post_type, 'normal', 'core' );
	}

	/**
	 * Generate and output the HTML for our meta box
	 *
	 * @param $post
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function meta_html( $post ) {
		/**
		 * Get the post meta containing our media
		 */
		$files = get_post_meta( $post->ID, '_pamd_files', true );
		if ( empty( $files ) ) {
			$files = array();
		}

		echo '
			<input type="hidden" id="pamd-media-ids" name="pamd-media-ids" value="" />
			<table id="pamd-media-list" class="wp-list-table widefat">
				<thead>
					<tr>
						<th>' . __( 'Label', 'post-attached-media-downloads' ) . '</th>
						<th>' . __( 'Download link', 'post-attached-media-downloads' ) . '</th>
						<th>' . __( 'Actions', 'post-attached-media-downloads' ) . '</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th>' . __( 'Label', 'post-attached-media-downloads' ) . '</th>
						<th>' . __( 'Download link', 'post-attached-media-downloads' ) . '</th>
						<th>' . __( 'Actions', 'post-attached-media-downloads' ) . '</th>
					</tr>
				</tfoot>
				<tbody id="pamd-media-list-body">
		';

		/**
		 * The $odd filter is used to style the tables
		 */
		$odd = true;

		/**
		 * Loop over every media element in our meta
		 */
		foreach( $files AS $enum => $media ) {
			echo '
				<tr data-pamd-entry-num="' . $enum . '" class="' . ( $odd ? 'alternate' : '' ) . '">
					<td data-pamd-media-id="' . $media['id'] . '">' . $media['label'] . '</td>
					<td><a href="' . $media['url'] . '">' . $media['url'] . '</a></td>
					<td>
						<a href="#" class="pamd-edit">' . __( 'Edit label', 'post-attached-media-downloads' ) . '</a>
						 | <a href="#" class="pamd-delete" data-pamd-remove="' . $enum . '">' . __( 'Remove', 'post-attached-media-downloads' ) . '</a>
					</td>
				</tr>
			';

			if ( $odd ) {
				$odd = false;
			}
			else {
				$odd = true;
			}
		}

		echo '
				</tbody>
			</table>
		';

		echo '
			<br />

			<div class="pamd-post-objects" style="width: 100%; text-align: right;">
				<button id="pamd-add-new-media" class="add-new-h2">' . __( 'Add new media', 'post-attached-media-downloads' ) . '</button>
			</div>
		';
	}
}

/**
 * Instantiate the plugin, and make available the $pamd function for use by developers
 */
$pamd = new pamd();