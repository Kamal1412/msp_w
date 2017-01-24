<?php
/*
Plugin Name: LearnPress - WooCommerce Payment Methods Integration
Plugin URI: http://thimpress.com/learnpress
Description: Using the payment system provided by WooCommerce
Author: ThimPress
Version: 2.0
Author URI: http://thimpress.com
Tags: learnpress,woocommerce
Text Domain: learnpress-woo-payment
Domain Path: /languages/
Requires at least: 3.8
Tested up to: 4.6.1
Last updated: 2015-12-01 3:29pm GMT
*/
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
define( 'LP_ADDON_WOOCOMMERCE_PAYMENT_FILE', __FILE__ );
define( 'LP_ADDON_WOOCOMMERCE_PAYMENT_PATH', dirname( __FILE__ ) );
define( 'LP_ADDON_WOOCOMMERCE_PAYMENT_VER', '2.0' );
define( 'LP_ADDON_WOOCOMMERCE_PAYMENT_REQUIRE_VER', '2.0' );

class LP_Woo_Payment_Init {
	protected static $_error = 0;

	/**
	 * init Addon processer
	 *
	 * this called by action 'wp_loaded'
	 */
	public static function init() {
		if ( !defined( 'LEARNPRESS_VERSION' ) || ( version_compare( LEARNPRESS_VERSION, LP_ADDON_WOOCOMMERCE_PAYMENT_REQUIRE_VER, '<' ) ) ) {
			self::$_error = 2;
			add_action( 'admin_notices', array( __CLASS__, 'admin_notice' ) );
			return false;
		}
		self::_includes();
	}

	/**
	 * Include files needed
	 */
	public static function _includes() {
		// load text domain
		self::load_textdomain();
		// WooCommerce actived
		if ( self::woo_actived() && function_exists( 'LP' ) ) {
			// Enabled payment and checkout
			if ( LP_Woo_Payment_Init::is_enabled() && self::woo_payment_enabled() || self::woo_checkout_enabled() ) {
				require_once LP_ADDON_WOOCOMMERCE_PAYMENT_PATH . '/incs/class-wc-product-lp-course.php';
			}
			// init hooks
			self::init_hooks();
			$payment = LP_ADDON_WOOCOMMERCE_PAYMENT_PATH . '/incs/class-lp-wc-payment.php';
			if ( file_exists( $payment ) ) {
				require_once $payment;
			}

			if ( LP_Woo_Payment_Init::is_enabled() && self::woo_checkout_enabled() ) {
				// WooCommerce checkout
				$checkout = require_once LP_ADDON_WOOCOMMERCE_PAYMENT_PATH . '/incs/class-lp-wc-checkout.php';
				if ( file_exists( $checkout ) ) {
					require_once $checkout;
				}
			}
		} else {
			self::$_error = 1;
			add_action( 'admin_notices', array( __CLASS__, 'admin_notice' ) );
		}
	}

	/**
	 * Init hooks
	 */
	public static function init_hooks() {

		if ( !self::is_enabled() ) {
			return;
		}
		// LearnPress hook
		// add to cart
		add_action( 'learn_press_add_to_cart', array( __CLASS__, 'add_course_woo_cart' ), 10, 4 );
		// WooCommerce Empty Cart
		add_action( 'learn_press_emptied_cart', array( __CLASS__, 'empty_woo_cart' ) );
		// remove cart item
		add_action( 'learn_press_remove_cart_item', array( __CLASS__, 'remove_course_woo_cart' ), 10, 4 );
		// trigger create WooCommercer order
		add_action( 'learn_press_checkout_update_order_meta', array( __CLASS__, 'create_woo_order' ), 10, 2 );
		// trigger update WooCommercer order meta when process checkout with LearnPress
		add_action( 'learn_press_checkout_order_processed', array( __CLASS__, 'woo_update_order_meta' ), 10, 2 );
		// trigger update WooCommercer status
		add_action( 'learn_press_order_status_changed', array( __CLASS__, 'woo_update_order_status' ), 10, 3 );

		// product class
		add_filter( 'woocommerce_product_class', array( __CLASS__, 'product_class' ), 10, 4 );
		// WooCommerce Empty Cart
		add_action( 'woocommerce_cart_emptied', array( __CLASS__, 'empty_learnpress_cart' ) );
		// Woo Remove Cart item
		add_action( 'woocommerce_remove_cart_item', array( __CLASS__, 'remove_course_learnpress_cart' ), 10, 2 );
		// disabled select box quantity
		add_filter( 'woocommerce_cart_item_quantity', array( __CLASS__, 'disable_quantity_box' ), 10, 3 );
		// trigger create LearnPress order
		add_action( 'woocommerce_checkout_update_order_meta', array( __CLASS__, 'create_learnpress_order' ), 10, 2 );
		// update LearnPress order meta when checkout with WooCommerce order
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'learnpress_update_order_meta' ), 10, 2 );

		// trigger update learnpress status
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'learnpress_update_order_status' ), 10, 3 );
	}

	/**
	 * Get the product class name.
	 *
	 * @param  WP_Post $the_product
	 * @param  array   $args (default: array())
	 *
	 * @return string
	 */
	public static function product_class( $classname, $product_type, $post_type, $product_id ) {
		if ( LP_COURSE_CPT == $post_type ) {
			$classname = 'WC_Product_LP_Course';
		}
		return $classname;
	}

	/**
	 * Trigger empty LearnPress Cart
	 *
	 * @return mixed
	 */
	public static function empty_learnpress_cart() {
		remove_action( 'woocommerce_cart_emptied', array( __CLASS__, 'empty_learnpress_cart' ) );
		LP()->cart->empty_cart();
		add_action( 'woocommerce_cart_emptied', array( __CLASS__, 'empty_learnpress_cart' ) );
	}

	/**
	 * Trigger Empty WooCommerce Cart
	 *
	 * @return mixed
	 */
	public static function empty_woo_cart() {
		remove_action( 'learn_press_emptied_cart', array( __CLASS__, 'empty_woo_cart' ) );
		WC()->cart->empty_cart();
		add_action( 'learn_press_emptied_cart', array( __CLASS__, 'empty_woo_cart' ) );
	}

	/**
	 * Trigger add to WooCommerce Cart
	 *
	 * @param type $course_id
	 * @param type $quantity
	 * @param type $item_data
	 * @param type $checkout
	 */
	public static function add_course_woo_cart( $course_id, $quantity, $item_data, $cart ) {
		$woo_cart_key = WC()->cart->generate_cart_id( $course_id, 0, array(), $item_data );
		if ( !WC()->cart->get_cart_item( $woo_cart_key ) ) {
			WC()->cart->add_to_cart( $course_id, $quantity, 0, array(), $item_data );
		}
	}

	/**
	 * Remove Course From Woo Cart items
	 *
	 * @param type $item_id
	 * @param type $cart
	 *
	 * @return mixed
	 */
	public static function remove_course_woo_cart( $item_id, $cart ) {
		remove_action( 'learn_press_remove_cart_item', array( __CLASS__, 'remove_course_woo_cart' ), 10, 4 );
		$item         = $cart->get_item( $item_id );
		$woo_cart_key = WC()->cart->generate_cart_id( $item['item_id'], 0, array(), $item['data'] );
		WC()->cart->remove_cart_item( $woo_cart_key );
		add_action( 'learn_press_remove_cart_item', array( __CLASS__, 'remove_course_woo_cart' ), 10, 4 );
	}

	/**
	 * Remove Course item in LearnPress cart
	 *
	 * @param type $item_key
	 * @param type $woo_cart
	 *
	 * @return mixed
	 */
	public static function remove_course_learnpress_cart( $item_key, $woo_cart ) {
		remove_action( 'woocommerce_remove_cart_item', array( __CLASS__, 'remove_course_learnpress_cart' ), 10, 2 );
		$item = $woo_cart->get_cart_item( $item_key );
		LP()->cart->remove_item( $item['product_id'] );
		add_action( 'woocommerce_remove_cart_item', array( __CLASS__, 'remove_course_learnpress_cart' ), 10, 2 );
	}

	/**
	 * Create WooCommerce order
	 *
	 * @param type $order_id
	 * @param type $request
	 *
	 * @return mixed
	 */
	public static function create_woo_order( $order_id, $request ) {
		remove_action( 'learn_press_checkout_update_order_meta', array( __CLASS__, 'create_woo_order' ), 10, 2 );
		remove_action( 'woocommerce_checkout_update_order_meta', array( __CLASS__, 'create_learnpress_order' ), 10, 2 );
		$woo_order_id = WC()->checkout()->create_order();
		// Store Order ID in session so it can be re-used after payment failure
		WC()->session->order_awaiting_payment = $woo_order_id;
		// set post meta
		update_post_meta( $woo_order_id, '_learn_press_order_id', $order_id );
		update_post_meta( $order_id, '_woo_order_id', $woo_order_id );
		add_action( 'learn_press_checkout_update_order_meta', array( __CLASS__, 'create_woo_order' ), 10, 2 );
		add_action( 'woocommerce_checkout_update_order_meta', array( __CLASS__, 'create_learnpress_order' ), 10, 2 );
	}

	/**
	 * Create LearnPress order
	 *
	 * @param type $order_id
	 * @param type $posted
	 *
	 * @return mixed
	 */
	public static function create_learnpress_order( $order_id, $posted ) {
		remove_action( 'woocommerce_checkout_update_order_meta', array( __CLASS__, 'create_learnpress_order' ), 10, 2 );
		remove_action( 'learn_press_checkout_update_order_meta', array( __CLASS__, 'create_woo_order' ), 10, 2 );
		$lp_order_id                          = LP()->checkout()->create_order();
		LP()->session->order_awaiting_payment = $lp_order_id;
		// set post meta
		update_post_meta( $order_id, '_learn_press_order_id', $lp_order_id );
		update_post_meta( $lp_order_id, '_woo_order_id', $order_id );
		add_action( 'woocommerce_checkout_update_order_meta', array( __CLASS__, 'create_learnpress_order' ), 10, 2 );
		add_action( 'learn_press_checkout_update_order_meta', array( __CLASS__, 'create_woo_order' ), 10, 2 );
	}

	/**
	 * Update WooCommerce order item meta when User checkout with LearnPress
	 *
	 * @param type $order_id
	 * @param type $checkout
	 *
	 * @return mixed
	 */
	public static function woo_update_order_meta( $order_id, $checkout ) {
		$woo_order_id = get_post_meta( $order_id, '_woo_order_id', true );
		if ( $woo_order_id ) {
			foreach ( self::get_meta_map() as $key => $name ) {
				update_post_meta( $woo_order_id, $name, get_post_meta( $order_id, $key, true ) );
			}
		}
	}

	/**
	 * Update LearnPress order meta
	 *
	 * @param type $order_id
	 * @param type $posted
	 */
	public static function learnpress_update_order_meta( $order_id, $posted ) {
		$lp_order_id = get_post_meta( $order_id, '_learn_press_order_id', true );
		if ( $lp_order_id ) {
			foreach ( self::get_meta_map() as $key => $name ) {
				update_post_meta( $lp_order_id, $key, get_post_meta( $order_id, $name, true ) );
			}
		}
	}

	/**
	 * Map meta keys from LearnPress order and WooCommerce order
	 *
	 * @return array
	 */
	public static function get_meta_map() {
		// map LP order key with WC order key
		$map_keys = array(
			'_order_currency'       => '_order_currency',
			'_user_id'              => '_customer_user',
			'_order_subtotal'       => '_order_total',
			'_order_total'          => '_order_total',
			'_payment_method_id'    => '_payment_method',
			'_payment_method_title' => '_payment_method_title'
		);

		return apply_filters( 'learnpress_woo_meta_caps', $map_keys );
	}

	/**
	 * Trigger update WooCommercer order status when LearnPress order updated
	 *
	 * @param type $order_id
	 * @param type $old_status
	 * @param type $new_status
	 */
	public static function woo_update_order_status( $order_id, $old_status, $new_status ) {
		remove_action( 'learn_press_order_status_changed', array( __CLASS__, 'woo_update_order_status' ), 10, 3 );
		$woo_order_id = get_post_meta( $order_id, '_woo_order_id', true );
		if ( $woo_order_id ) {
			$woo_order = wc_get_order( $woo_order_id );
			$woo_order->update_status( $new_status );
		}
		add_action( 'learn_press_order_status_changed', array( __CLASS__, 'woo_update_order_status' ), 10, 3 );
	}

	/**
	 * Update LearnPress order status when WooCommerce updated status
	 *
	 * @param type $order_id
	 * @param type $old_status
	 * @param type $new_status
	 */
	public static function learnpress_update_order_status( $order_id, $old_status, $new_status ) {
		remove_action( 'woocommerce_order_status_changed', array( __CLASS__, 'learnpress_update_order_status' ), 10, 3 );
		$lp_order_id = get_post_meta( $order_id, '_learn_press_order_id', true );
		if ( $lp_order_id ) {
			$lp_order = learn_press_get_order( $lp_order_id );
			$lp_order->update_status( $new_status );
		}
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'learnpress_update_order_status' ), 10, 3 );
	}

	/**
	 * Disable select quantity product has post_type 'lp_course'
	 *
	 * @param type $product_quantity
	 * @param type $cart_item_key
	 * @param type $cart_item
	 */
	public static function disable_quantity_box( $product_quantity, $cart_item_key, $cart_item ) {
		return ( $cart_item['data']->post->post_type === 'lp_course' ) ? sprintf( '<span style="text-align: center; display: block">%s</span>', $cart_item['quantity'] ) : $product_quantity;
	}

	public static function is_enabled() {
		return LP()->settings->get( 'woo_payment_enabled' ) === 'yes';
	}

	/**
	 * If use woo checkout
	 * @return boolean
	 */
	public static function woo_checkout_enabled() {
		return self::woo_actived() && LP()->settings->get( 'woo_payment_type' ) === 'checkout';
	}

	/**
	 * Payment is enabled
	 * @return boolean
	 */
	public static function woo_payment_enabled() {
		return LP()->settings->get( 'woo_payment_type' ) == 'payment' && self::woo_actived();
	}

	/**
	 * WooCommercer is actived
	 * @return boolean
	 */
	public static function woo_actived() {
		if ( !function_exists( 'is_plugin_active' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		return is_plugin_active( 'woocommerce/woocommerce.php' );
	}

	/**
	 * Load plugin text domain
	 */
	public static function load_textdomain() {
		if ( function_exists( 'learn_press_load_plugin_text_domain' ) ) {
			learn_press_load_plugin_text_domain( LP_ADDON_WOOCOMMERCE_PAYMENT_PATH, true );
		}
	}

	/**
	 * Add Admin notices
	 */
	public static function admin_notice() {
		switch ( self::$_error ) {
			case 1:
				echo '<div class="error">';
				echo '<p>' . sprintf( __( 'WooCommerce Payment Gateways require <a href="%s">WooCommerce</a> is installed. Please install and active it before you can using this addon.', 'learnpress-woo-payment' ), 'http://wordpress.org/plugins/woocommerce' ) . '</p>';
				echo '</div>';
				break;
			case 2:
				?>
				<div class="error">
					<p><?php printf( __( '<strong>WooCommerce</strong> addon version %s requires LearnPress version %s or higher', 'learnpress-paid-membership-pro' ), LP_ADDON_WOOCOMMERCE_PAYMENT_VER, LP_ADDON_WOOCOMMERCE_PAYMENT_REQUIRE_VER ); ?></p>
				</div>
				<?php
		}

	}

}

add_action( 'plugins_loaded', array( 'LP_Woo_Payment_Init', 'init' ) );