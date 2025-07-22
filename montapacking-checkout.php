<?php
/**
 * Plugin Name: Monta Checkout
 * Plugin URI: https://github.com/Montapacking/woocommerce-monta-checkout
 * Description: Monta Check-out extension
 * Version: 1.58.54
 * Author: Monta
 * Author URI: https://www.monta.nl/
 * Developer: Monta
 * Developer URI: https://www.monta.nl/
 * Text Domain: montapacking-checkout-woocommerce-extension
 * Domain Path: /languages
 *
 * WC requires at least: 5.0.0
 * WC tested up to: 9.9.5
 * Requires PHP: 8.0
 *
 * Copyright: © 2009-2021 WooCommerce.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

use Monta\Packing;

// Include config and autoload
include('montapacking-config.php');

if (esc_attr(get_option('monta_logerrors'))) {
    define('WC_LOG_HANDLER', 'WC_Log_Handler_DB');
}
// Declare WooCommerce HPOS compatibility
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

## Add config actions
add_action('admin_menu', 'montacheckout_init_menu');

add_action('admin_init', function () {
    register_setting('montapacking-plugin-settings', 'monta_shop');
    register_setting('montapacking-plugin-settings', 'monta_username');
    register_setting('montapacking-plugin-settings', 'monta_password');
    register_setting('montapacking-plugin-settings', 'monta_google_key');
    register_setting('montapacking-plugin-settings', 'monta_logerrors');
    register_setting('montapacking-plugin-settings', 'monta_pickupname');
    register_setting('montapacking-plugin-settings', 'monta_shippingcosts_fallback_woocommerce');
    register_setting('montapacking-plugin-settings', 'monta_shippingcosts');
    register_setting('montapacking-plugin-settings', 'monta_shippingcosts_start');
    register_setting('montapacking-plugin-settings', 'monta_leadingstock');
    register_setting('montapacking-plugin-settings', 'monta_disabledelivery');
    register_setting('montapacking-plugin-settings', 'monta_hidedhlpackstations');
    register_setting('montapacking-plugin-settings', 'monta_disablepickup');
    register_setting('montapacking-plugin-settings', 'monta_disablecollect');
    register_setting('montapacking-plugin-settings', 'monta_checkproductsonsku');
    register_setting('montapacking-plugin-settings', 'monta_standardshipmentname');
    register_setting('montapacking-plugin-settings', 'monta_max_pickuppoints');
    register_setting('montapacking-plugin-settings', 'monta_show_seperate_shipping_email_and_phone_fields');
    register_setting('montapacking-plugin-settings', 'monta_exclude_discounted_shipping_for_role');
    register_setting('montapacking-plugin-settings', 'monta_collect_name');
    register_setting('montapacking-plugin-settings', 'monta_show_zero_costs_as_free');
    register_setting('montapacking-plugin-settings', 'monta_afh_image_path');
});

// Include installed Language packs
load_plugin_textdomain('montapacking-checkout', false, dirname(plugin_basename(__FILE__)) . '/languages/');

$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'montacheckout_plugin_add_settings_link');

add_action('wp_loaded', 'montacheckout_init');

function montacheckout_init()
{
    ## Check of we in woocommerce zijn
    if (is_plugin_active('woocommerce/woocommerce.php')) {
        remove_action('woocommerce_cart_totals_after_order_total', ['WC_Subscriptions_Cart', 'display_recurring_totals'], 10);
        remove_action('woocommerce_review_order_after_order_total', ['WC_Subscriptions_Cart', 'display_recurring_totals'], 10);

        ## Standaard woocommerce verzending uitschakelen
        add_filter('woocommerce_shipping_calculator_enable_postcode', false);
        add_filter('woocommerce_shipping_calculator_enable_city', false);

        ## Shipping form in checkout plaatsen
        add_action('woocommerce_before_order_notes', [Packing::class, 'shipping'], 10);

        ## Shipping package toevoegen
        add_action('woocommerce_cart_shipping_packages', [Packing::class, 'shipping_package'], 10);

        ## Shipping cost calculation
        add_action('woocommerce_package_rates', [Packing::class, 'shipping_calculate'], 10);

        ## Shipping cost calculation
        add_action('woocommerce_review_order_before_shipping', [Packing::class, 'shipping_calculate'], 10);
        // TODO argument is deprecated
        add_filter('woocommerce_cart_get_total', [Packing::class, 'shipping_total'], PHP_INT_MAX, 1);

        ## Shipping cost calculation
        update_option('woocommerce_enable_shipping_calc', 'no');
        update_option('woocommerce_shipping_cost_requires_address', 'no');

        ## Validation rules
        add_action('woocommerce_after_checkout_validation', [Packing::class, 'checkout_validate'], 10, 2);

        ## Shipment data opslaan bij order
        add_action('woocommerce_checkout_create_order', [Packing::class, 'checkout_store'], 10, 2);

        // CSS/JS scripts registreren
        add_action('wp_enqueue_scripts', 'montacheckout_enqueue_scripts');

        ## Ajax actions
        add_action('wp_ajax_monta_shipping_options', [Packing::class, 'shipping_options']);
        add_action('wp_ajax_nopriv_monta_shipping_options', [Packing::class, 'shipping_options']);

        //add_filter('woocommerce_order_shipping_to_display_shipped_via', 'filter_woocommerce_order_shipping_to_display_shipped_via', 10, 2);
        add_filter('woocommerce_order_shipping_method', 'filter_woocommerce_order_shipping_method', 10, 2);

        add_filter('woocommerce_cart_needs_shipping_address', 'filter_woocommerce_cart_needs_shipping_address', 10, 1);
        add_filter('woocommerce_cart_totals_order_total_html', [Packing::class, 'taxes'], 20, 1);
        add_action('woocommerce_cart_totals_before_shipping', 'filter_review_order_before_shipping');
        add_action("woocommerce_removed_coupon", 'updatecheckout');
        add_action("woocommerce_applied_coupon", 'updatecheckout');
        add_action('monta_shipping_calculate_html_output', [Packing::class, 'shipping_calculate_html_output']);

        if (esc_attr(get_option('monta_show_seperate_shipping_email_and_phone_fields'))) {
            add_filter('woocommerce_checkout_fields', 'ts_shipping_phone_checkout');
            add_action('woocommerce_admin_order_data_after_shipping_address', 'ts_shipping_phone_checkout_display');
        }
    } else {
        add_action('woocommerce_checkout_create_order', 'checkout_create_order', 20, 2);
        add_action('woocommerce_before_checkout_form', 'before_checkout_form', 20, 2);
        add_action('woocommerce_package_rates', 'overrule_package_rates', 20, 2);
    }
}

function filter_review_order_before_shipping($needs_shipping_address)
{
    do_action('woocommerce_review_order_before_shipping');
}

function filter_woocommerce_cart_needs_shipping_address($needs_shipping_address)
{
    $cart_needs_shipping_address = true;

    if (empty($_POST['montapacking']) || !is_array($_POST['montapacking'])) {
        return $cart_needs_shipping_address;
    }

    $postdata = sanitize_post($_POST['montapacking']);

    if ($postdata['shipment']['type'] == 'pickup') {
        $cart_needs_shipping_address = false;
    }

    return $cart_needs_shipping_address;
}

function updateCheckout()
{
    echo '<script type="text/javascript">jQuery("#billing_postcode").trigger("change");</script>';
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
        wp_enqueue_style('montapacking_checkout_plugin', plugins_url('montapacking-checkout-woocommerce-extension/assets/css/monta-shipping.css'), [], date("h:i:s"));

        // Javascript
        wp_enqueue_script('montapacking_checkout_plugin_map', 'https://maps.google.com/maps/api/js?key=' . esc_attr(get_option('monta_google_key')), ['jquery']);
        wp_enqueue_script('montapacking_checkout_plugin_handlebars', plugins_url('montapacking-checkout-woocommerce-extension/assets/js/monta-handlebars.js'), ['jquery'], date("h:i:s"));
        wp_enqueue_script('montapacking_checkout_plugin_storelocator_js', plugins_url('montapacking-checkout-woocommerce-extension/assets/js/monta-storelocator.js'), ['jquery'], date("h:i:s"));
        wp_enqueue_script('montapacking_checkout_plugin_monta', plugins_url('montapacking-checkout-woocommerce-extension/assets/js/monta-shipping.js'), ['jquery'], date("h:i:s"));
        wp_enqueue_script('montapacking_checkout_plugin_popper', plugins_url('montapacking-checkout-woocommerce-extension/assets/js/popper.min.js'), date("h:i:s"));
        wp_enqueue_script('wc-price-js', plugin_dir_url(__FILE__) . 'assets/js/wc_price.js', ['jquery'], '1.0', false);

        $wc_store_object = [
            'html' => false,
            'currency_symbol' => get_woocommerce_currency_symbol(get_woocommerce_currency()),
            'currency_position' => get_option('woocommerce_currency_pos', true),
            'decimal_separator' => wc_get_price_decimal_separator(),
            'currency_format_trim_zeros' => wc_get_price_thousand_separator(),
            'currency_format_num_decimals' => wc_get_price_decimals(),
            'price_format' => get_woocommerce_price_format(),
        ];

        wp_add_inline_script('wc-price-js', ' var wc_settings_args=' . wp_json_encode($wc_store_object) . ';');
        wp_localize_script('montapacking_checkout_plugin_monta', 'shopData', [
            'language' => get_bloginfo('language'),
            'translations' => [
                'free' => __('Free', 'montapacking-checkout')
            ]
        ]);
    }
}

/**
 * @return void
 * @deprecated - This method is not referenced anywhere
 */
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

function ts_shipping_phone_checkout($fields)
{
    $fields['shipping']['shipping_email'] = [
        'label' => __('Email', 'montapacking-checkout'),
        'type' => 'email',
        'required' => false,
        'class' => ['form-row-wide'],
        'validate' => ['email'],
        'autocomplete' => 'email',
        'priority' => 25,
    ];

    $fields['shipping']['shipping_phone'] = [
        'label' => __('Phone', 'montapacking-checkout'),
        'type' => 'tel',
        'required' => false,
        'class' => ['form-row-wide'],
        'validate' => ['tel'],
        'autocomplete' => 'tel',
        'priority' => 26,
    ];

    return $fields;
}

function ts_shipping_phone_checkout_display($order)
{
    $order = wc_get_order($order->get_id());

    $shippingphone = $order->get_meta('_shipping_phone', true);
    $shippingemail = $order->get_meta('_shipping_email', true);

    if (isset($shippingemail) && trim($shippingemail) != "") {
        echo '<p><b>' . __('Email', 'montapacking-checkout') . '</b> ' . $order->get_meta('_shipping_email', true) . '</p>';
    }

    if (isset($shippingphone) && trim($shippingphone) != "") {
        echo '<p><b>' . __('Phone', 'montapacking-checkout') . '</b> ' . $order->get_meta('_shipping_phone', true) . '</p>';
    }
}

function montacheckout_render_settings()
{
    wp_enqueue_media();

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
            <h1>Monta Checkout</h1>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="monta_shop">Shop * </label></th>
                    <td><input required type="text" name="monta_shop"
                               value="<?php echo esc_attr(get_option('monta_shop')); ?>" size="50"/>
                        <br><i style="font-size:12px">The name of the webshop in Monta Portal. Name can be found <a
                                    target="_new"
                                    href="https://montaportal.nl/Home/CustomerSettings#CheckoutOptions">here</a>.</i>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="monta_username">Username * </label></th>
                    <td><input required type="text" name="monta_username"
                               value="<?php echo esc_attr(get_option('monta_username')); ?>" size="50"/>
                        <br><i style="font-size:12px">The username of Monta REST API provided by Montapacking.</i>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="monta_password">Password * </label></th>
                    <td><input required type="password" name="monta_password"
                               value="<?php echo esc_attr(get_option('monta_password')); ?>" size="50"/>
                        <br><i style="font-size:12px">The password of Monta REST API provided by Montapacking.</i>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="monta_shippingcosts_fallback_woocommerce">Use WooCommerce shipping costs
                            as fallback *</label></th>
                    <td>
                        <input type="checkbox" name="monta_shippingcosts_fallback_woocommerce"
                               value="1" <?php checked('1', get_option('monta_shippingcosts_fallback_woocommerce')); ?>/>
                        <br><i style="font-size:12px">Use shipping costs set in WooCommerce settings instead of amount
                            set below as fallback if API connection is unsuccessful.</i>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="monta_shippingcosts">Shipping Costs *</label></th>
                    <td>
                        <input required type="number" name="monta_shippingcosts" step="0.01"
                               value="<?php echo esc_attr(get_option('monta_shippingcosts')); ?>" size="5"/>
                        <br><i style="font-size:12px">The base shipping costs used when there is no API connection
                            available.</i>
                    </td>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="monta_shippingcosts_start">Start shipping Costs *</label></th>
                    <td>
                        <input required type="number" name="monta_shippingcosts_start" step="0.01"
                               value="<?php echo esc_attr(get_option('monta_shippingcosts_start')); ?>" size="5"/>
                        <br><i style="font-size:12px">The start shipping costs which is used in the shopping cart
                            available.</i>
                    </td>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="monta_checkproductsonsku">Check products on SKU</label></th>
                    <td><input type="checkbox" name="monta_checkproductsonsku"
                               value="1" <?php checked('1', get_option('monta_checkproductsonsku')); ?>/>
                        <br><i style="font-size:12px">If this option is active, the stock, sizes and weights of the SKUs
                            are checked with the data known in the Montaportal.</i>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="monta_logerrors">Log errors</label></th>
                    <td><input type="checkbox" name="monta_logerrors"
                               value="1" <?php checked('1', get_option('monta_logerrors')); ?>/>
                        <br><i style="font-size:12px">Turn on logs which are shown <a
                                    href=/wp-admin/admin.php?page=wc-status&tab=logs">here</a>.</i>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="monta_disabledelivery">Disable delivery options</label></th>
                    <td><input type="checkbox" name="monta_disabledelivery"
                               value="1" <?php checked('1', get_option('monta_disabledelivery')); ?>/>
                        <br><i style="font-size:12px">When disabled no delivery options are shown.</i>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="monta_hidedhlpackstations">Hide DHL Packstations</label></th>
                    <td><input type="checkbox" name="monta_hidedhlpackstations"
                               value="1" <?php checked('1', get_option('monta_hidedhlpackstations')); ?>/>
                        <br><i style="font-size:12px">When disabled no DHL Packstations are shown.</i>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="monta_disablepickup">Disable pickup points</label></th>
                    <td><input type="checkbox" name="monta_disablepickup"
                               value="1" <?php checked('1', get_option('monta_disablepickup')); ?>/>
                        <br><i style="font-size:12px">When disabled no pickup points are shown.</i>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="monta_disablecollect">Disable collect in store</label></th>
                    <td><input type="checkbox" name="monta_disablecollect"
                               value="1" <?php checked('1', get_option('monta_disablecollect')); ?>/>
                        <br><i style="font-size:12px">When disabled no store collect options are shown.</i>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="monta_max_pickuppoints">Max pickup points *</label></th>
                    <td>
                        <input required type="number" name="monta_max_pickuppoints" step="1" min="1" max="10" size="5"
                               value="<?php echo esc_attr(get_option('monta_max_pickuppoints')) <= 0 ? 3 : esc_attr(get_option('monta_max_pickuppoints')); ?>"/>
                        <br><i style="font-size:12px">The number of pickupoints shown in the overview view</i>
                    </td>
                    </td>
                </tr>

                <!--                <tr>-->
                <!--                    <th scope="row"><label for="monta_leadingstock">monta_leadingstock</label></th>-->
                <!--                    <td>-->
                <input type="hidden" name="monta_leadingstock"
                       value="1" <?php checked('1', get_option('monta_leadingstock')); ?>/>
                <!--                        <br><i style="font-size:12px">monta_leadingstock.</i>-->
                <!--                    </td>-->
                <!--                </tr>-->
                <!--                <input type="hidden" name="monta_leadingstock" value="woocommerce">-->

                <tr>
                    <th scope="row"><label for="monta_pickupname">Pickup name</label></th>
                    <td><input type="text" name="monta_pickupname"
                               value="<?php echo esc_attr(get_option('monta_pickupname')); ?>" size="50"/>
                        <br><i style="font-size:12px">In some situations you want to change the company name of the
                            option 'AFH'. Here you can override this name.</i>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="monta_collect_name">Store collect name</label></th>
                    <td><input type="text" name="monta_collect_name"
                               value="<?php echo esc_attr(get_option('monta_collect_name')); ?>" size="50"/>
                        <br><i style="font-size:12px">In some situations you want to change the name of the
                            option 'Store collect'. Here you can override this name.</i>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="monta_show_zero_costs_as_free">Shipping costs free</label></th>
                    <td><input type="checkbox" name="monta_show_zero_costs_as_free"
                               value="1" <?php checked("1", get_option('monta_show_zero_costs_as_free')); ?>/>
                        <br><i style="font-size:12px">Show the text "free" in stead of 0 when the shipping costs are
                            free.</i>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="monta_standardshipmentname">Standard shipment name</label></th>
                    <td><input type="text" name="monta_standardshipmentname"
                               value="<?php echo esc_attr(get_option('monta_standardshipmentname')); ?>" size="50"/>
                        <br><i style="font-size:12px">We have a standard shipment option in the Montaportal. Here you
                            can override this name.</i>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="monta_afh_image_path">Store collect image path</label></th>
                    <td><input type="text" id="monta_afh_image_path" name="monta_afh_image_path"
                               value="<?php echo esc_attr(get_option('monta_afh_image_path')); ?>" size="50"/>
                        <span><input type=button id="upload_image_button" name="upload_image_button"
                                     value="Upload image"/></span>
                        <br><i style="font-size:12px">Here you can set the path of your own image for store collect.</i>

                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="monta_show_seperate_shipping_email_and_phone_fields">Shipping phone
                            number and email</label></th>
                    <td><input type="checkbox" name="monta_show_seperate_shipping_email_and_phone_fields"
                               value="1" <?php checked('1', get_option('monta_show_seperate_shipping_email_and_phone_fields')); ?>/>
                        <br><i style="font-size:12px">Show separate fields for shipping phone number and email</i>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="monta_exclude_discounted_shipping_for_role">Exclude shipping
                            discount</label>
                    </th>
                    <td><input type="text" name="monta_exclude_discounted_shipping_for_role"
                               value="<?php echo esc_attr(get_option('monta_exclude_discounted_shipping_for_role')); ?>"
                               size="50"/>
                        <br><i style="font-size:12px">Role for which you want to exclude shipping discounts</i>
                    </td>

                </tr>

            </table>

            <h1>Google API Settings</h1>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="monta_google_key">API Key</label></th>
                    <td><input type="text" name="monta_google_key"
                               value="<?php echo esc_attr(get_option('monta_google_key')); ?>" size="50"/>
                        <br><i style="font-size:12px">A Google API key is required if you want to make use of the world
                            map. A Google key can be created
                            <a target="_new" href="https://console.cloud.google.com/">here</a>.</i>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>

    <script>
        jQuery(document).ready(function ($) {
            $('#upload_image_button').click(function (e) {
                e.preventDefault();
                var image = wp.media({
                    title: 'Upload Image',
                    // multiple: true if you want to upload multiple files at once
                    multiple: false,
                    library: {
                        type: ['image']
                    },
                    type: 'image'
                }).open()
                    .on('select', function (e) {
                        // This will return the selected image from the Media Uploader, the result is an object
                        const uploaded_image = image.state().get('selection').first();
                        // We convert uploaded_image to a JSON object to make accessing it easier
                        // Output to the console uploaded_image
                        const image_url = uploaded_image.toJSON().url.toString();
                        const formatted_url = image_url.split("/").slice(3).join("/");

                        // Let's assign the url value to the input field
                        $('#monta_afh_image_path').val("/" + formatted_url);
                    });
            });
        });
    </script>
    <?php
}

function filter_woocommerce_order_shipping_method($html, $instance)
{
    $json = json_decode($instance);
    $order = wc_get_order($json->id);

    $line_items_shipping = $order->get_items('shipping');

    foreach ($line_items_shipping as $item_id => $item) {
        $meta_data = $item->get_meta_data();

        foreach ($meta_data as $value) {
            if ($value->key == 'Shipmentmethod') {
                $method = strip_tags($value->value);

                if ($value->value == 'SEL,SELBuspakje') {
                    $method = strip_tags('DHL');
                }
                return $method;
            }

            if ($value->key == 'Pickup Data') {
                return strip_tags($value->value['description']);
            }
        }
    }

    $names = [];
    foreach ($order->get_shipping_methods() as $shipping_method) {
        $names[] = $shipping_method->get_name();
    }

    return implode(', ', $names);
}