<?php
/**
 * Plugin Name: Montapacking Checkout WooCommerce Extension
 * Plugin URI: https://github.com/Montapacking/woocommerce-monta-checkout
 * Description: Montapacking Check-out extension
 * Version: 1.0.4
 * Author: Montapacking
 * Author URI: https://www.montapacking.nl/
 * Developer: Montapacking
 * Developer URI: https://www.montapacking.nl/
 * Text Domain: montapacking-checkout-woocommerce-extension
 * Domain Path: /languages
 *
 * WC requires at least: 3.6.5
 * WC tested up to: 3.6.5
 *
 * Copyright: © 2009-2019 WooCommerce.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

include('montapacking-config.php');
include('montapacking-class.php');

$api = new MontapackingShipping(esc_attr( get_option('monta_shop')), esc_attr( get_option('monta_username')), esc_attr( get_option('monta_password')), false);

## Add config actions
add_action('admin_menu', 'montacheckout_init_menu');

add_action('admin_init', function () {
    register_setting('montapacking-plugin-settings', 'monta_shop');
    register_setting('montapacking-plugin-settings', 'monta_username');
    register_setting('montapacking-plugin-settings', 'monta_password');
    register_setting('montapacking-plugin-settings', 'monta_google_key');
});

// Include installed Language packs
load_plugin_textdomain('montapacking-checkout', false, dirname(plugin_basename(__FILE__)) . '/languages/');

$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'montacheckout_plugin_add_settings_link');


## Check of we in woocommerce zijn
if (true === $api->checkConnection() && in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    ## Standaard woocommerce verzending uitschakelen
    add_filter('woocommerce_shipping_calculator_enable_postcode', false);
    add_filter('woocommerce_shipping_calculator_enable_city', false);

    ## Shipping form in checkout plaatsen
    add_action('woocommerce_before_order_notes', array('montapacking', 'shipping'), 10);

    ## Shipping package toevoegen
    add_action('woocommerce_cart_shipping_packages', array('montapacking', 'shipping_package'), 10);

    ## Shipping cost calculation
    add_action('woocommerce_package_rates', array('montapacking', 'shipping_calculate'), 10);

    ## Shipping cost calculation
    add_action('woocommerce_review_order_before_shipping', array('montapacking', 'shipping_calculate'), 10);
    add_filter('woocommerce_cart_get_total', array('montapacking', 'shipping_total'), 10, 1);
    add_filter('woocommerce_cart_get_shipping_total', array('montapacking', 'shipping_total'), 10, 1);

    ## Shipping cost calculation
    update_option('woocommerce_enable_shipping_calc', 'no');
    update_option('woocommerce_shipping_cost_requires_address', 'no');

    ## Validation rules
    add_action('woocommerce_after_checkout_validation', array('montapacking', 'checkout_validate'), 10, 2);

    ## Shipment data opslaan bij order
    add_action('woocommerce_checkout_create_order', array('montapacking', 'checkout_store'), 10, 2);

    // CSS/JS scripts registreren
    add_action('wp_enqueue_scripts', 'montacheckout_enqueue_scripts');

    ## Ajax actions
    add_action('wp_ajax_monta_shipping_options', array('montapacking', 'shipping_options'));
    add_action('wp_ajax_nopriv_monta_shipping_options', array('montapacking', 'shipping_options'));

    ## Init session usage
    add_action('init', 'montacheckout_register_session');

    add_action('wp_footer', 'montacheckout_footer');


} else {
    add_action('woocommerce_checkout_create_order', 'before_checkout_create_order', 20, 2);
}

function montacheckout_plugin_add_settings_link($links)
{
    $settings_link[] = '<a href="options-general.php?page=montapacking-settings">' . __('Settings') . '</a>';

    #array_push( $links, $settings_link );
    return array_merge($settings_link, $links);
}

function montacheckout_enqueue_scripts()
{
    // CSS
    if (is_cart() || is_checkout()) {
        wp_enqueue_style('montapacking_checkout_storelocator', plugins_url('montapacking-checkout-woocommerce-extension/assets/css/monta-storelocator.css'), date("h:i:s"));
        wp_enqueue_style('montapacking_checkout_plugin', plugins_url('montapacking-checkout-woocommerce-extension/assets/css/monta-shipping.css'), date("h:i:s"));

        // Javascript
        wp_enqueue_script('montapacking_checkout_plugin_map', 'https://maps.google.com/maps/api/js?key=' . esc_attr(get_option('monta_google_key')), ['jquery']);
        wp_enqueue_script('montapacking_checkout_plugin_handlebars', plugins_url('montapacking-checkout-woocommerce-extension/assets/js/monta-handlebars.js'), ['jquery'], date("h:i:s"));
        wp_enqueue_script('montapacking_checkout_plugin_storelocator_js', plugins_url('montapacking-checkout-woocommerce-extension/assets/js/monta-storelocator.js'), ['jquery'], date("h:i:s"));
        wp_enqueue_script('montapacking_checkout_plugin_monta', plugins_url('montapacking-checkout-woocommerce-extension/assets/js/monta-shipping.js'), ['jquery'], date("h:i:s"));
    }
}

function montacheckout_footer()
{
    require_once 'views/pickup.php';
}

function montacheckout_register_session()
{
    if (!session_id()) {
        session_start();
    }
}

function montacheckout_init_menu()
{
    add_submenu_page('options-general.php', 'Montapacking', 'Montapacking', 'manage_options', 'montapacking-settings', 'montacheckout_render_settings');
}

function montacheckout_render_settings()
{
    // Check that the user is allowed to update options
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    ?>

    <div class="wrap">
        <form action="options.php" method="post">

            <?php
            settings_fields('montapacking-plugin-settings');
            do_settings_sections('montapacking-plugin-settings');
            ?>
            <h1>Montapacking API Settings</h1>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="monta_shop">Shop</label></th>
                    <td><input type="text" name="monta_shop"
                               value="<?php echo esc_attr(get_option('monta_shop')); ?>" size="50"/></td>
                </tr>

                <tr>
                    <th scope="row"><label for="monta_username">Username</label></th>
                    <td><input type="text" name="monta_username"
                               value="<?php echo esc_attr(get_option('monta_username')); ?>" size="50"/></td>
                </tr>

                <tr>
                    <th scope="row"><label for="monta_password">Password</label></th>
                    <td><input type="password" name="monta_password"
                               value="<?php echo esc_attr(get_option('monta_password')); ?>" size="50"/></td>
                </tr>

            </table>

            <h1>Google API Settings</h1>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="monta_google_key">API Key</label></th>
                    <td><input type="text" name="monta_google_key"
                               value="<?php echo esc_attr(get_option('monta_google_key')); ?>" size="50"/></td>
                </tr>
            </table>

            <?php submit_button(); ?>

        </form>
    </div>
    <?php
}

function before_checkout_create_order($order, $data)
{

    $arr = array();
    $arr[] = "Webshop was unable to connect to Montapacking. Please contact Montapacking";
    $arr = implode("\n\r", $arr);

    $order->add_meta_data('No Connection with Montapacking', $arr, true);

}