<?php

/**
 * @package mango-ssl
 * @version 1.1
 */
/*

Plugin Name: Mango ssl
Plugin URI: http://wordpress.org/plugins/mango-ssl/
Description: Mango ssl plugin sends all the order changes entered in WooCommerce to mango(www.mangossl.com) to avoid reentering those orders manually.
Author: Franck Janini
Version: 1.1
Author URI: https://www.mangossl.com/
*/

require("mango-ssl_call.php");
require("mango-ssl_order.php");

// Load Css menu

add_action( 'admin_print_styles', 'mango_ssl_load_plugin_css' );

	function mango_ssl_load_plugin_css() 
	{
		$plugin_url = plugin_dir_url( __FILE__ );

		wp_enqueue_style( 'login_menu', $plugin_url . 'css/menu.css' );
	}

// Add the setup menu to define the connection to mango 

add_action( 'admin_menu', 'mango_ssl_menu' );

	function mango_ssl_menu() 
	{
		add_submenu_page('options-general.php',__('Mango setup','menu-mango-ssl'), __('Mango setup','menu-mango-ssl'), 'manage_options', 'sub-page','mango_ssl_options');
	}

	function mango_ssl_options() 
	{
		if ( !current_user_can( 'manage_options' ) )  
		{
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		
		// variables for the field and option names 
		 
		$mangossl_token = 'mangossl_token';
		
		$mangossl_pwd = 'mangossl_pwd';
		$mangossl_login = 'mangossl_login';
		$mangossl_domain = 'mangossl_domain';
		$hidden_field_name = 'mt_submit_hidden';

		// Read in existing option value from database
		
		$mangossl_pwd_val = '';
		$mangossl_login_val = get_option( $mangossl_login );
		$mangossl_domain_val = get_option( $mangossl_domain );

		// See if the user has posted us some information
		// If they did, this hidden field will be set to 'Y'
		
		if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) 
		{
			// Read their posted value
			$mangossl_pwd_val = $_POST[ $mangossl_pwd ];
			$mangossl_login_val = sanitize_text_field($_POST[ $mangossl_login ]);
			$mangossl_domain_val = sanitize_text_field($_POST[ $mangossl_domain ]);
			
			$mango = new mango_ssl_call();
			$result = $mango->login($mangossl_domain_val, $mangossl_login_val, $mangossl_pwd_val);
			
			if ($result==null)
			{
				update_option( $mangossl_token, '');
				update_option( $mangossl_login, '');
				update_option( $mangossl_domain, '');
				
				echo '<div class="error"><p><strong>';
				_e('Invalid credentials.', 'menu-mango-ssl' );
				echo '</strong></p></div>';
			}
			else 
			{
				if ($result->EXIST==0)
				{
					update_option( $mangossl_token, sanitize_text_field($result->token) );
					update_option( $mangossl_login, $mangossl_login_val );
					update_option( $mangossl_domain, $mangossl_domain_val );

					echo '<div class="updated"><p><strong>';
					_e('settings saved.', 'menu-mango-ssl' );
					echo '</strong></p></div>';
				}
				else
				{
					update_option( $mangossl_token, '');
					update_option( $mangossl_login, '');
					update_option( $mangossl_domain, '');
					
					echo '<div class="error"><p><strong>';
					_e('Incorrect Login/Password. Attempt '.$result->EXIST.' on 3.', 'menu-mango-ssl' );
					echo '</strong></p></div>';
				}
			}
		}

		// Now display the menu

		echo '<div class="wrap">';
		echo "<h2>" . __( 'Mango Connection Menu', 'menu-mango-ssl' ) . "</h2>";

		// mango login form
		
		?>

			<form name="form1" method="post" action="">
			<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">

			<p><b class="mango_label"><?php _e("Domain", 'menu-mango-ssl' ); ?></b> 
			<input  type="text" name="<?php echo $mangossl_domain; ?>" value="<?php echo $mangossl_domain_val; ?>" size="20">
			</p><hr />
			
			<p><b class="mango_label"><?php _e("Login", 'menu-mango-ssl' ); ?></b> 
			<input type="text" name="<?php echo $mangossl_login; ?>" value="<?php echo $mangossl_login_val; ?>" size="20">
			</p><hr />
			
			<p><b class="mango_label"><?php _e("Password", 'menu-mango-ssl' ); ?></b>
			<input type="password" name="<?php echo $mangossl_pwd; ?>" value="<?php echo $mangossl_pwd_val; ?>" size="20">
			</p><hr />

			<p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
			</p>

			</form>
			</div>

		<?php
	}
?>
