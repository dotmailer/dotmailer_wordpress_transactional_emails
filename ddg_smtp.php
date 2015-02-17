<?php

/*
Plugin Name: DDG SMTP
Version: 1.0.0
Plugin URI: http://www.dotmailer.com/
Description: dotmailer transactional emails
Author: Calin Diacon
Author URI: http://www.dotmailer.com/
*/

/**
 * Add menu and submenu.
 * @return void
 */

if ( ! function_exists( 'ddg_smtp_admin_default_setup' ) ) {
	function ddg_smtp_admin_default_setup() {
		add_options_page(__('DDG SMTP', 'ddg_smtp'), __('DDG SMTP', 'ddg_smtp'), 'manage_options', __FILE__, 'ddg_smtp_settings');
	}
}

/**
 * Plugin functions for init
 * @return void
 */
 
if ( ! function_exists ( 'ddg_smtp_admin_init' ) ) {
	function ddg_smtp_admin_init() {

		if ( isset( $_REQUEST['page'] ) && 'ddg_smtp_settings' == $_REQUEST['page'] ) {
			/* register plugin settings */
			ddg_smtp_register_settings();
		}
	}
}

/**
 * Register settings function
 * @return void
 */
 
if ( ! function_exists( 'ddg_smtp_register_settings' ) ) {
	function ddg_smtp_register_settings() {
		$dmsmtp_options_default = array(
			'from_email_field' 		=> '',
			'from_name_field'   		=> '',
			'smtp_settings'     		=> array(
				'host'               	=> 'smtp.example.com',
				'type_encryption'	=> 'none',
				'port'              	=> 25,
				'autentication'		=> 'yes',
				'username'		=> 'yourusername',
				'password'          	=> 'yourpassword'
			)
		);

		/* install the default plugin options */
		if ( ! get_option( 'ddg_smtp_options' ) ){
			add_option( 'ddg_smtp_options', $dmsmtp_options_default, '', 'yes' );
		}
	}
}

/**
 * Add action links on plugin page in to Plugin Name block
 * @param $links array() action links
 * @param $file  string  relative path to plugin
 * @return $links array() action links
 */
 
if ( ! function_exists ( 'ddg_smtp_plugin_action_links' ) ) {
	function ddg_smtp_plugin_action_links( $links, $file ) {
		/* Static so we don't call plugin_basename on every plugin row. */
		static $this_plugin;
		if ( ! $this_plugin ) {
			$this_plugin = plugin_basename( __FILE__ );
		}
		if ( $file == $this_plugin ) {
			$settings_link = '<a href="options-general.php?page=' . plugin_basename(__FILE__) . '">' . __( 'Settings', 'ddg_smtp' ) . '</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
	}
}

/**
 * Add action links on plugin page in to Plugin Description block
 * @param $links array() action links
 * @param $file  string  relative path to pugin
 * @return $links array() action links
 */
 
if ( ! function_exists ( 'ddg_smtp_register_plugin_links' ) ) {
	function ddg_smtp_register_plugin_links( $links, $file ) {
		$base = plugin_basename( __FILE__ );
		if ( $file == $base ) {
			$links[] = '<a href="options-general.php?page=ddg_smtp_settings">' . __( 'Settings', 'ddg_smtp' ) . '</a>';
		}
		return $links;
	}
}

/**
 * Function to add smtp options in the phpmailer_init
 * @return void
 */
 
if ( ! function_exists ( 'ddg_smtp_init_smtp' ) ) {
	function ddg_smtp_init_smtp( $phpmailer ) {
		$ddg_smtp_options = get_option( 'ddg_smtp_options' );
		/* Set the mailer type as per config above, this overrides the already called isMail method */
		$phpmailer->IsSMTP();
		$from_email = $ddg_smtp_options['from_email_field'];
		if (isset($phpmailer->From)) {
			if (empty($phpmailer->From)) {
				$phpmailer->From = $from_email;
			} else if (strpos($phpmailer->From, 'wordpress@') !== false){
				$phpmailer->From = $from_email;
			}
		} else {
			$phpmailer->From = $from_email;
		}
		$from_name  = $ddg_smtp_options['from_name_field'];
		if (isset($phpmailer->FromName)) {
			if(empty($phpmailer->FromName)){
				$phpmailer->FromName = $from_name;
			} else if (strpos($phpmailer->FromName, 'WordPress') !== false) {
				$phpmailer->FromName = $from_name;
			}
		} else {
			$phpmailer->FromName = $from_name;
		}
		$phpmailer->SetFrom($phpmailer->From, $phpmailer->FromName);
		/* Set the SMTPSecure value */
		if ( $ddg_smtp_options['smtp_settings']['type_encryption'] !== 'none' ) {
			$phpmailer->SMTPSecure = $ddg_smtp_options['smtp_settings']['type_encryption'];
		}

		/* Set the other options */
		$phpmailer->Host = $ddg_smtp_options['smtp_settings']['host'];
		$phpmailer->Port = $ddg_smtp_options['smtp_settings']['port'];

		/* If we're using smtp auth, set the username & password */
		if( 'yes' == $ddg_smtp_options['smtp_settings']['autentication'] ){
			$phpmailer->SMTPAuth = true;
			$phpmailer->Username = $ddg_smtp_options['smtp_settings']['username'];
			$phpmailer->Password = $ddg_smtp_options['smtp_settings']['password'];
		}
	}
}

/**
 * View function the settings to send messages.
 * @return void
 */
 
if ( ! function_exists( 'ddg_smtp_settings' ) ) {
	function ddg_smtp_settings() {

		$message = $error = $result = '';
		$ddg_smtp_options = get_option( 'ddg_smtp_options' );

		if ( isset( $_POST['ddg_smtp_form_submit'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'ddg_smtp_nonce_name')) {
			/* Update settings */
			$ddg_smtp_options['from_name_field'] = isset( $_POST['ddg_smtp_from_name'] ) ? $_POST['ddg_smtp_from_name'] : '';
			if( isset( $_POST['ddg_smtp_from_email'] ) ) {
				if ( is_email( $_POST['ddg_smtp_from_email'])) {
					$ddg_smtp_options['from_email_field'] = $_POST['ddg_smtp_from_email'];
				} else {
					$error .= " " . __( "Please enter a valid email address in the 'FROM' field.", 'ddg_smtp' );
				}
			}
			$ddg_smtp_options['smtp_settings']['host']     				= $_POST['ddg_smtp_smtp_host'];
			$ddg_smtp_options['smtp_settings']['type_encryption'] = ( isset( $_POST['ddg_smtp_smtp_type_encryption'] ) ) ? $_POST['ddg_smtp_smtp_type_encryption'] : 'none' ;
			$ddg_smtp_options['smtp_settings']['autentication']   = ( isset( $_POST['ddg_smtp_smtp_autentication'] ) ) ? $_POST['ddg_smtp_smtp_autentication'] : 'yes' ;
			$ddg_smtp_options['smtp_settings']['username']  			= $_POST['ddg_smtp_smtp_username'];
			$ddg_smtp_options['smtp_settings']['password'] 				= $_POST['ddg_smtp_smtp_password'];


			/* Check value from "SMTP port" option */
			if ( isset( $_POST['ddg_smtp_smtp_port'] ) ) {
				if ( empty( $_POST['ddg_smtp_smtp_port'] ) || 1 > intval( $_POST['ddg_smtp_smtp_port'] ) || ( ! preg_match( '/^\d+$/', $_POST['ddg_smtp_smtp_port'] ) ) ) {
					$ddg_smtp_options['smtp_settings']['port'] = '25';
					$error .= " " . __( "Please enter a valid port in the 'SMTP Port' field.", 'ddg_smtp' );
				} else {
					$ddg_smtp_options['smtp_settings']['port'] = $_POST['ddg_smtp_smtp_port'];
				}
			}

			/* Update settings in the database */
			if ( empty( $error ) ) {
				update_option( 'ddg_smtp_options', $ddg_smtp_options );
				$message .= __( "Settings saved.", 'ddg_smtp' );
			} else {
				$error .= " " . __( "Settings are not saved.", 'ddg_smtp' );
			}
		}

		/* Send test letter */
		if ( isset( $_POST['ddg_smtp_test_submit'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'ddg_smtp_nonce_name')) {
			if ( isset( $_POST['ddg_smtp_to'] ) ) {
				if( is_email( $_POST['ddg_smtp_to'] ) ) {
					$ddg_smtp_to = $_POST['ddg_smtp_to'];
				} else {
					$error .= " " . __( "Please enter a valid email address in the 'FROM' field.", 'ddg_smtp' );
				}
			}
			$ddg_smtp_subject = 'Testing Emails';
			$ddg_smtp_message = 'Welcome to DDG transactional emails for Wordpress.';
			if( ! empty( $ddg_smtp_to ) )
				$result = ddg_smtp_test_mail( $ddg_smtp_to, $ddg_smtp_subject, $ddg_smtp_message );
		} ?>
		<div class="wrap" id="dmpsmtp-mail">
			<div id="icon-options-general" class="icon32 icon32-bws"></div>
			<h2><?php _e( "DDG SMTP Settings", 'ddg_smtp' ); ?></h2>
			<div class="updated fade" <?php if( empty( $message ) ) echo "style=\"display:none\""; ?>>
				<p><strong><?php echo $message; ?></strong></p>
			</div>
			<div class="error" <?php if ( empty( $error ) ) echo "style=\"display:none\""; ?>>
				<p><strong><?php echo $error; ?></strong></p>
			</div>
			<div id="ddg_smtp-settings-notice" class="updated fade" style="display:none">
				<p><strong><?php _e( "Notice:", 'ddg_smtp' ); ?></strong> <?php _e( "The plugin's settings have been changed. In order to save them please don't forget to click the 'Save Changes' button.", 'ddg_smtp' ); ?></p>
			</div>
			<h3><?php _e( 'General', 'ddg_smtp' ); ?></h3>
			<form id="ddg_smtp_settings_form" method="post" action="">
				<table class="form-table">
					<tr class="ad_opt ddg_smtp_smtp_options">
						<th><?php _e( 'SMTP Host', 'ddg_smtp' ); ?></th>
						<td>
							<input type='text' name='ddg_smtp_smtp_host' value='<?php echo $ddg_smtp_options['smtp_settings']['host']; ?>' /><br />
							<span class="ddg_smtp_info"><?php _e( "Your mail server", 'ddg_smtp' ); ?></span>
						</td>
					</tr>
					<tr class="ad_opt ddg_smtp_smtp_options">
						<th><?php _e( 'SMTP Port', 'ddg_smtp' ); ?></th>
						<td>
							<select name="ddg_smtp_smtp_port" id="ddg_smtp_smtp_port">
								<option value="25">25</option>
								<option value="587">587</option>
							</select>
						</td>
					</tr>
					<tr class="ad_opt ddg_smtp_smtp_options">
						<th><?php _e( 'SMTP username', 'ddg_smtp' ); ?></th>
						<td>
							<input type='text' name='ddg_smtp_smtp_username' value='<?php echo $ddg_smtp_options['smtp_settings']['username']; ?>' /><br />
							<span class="ddg_smtp_info"><?php _e( "The username to login to your mail server", 'ddg_smtp' ); ?></span>
						</td>
					</tr>
					<tr class="ad_opt ddg_smtp_smtp_options">
						<th><?php _e( 'SMTP Password', 'ddg_smtp' ); ?></th>
						<td>
							<input type='password' name='ddg_smtp_smtp_password' value='<?php echo $ddg_smtp_options['smtp_settings']['password']; ?>' /><br />
							<span class="ddg_smtp_info"><?php _e( "The password to login to your mail server", 'ddg_smtp' ); ?></span>
						</td>
					</tr>
					<tr class="ad_opt dmpsmtp_smtp_options">
						<th><?php _e( 'Type of Encription', 'ddg_smtp' ); ?></th>
						<td>
							<label for="ddg_smtp_smtp_type_encryption_1"><input type="radio" id="ddg_smtp_smtp_type_encryption_1" name="ddg_smtp_smtp_type_encryption" value='none' <?php if( 'none' == $ddg_smtp_options['smtp_settings']['type_encryption'] ) echo 'checked="checked"'; ?> /> <?php _e( 'None', 'ddg_smtp' ); ?></label>
							<label for="ddg_smtp_smtp_type_encryption_3"><input type="radio" id="ddg_smtp_smtp_type_encryption_3" name="ddg_smtp_smtp_type_encryption" value='tls' <?php if( 'tls' == $ddg_smtp_options['smtp_settings']['type_encryption'] ) echo 'checked="checked"'; ?> /> <?php _e( 'TLS', 'ddg_smtp' ); ?></label><br />
							<span class="ddg_smtp_info"><?php _e( "For most servers SSL is the recommended option", 'ddg_smtp' ); ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( "From Email", 'ddg_smtp' ); ?></th>
						<td>
							<input type="text" name="ddg_smtp_from_email" value="<?php echo stripslashes( (isset($ddg_smtp_options['from_email_field']))? $ddg_smtp_options['from_email_field'] : '' ); ?>"/><br />
							<span class="ddg_smtp_info"><?php _e( "Friendly from email.", 'ddg_smtp' ); ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( "From Name", 'ddg_smtp' ); ?></th>
						<td>
							<input type="text" name="ddg_smtp_from_name" value="<?php echo (isset($ddg_smtp_options['from_name_field']))? $ddg_smtp_options['from_name_field'] : ''; ?>"/><br />
							<span  class="ddg_smtp_info"><?php _e( "Friendly from name", 'ddg_smtp' ); ?></span>
						</td>
					</tr>
				</table>
				<p class="submit">
					<input type="submit" id="settings-form-submit" class="button-primary" value="<?php _e( 'Save Changes', 'ddg_smtp' ) ?>" />
					<input type="hidden" name="ddg_smtp_form_submit" value="submit" />
					<?php wp_nonce_field( plugin_basename( __FILE__ ), 'ddg_smtp_nonce_name' ); ?>
				</p>
			</form>

			<div class="updated fade" <?php if( empty( $result ) ) echo "style=\"display:none\""; ?>>
				<p><strong><?php echo $result; ?></strong></p>
			</div>
			<h3><?php _e( 'Testing And Debugging Settings', 'ddg_smtp' ); ?></h3>
			<form id="ddg_smtp_settings_form" method="post" action="">
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php _e( "To", 'ddg_smtp' ); ?>:</th>
						<td>
							<input type="text" name="ddg_smtp_to" value=""/><br />
							<span class="ddg_smtp_info"><?php _e( "Enter the email address to recipient", 'ddg_smtp' ); ?></span>
						</td>
					</tr>
				</table>
				<p class="submit">
					<input type="submit" id="settings-form-submit" class="button-primary" value="<?php _e( 'Send Test Email', 'ddg_smtp' ) ?>" />
					<input type="hidden" name="ddg_smtp_test_submit" value="submit" />
					<?php wp_nonce_field( plugin_basename( __FILE__ ), 'ddg_smtp_nonce_name' ); ?>
				</p>
			</form>
		</div>
	<?php }
}

/**
 * Test mail sending
 */
if ( ! function_exists( 'ddg_smtp_test_mail' ) ) {
	function ddg_smtp_test_mail( $to_email, $subject, $message ) {
		$errors = '';

		$ddg_smtp_options = get_option( 'ddg_smtp_options' );
		require_once( ABSPATH . WPINC . '/class-phpmailer.php' );
		$mail = new PHPMailer();

		$from_name  = (isset($ddg_smtp_options['from_name_field']))? $ddg_smtp_options['from_name_field'] : '';
		$from_email = (isset($ddg_smtp_options['from_email_field']))? $ddg_smtp_options['from_email_field'] : '';

		$mail->IsSMTP();

		/* If using smtp auth, set the username & password */
		if( 'yes' == $ddg_smtp_options['smtp_settings']['autentication'] ){
			$mail->SMTPAuth = true;
			$mail->Username = $ddg_smtp_options['smtp_settings']['username'];
			$mail->Password = $ddg_smtp_options['smtp_settings']['password'];
		}

		/* Set the SMTPSecure value, if set to none, leave this blank */
		if ( $ddg_smtp_options['smtp_settings']['type_encryption'] !== 'none' ) {
			$mail->SMTPSecure = $ddg_smtp_options['smtp_settings']['type_encryption'];
		}

		/* Set the other options */
		$mail->Host = $ddg_smtp_options['smtp_settings']['host'];
		$mail->Port = $ddg_smtp_options['smtp_settings']['port'];
		$mail->SetFrom( $from_email, $from_name );
		$mail->isHTML( true );
		$mail->Subject = $subject;
		$mail->MsgHTML( $message );
		$mail->AddAddress( $to_email );
		$mail->SMTPDebug = 0;

		/* Send mail and return result */
		if ( ! $mail->Send() )
			$errors = $mail->ErrorInfo;

		$mail->ClearAddresses();
		$mail->ClearAllRecipients();

		if ( ! empty( $errors ) ) {
			return $errors;
		}
		else{
			return 'Test mail was sent';
		}
	}
}

/**
 * Function to add plugin scripts
 * @return void
 */
 
if ( ! function_exists ( 'ddg_smtp_admin_head' ) ) {
	function ddg_smtp_admin_head() {
		wp_enqueue_style( 'ddg_smtp_stylesheet', plugins_url( 'css/style.css', __FILE__ ) );

	}
}

/**
 * Performed at uninstal.
 * @return void
 */
 
if ( ! function_exists( 'ddg_smtp_send_uninstall' ) ) {
	function ddg_smtp_send_uninstall() {
		/* delete plugin options */
		delete_site_option( 'ddg_smtp_options' );
		delete_option( 'ddg_smtp_options' );
	}
}

/**
 * Add all hooks
 */
add_filter( 'plugin_action_links', 'ddg_smtp_plugin_action_links', 10, 2 );
add_filter( 'plugin_row_meta', 'ddg_smtp_register_plugin_links', 10, 2 );

add_action( 'phpmailer_init','ddg_smtp_init_smtp');
add_action( 'admin_menu', 'ddg_smtp_admin_default_setup' );
add_action( 'admin_init', 'ddg_smtp_admin_init' );
add_action( 'admin_enqueue_scripts', 'ddg_smtp_admin_head' );


register_uninstall_hook( plugin_basename( __FILE__ ), 'ddg_smtp_send_uninstall' );
