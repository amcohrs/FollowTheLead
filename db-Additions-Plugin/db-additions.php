<?php
/*
Plugin Name: dB Additions
Description: This plugin sets the Return-Path header of outgoing emails to equal the from email address. Creates an Editorial Notes field for comments to authors on pages and posts.
Author: drumBEAT Marketing
Author URI: http://www.drumbeatdev.com
Plugin URI: http://www.drumbeatdev.com
Version: 0.1.2
License: GPLv2
*/





/* ----------------------------------------------------------------------------------------
						Adding and Displaying the Editorial Notes
---------------------------------------------------------------------------------------- */
		//Add custom column
		add_filter('manage_posts_columns', 'db_my_columns_head');
		function db_my_columns_head($defaults) {
			$defaults['Editorial Notes'] = 'Editorial Notes';
			return $defaults;
		}
		//Add rows data
		add_action( 'manage_posts_custom_column' , 'db_my_custom_column', 10, 2 );
		function db_my_custom_column($column, $post_id ){
			switch ( $column ) {
				case 'Editorial Notes':echo get_post_meta( $post_id , 'dbshed_textarea' , true );
				break;
			}
		}
		
		function dbshed_get_custom_field( $value ) {
			global $post;
		    $custom_field = get_post_meta( $post->ID, $value, true );
		    if ( !empty( $custom_field ) )
			    return is_array( $custom_field ) ? stripslashes_deep( $custom_field ) : stripslashes( wp_kses_decode_entities( $custom_field ) );
		    return false;
		}
		
		// Register the Metabox
		function dbshed_add_custom_meta_box() {
			add_meta_box( 'dbshed-meta-box', __( 'Editor Notes', 'textdomain' ), 'dbshed_meta_box_output', 'post', 'normal', 'high' );
		}
		add_action( 'add_meta_boxes', 'dbshed_add_custom_meta_box' );
		// Output the Metabox
		function dbshed_meta_box_output( $post ) {
			// create a nonce field
			wp_nonce_field( 'db_my_dbshed_meta_box_nonce', 'dbshed_meta_box_nonce' ); ?>
			<p><textarea name="dbshed_textarea" style="width: 100%;" id="dbshed_textarea" rows="4"><?php echo dbshed_get_custom_field( 'dbshed_textarea' ); ?></textarea></p>
			<?php
		}
		// Save the Metabox values
		function dbshed_meta_box_save( $post_id ) {
			// Stop the script when doing autosave
			if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
			// Verify the nonce. If insn't there, stop the script
			if( !isset( $_POST['dbshed_meta_box_nonce'] ) || !wp_verify_nonce( $_POST['dbshed_meta_box_nonce'], 'db_my_dbshed_meta_box_nonce' ) ) return;
			// Stop the script if the user does not have edit permissions
			if( !current_user_can( 'edit_post' ) ) return;
		    // Save the textarea
			if( isset( $_POST['dbshed_textarea'] ) )
				update_post_meta( $post_id, 'dbshed_textarea', esc_attr( $_POST['dbshed_textarea'] ) );
		}
		add_action( 'save_post', 'dbshed_meta_box_save' );





/* ----------------------------------------------------------------------------------------
						Adding the Return Address Path Fix
---------------------------------------------------------------------------------------- */
		class email_return_path {
		  	function __construct() {
				add_action( 'phpmailer_init', array( $this, 'fix' ) );
		  	}
			function fix( $phpmailer ) {
			  	$phpmailer->Sender = $phpmailer->From;
			}
		}
		new email_return_path();





/* ----------------------------------------------------------------------------------------
						Adding the Additional General Settings
						includes: 
---------------------------------------------------------------------------------------- */

/* ------------------------------------------------------------------------ *
 * Setting Registration
 * ------------------------------------------------------------------------ */
 
/**
 * Initializes the theme options page by registering the Sections,
 * Fields, and Settings.
 *
 * This function is registered with the 'admin_init' hook.
 */
add_action('admin_init', 'db_initialize_additional_options');
function db_initialize_additional_options() {
 
    // First, we register a section. This is necessary since all future options must belong to one. 
    add_settings_section(
        'db_additional_general_settings_section',         // ID used to identify this section and with which to register options
        'dB Additional Site Settings',                  // Title to be displayed on the administration page
        'db_additional_general_options_callback', // Callback used to render the description of the section
        'general'                           // Page on which to add this section of options
    );
     
    // Next, we will introduce the fields for toggling the visibility of content elements.
    add_settings_field( 
        'db_excerpt_length',                      // ID used to identify the field throughout the theme
        'Custom Excerpt Length',                           // The label to the left of the option interface element
        'db_toggle_excerpt_callback',   // The name of the function responsible for rendering the option interface
        'general',                          // The page on which this option will be displayed
        'db_additional_general_settings_section',         // The name of the section to which this field belongs
        array(                              // The array of arguments to pass to the callback. In this case, just a description.
            'Define the lenght of an additional excerpt.'
        )
    );
     
    // Finally, we register the fields with WordPress
    register_setting(
        'general',
        'db_excerpt_length'
    );
     
} // end db_initialize_additional_options
 
/* ------------------------------------------------------------------------ *
 * Section Callbacks
 * ------------------------------------------------------------------------ */
 
/**
 * This function provides a simple description for the General Options page. 
 *
 * It is called from the 'db_initialize_additional_options' function by being passed as a parameter
 * in the add_settings_section function.
 */
function db_additional_general_options_callback() {
    echo '<p>Custom Site Settings</p>';
} // end db_additional_general_options_callback
 
/* ------------------------------------------------------------------------ *
 * Field Callbacks
 * ------------------------------------------------------------------------ */
 
/**
 * This function renders the interface elements for toggling the visibility of the header element.
 * 
 * It accepts an array of arguments and expects the first element in the array to be the description
 * to be displayed next to the checkbox.
 */
function db_toggle_excerpt_callback($args) {
     
    // Note the ID and the name attribute of the element match that of the ID in the call to add_settings_field
    $html = '<input type="text" id="db_excerpt_length" name="db_excerpt_length" value="'. checked(1, get_option('db_excerpt_length'), false) . '"/>'; 
     
    // Here, we will take the first argument of the array and add it to a label next to the checkbox
    $html .= '<label for="db_excerpt_length"> '  . $args[0] . '</label>'; 
     
    echo $html;
     
} // end db_toggle_excerpt_callback