<?php
namespace Monta;

use Monta\CheckoutApiWrapper\MontapackingShipping;
use Monta\CheckoutApiWrapper\Objects\Settings;
use Monta\Helper\System;

class Packing
{
    private static $WooCommerceShippingMethod = null;

    public static function shipping_package($packages)
    {
        if (isset($packages[0])) {
            self::$WooCommerceShippingMethod = self::getWooCommerceShippingMethod($packages[0]);
        }

        return [];
    }

    public static function checkout_validate($data, $errors)
    {
        if (empty($_POST['montapacking']) || !is_array($_POST['montapacking'])) {
            return;
        }

        $cart_items = WC()->cart->get_cart();

        $has_virtual_products = false;

        // Controleer elk product in de winkelwagen
        foreach ($cart_items as $cart_item) {
            $product = wc_get_product($cart_item['product_id']);

            if ($product && $product->get_virtual()) {
                $has_virtual_products = true;
            }
        }

        $type = sanitize_post($_POST['montapacking']);
        $pickup = $type['pickup'];
        $shipment = $type['shipment'];
        $time = $shipment['time'];

        $shipper = "";
        if (isset($shipment['shipper'])) {
            $shipper = $shipment['shipper'];
        }

        $items = null;
        if (!isset($shipment['type']) || $shipment['type'] == '') {
            $errors->add('shipment', __('Select a shipping method.', 'montapacking-checkout'));
        }

        switch ($shipment['type']) {
            case 'delivery':

                $frames = self::get_frames('delivery');

                if ($frames !== null) {
                    // Frames naar handige array zetten
                    $items = $frames['DeliveryOptions'];
                }

                break;
            case 'pickup':

                if ((!isset($pickup) || !isset($pickup['code']) || $pickup['code'] == '') && ($has_virtual_products == false)) {
                    $errors->add('shipment', __('Select a pickup location.', 'montapacking-checkout'));
                }

                if (isset($pickup['postnumber']) && trim($pickup['postnumber']) == '' && $has_virtual_products == false) {
                    $errors->add('shipment', __('Please enter a postal number, this is mandatory for this pick-up option', 'montapacking-checkout'));
                }

                break;

            case 'collect':
                if (!isset($pickup) || !isset($pickup['code']) || $pickup['code'] == '' && ($has_virtual_products == false)) {
                    $errors->add('shipment', __('Select a pickup location.', 'montapacking-checkout'));
                }
        }

        ## Check of timeframes bekend zijn en niet van een te oude sessie
        if ($items !== null) {
            $error = false;
            if (isset($items[$time])) {
                ## Check of timeframe opties heeft
                $frame = $items[$time];
                if ($shipper == "MultipleShipper_ShippingDayUnknown") {
                    $found = true;
                } else if (isset($frame->options)) {
                    ## Gekozen shipper ophalen
                    $found = false;
                    foreach ($frame->options as $option) {
                        if ($option->code == $shipper) {
                            $found = true;
                            break;
                        }
                    }

                    ## Check of optie is gevonden
                    if (!$found) {
                        $error = true;
                    }
                } else {
                    $error = true;
                }
            } else {
                $error = true;
            }

            if ($error) {
                $errors->add('shipment', __('The shipment option(s) you choose are not available at this time, please select an other option.', 'montapacking-checkout'));
            }
        }
    }

    public static function checkout_store(WC_Abstract_Order $order)
    {
        $hasDigitalProducts = false;
        $hasPhysicalProducts = false;

        foreach ($order->get_items() as $cart_item) {
            if ($cart_item['quantity']) {
                if ($cart_item['variation_id']) {
                    $product = wc_get_product($cart_item['variation_id']);
                } else {
                    $product = wc_get_product($cart_item['product_id']);
                }

                $virtual = $product->get_virtual();

                if ($virtual) {
                    $hasDigitalProducts = true;
                } else {
                    $hasPhysicalProducts = true;
                }
            }
        }
        if ($hasPhysicalProducts == false && $hasDigitalProducts == true) {
            return;
        }

        $bMontapackingAdd = false;

        ## Shipping regel aanmaken bij order
        $item = new WC_Order_Item_Shipping();

        ## Ingevulde meta data opslaan
        $type = sanitize_post($_POST['montapacking']);

        $shipment = $type['shipment'];
        $time = $shipment['time'];
        $shipper = "";
        if (isset($shipment['shipper'])) {
            $shipper = $shipment['shipper'];
        }

        $extras = "";
        if (isset($shipment['extras'])) {
            $extras = $shipment['extras'];
        }

        $pickup = $type['pickup'];

        $items = null;
        $standardShipper = null;
        switch ($shipment['type']) {
            case 'delivery':
                $frames = self::get_frames();

                if ($shipment['shipper'] == "MultipleShipper_ShippingDayUnknown") {
                    $standardShipper = $frames['StandardShipper'];
                } else if ($frames !== null) {
                    $items = $frames['DeliveryOptions'];
                }

                break;
            case 'pickup':
            case 'collect':
                $item->add_meta_data('Shipmentmethod', $pickup["shipper"], true);
                $name = $order->get_billing_first_name() . " " . $order->get_billing_last_name();
                $pickup['name'] = $name;

                $order->set_shipping_first_name($order->get_billing_first_name());
                $order->set_shipping_last_name($order->get_billing_last_name());

                $order->set_shipping_company($pickup['company']);
                $order->set_shipping_address_1($pickup['street'] . " " . $pickup['houseNumber']);
                $order->set_shipping_postcode($pickup['postal']);
                $order->set_shipping_city($pickup['city']);
                $order->set_shipping_country($pickup['country']);

                // post nummer voor duitsland
                if ($pickup['postnumber'] && trim($pickup['postnumber'])) {
                    if (isset($pickup['shippingOptions'])) {
                        $pickup['shippingOptions'] = $pickup['shippingOptions'] . ",DHLPCPostNummer_" . $pickup['postnumber'];
                    } else {
                        $pickup['shippingOptions'] = "DHLPCPostNummer_" . $pickup['postnumber'];
                    }
                    unset($pickup['postnumber']);
                }

                $item->add_meta_data('Pickup Data', $pickup, true);

                // setting up address in a nice array
                $arr = [];
                //$arr[] = $pickup['shipper'];
                //$arr[] = $pickup['code'];
                $arr[] = "<b>Ophaalpunt</b>";
                $arr[] = $pickup['description'];
                $arr[] = $pickup['company'];
                $arr[] = $pickup['name'];
                $arr[] = $pickup['street'] . " " . $pickup['houseNumber'];
                $arr[] = $pickup['postal'] . " " . $pickup['city'] . " (" . $pickup['country'] . ")";

                $arr = implode("\n\r", $arr);

                $item->add_meta_data('Leveringsopties', $arr, true);

                $bMontapackingAdd = true;

                break;
        }

        if ($standardShipper != null) {
            $item->add_meta_data('Shipmentmethod', implode(',', $standardShipper->shipperCodes), true);
            $bMontapackingAdd = true;
        } ## Check of gekozen timeframe bestaat
        else if (isset($items[$time])) {
            $method = null;
            $shipperCode = null;

            ## Check of timeframe opties heeft
            $frame = $items[$time];
            if (isset($frame->options)) {
                ## Gekozen shipper ophalen
                foreach ($frame->options as $option) {
                    if ($option->code == $shipper) {
                        $shipperCodes = implode(',', $option->shipperCodes);
                        $method = $option;
                        break;
                    }
                }
            }

            ## Check of verzendmethode is gevonden
            if ($method !== null) {
                ## Gekozen optie met datum en tijd toevoegen
                $item->add_meta_data('Shipmentmethod', $shipperCodes, true);
                $item->add_meta_data('Delivery date', $frame->date, true);

                $time_check = $method->from . ' ' . $method->to;
                if ($time_check != '00:00 00:00' && trim($time_check) && $method->from != $method->to) {
                    $item->add_meta_data('Delivery timeframe', $method->from . ' ' . $method->to, true);
                }

                if ($method->type == 'shippingdate') {
                    $item->add_meta_data('Delivery type', "Date mentioned above is an send date", true);
                }

                if (is_array($extras)) {
                    if (is_string($method->optionCodes)) {
                        array_push($extras, $method->optionCodes);
                    }

                    if (!empty($method->shipperCodes)) {
                        foreach ($method->shipperCodes as $optionCode) {
                            array_push($extras, $optionCode);
                        }
                    }

                    $item->add_meta_data('Extras', implode(", ", $extras), true);
                } else if (!empty($method->optionCodes)) {
                    $extras = [];
                    if (is_string($method->optionCodes)) {
                        array_push($extras, $method->optionCodes);
                    }
                    $item->add_meta_data('Extras', implode(", ", $extras), true);
                }

                $shipping_phone = WC()->checkout->get_value('shipping_phone');
                $shipping_email = WC()->checkout->get_value('shipping_email');
                if (esc_attr(get_option('monta_show_seperate_shipping_email_and_phone_fields'))) {
                    if (isset($shipping_phone) && trim($shipping_phone) != "") {
                        $item->add_meta_data('shipping_phone', $shipping_phone, true);
                    }

                    if (isset($shipping_email) && trim($shipping_email) != "") {
                        $item->add_meta_data('shipping_email', $shipping_email, true);
                    }
                }

                $bMontapackingAdd = true;
            }
        }

        $settings = new Settings(esc_attr(get_option('monta_shop')), esc_attr(get_option('monta_username')), esc_attr(get_option('monta_password')), true, 5, esc_attr(get_option('monta_google_key')), 10, 'nl-NL', '€', false, false, esc_attr(get_option('monta_hidedhlpackstations')));
        $api = new MontapackingShipping($settings, get_bloginfo('language'));

//        if (true !== $api->checkConnection()) {
        if (false) {
            $arr = [];
            $arr[] = "Webshop was unable to connect to Montapacking REST api. Please contact Montapacking";
            $arr = implode("\n\r", $arr);

            $item->add_meta_data('No Connection', $arr, true);
        } else {
            if (false === $bMontapackingAdd) {
                $arr = [];

                switch ($shipment['type']) {
                    case 'delivery':
                        $arr[] = "1 - No shippers available for the chosen delivery address";
                        $arr = implode("\n\r", $arr);
                        $item->add_meta_data('1 - No shippers available for the chosen delivery address', $arr, true);
                        break;
                    case 'pickup':
                        $arr[] = "2 - No pickups available for the chosen delivery address";
                        $arr = implode("\n\r", $arr);
                        $item->add_meta_data('2 - No pickup address chosen ', $arr, true);
                        break;
                }
            }
        }

        $price = wc_format_decimal(self::get_shipping_total(sanitize_post($_POST)));

        if (wc_tax_enabled() && WC()->cart->display_prices_including_tax()) {
            $mixed = WC_Tax::get_shipping_tax_rates(null, null);

            $found = false;
            $vat_percent = 0;
            $id = "";
            foreach ($mixed as $key => $obj) {
                $vat_percent = $obj['rate'];
                $found = true;
                $id = ":" . $key;
                break;
            }

            $vat_calculate = 100 + $vat_percent;

            $tax = (self::get_shipping_total(sanitize_post($_POST)) / $vat_calculate) * $vat_percent;

            $rate = new WC_Shipping_Rate('flat_rate_shipping' . $id, 'Webshop verzendmethode', (double)$price - $tax, $tax, 'flat_rate');

            $item->set_props([
                'method_title' => $rate->label,
                'method_id' => $rate->id,
                'total' => wc_format_decimal($rate->cost),
                'taxes' => $rate->taxes,
                'meta_data' => $rate->get_meta_data()
            ]);

            $order->add_item($item);
            $order->save();

            WC()->session->set('chosen_shipping_methods', ['flat_rate_shipping' . $id]);
            $order->calculate_totals(true);
        } else {
            $item->set_props([
                'method_title' => 'Webshop verzendmethode',
                'method_id' => 0,
                'total' => $price,
            ]);

            $order->add_item($item);
            $order->save();

            WC()->session->set('chosen_shipping_methods', [0]);
            $order->calculate_totals(true);
        }
    }

    public static function shipping_total($wc_price = 0)
    {
        $data = null;
        if (isset($_POST['montapacking'])) {
            $data = sanitize_post($_POST);
        }

        $price = (float)self::get_shipping_total($data);

        return $wc_price + $price;
    }

    public static function shipping_calculate_html_output($data)
    {
        ?>
        <tr>
            <th><?php _e('Shipping', 'woocommerce'); ?> </th>
            <?php
            if ($data['price'] == 0 && $data['selectedOption'] == false) {
                ?>
                <td><?php _e('Choose a shipping method', 'montapacking-checkout'); ?></td>
                <?php
            } else if ($data['price'] > 0) {
                ?>
                <td><?= wc_price($data['price']) ?></td>
                <?php
            } else if ($data['price'] == 0) {
                ?>
                <td><?php echo translate('Free of charge', 'montapacking-checkout') ?></td>
                <?php
            }
            ?>
        </tr>
        <?php
    }

    public static function shipping_calculate()
    {
        $data = null;
        if (isset($_POST['montapacking'])) {
            $data = sanitize_post($_POST);
        }

        $price = self::get_shipping_total($data);
        $datapost = null;
        if (isset($_POST['post_data'])) {
            parse_str(sanitize_post($_POST['post_data']), $datapost);
        }

        $selectedOption = false;

        if (isset($datapost['montapacking']['shipment']['type']) && $datapost['montapacking']['shipment']['type'] == 'delivery') {
            if (isset($datapost['montapacking']['shipment']['shipper'])) {
                $selectedOption = true;
            }
        }

        if (isset($datapost['montapacking']['shipment']['type']) && isset($datapost['montapacking']['shipment']['type']) == 'pickup' || isset($datapost['montapacking']['shipment']['type']) == 'collect') {
            if (isset($datapost['montapacking']['pickup']['code']) && trim($datapost['montapacking']['pickup']['code'])) {
                $selectedOption = true;
            }
        }

        do_action('monta_shipping_calculate_html_output', ['price' => $price, 'selectedOption' => $selectedOption]);
    }

    public static function stringCurrencyToFloat($money)
    {
        $cleanString = preg_replace('/([^0-9\.,])/i', '', $money);
        $onlyNumbersString = preg_replace('/([^0-9])/i', '', $money);

        $separatorsCountToBeErased = strlen($cleanString) - strlen($onlyNumbersString) - 1;

        $stringWithCommaOrDot = preg_replace('/([,\.])/', '', $cleanString, $separatorsCountToBeErased);
        $removedThousandSeparator = preg_replace('/(\.|,)(?=[0-9]{3,}$)/', '', $stringWithCommaOrDot);

        return (float)str_replace(',', '.', $removedThousandSeparator);
    }

    public static function get_shipping_total($data = null)
    {
        global $woocommerce;

        $items = $woocommerce->cart->get_cart();

        // verzendkosten op 0 zetten
        // dit is voor de instelling 'sta gratis verzending toe' bij waardebonnen, zodat nu verzendprijs dan ook werkelijk op 0 wordt gezet
        // dan hoef je de prijs verder ook niet meer te berekenen   
        $applied_coupons = WC()->cart->get_applied_coupons();
        foreach ($applied_coupons as $coupon_code) {
            $coupon = new WC_Coupon($coupon_code);
            if ($coupon->get_free_shipping()) {
                return 0;
            }
        }

        $hasDigitalProducts = false;
        $hasPhysicalProducts = false;
        foreach ($items as $item => $values) {
            $virtual = $values['data']->get_virtual();

            if ($virtual) {
                $hasDigitalProducts = true;
            } else {
                $hasPhysicalProducts = true;
            }
        }

        if ($hasPhysicalProducts == false && $hasDigitalProducts == true) {
            return 0;
        }

        if ($data === null) {
            if (isset($_POST['post_data'])) {
                parse_str(sanitize_post($_POST['post_data']), $data);
            }
        }

        $price = 0;

        $monta = null;
        $shipment = null;
        $pickup = null;
        $type = null;
        $time = null;
        $shipper = null;
        $extras = null;

        ## Postdata ophalen
        if (isset($data['montapacking'])) {
            $monta = $data['montapacking'];

            if (isset($monta['shipment'])) {
                $shipment = $monta['shipment'];
            }
            if (isset($monta['pickup'])) {
                $pickup = $monta['pickup'];
            }
            if (isset($shipment['type'])) {
                $type = $shipment['type'];
            }
            if (isset($shipment['time'])) {
                $time = $shipment['time'];
            }
            if (isset($shipment['shipper'])) {
                $shipper = $shipment['shipper'];
            }
            if (isset($shipment['extras'])) {
                $extras = $shipment['extras'];
            }
        }

        ## Check by type
        $isfound = false;
        if ($type == 'delivery') {
            ## Timeframes uit sessie ophalen
            $frames = WC()->session->get('montapacking-frames');
            if ($shipper == "MultipleShipper_ShippingDayUnknown") {
                $price = $frames['StandardShipper']->price;
                $isfound = true;
            } else if (is_array($frames)) {
                ## Check of gekozen timeframe bestaat
                $frames = $frames['DeliveryOptions'];
                if (isset($frames)) {
                    $method = null;

                    ## Check of timeframe opties heeft
                    foreach ($frames as $frame) {
                        $thisis = $shipper;
                        if (!is_array($options = $frame->options)) {
                            throw new \Exception("Shipper options should be an array, actual: " . var_export($options, true));
                        }
                        foreach ($options as $option) {
                            if ($option->code == $shipper) {
                                $method = $option;
                                break;
                            }
                        }
                    }

                    ## Check of verzendmethode is gevonden
                    if ($method !== null) {
                        ## Basis prijs bepalen
                        $price += $method->price;
                        $isfound = true;

                        ## Eventuele extra's bijvoeren
                        if (is_array($extras)) {
                            ## Extra's toevoegen
                            foreach ($extras as $extra) {
                                foreach ($method->deliveryOptions as $deliveryOption) {
                                    if ($extra == $deliveryOption->code) {
                                        $price += $deliveryOption->price;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else if ($type == 'pickup' || $type == 'collect') {
            $price = $pickup['price'];
            $isfound = true;
        }

        if (false === $isfound) {
            $start = esc_attr(get_option('monta_shippingcosts_start'));
            $default = esc_attr(get_option('monta_shippingcosts'));

            if (trim($start)) {
                return $start;
            } else {
                //return $default;
                return;
            }
        }

        return self::stringCurrencyToFloat($price);
    }

    public static function shipping_options()
    {
        if (empty($_POST['montapacking']) || !is_array($_POST['montapacking'])) {
            return;
        }

        $type = sanitize_post($_POST['montapacking']);

        if (!isset($type['shipment'])) {
            return;
        }

        $shipment = $type['shipment'];

        switch ($shipment['type']) {
            case 'delivery':
                $frames = self::get_frames('delivery');

                if ($frames !== null) {
                    ## Frames naar handige array zetten
                    $items = $frames['DeliveryOptions'];
                    WC()->session->set('montapacking-frames', $frames);

                    if ($items !== null) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => true,
                            'frames' => $items,
                            'standardShipper' => $frames['StandardShipper']
                        ]);
                    } else {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => false,
                            'message' => translate('3 - No shippers available for the chosen delivery address.', 'montapacking-checkout')
                        ]);
                    }
                } else {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => translate('4 - No shippers available for the chosen delivery address.', 'montapacking-checkout')
                    ]);
                }

                break;
            case 'pickup':

                #echo '<pre>';
                $frames = self::get_frames('pickup');
                if ($frames !== null) {
                    ## Frames naar handige array zetten
                    $items = $frames['PickupOptions'];

                    if ($items !== null) {
                        // Get order location (lat and long by address)
                        if (isset($_POST['ship_to_different_address']) && $_POST['ship_to_different_address'] == 1) {
                            $address = sanitize_text_field($_POST['shipping_address_1']) . ' ' .
                                sanitize_text_field($_POST['shipping_address_2']) . ' ' .
                                sanitize_text_field($_POST['shipping_postcode']) . ', ' .
                                sanitize_text_field($_POST['shipping_city']) . ' ' .
                                sanitize_text_field($_POST['shipping_country']) . '';
                        } else {
                            $address = sanitize_text_field($_POST['billing_address_1']) . ' ' .
                                sanitize_text_field($_POST['billing_address_2']) . ' ' .
                                sanitize_text_field($_POST['billing_postcode']) . ', ' .
                                sanitize_text_field($_POST['billing_city']) . ' ' .
                                sanitize_text_field($_POST['billing_country']) . '';
                        }
                        $prepAddr = str_replace('  ', ' ', $address);
                        $prepAddr = str_replace(' ', '+', $prepAddr);

                        $geocode = wp_remote_get('https://maps.google.com/maps/api/geocode/json?address=' . $prepAddr . '&sensor=false&key=' . esc_attr(get_option('monta_google_key')));
                        if (isset($geocode['body'])) {
                            $geocode = $geocode['body'];
                        }

                        $output = json_decode($geocode);

                        $result = end($output->results);
                        if (isset($result->geometry)) {
                            $latitude = $result->geometry->location->lat;
                            $longitude = $result->geometry->location->lng;
                        } else {
                            $latitude = 0;
                            $longitude = 0;
                        }

                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => true,
                            'default' => (object)[
                                'lat' => $latitude,
                                'lng' => $longitude,
                            ],
                            'pickups' => $items
                        ]);
                    } else {
                        header('Content-Type: application/json');

                        echo json_encode([
                            'success' => false,
                            'message' => translate('No pickups available for the chosen delivery address.', 'montapacking-checkout')
                        ]);

                        //$logger = wc_get_logger();
                        //$context = array('source' => 'Montapacking Checkout WooCommerce Extension');
                        //$logger->notice('No pickups available for the chosen delivery address', $context);

                    }
                } else {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => translate('No pickups available for the chosen delivery address.', 'montapacking-checkout')
                    ]);

                    //$logger = wc_get_logger();
                    //$context = array('source' => 'Montapacking Checkout WooCommerce Extension');
                    //$logger->notice('No pickups available for the chosen delivery address', $context);

                }

                break;
            case 'collect':

                $frames = self::get_frames('collect');
                if ($frames !== null) {
                    $items = $frames['StoreLocation'];
                    $items = [$items];

                    if ($items !== null) {
                        // Get order location (long and lat by address)
                        if (isset($_POST['ship_to_different_address']) && $_POST['ship_to_different_address'] == 1) {
                            $address = sanitize_text_field($_POST['shipping_address_1']) . ' ' .
                                sanitize_text_field($_POST['shipping_address_2']) . ' ' .
                                sanitize_text_field($_POST['shipping_postcode']) . ', ' .
                                sanitize_text_field($_POST['shipping_city']) . ' ' .
                                sanitize_text_field($_POST['shipping_country']) . '';
                        } else {
                            $address = sanitize_text_field($_POST['billing_address_1']) . ' ' .
                                sanitize_text_field($_POST['billing_address_2']) . ' ' .
                                sanitize_text_field($_POST['billing_postcode']) . ', ' .
                                sanitize_text_field($_POST['billing_city']) . ' ' .
                                sanitize_text_field($_POST['billing_country']) . '';
                        }
                        $prepAddr = str_replace('  ', ' ', $address);
                        $prepAddr = str_replace(' ', '+', $prepAddr);

                        $geocode = wp_remote_get('https://maps.google.com/maps/api/geocode/json?address=' . $prepAddr . '&sensor=false&key=' . esc_attr(get_option('monta_google_key')));
                        if (isset($geocode['body'])) {
                            $geocode = $geocode['body'];
                        }

                        $output = json_decode($geocode);

                        $result = end($output->results);
                        if (isset($result->geometry)) {
                            $latitude = $result->geometry->location->lat;
                            $longitude = $result->geometry->location->lng;
                        } else {
                            $latitude = 0;
                            $longitude = 0;
                        }

                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => true,
                            'default' => (object)[
                                'lat' => $latitude,
                                'lng' => $longitude,
                            ],
                            'pickups' => $items
                        ]);
                    } else {
                        header('Content-Type: application/json');

                        echo json_encode([
                            'success' => false,
                            'message' => translate('No pickups available for the chosen delivery address.', 'montapacking-checkout')
                        ]);

                        //$logger = wc_get_logger();
                        //$context = array('source' => 'Montapacking Checkout WooCommerce Extension');
                        //$logger->notice('No pickups available for the chosen delivery address', $context);

                    }
                } else {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => translate('No pickups available for the chosen delivery address.', 'montapacking-checkout')
                    ]);

                    //$logger = wc_get_logger();
                    //$context = array('source' => 'Montapacking Checkout WooCommerce Extension');
                    //$logger->notice('No pickups available for the chosen delivery address', $context);
                }

                break;
        }

        wp_die();
    }

    public static function get_frames($type = 'delivery')
    {
        global $woocommerce;

        ## Postdata escapen
        $data = sanitize_post($_POST);
        foreach ($data as $field => $value) {
            $data[$field] = ($value != '' && $value != null && !is_array($value)) ? htmlspecialchars($value) : $value;
        }
        $data = (object)$data;

        if (isset($data->billing_street_name) && trim($data->billing_street_name)) {
            $data->billing_address_1 = $data->billing_street_name;

            if (trim($data->billing_house_number)) {
                $data->billing_address_1 = $data->billing_address_1 . " " . $data->billing_house_number;
            }
        }

        $excludeShippingDiscount = false;
        if (esc_attr(get_option('monta_exclude_discounted_shipping_for_role')) != "") {
            if (is_user_logged_in()) {
                $user = wp_get_current_user();
                $roles = $user->roles;
                $role = esc_attr(get_option('monta_exclude_discounted_shipping_for_role'));
                if (in_array($role, $roles)) {
                    $excludeShippingDiscount = true;
                }
            }
        }

        $settings = new Settings(
            origin: esc_attr(get_option('monta_shop')),
            user: esc_attr(get_option('monta_username')),
            password: esc_attr(get_option('monta_password')),
            pickupPointsEnabled: !esc_attr(get_option('monta_disablepickup')),
            maxPickupPoints: esc_attr(get_option('monta_max_pickuppoints')),
            googleKey: esc_attr(get_option('monta_google_key')),
            defaultCosts: (get_option('monta_shippingcosts')),
            excludeShippingDiscount: $excludeShippingDiscount,
            hideDHLPackstations: esc_attr(get_option('monta_hidedhlpackstations'))
        );
        // Add SystemInfo to API call
        $settings->setSystemInfo(System::getInfo());

        switch ($type) {
            case 'delivery':
            case 'pickup':
            case 'collect':
                $api = new MontapackingShipping(
                    settings: $settings,
                    language: get_bloginfo('language'),
                );
                break;
        }

        ## Monta packing API aanroepen
        if (!isset($data->ship_to_different_address)) {
            ## Set address of customer
            $api->setAddress(
                $data->billing_address_1,
                '',
                $data->billing_address_2,
                $data->billing_postcode,
                $data->billing_city,
                $data->billing_state,
                $data->billing_country
            );
        } else {
            ## Set shipping adres of customer when different
            $api->setAddress(
                $data->shipping_address_1,
                '',
                $data->shipping_address_2,
                $data->shipping_postcode,
                $data->shipping_city,
                $data->shipping_state,
                $data->shipping_country
            );
        }

        ## Fill products
        $items = $woocommerce->cart->get_cart();

        $bAllProductsAvailableAtMontapacking = true;
        $bAllProductsAvailableAtWooCommerce = true;

        $hasDigitalProducts = false;
        $hasPhysicalProducts = false;

        $skuArray = [];
        $x = 0;

        foreach ($items as $item => $values) {
            $product = wc_get_product($values['product_id']);
            if ($values['variation_id']) {
                $product = wc_get_product($values['variation_id']);
            }
            if ($product->get_type() != "woosb") {
                $sku = $product->get_sku();
                $price = $product->get_price();
                $quantity = $values['quantity'] ?? 1;

                if (trim($sku)) {
                    $skuArray[$x] = [$sku, $quantity];
                    $x++;
                }

                $virtual = $product->get_virtual();

                if ($virtual) {
                    $hasDigitalProducts = true;
                } else {
                    //monta_checkproductsonsku checken?
                    $price = $product->get_price();

                    if (get_option('monta_checkproductsonsku')) {
                        $api->addProduct(sku: $sku, quantity: $quantity, price: $price);
                    } else {
                        $api->addProduct(sku: $sku, quantity: $quantity, lengthMm: ($product->get_length() == '' ?? 0) * 10, widthMm: ($product->get_width() == '' ?? 0) * 10, heightMm: ($product->get_height() == '' ?? 0) * 10, weightGrammes: ($product->get_weight() == '' ?? 0) * 1000, price: $price);
                    }

                    $hasPhysicalProducts = true;

                    $stockstatus = $product->get_stock_status();

                    if ($stockstatus != 'instock') {
                        $bAllProductsAvailableAtWooCommerce = false;
                    }
                }
            }
        }

        if ($hasPhysicalProducts == false && $hasDigitalProducts == true) {
            return null;
        }

        $subtotal = (WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax());
        $subtotal_ex = WC()->cart->get_subtotal_tax();

        $api->setOrder($subtotal, $subtotal_ex);

        ## Type timeframes ophalen
        if (esc_attr(get_option('monta_leadingstock')) == '') {
            $bStockStatus = $bAllProductsAvailableAtWooCommerce;
            $api->setOnStock($bStockStatus);
        } else {
            $bStockStatus = $bAllProductsAvailableAtMontapacking;
            $api->setOnStock($bStockStatus);
        }
        do_action('woocommerce_cart_shipping_packages');

        if ($type == 'delivery') {
            if (esc_attr(get_option('monta_checkproductsonsku'))) {
                $shippingOptions = $api->getShippingOptions($bStockStatus);
                do_action('woocommerce_cart_shipping_packages');
            } else {
                $shippingOptions = $api->getShippingOptions($bStockStatus);
                do_action('woocommerce_cart_shipping_packages');
            }
            if (esc_attr(get_option('monta_shippingcosts_fallback_woocommerce'))) {
                if ($shippingOptions != null && isset($shippingOptions[0]->code) == 'Monta' && isset($shippingOptions[0]->description) == 'Monta') {
                    foreach ($shippingOptions[0]->options as $option) {
                        $option->price = self::$WooCommerceShippingMethod['cost'];
                    }
                }

                do_action('woocommerce_cart_shipping_packages');
            }
            return $shippingOptions;
        } else if ($type == 'pickup') {
            if (esc_attr(get_option('monta_checkproductsonsku'))) {
                return $api->getShippingOptions($bStockStatus);
            } else {
                return $api->getShippingOptions($bStockStatus);
            }
        } else if ($type == 'collect') {
            if (esc_attr(get_option('monta_checkproductsonsku'))) {
                return $api->getShippingOptions($bStockStatus);
            } else {
                return $api->getShippingOptions($bStockStatus);
            }
        }
    }

    public static function shipping()
    {
        include __DIR__ . DIRECTORY_SEPARATOR . "../views/choice.php";
    }

    public static function taxes()
    {
        $value = '<strong>' . WC()->cart->get_total() . '</strong> ';

        // If prices are tax inclusive, show taxes here.
        if (wc_tax_enabled() && WC()->cart->display_prices_including_tax()) {
            $tax_string_array = [];
            $cart_tax_totals = WC()->cart->get_tax_totals();

            if (get_option('woocommerce_tax_total_display') === 'itemized') {
                foreach ($cart_tax_totals as $code => $tax) {
                    $tax_string_array[] = sprintf('%s %s', $tax->formatted_amount, $tax->label);
                }
            } elseif (!empty($cart_tax_totals)) {
                $mixed = WC_Tax::get_shipping_tax_rates(null, null);

                $found = false;
                $vat_percent = 0;
                $id = "";
                foreach ($mixed as $key => $obj) {
                    $vat_percent = $obj['rate'];
                    $found = true;
                    $id = ":" . $key;
                    break;
                }

                $shipping_costs = self::shipping_total();
                $tax = ($shipping_costs / 121) * $vat_percent;

                $vat_ex_shipment = WC()->cart->get_taxes_total(true, true);

                $price = wc_price($vat_ex_shipment + $tax);

                $tax_string_array[] = sprintf('%s %s', $price, WC()->countries->tax_or_vat());
            }

            if (!empty($tax_string_array)) {
                $taxable_address = WC()->customer->get_taxable_address();
                /* translators: %s: country name */
                $estimated_text = WC()->customer->is_customer_outside_base() && !WC()->customer->has_calculated_shipping() ? sprintf(' ' . __('estimated for %s', 'woocommerce'), WC()->countries->estimated_for_prefix($taxable_address[0]) . WC()->countries->countries[$taxable_address[0]]) : '';
                $value .= '<small class="includes_tax">'
                    /* translators: includes tax information */
                    . sprintf(__('(includes %s)', 'woocommerce'), implode(', ', $tax_string_array))
                    . esc_html($estimated_text)
                    . '</small>';
            }
        }

        return $value;
    }

    public static function checkFreeShippingCouponCodes()
    {
        global $woocommerce;
        if ($woocommerce->cart->get_applied_coupons()) {
            foreach ($woocommerce->cart->get_applied_coupons() as $coupon) {
                $getDetails = (new WC_Coupon($coupon));

                if ($getDetails->get_free_shipping()) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param $package
     * @return array|null
     */
    public static function getWooCommerceShippingMethod($package): ?array
    {
        if ($package == "") {
            $address = [];
            $address['destination'] = [];
            $address['destination']['country'] = WC()->customer->get_shipping_country();
            $address['destination']['state'] = WC()->customer->get_shipping_state();
            $address['destination']['postcode'] = WC()->customer->get_shipping_postcode();
            $shipping_zone = WC_Shipping_Zones::get_zone_matching_package($address);
        } else {
            $shipping_zone = WC_Shipping_Zones::get_zone_matching_package($package);
        }
        $chosenMethod = null;
        foreach ($shipping_zone->get_shipping_methods(true) as $key => $class) {
            // Method's ID and custom name
            $item = [
                'id' => $class->method_title,
                'name' => $class->title,
                'cost' => !empty($class->instance_settings["cost"]) ? $class->instance_settings["cost"] : 0,
                'requires' => !empty($class->requires) ? $class->requires : null
            ];

            // If minimum amount is required
            if (isset($class->min_amount) && $class->min_amount > 0) $item['minimum'] = (float)$class->min_amount;

            if ($chosenMethod == null || ($chosenMethod['cost'] != null && (float)$chosenMethod['cost'] > (float)$item['cost'])) {
                if (!isset($item['requires']) || $item["requires"] == "") {
                    $chosenMethod = $item;
                } else if ($item['requires'] == 'min_amount' && WC()->cart->get_cart_contents_total() >= (float)$item['minimum']) {
                    $chosenMethod = $item;
                } else if ($item['requires'] == 'both' || $item['requires'] == 'coupon' || $item['requires'] == 'either') {
                    $applied_coupons = WC()->cart->get_applied_coupons();
                    $hasFreeShipping = false;
                    foreach ($applied_coupons as $coupon_code) {
                        $coupon = new WC_Coupon($coupon_code);
                        if ($coupon->get_free_shipping()) {
                            $hasFreeShipping = true;
                        }
                    }
                    if ($hasFreeShipping) {
                        if ($item['requires'] == 'coupon' || (WC()->cart->get_cart_contents_total() >= (float)$item['minimum'])) {
                            $chosenMethod = $item;
                        }
                    } else {
                        if ($item['requires'] == 'either' || (WC()->cart->get_cart_contents_total() >= (float)$item['minimum'])) {
                            $chosenMethod = $item;
                        }
                    }
                }
            }
        }
        return $chosenMethod;
    }

}
