<?php
/**
 * wootpr
 *
 * @package       TPR
 * @author        Rifky Maulana
 * @version       1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:   Woocommerce Thankyou Page Redirect According to Payment Method
 * Plugin URI:    https://github.com/rifkymol
 * Description:   Instead of redirecting to static thank you page, you can use this plugin to redirect to dynamic page depends on what your customer's payment method. you can utilize this plugin by directing them to a page for more instruction and more details about what your customer need to do next after thankyou.
 * Version:       1.0.0
 * Author:        Rifky Maulana
 * Author URI:    https://github.com/rifkymol
 * Text Domain:   wootpr
 * Domain Path:   /languages
 */

class WC_Settings_Tab_TPR {

    /*
     * Bootstraps the class and hooks required actions & filters.
     *
     */
    public static function init() {
        add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50 );
        add_action( 'woocommerce_settings_tabs_settings_thankyou_page_redirect', __CLASS__ . '::settings_tab' );
        add_action( 'woocommerce_update_options_settings_thankyou_page_redirect', __CLASS__ . '::update_settings' );
        add_action( 'woocommerce_thankyou',  __CLASS__ . '::thankyou_custom_conditional_redirect');
    }
    
    
    /*
     * Add a new settings tab to the WooCommerce settings tabs array.
     * Tab Thankyou Page Redirect
     * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
     * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
     */
    public static function add_settings_tab( $settings_tabs ) {
        $settings_tabs['settings_thankyou_page_redirect'] = __( 'Thankyou page Redirect', 'woocommerce-settings-thankyou-page-redirect' );
        return $settings_tabs;
    }


    /*
     * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
     *
     * @uses woocommerce_admin_fields()
     * @uses self::get_settings()
     */
    public static function settings_tab() {
        woocommerce_admin_fields( self::get_settings() );
    }


    /*
     * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
     *
     * @uses woocommerce_update_options()
     * @uses self::get_settings()
     */
    public static function update_settings() {
        woocommerce_update_options( self::get_settings() );
    }


    /*
     * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
     *
     * @return array Array of settings for @see woocommerce_admin_fields() function.
     */
    public static function get_settings() {

        $gateways = WC()->payment_gateways->payment_gateways();
        $list_payment = [
            'settings' => array(
                'name'     => __( 'Settings', 'woocommerce-settings-thankyou-page-redirect' ),
                'type'     => 'title',
                'desc'     => __('below is a list of active payment methods
                if you want to add another payment method, please activate the payment method in Woocommerce > Settings > Payments.<br> Then refresh this page.'),
                'id'       => 'wc_settings_thankyou_page_redirect_section_title'
            ),
        ];

        get_option('wc_settings_thankyou_page_redirect_url');

        if( $gateways ) {
            foreach( $gateways as $gateway ) {
                if( $gateway->enabled == 'yes' ) {
                    $list_payment[$gateway->id] = array(
                        'name' => __( $gateway->title, 'woocommerce-settings-thankyou-page-redirect' ),
                        'type' => 'text',
                        'desc' => __( 'URL Page for '.$gateway->title.' payment method <br> example: http://google.com/ <br> Leave the form blank to use the default thankyou page.' ),
                        'id'   => 'wc_settings_thankyou_page_redirect_'.$gateway->id
                    );
                }
            }
        }

        $lastsection = array(
            'type' => 'sectionend',
            'id' => 'wc_settings_thankyou_page_redirect_section_end'
        );
        array_push($list_payment, $lastsection);

        return apply_filters( 'wc_settings_thankyou_page_redirect_settings', $list_payment );
    }

    public static function thankyou_custom_conditional_redirect($order_id){
        global $wp;

        $order = wc_get_order( $order_id );
        if ( ! $order->has_status( 'failed' ) ) {
            $chosen_payment_method = $order->get_payment_method();
            $redirect_payment_method = 'bacs';
            $gateways = WC()->payment_gateways->get_available_payment_gateways();
            if( $gateways ) {
                foreach( $gateways as $gateway ) {
                    if( $gateway->enabled == 'yes' ) {
                        if ($chosen_payment_method == $gateway->id) {
                            $redirect_url = get_option('wc_settings_thankyou_page_redirect_'.$gateway->id);
                            if (!empty($redirect_url)) {
                                wp_redirect($redirect_url);
                                exit;
                            }else {
                                exit;
                            }
                        }
                    }
                }
            }
        }
    }

}

WC_Settings_Tab_TPR::init();
