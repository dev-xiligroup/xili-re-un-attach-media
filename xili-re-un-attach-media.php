<?php
/*
Plugin Name: xili re/un-attach media
Plugin URI: http://dev.xiligroup.com/
Description: An OOP rewritten version of unattach-and-reattach plugin from davidn.de
Author: dev.xiligroup - MS
Version: 0.9.0
Author URI: http://dev.xiligroup.com
License: GPLv2
Text Domain: xili_re_un_attach_media
Domain Path: /languages/
*/

# 0.9.0 - 140613 - first public version

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define( 'XILIUNATTACHMEDIA_VER', '0.9.0' );

class xili_re_un_attach_media {

	var $news_case = array(); // for pointers
	var $news_id = 0 ;

	public function __construct() {

		add_action( 'load-upload.php', array( &$this, 'load_upload' ) );

		// column header
		// add_filter( 'manage_media_columns', array( &$this, 'manage_media_columns'), 10, 2 );

		// column row
		// add_action( 'manage_media_custom_column', array( &$this, 'manage_media_custom_column'), 10, 2 );

		// row actions
		add_filter( 'media_row_actions', array( &$this, 'media_row_actions'), 10, 3 ); // Media Library List Table class and

		add_action( 'contextual_help', array( &$this,'add_help_text' ), 10, 3 );

	}

	function load_upload (){
		if ( isset ( $_REQUEST['post_id'] ) && $_REQUEST['xiliaction'] ) {
			check_admin_referer('unattach-post_' .$_REQUEST['post_id']); // nonce control

			if ( $_REQUEST['xiliaction'] == 'unattach' && !empty($_REQUEST['post_id']) ) {
				$this->unattach_attachment( $_REQUEST['post_id'] );
				$_GET['message'] = 1; // generic media updated
			}
		}
		load_plugin_textdomain('xili_re_un_attach_media', false, 'xili-re-un-attach-media/languages' ); // here to be live changed
		$this->insert_news_pointer ( 'xreunam_new_version' ); // pointer in menu for updated version
		add_action( 'admin_print_footer_scripts', array(&$this, 'print_the_pointers_js') );
	}

	function unattach_attachment ( $post_id ) {
		global $wpdb;
		$wpdb->update( $wpdb->posts, array( 'post_parent' => 0 ),
			array('ID' => (int) $post_id, 'post_type' => 'attachment')
		);
	}

	// future release
	function manage_media_columns ( $posts_columns, $detached ) {

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
				$actions['un-attach'] = '<a href="'.$url_unattach.'" class="hide-if-no-js">'.__( 'Unattach','xili_re_un_attach_media' ).'</a>';
			}
			if ( current_user_can( 'edit_post', $post->ID ) )
				$actions['re-attach'] = '<a href="#the-list" onclick="findPosts.open( \'media[]\',\''.$post->ID.'\' );return false;" class="hide-if-no-js">'.__( 'Reattach','xili_re_un_attach_media' ).'</a>';
		}

		return $actions;
	}

	// pointer and help parts

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
	 * @since 2.6.2
	 *
	 */
	function localize_admin_js( $case_news, $news_id ) {
		$about = __('Docs about xili re/un-attach media', 'xili-language');
		$changelog = __('Changelog tab of xili re/un-attach media', 'xili-language');
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
		$xili_re_un_attach_media = new xili_re_un_attach_media(); // only used in admin side (upload.php screen)
	}
}
add_action( 'plugins_loaded', 'xili_re_un_attach_media', 10 );



?>