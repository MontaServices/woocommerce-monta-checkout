<?php
/**
 * Plugin Name: Montapacking Checkout WooCommerce Extension
 * Plugin URI: https://github.com/Montapacking/woocommerce-monta-checkout
 * Description: Montapacking Check-out extension
 * Version: 1.1
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

if (esc_attr(get_option('monta_logerrors'))) {
    define('WC_LOG_HANDLER', 'WC_Log_Handler_DB');
}

## Add config actions
add_action('admin_menu', 'montacheckout_init_menu');

add_action('admin_init', function () {
    register_setting('montapacking-plugin-settings', 'monta_shop');
    register_setting('montapacking-plugin-settings', 'monta_username');
    register_setting('montapacking-plugin-settings', 'monta_password');
    register_setting('montapacking-plugin-settings', 'monta_google_key');
    register_setting('montapacking-plugin-settings', 'monta_logerrors');
    register_setting('montapacking-plugin-settings', 'monta_shippingcosts');

});

// Include installed Language packs
load_plugin_textdomain('montapacking-checkout', false, dirname(plugin_basename(__FILE__)) . '/languages/');

$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'montacheckout_plugin_add_settings_link');


## Check of we in woocommerce zijn
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

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

    ## Init session usage#
    add_action('init', 'montacheckout_register_session');

    add_filter('woocommerce_order_shipping_to_display_shipped_via', 'filter_woocommerce_order_shipping_to_display_shipped_via', 10, 2);


} else {
    add_action('woocommerce_checkout_create_order', 'checkout_create_order', 20, 2);
    add_action('woocommerce_before_checkout_form', 'before_checkout_form', 20, 2);
    add_action('woocommerce_package_rates', 'overrule_package_rates', 20, 2);
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
            <h1>Montapacking Checkout WooCommerce Extension Settings</h1>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="monta_shop">Shop</label></th>
                    <td><input required type="text" name="monta_shop"
                               value="<?php echo esc_attr(get_option('monta_shop')); ?>" size="50"/>
                        <br><i style="font-size:12px">The name of the webshop in Monta Portal. Name can be found <a
                                    target="_new" href="https://montaportal.nl/Home/CustomerSettings#CheckoutOptions">here</a></i>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="monta_username">Username</label></th>
                    <td><input required type="text" name="monta_username"
                               value="<?php echo esc_attr(get_option('monta_username')); ?>" size="50"/>
                        <br><i style="font-size:12px">The username of Monta REST API provided by Montapacking .</i>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="monta_password">Password</label></th>
                    <td><input required type="password" name="monta_password"
                               value="<?php echo esc_attr(get_option('monta_password')); ?>" size="50"/>
                        <br><i style="font-size:12px">The password of Monta REST API provided by Montapacking .</i>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="monta_shippingcosts">Shipping Costs</label></th>
                    <td>
                        <input required type="number" name="monta_shippingcosts" step="0.01"
                               value="<?php echo esc_attr(get_option('monta_shippingcosts')); ?>" size="5"/>
                        <br><i style="font-size:12px">The base shipping costs used when there is no API connection
                            available.</i>
                    </td>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="monta_shop">Log errors</label></th>
                    <td><input type="checkbox" name="monta_logerrors"
                               value="1" <?php checked('1', get_option('monta_logerrors')); ?>/></td>
                </tr>

            </table>

            <h1>Google API Settings</h1>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="monta_google_key">API Key</label></th>
                    <td><input required type="text" name="monta_google_key"
                               value="<?php echo esc_attr(get_option('monta_google_key')); ?>" size="50"/>
                        <br><i style="font-size:12px">A Google API key is required for this plug-in. Key can be created
                            <a target="_new" href="https://console.cloud.google.com/">here</a></i>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>

        </form>
    </div>
    <?php
}

function filter_woocommerce_order_shipping_to_display_shipped_via($html, $instance)
{

    $json = json_decode($instance);

    $order = wc_get_order($json->id);

    $line_items_shipping = $order->get_items('shipping');

    $method = (string)$order->get_shipping_method();

    // Loop through order items
    foreach ($line_items_shipping as $item_id => $item) {

        $meta_data = $item->get_meta_data();

        foreach ($meta_data as $value) {

            if ($value->key == 'Shipmentmethod') {

                $method = strip_tags($value->value);

                if ($value->value == 'SEL,SELBuspakje') {
                    $method = strip_tags('DHL Parcel');
                }
            }

            if ($value->key == 'Pickup Data') {
                $method = strip_tags($value->value['description']);
            }
        }
    }

    return "&nbsp;<small class='shipped_via'>" . sprintf(__('via %s', 'woocommerce'), esc_attr($method)) . "</small>";
}

;


