<?php
/*
Plugin Name: xili re/un-attach media
Plugin URI: http://dev.xiligroup.com/
Description: Unattach, Reattach new actions in Media Library Table list to manage attachments
Author: dev.xiligroup - MSC
Version: 0.9.1
Author URI: http://dev.xiligroup.com
License: GPLv2
Text Domain: xili_re_un_attach_media
Domain Path: /languages/
*/

# 0.9.1 - 140623 - add pointer for single metabox (attachement infos)
# 0.9.0 - 140613 - first public version

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define( 'XILIUNATTACHMEDIA_VER', '0.9.1' );

class xili_re_un_attach_media {

	var $news_case = array(); // for pointers
	var $news_id = 0 ;
	var $plugin_dir = '';
	var $plugin_subfoldername = ''; // xili-re-un-attach-media/

	public function __construct() {

		$this->plugin_dir = plugin_dir_url( __FILE__ ) ;
		$this->plugin_subfoldername = basename ($this->plugin_dir) ;

		add_action( 'load-upload.php', array( &$this, 'load_upload' ) );

		add_action( 'load-post.php', array( &$this, 'load_post' ) );

		// column header
		// add_filter( 'manage_media_columns', array( &$this, 'manage_media_columns'), 10, 2 );

		// column row
		// add_action( 'manage_media_custom_column', array( &$this, 'manage_media_custom_column'), 10, 2 );

		// row actions
		add_filter( 'media_row_actions', array( &$this, 'media_row_actions'), 10, 3 ); // Media Library List Table class and

		add_action( 'admin_menu', array(&$this, 'add_custom_box_in_media_edit') ); // custom meta box in single media edit
		add_action( 'admin_print_scripts-post.php', array(&$this,'find_post_script') );

		add_action( 'contextual_help', array( &$this,'add_help_text' ), 10, 3 );

	}

	/**
	 * Add action on top of upload.php to unattach (called also by custom metabox)
	 *
	 * @since 0.9.0
	 *
	 */
	function load_upload (){
		if ( isset ( $_REQUEST['post_id'] ) && isset ( $_REQUEST['xiliaction'] ) ) {
			check_admin_referer('unattach-post_' .$_REQUEST['post_id']); // nonce control

			if ( $_REQUEST['xiliaction'] == 'unattach' && !empty( $_REQUEST['post_id']) ) {
				$this->set_parent_attachment( $_REQUEST['post_id'] );

				if ( $referer = wp_get_referer() ) {
					if ( false !== strpos( $referer, 'post.php' ) ) { // from metabox in Edit Media
						$location = add_query_arg( array( 'message' => '1' ) , $referer );
						wp_redirect( $location );
						exit;
					} else if ( false !== strpos( $referer, 'upload.php' ) ) {
						$location = remove_query_arg( array('xiliaction', 'post_id', '_wpnonce'), $referer ); // clean for further actions
						$location = add_query_arg( array( 'message' => '1' ) , $location );
						wp_redirect( $location );
						exit;
					}
				}
			}
		}

		$this->insert_news_pointer ( 'xreunam_new_version' ); // pointer in menu for updated version
		add_action( 'admin_print_footer_scripts', array(&$this, 'print_the_pointers_js') );
	}

	/**
	 * Add action on top of post.php to attach (called by custom metabox inside Edit Media)
	 *
	 * @since 0.9.0
	 *
	 */
	function load_post (){
		if ( isset ( $_REQUEST['_ajax_nonce'] ) && isset ( $_REQUEST['found_post_id'] ) && isset ( $_REQUEST['post_type'] ) && 'attachment' == $_REQUEST['post_type'] ) {
			wp_verify_nonce( $_REQUEST['_ajax_nonce'], 'find-posts'); // generated in find posts div requester
			global $wpdb;
			$parent_id = (int) $_REQUEST['found_post_id']; // found so > 0

			$att_id = $_REQUEST['post_ID'];
			$attached = $this->set_parent_attachment( $att_id, $parent_id );
			clean_attachment_cache( $att_id );
			$_GET['message'] = 1;
		}
		$this->insert_news_pointer ( 'xreunam_infos_metabox' ); // pointer
		add_action( 'admin_print_footer_scripts', array(&$this, 'print_the_pointers_js') );
	}

	function set_parent_attachment ( $att_id, $parent_id = 0 ) {
		// verify right to modify parent !
		if ( $parent_id > 0 && !current_user_can( 'edit_post', $parent_id ) )
				wp_die( __( 'You are not allowed to edit this post.' ) );

		global $wpdb;
		$attached = $wpdb->update( $wpdb->posts, array( 'post_parent' => $parent_id ),
			array( 'ID' => (int) $att_id, 'post_type' => 'attachment' )
		);
		return $attached;
	}

	// future release
	function manage_media_columns ( $posts_columns, $detached = null ) { // also called in media-new.php with one params

		return $posts_columns;
	}

	// future release
	function manage_media_custom_column ( $column_name, $post_ID ) {

	}

	// add action in array used in class table
	function media_row_actions ( $actions, $post, $detached ) { // first column

		if ( $post->post_parent == 0 ) {
			if ( current_user_can( 'edit_post', $post->ID ) )
				$actions['attach'] = '<a href="#the-list" onclick="findPosts.open( \'media[]\',\''.$post->ID.'\' );return false;" class="hide-if-no-js">'.__( 'Attach' ).'</a>';
		} else {
			if ( current_user_can( 'edit_post', $post->ID ) ) {
				$url_unattach = wp_nonce_url('upload.php?xiliaction=unattach&post_id=' . $post->ID ,'unattach-post_' . $post->ID); //
				$actions['un-attach'] = '<a href="'.$url_unattach.'" >'.__( 'Unattach','xili_re_un_attach_media' ).'</a>';
			}
			if ( current_user_can( 'edit_post', $post->ID ) )
				$actions['re-attach'] = '<a href="#the-list" onclick="findPosts.open( \'media[]\',\''.$post->ID.'\' );return false;" class="hide-if-no-js">'.__( 'Reattach','xili_re_un_attach_media' ).'</a>';
		}

		return $actions;
	}

	/**
	 * Add a meta box in Edit Media page (edit-form-advanced.php)
	 * @since 0.9.0
	 *
	 */
	function add_custom_box_in_media_edit(){
		load_plugin_textdomain('xili_re_un_attach_media', false, $this->plugin_subfoldername . '/languages' ); // here to be live changed
		add_meta_box( 'xili_media_attachment', __( 'Attachment infos', 'xili_re_un_attach_media') , array(&$this,'media_attachment_box'), 'attachment', 'side', 'low' );
	}

	function media_attachment_box () {
		global $post;
		// inspired from class-wp-media-list-table.php
		if ( $post->post_parent > 0 )
			$parent = get_post( $post->post_parent );
		else
			$parent = false;

		if ( $parent ) {
			$title = _draft_or_post_title( $post->post_parent );
			$parent_type = get_post_type_object( $parent->post_type );

			echo '<p>'.__( 'Attached to:', 'xili_re_un_attach_media' ).'<br /><strong>';
			if ( current_user_can( 'edit_post', $post->post_parent ) && $parent_type && $parent_type->show_ui ) { ?>
					<a href="<?php echo get_edit_post_link( $post->post_parent ); ?>">
						<?php echo $title ?></a><?php
				} else {
					echo $title;
				} ?></strong>,
				<?php echo get_the_time( __( 'Y/m/d' ) ); ?>
			</p>
		<?php
			$url_unattach = wp_nonce_url('upload.php?xiliaction=unattach&post_id=' . $post->ID ,'unattach-post_' . $post->ID);
			echo '<p><a href="'.$url_unattach.'">'.__( 'Unattach','xili_re_un_attach_media' ).'</a>';
			echo '&nbsp;|&nbsp;';
			echo '<a href="#the-list" onclick="findPosts.open( \'media[]\',\''.$post->ID.'\' );return false;" class="hide-if-no-js">'.__( 'Reattach','xili_re_un_attach_media' ).'</a></p>';

		} else {
		?>
			<p><?php _e( '(Unattached)' );
				echo '<br />';
				?>
				<a class="hide-if-no-js"
					onclick="findPosts.open( 'media[]','<?php echo $post->ID ?>' ); return false;"
					href="#the-list">

				<?php echo __( 'Attach' ). '</a>'; ?>
			</p>
			<?php
		}
		echo '<div id="ajax-response"></div>';
		find_posts_div(); // div to search - template.php
		echo '<p><small>©xili re/un-attach Media v. ' . XILIUNATTACHMEDIA_VER .'</small></p>';
	}

	function find_post_script () {
		global $post;
		if ( get_post_type($post->ID) == 'attachment' ) {
			wp_enqueue_script( 'wp-ajax-response' );
			wp_enqueue_script( 'media' ); // media.js
		}
	}

	/** pointer and help parts **/

	// called by each pointer
	function insert_news_pointer ( $case_news ) {
			wp_enqueue_style( 'wp-pointer' );
			wp_enqueue_script( 'wp-pointer', false, array('jquery') );
			++$this->news_id;
			$this->news_case[$this->news_id] = $case_news;
	}
	// insert the pointers registered before
	function print_the_pointers_js ( ) {
		if ( $this->news_id != 0 ) {
			for ($i = 1; $i <= $this->news_id; $i++) {
				$this->print_pointer_js ( $i );
			}
		}
	}
	// one pointer
	function print_pointer_js ( $indice ) {

		$args = $this->localize_admin_js( $this->news_case[$indice], $indice );
		if ( $args['pointerText'] != '' ) { // only if user don't read it before
		?>
	<script type="text/javascript">
	//<![CDATA[
	jQuery(document).ready( function() {
	var strings<?php echo $indice; ?> = <?php echo json_encode( $args ); ?>;
<?php /** Check that pointer support exists AND that text is not empty - inspired www.generalthreat.com */ ?>
	if(typeof(jQuery().pointer) != 'undefined' && strings<?php echo $indice; ?>.pointerText != '') {
		jQuery( strings<?php echo $indice; ?>.pointerDiv ).pointer({
			content : strings<?php echo $indice; ?>.pointerText,
			position: { edge: strings<?php echo $indice; ?>.pointerEdge,
				at: strings<?php echo $indice; ?>.pointerAt,
				my: strings<?php echo $indice; ?>.pointerMy
			},
			close : function() {
				jQuery.post( ajaxurl, {
					pointer: strings<?php echo $indice; ?>.pointerDismiss,
					action: 'dismiss-wp-pointer'
				});
			}
		}).pointer('open');
		}
	});
	//]]>
	</script>
		<?php
		}
	}

	/**
	 * News pointer for tabs
	 *
	 * @since 0.9.0
	 *
	 */
	function localize_admin_js( $case_news, $news_id ) {
		$about = __('Docs about xili re/un-attach media', 'xili_re_un_attach_media');
		$changelog = __('Changelog tab of xili re/un-attach media', 'xili_re_un_attach_media');
		//$pointer_Offset = '';
		$pointer_edge = '';
		$pointer_at = '';
		$pointer_my = '';
		switch ( $case_news ) {

			case 'xreunam_new_version' :
				$pointer_text = '<h3>' . esc_js( __( 'xili re/un-attach media updated', 'xili_re_un_attach_media') ) . '</h3>';
				$pointer_text .= '<p>' . esc_js( sprintf( __( 'xili re/un-attach media was updated to version %s', 'xili_re_un_attach_media' ) , XILIUNATTACHMEDIA_VER) ). '</p>';

				$pointer_text .= '<p>' . esc_js( sprintf( __( 'This version %s improves Media (file) Library page by adding actions in File column of the list. See Help tab on top right and also %s.','xili_re_un_attach_media' ) , XILIUNATTACHMEDIA_VER, '<a href="http://wordpress.org/plugins/xili-re-un-attach-media/changelog/" title="'.$changelog.'" >'.$changelog.'</a>') ). '</p>';

				//$pointer_text .= '<p>' . esc_js( sprintf( __( 'Previous version before v. %s improves xml import and importations from GlotPress. See also %s.','xili-language' ) , XILILANGUAGE_VER, '<a href="http://wordpress.org/plugins/xili-language/changelog/" title="'.$changelog.'" >'.$changelog.'</a>') ). '</p>';

				$pointer_dismiss = 'xreunam-new-version-'.str_replace('.', '-', XILIUNATTACHMEDIA_VER);
				$pointer_div = 'div.wrap > h2'; // title of page

				$pointer_edge = 'top'; // the arrow
				$pointer_my = 'top+230px'; // relative to the box
				$pointer_at = 'left+310px'; // relative to div where pointer is attached
				break;

			case 'xreunam_infos_metabox' :
				$pointer_text = '<h3>' . esc_js( __( 'xili re/un-attach media metabox', 'xili_re_un_attach_media') ) . '</h3>';
				$pointer_text .= '<p>' . esc_js( __( 'In this metabox, find infos about the attached post (title, date) and action links to manage the attachment (Attach, Unattach, Reattach)!','xili_re_un_attach_media' ) ). '</p>';

				$pointer_dismiss = 'xreunam_infos_metabox';
				$pointer_div = '#xili_media_attachment'; // title of page

				$pointer_edge = 'right'; // the arrow
				$pointer_my = 'right'; // relative to the box
				$pointer_at = 'left-30px'; // relative to div where pointer is attached
				break;
			default: // nothing
				$pointer_text = '';
		}

		// inspired from www.generalthreat.com
		// Get the list of dismissed pointers for the user
		$dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
		if ( in_array( $pointer_dismiss, $dismissed ) && $pointer_dismiss == 'xreunam-new-version-'.str_replace('.', '-', XILIUNATTACHMEDIA_VER) ) {
			$pointer_text = '';
		} elseif ( in_array( $pointer_dismiss, $dismissed ) ) {
			$pointer_text = '';
		}

		return array(
			'pointerText' => html_entity_decode( (string) $pointer_text, ENT_QUOTES, 'UTF-8'),
			'pointerDismiss' => $pointer_dismiss,
			'pointerDiv' => $pointer_div,
			'pointerEdge' => ( '' == $pointer_edge ) ? 'top' : $pointer_edge ,
			'pointerAt' => ( '' == $pointer_at ) ? 'left top' : $pointer_at ,
			'pointerMy' => ( '' == $pointer_my ) ? 'left top' : $pointer_my ,
			'newsID' => $news_id
		);
	}

	/**
	 * Contextual help
	 *
	 * @since 0.9.0
	 *
	 */
	function add_help_text( $contextual_help, $screen_id, $screen ) {

		if ( $screen->id == 'upload' ) {

			$to_remember = '<p><strong>' . sprintf( __('About the new actions reattach and unattach actions for media by %s', 'xili_re_un_attach_media'), '[©xili]') . '</strong></p>'
							.'<p>' . __('One or two new actions are added in column file after View action:', 'xili_re_un_attach_media') . '</p>'
							. '<ul>'
								.'<li>' . __('Attach if the media (file) is not attached to a post.', 'xili_re_un_attach_media') . '</li>'
								.'<li>' . __('Unattach if the media (file) is attached to a post and you want to unlink this media from the post.', 'xili_re_un_attach_media') . '</li>'
								.'<li>' . __('Reattach if you want to change the post with which the media is yet attached.', 'xili_re_un_attach_media') . '</li>'
							. '</ul>';

			$screen->add_help_tab( array(
				'id'		=> 'xili-re-un-attach-media',
				'title'		=> __('Re/un-attach Actions', 'xili_re_un_attach_media'),
				'content'	=> $to_remember,
			));

		}
		return $contextual_help;
	}

}
/**
 * instantiation
 */
function xili_re_un_attach_media () {
	if ( is_admin() ){
		$xili_re_un_attach_media = new xili_re_un_attach_media(); // only used in admin side (upload.php and Edit Media screen)
	}
}
add_action( 'plugins_loaded', 'xili_re_un_attach_media', 10 );



?>