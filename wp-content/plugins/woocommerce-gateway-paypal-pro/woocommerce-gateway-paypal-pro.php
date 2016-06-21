<?php
/*
Plugin Name: WooCommerce PayPal Pro (Classic and PayFlow Editions) Gateway
Plugin URI: http://www.woothemes.com/products/paypal-pro/
Description: A payment gateway for PayPal Pro classic and PayFlow edition. A PayPal Pro merchant account, Curl support, and a server with SSL support and an SSL certificate is required (for security reasons) for this gateway to function.
Version: 4.4.3
Author: WooThemes
Author URI: http://woothemes.com/

	Copyright: © 2009-2015 WooThemes.
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html

	PayPal Pro Docs:
		https://cms.paypal.com/cms_content/US/en_US/files/developer/PP_WPP_IntegrationGuide.pdf
		https://www.paypalobjects.com/webstatic/en_US/developer/docs/pdf/payflowgateway_guide.pdf
*/

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '6d23ba7f0e0198937c0029f9e865b40e', '18594' );

if ( ! class_exists( 'WC_PayPal_Pro' ) ) :

class WC_PayPal_Pro {

	/**
	 * PayPal Pro version
	 */
	const VERSION = '4.4.3';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		if ( is_woocommerce_active() && class_exists( 'WC_Payment_Gateway' ) ) {

			if ( version_compare( get_option( 'woocommerce_paypal_pro_version', 0 ), self::VERSION, '<' ) ) {
				$this->update();
			}

			add_filter( 'woocommerce_payment_gateways', array( $this, 'register_gateway' ) );

			if ( is_admin() ) {
				add_action( 'admin_notices', array( $this, 'ssl_check' ) );
			}

			include( 'includes/class-wc-gateway-paypal-pro.php' );
			include( 'includes/class-wc-gateway-paypal-pro-payflow.php' );

			add_action( 'woocommerce_order_status_on-hold_to_processing', array( $this, 'capture_payment' ) );
			add_action( 'woocommerce_order_status_on-hold_to_completed', array( $this, 'capture_payment' ) );
			add_action( 'woocommerce_order_status_on-hold_to_cancelled', array( $this, 'cancel_payment' ) );
			add_action( 'woocommerce_order_status_on-hold_to_refunded', array( $this, 'cancel_payment' ) );
			add_action( 'admin_init', array( $this, 'update_ssl_nag' ) );
			add_action( 'admin_notices', array( $this, 'cardinal_upgrade_notice' ) );
			add_action( 'wp_ajax_dismiss-notice', array( $this, 'dissmissable_notice_handler' ) );
		} else {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
		}
	}

	/**
	 * Updates version
	 */
	public function update() {
		// 3dsecure update logic when updating from a version of this extension older than 4.4.0
		if ( version_compare( get_option( 'woocommerce_paypal_pro_version', 0 ), '4.4.0', '<' ) ) {
			$settings = get_option( 'woocommerce_paypal_pro_settings', array() );

			if ( ! empty( $settings['enabled'] ) && ! empty( $settings['enable_3dsecure'] ) && 'yes' === $settings['enable_3dsecure'] && 'yes' === $settings['enabled'] ) {
				$settings['enable_3dsecure'] = 'no';
				update_option( 'woocommerce_paypal_pro_settings', $settings );
				update_option( 'woocommerce_paypal_pro_cardinal_upgrade_notice', true );
			}
		}
		update_option( 'woocommerce_paypal_pro_version', self::VERSION );
	}

	/**
	 * cardinal_upgrade_notice
	 */
	public function cardinal_upgrade_notice() {
		if ( get_option( 'woocommerce_paypal_pro_cardinal_upgrade_notice' ) && current_user_can( 'manage_options' ) ) {
			echo '<div class="notice error is-dismissible" data-notice-id="cardinal_upgrade_notice">
				<p>' . __( 'PayPal Pro requires the "CC" TransactionType to be enabled on your Cardinal Commerce merchant profile in order to use 3dSecure. Please contact Cardinal support to get your account updated. In the meantime, 3dsecure has been disabled so you can continue to recieve payments. Re-enable it from the settings page once your account has been updated.', 'woocommerce-gateway-paypal-pro' ) . '</p>
			</div>
			<script type="text/javascript">
				jQuery(function() {
					jQuery(".is-dismissible[data-notice-id]").on("click", function(){
						jQuery.post( ajaxurl, {
							action:    "dismiss-notice",
							notice_id: jQuery( this ).data("notice-id")
						});
					});
				});
			</script>
			';
		}
	}

	/**
	 * Remove the notice
	 */
	public function dissmissable_notice_handler() {
		if ( ! empty( $_POST['notice_id'] ) && 'cardinal_upgrade_notice' === $_POST['notice_id'] && current_user_can( 'manage_options' ) ) {
			delete_option( 'woocommerce_paypal_pro_cardinal_upgrade_notice' );
		}
	}

	/**
	 * load the plugin text domain for translation.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'wc_paypal_pro_plugin_locale', get_locale(), 'woocommerce-gateway-paypal-pro' );

		load_textdomain( 'woocommerce-gateway-paypal-pro', trailingslashit( WP_LANG_DIR ) . 'woocommerce-gateway-paypal-pro/woocommerce-gateway-paypal-pro' . '-' . $locale . '.mo' );

		load_plugin_textdomain( 'woocommerce-gateway-paypal-pro', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		return true;
	}

	/**
	 * WooCommerce fallback notice.
	 *
	 * @return string
	 */
	public function woocommerce_missing_notice() {
		echo '<div class="error"><p>' . sprintf( __( 'WooCommerce PayPal Pro Plugin requires WooCommerce to be installed and active. %s', 'woocommerce-gateway-paypal-pro' ), '<a href="http://www.woothemes.com/woocommerce/" target="_blank">WooCommerce</a>' ) . '</p></div>';

		return true;
	}

	/**
	 * Update SSL nag
	 *
	 * @since 4.3.2
	 * @return bool
	 */
	public function update_ssl_nag() {

		if ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( $_GET['_wpnonce'], 'wc_paypal_pro_ssl_nag_hide' ) ) {
			return;
		}

		if ( isset( $_GET['wc_paypal_pro_ssl_nag'] ) && '1' === $_GET['wc_paypal_pro_ssl_nag'] ) {
			add_user_meta( get_current_user_id(), '_wc_paypal_pro_ssl_nag_hide', '1', true );
		}
	}

	/**
	 * Register the gateway for use
	 */
	public function register_gateway( $methods ) {
		$methods[] = 'WC_Gateway_PayPal_Pro';
		$methods[] = 'WC_Gateway_PayPal_Pro_Payflow';

		return $methods;
	}

	/**
	 * Show a notice if SSL is disabled
	 */
	public function ssl_check() {

		$settings = get_option( 'woocommerce_paypal_pro_settings', array() );

	 	// Show message if enabled and FORCE SSL is disabled and WordpressHTTPS plugin is not detected
		if ( get_option( 'woocommerce_force_ssl_checkout' ) === 'no'
			&& ! class_exists( 'WordPressHTTPS' )
			&& isset( $settings['enabled'] )
			&& $settings['enabled'] === 'yes'
			&& $settings['testmode'] !== 'yes'
			&& ! get_user_meta( get_current_user_id(), '_wc_paypal_pro_ssl_nag_hide' )
		) {
			echo '<div class="error"><p>' . sprintf( __( 'PayPal Pro requires that the <a href="%s">Force secure checkout</a> option is enabled; your checkout may not be secure! Please enable SSL and ensure your server has a valid SSL certificate - PayPal Pro will only work in test mode.', 'woocommerce-gateway-paypal-pro') . ' <a href="%s">' . __( 'Hide Notice', 'woocommerce' ) . '</a>', admin_url('admin.php?page=woocommerce' ), wp_nonce_url( add_query_arg( 'wc_paypal_pro_ssl_nag', '1' ), 'wc_paypal_pro_ssl_nag_hide' ) ) . '</p></div>';
		}

		return true;
	}

	/**
	 * Capture payment when the order is changed from on-hold to complete or processing
	 *
	 * @param  int $order_id
	 */
	public function capture_payment( $order_id ) {
		$order = new WC_Order( $order_id );

		$txn_id   = get_post_meta( $order_id, '_transaction_id', true );
		$captured = get_post_meta( $order_id, '_paypalpro_charge_captured', true );

		if ( $order->payment_method === 'paypal_pro' && $txn_id && $captured === 'no' ) {

			$paypalpro = new WC_Gateway_PayPal_Pro();

			$url = $paypalpro->testmode ? $paypalpro->testurl : $paypalpro->liveurl;

			$post_data = array(
				'VERSION'         => $paypalpro->api_version,
				'SIGNATURE'       => $paypalpro->api_signature,
				'USER'            => $paypalpro->api_username,
				'PWD'             => $paypalpro->api_password,
				'METHOD'          => 'DoCapture',
				'AUTHORIZATIONID' => $txn_id,
				'AMT'             => $order->get_total(),
				'CURRENCYCODE'    => $order->get_order_currency(),
				'COMPLETETYPE'    => 'Complete'
			);

			if ( $paypalpro->soft_descriptor ) {
				$post_data['SOFTDESCRIPTOR'] = $paypalpro->soft_descriptor;
			}

			$response = wp_remote_post( $url, array(
				'method'        => 'POST',
				'headers'       => array(
					'PAYPAL-NVP' => 'Y'
				),
				'body'          => $post_data,
				'timeout'       => 70,
				'user-agent'    => 'WooCommerce',
				'httpversion'   => '1.1'
			));

			if ( is_wp_error( $response ) ) {
				$order->add_order_note( __( 'Unable to capture charge!', 'woocommerce-gateway-paypal-pro' ) . ' ' . $response->get_error_message() );

			} else {
				parse_str( $response['body'], $parsed_response );

				$order->add_order_note( sprintf( __( 'PayPal Pro charge complete (Transaction ID: %s)', 'woocommerce-gateway-paypal-pro' ), $parsed_response['TRANSACTIONID'] ) );

				update_post_meta( $order->id, '_paypalpro_charge_captured', 'yes' );

				// update the transaction ID of the capture
				update_post_meta( $order->id, '_transaction_id', $parsed_response['TRANSACTIONID'] );
			}
		}

		if ( $order->payment_method === 'paypal_pro_payflow' && $txn_id && $captured === 'no' ) {

			$paypalpro_payflow = new WC_Gateway_PayPal_Pro_PayFlow();

			$url = $paypalpro_payflow->testmode ? $paypalpro_payflow->testurl : $paypalpro_payflow->liveurl;

			$post_data                 = array();
			$post_data['USER']         = $paypalpro_payflow->paypal_user;
			$post_data['VENDOR']       = $paypalpro_payflow->paypal_vendor;
			$post_data['PARTNER']      = $paypalpro_payflow->paypal_partner;
			$post_data['PWD']          = $paypalpro_payflow->paypal_password;
			$post_data['TRXTYPE']      = 'D'; // payflow only allows delayed capture for authorized only transactions
			$post_data['ORIGID']        = $txn_id;

			if ( $paypalpro_payflow->soft_descriptor ) {
				$post_data['MERCHDESCR'] = $paypalpro_payflow->soft_descriptor;
			}

			$response = wp_remote_post( $url, array(
				'method'      => 'POST',
				'body'        => urldecode( http_build_query( $post_data, null, '&' ) ),
				'timeout'     => 70,
				'user-agent'  => 'WooCommerce',
				'httpversion' => '1.1'
			));

			parse_str( $response['body'], $parsed_response );

			if ( is_wp_error( $response ) ) {
				$order->add_order_note( __( 'Unable to capture charge!', 'woocommerce-gateway-paypal-pro' ) . ' ' . $response->get_error_message() );

			} elseif ( $parsed_response['RESULT'] !== '0' )  {
				$order->add_order_note( __( 'Unable to capture charge!', 'woocommerce-gateway-paypal-pro' ) );

				// log it
				$paypalpro_payflow->log( 'Parsed Response ' . print_r( $parsed_response, true ) );

			} else {

				$order->add_order_note( sprintf( __( 'PayPal Pro (Payflow) delay charge complete (PNREF: %s)', 'woocommerce-gateway-paypal-pro' ), $parsed_response['PNREF'] ) );

				update_post_meta( $order->id, '_paypalpro_charge_captured', 'yes' );

				// update the transaction ID of the capture
				update_post_meta( $order->id, '_transaction_id', $parsed_response['PNREF'] );
			}
		}

		return true;
	}

	/**
	 * Cancel pre-auth on refund/cancellation
	 *
	 * @param  int $order_id
	 */
	public function cancel_payment( $order_id ) {
		$order = new WC_Order( $order_id );

		$txn_id   = get_post_meta( $order_id, '_transaction_id', true );
		$captured = get_post_meta( $order_id, '_paypalpro_charge_captured', true );

		if ( $order->payment_method === 'paypal_pro' && $txn_id && $captured === 'no' ) {

			$paypalpro = new WC_Gateway_PayPal_Pro();

			$url = $paypalpro->testmode ? $paypalpro->testurl : $paypalpro->liveurl;

			$post_data = array(
				'VERSION'         => $paypalpro->api_version,
				'SIGNATURE'       => $paypalpro->api_signature,
				'USER'            => $paypalpro->api_username,
				'PWD'             => $paypalpro->api_password,
				'METHOD'          => 'DoVoid',
				'AUTHORIZATIONID' => $txn_id
			);

			$response = wp_remote_post( $url, array(
				'method'		=> 'POST',
				'headers'       => array(
					'PAYPAL-NVP' => 'Y'
				),
				'body'          => $post_data,
				'timeout'       => 70,
				'user-agent'    => 'WooCommerce',
				'httpversion'   => '1.1'
			));

			if ( is_wp_error( $response ) ) {
				$order->add_order_note( __( 'Unable to void charge!', 'woocommerce-gateway-paypal-pro' ) . ' ' . $response->get_error_message() );

			} else {
				parse_str( $response['body'], $parsed_response );

				$order->add_order_note( sprintf( __( 'PayPal Pro void complete (Authorization ID: %s)', 'woocommerce-gateway-paypal-pro' ), $parsed_response['AUTHORIZATIONID'] ) );

				delete_post_meta( $order->id, '_paypalpro_charge_captured' );
				delete_post_meta( $order->id, '_transaction_id' );
			}
		}

		if ( $order->payment_method === 'paypal_pro_payflow' && $txn_id && $captured === 'no' ) {

			$paypalpro_payflow = new WC_Gateway_PayPal_Pro_Payflow();

			$url = $paypalpro_payflow->testmode ? $paypalpro_payflow->testurl : $paypalpro_payflow->liveurl;

			$post_data                 = array();
			$post_data['USER']         = $paypalpro_payflow->paypal_user;
			$post_data['VENDOR']       = $paypalpro_payflow->paypal_vendor;
			$post_data['PARTNER']      = $paypalpro_payflow->paypal_partner;
			$post_data['PWD']          = $paypalpro_payflow->paypal_password;
			$post_data['TRXTYPE']      = 'V'; // void
			$post_data['ORIGID']        = $txn_id;

			$response = wp_remote_post( $url, array(
				'method'      => 'POST',
				'body'        => urldecode( http_build_query( $post_data, null, '&' ) ),
				'timeout'     => 70,
				'user-agent'  => 'WooCommerce',
				'httpversion' => '1.1'
			));

			parse_str( $response['body'], $parsed_response );

			if ( is_wp_error( $response ) ) {
				$order->add_order_note( __( 'Unable to void charge!', 'woocommerce-gateway-paypal-pro' ) . ' ' . $response->get_error_message() );

			} elseif ( $parsed_response['RESULT'] !== '0' ) {
				$order->add_order_note( __( 'Unable to void charge!', 'woocommerce-gateway-paypal-pro' ) . ' ' . $response->get_error_message() );

				// log it
				$paypalpro_payflow->log( 'Parsed Response ' . print_r( $parsed_response, true ) );
			} else {
				$order->add_order_note( sprintf( __( 'PayPal Pro (Payflow) void complete (PNREF: %s)', 'woocommerce-gateway-paypal-pro' ), $parsed_response['PNREF'] ) );

				delete_post_meta( $order->id, '_paypalpro_charge_captured' );
				delete_post_meta( $order->id, '_transaction_id' );
			}
		}
	}
}

add_action( 'plugins_loaded', 'woocommerce_paypal_pro_init', 0 );

/**
 * init function
 *
 * @package  WC_PayPal_Pro
 * @since 4.3.0
 * @return bool
 */
function woocommerce_paypal_pro_init() {
	new WC_PayPal_Pro();

	return true;
}

endif;
