<?php

class Montapacking
{

    public static function shipping_package()
    {

        return [];

    }

    public function checkout_validate($data, $errors)
    {
        if (empty($_POST['montapacking']) || !is_array($_POST['montapacking'])) {
            return;
        }

        $type = sanitize_post($_POST['montapacking']);
        $pickup = $type['pickup'];
        $shipment = $type['shipment'];

        $time = $shipment['time'];
        $shipper = $shipment['shipper'];

        $items = null;

        if (!isset($shipment['type']) || $shipment['type'] == '') {
            $errors->add('shipment', __('Select a shipping method.', 'montapacking-checkout'));
        }

        switch ($shipment['type']) {
            case 'delivery':

                $frames = self::get_frames('delivery');
                if ($frames !== null) {

                    ## Frames naar handige array zetten
                    $items = self::format_frames($frames);

                }

                break;
            case 'pickup':

                if (!isset($pickup) || !isset($pickup['code']) || $pickup['code'] == '') {
                    $errors->add('shipment', __('Select a pickup location.', 'montapacking-checkout'));
                }

                break;
        }

        ## Check of timeframes bekend zijn en niet van een te oude sessie
        if ($items !== null) {

            $error = false;
            if (isset($items[$time])) {

                ## Check of timeframe opties heeft
                $frame = $items[$time];
                if (isset($frame->options)) {

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

    public function checkout_store($order)
    {

        $bMontapackingAdd = false;

        ## Shipping regel aanmaken bij order
        $item = new WC_Order_Item_Shipping();
        $item->set_props(array(
            'method_title' => 'Monta Shipping',
            'method_id' => 0,
            'total' => wc_format_decimal(self::get_shipping_total(sanitize_post($_POST)))
        ));

        ## Ingevulde meta data opslaan
        $type = sanitize_post($_POST['montapacking']);

        $shipment = $type['shipment'];
        $time = $shipment['time'];
        $shipper = $shipment['shipper'];
        $extras = $shipment['extras'];
        $pickup = $type['pickup'];

        $items = null;
        switch ($shipment['type']) {
            case 'delivery':
                $frames = self::get_frames('delivery');
                if ($frames !== null) {

                    ## Frames naar handige array zetten
                    $items = self::format_frames($frames, $time);

                }

                break;
            case 'pickup':
                $order->set_shipping_first_name($pickup['description']);
                $order->set_shipping_last_name('');
                $order->set_shipping_company($pickup['company']);
                $order->set_shipping_address_1($pickup['street'] . " " . $pickup['houseNumber']);
                $order->set_shipping_postcode($pickup['postal']);
                $order->set_shipping_city($pickup['city']);
                $order->set_shipping_country($pickup['country']);

                $item->add_meta_data('Pickup Data', $pickup, true);

                // setting up address in a nice array
                $arr = array();
                //$arr[] = $pickup['shipper'];
                //$arr[] = $pickup['code'];
                $arr[] = "<b>Ophaalpunt</b>";
                $arr[] = $pickup['company'];
                $arr[] = $pickup['description'];
                $arr[] = $pickup['street'] . " " . $pickup['houseNumber'];
                $arr[] = $pickup['postal'] . " " . $pickup['city'] . " (" . $pickup['country'] . ")";

                $arr = implode("\n\r", $arr);

                $item->add_meta_data('Leveringsopties', $arr, true);

                $bMontapackingAdd = true;

                break;
        }

        ## Check of gekozen timeframe bestaat
        if (isset($items[$time])) {

            $method = null;
            $shipperCode = null;

            ## Check of timeframe opties heeft
            $frame = $items[$time];
            if (isset($frame->options)) {

                ## Gekozen shipper ophalen
                foreach ($frame->options as $option) {

                    if ($option->code == $shipper) {

                        $shipperCode = implode(',', $option->codes);
                        $method = $option;
                        break;

                    }

                }

            }

            ## Check of verzendmethode is gevonden
            if ($method !== null) {

                ## Gekozen optie met datum en tijd toevoegen
                $item->add_meta_data('Shipmentmethod', $shipperCode, true);
                $item->add_meta_data('Delivery date', $frame->date, true);
                $item->add_meta_data('Delivery Timeframe', $method->from . ' ' . $method->to, true);

                if (is_array($extras)) {

                    if (!empty($method->optionCodes)) {

                        foreach ($method->optionCodes as $optionCode) {

                            array_push($extras, $optionCode);

                        }

                    }

                    $item->add_meta_data('Extras', implode(", ", $extras), true);

                } else if (!empty($method->optionCodes)) {

                    $extras = array();

                    foreach ($method->optionCodes as $optionCode) {

                        array_push($extras, $optionCode);

                    }

                    $item->add_meta_data('Extras', implode(", ", $extras), true);

                }

                $bMontapackingAdd = true;
            }

        }

        if (false === $bMontapackingAdd) {

            $arr = array();
            $arr[] = "Webshop was unable to connect to Montapacking. Please contact Montapacking";
            $arr = implode("\n\r", $arr);

            $order->add_meta_data('No Connection with Montapacking', $arr, true);
        }

        // Add item to order and save.
        $order->add_item($item);
    }

    public function shipping_total($wc_price = 0)
    {

        $data = null;
        if (isset($_POST['montapacking'])) {
            $data = sanitize_post($_POST);
        }

        $price = self::get_shipping_total($data);

        return $wc_price + $price;
    }

    public function shipping_calculate()
    {

        $data = null;
        if (isset($_POST['montapacking'])) {
            $data = sanitize_post($_POST);
        }

        $price = self::get_shipping_total($data);
        ?>
        <tr>
            <th><?php _e('Shipping', 'woocommerce'); ?></th>
            <?php
            if ($price == 0) {
                ?>
                <td><?php _e('Choose a shipping method', 'montapacking-checkout'); ?></td>
                <?php
            } else {
                ?>
                <td>&euro; <?php echo number_format($price, 2, ',', ''); ?></td>
                <?php
            }
            ?>
        </tr>
        <?php

    }

    public function get_shipping_total($data = null)
    {

        if ($data === null) {
            parse_str(sanitize_post($_POST['post_data']), $data);
        }

        $price = 0;

        $monta = null;
        $time = null;
        $shipper = null;
        $extras = null;

        ## Postdata ophalen
        if (isset($data['montapacking'])) {

            $monta = $data['montapacking'];

            $shipment = $monta['shipment'];
            $pickup = $monta['pickup'];

            $type = $shipment['type'];
            $time = $shipment['time'];
            $shipper = $shipment['shipper'];
            $extras = $shipment['extras'];

        }

        ## Check by type
        if ($type == 'delivery') {

            ## Timeframes uit sessie ophalen
            $frames = $_SESSION['montapacking-frames'];
            if (is_array($frames)) {

                ## Check of gekozen timeframe bestaat
                if (isset($frames[$time])) {

                    $method = null;

                    ## Check of timeframe opties heeft
                    $frame = $frames[$time];
                    if (isset($frame->options)) {

                        ## Gekozen shipper ophalen
                        foreach ($frame->options as $option) {

                            if ($option->code == $shipper) {

                                $method = $option;
                                break;

                            }

                        }

                    }

                    ## Check of verzendmethode is gevonden
                    if ($method !== null) {

                        ## Basis prijs bepalen
                        $price += $method->price_raw;

                        ## Eventuele extra's bijvoeren
                        if (is_array($extras)) {

                            ## Extra's toeveogen
                            foreach ($extras as $extra) {

                                if (isset($method->extras[$extra])) {

                                    ## Extra bedrag toevoegen
                                    $price += $method->extras[$extra]->price_raw;

                                }

                            }

                        }

                    }

                }

            }

        } else if ($type == 'pickup') {

            $price = $pickup['price'];

        }

        return $price;

    }

    public function shipping_options()
    {

        $type = sanitize_post($_POST['montapacking']);
        $shipment = $type['shipment'];

        switch ($shipment['type']) {
            case 'delivery':

                $frames = self::get_frames('delivery');
                if ($frames !== null) {

                    ## Frames naar handige array zetten
                    $items = self::format_frames($frames);
                    if ($items !== null) {

                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => true,
                            'frames' => $items
                        ]);

                    } else {

                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => false,
                            'message' => $frames
                            //'message' => translate( $frames, 'montapacking-checkout' )
                            //'message' => translate( '1 No deliveries available for the chosen delivery address, please try again ', 'montapacking-checkout' )
                        ]);

                    }

                } else {

                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => $frames
                        //'message' => translate( $frames, 'montapacking-checkout' )
                        //'message' => translate( '2 No deliveries available for the chosen delivery address, please try again ', 'montapacking-checkout' )
                    ]);

                }

                break;
            case 'pickup':

                #echo '<pre>';
                $frames = self::get_frames('pickup');
                if ($frames !== null) {

                    ## Frames naar handige array zetten
                    $items = self::format_pickups($frames);
                    #print_r($items);
                    if ($items !== null) {

                        ## Get order location
                        // Get lat and long by address
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

                        $geocode = wp_remote_get('https://maps.google.com/maps/api/geocode/json?address=' . $prepAddr . '&sensor=false&key=' . esc_attr( get_option('monta_google_key')));
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
                            'message' => translate('1 No pickups available for the chosen delivery address, please try again ', 'montapacking-checkout')
                        ]);

                    }

                } else {

                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => translate('2 No pickups available for the chosen delivery address, please try again ', 'montapacking-checkout')
                    ]);

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

        if (trim($data->billing_street_name)) {
            $data->billing_address_1 = $data->billing_street_name;

            if (trim($data->billing_house_number)) {
                $data->billing_address_1 = $data->billing_address_1 . " " . $data->billing_house_number;
            }
        }

        ## Monta packing API aanroepen
        $api = new MontapackingShipping(esc_attr( get_option('monta_shop')), esc_attr( get_option('monta_username')), esc_attr( get_option('monta_password')), false);
        #$api->debug = true;

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
        foreach ($items as $item => $values) {

            $sku = get_post_meta($values['product_id'], '_sku', true);

            $weight = get_post_meta($values['product_id'], '_weight', true);
            $length = get_post_meta($values['product_id'], '_length', true);
            $width = get_post_meta($values['product_id'], '_width', true);

            ## Add product
            if ($sku != '') {
                $api->addProduct($sku, $values['quantity'], $length, $width, $weight);

                if (false === $api->checkStock($sku)) {
                    $bAllProductsAvailableAtMontapacking = false;
                }

            }

        }

        ## Type timeframes ophalen
        if ($type == 'delivery') {

            return $api->getShippingOptions($bAllProductsAvailableAtMontapacking);

        } else if ($type == 'pickup') {

            return $api->getPickupOptions($bAllProductsAvailableAtMontapacking);

        }

    }

    public static function format_frames($frames)
    {

        $items = null;

        ## Check of er meerdere timeframes zijn, wanneer maar één dan enkel shipper keuze zonder datum/tijd
        if (is_array($frames) || is_object($frames)) {

            foreach ($frames as $nr => $frame) {

                ## Alleen als er van en tot tijd bekend is (skipped nu DPD en UPS)
                if ($frame->from != '' && $frame->to != '') {

                    ## Loop trough options
                    $selected = null;

                    ## Lowest price
                    $lowest = 9999999;

                    ## Currency symbol
                    $curr = '&euro;';

                    ## Shipper opties ophalen
                    $options = null;
                    foreach ($frame->options as $onr => $option) {
                        $from = $option->from;
                        $to = $option->to;

                        ## Check of maximale besteltijd voorbij is
                        if (time() < strtotime($option->date) && $selected == null) {

                            $selected = $option;

                        }

                        $extras = null;
                        if (isset($option->extras) && count($option->extras) > 0) {

                            foreach ($option->extras as $extra) {

                                ## Currency symbol
                                $curr = '&euro;';

                                ## Extra optie toevoegen
                                $extras[$extra->code] = (object)[
                                    'code' => $extra->code,
                                    'name' => $extra->name,
                                    'price' => $curr . ' ' . number_format($extra->price, 2, ',', ''),
                                    'price_raw' => $extra->price,
                                ];

                            }

                        }

                        ## Shipper optie toevoegen
                        $options[$onr] = (object)[
                            'code' => $option->code,
                            'codes' => $option->codes,
                            'optionCodes' => $option->optioncodes,
                            'name' => $option->description,
                            'price' => $curr . ' ' . number_format($option->price, 2, ',', ''),
                            'price_raw' => $option->price,
                            'from' => date('H:i', strtotime($from . ' +1 hour')),
                            'to' => date('H:i', strtotime($to . ' +1 hour')),
                            'extras' => $extras,
                        ];

                        ## Check if we have a lower price
                        if ($option->price < $lowest) {
                            $lowest = $option->price;
                        }

                    }


                    ## Check of er een prijs is
                    if ($options !== null) {

                        $items[$nr] = (object)[
                            'code' => $frame->code,
                            'date' => date('d-m-Y', strtotime($frame->from)),
                            'time' => (date('H:i', strtotime($frame->from)) != date('H:i', strtotime($frame->to))) ? date('H:i', strtotime($frame->from)) . '-' . date('H:i', strtotime($frame->to)) : '',
                            'description' => $frame->description,
                            'price' => $curr . ' ' . number_format($lowest, 2, ',', ''),
                            'options' => $options
                        ];

                    }

                } else {

                    ## Lowest price
                    $lowest = 9999999;

                    ## Currency symbol
                    $curr = '&euro;';

                    ## Geen begin en eindtijd bekend, dan alleen verzender keuze
                    $options = null;
                    foreach ($frame->options as $nr => $option) {

                        $extras = null;
                        if (isset($option->extras) && count($option->extras) > 0) {

                            foreach ($option->extras as $extra) {

                                ## Currency symbol
                                $curr = '&euro;';

                                ## Extra optie toevoegen
                                $extras[$extra->code] = (object)[
                                    'code' => $extra->code,
                                    'name' => $extra->name,
                                    'price' => $curr . ' ' . number_format($extra->price, 2, ',', ''),
                                    'price_raw' => $extra->price,
                                ];

                            }

                        }

                        $options[] = (object)[
                            'codes' => $option->codes,
                            'name' => $option->description,
                            'price' => $curr . ' ' . number_format($option->price, 2, ',', ''),
                            'price_raw' => $option->price,
                            'from' => null,
                            'to' => null,
                            'extras' => $extras,
                        ];

                        ## Check if we have a lower price
                        if ($option->price < $lowest) {
                            $lowest = $option->price;
                        }

                    }

                    ## Create item
                    $items[1] = (object)[
                        'code' => 'NOTIMES',
                        'date' => null,
                        'time' => null,
                        'description' => '',
                        'price' => $lowest,
                        'options' => $options
                    ];

                }

            }

        }

        ## Frames opslaan in sessie voor bepalen kosten
        $_SESSION['montapacking-frames'] = $items;

        return $items;

    }

    public static function format_pickups($frames)
    {

        $items = null;

        ## Check of er meerdere timeframes zijn, wanneer maar één dan enkel shipper keuze zonder datum/tijd
        if (is_array($frames) || is_object($frames)) {

            foreach ($frames as $nr => $frame) {

                ## Loop trough options
                $selected = null;

                ## Pickup optie ophalen
                $option = end($frame->options);
                if (isset($option->codes)) {

                    $extras = null;
                    if (isset($option->extras) && count($option->extras) > 0) {

                        foreach ($option->extras as $extra) {

                            ## Extra optie toevoegen
                            $extras[$extra->code] = (object)[
                                'code' => $extra->code,
                                'name' => $extra->name,
                                'price' => number_format($extra->price, 2, ',', ''),
                                'price_raw' => $extra->price,
                            ];

                        }

                    }

                    ## Check of er een prijs is
                    if ($option !== null) {

                        // Maak een string van alle shipperoptions
                        $shipperOptions = "";
                        foreach ($option->optionsWithValue as $key => $value) {
                            $shipperOptions .= $key . "_" . $value . ",";
                        }
                        $shipperOptions = rtrim($shipperOptions, " ,");

                        $items[$nr] = (object)[
                            'code' => implode(',', $option->codes),
                            'date' => date('d-m-Y', strtotime($option->date)),
                            'time' => (date('H:i', strtotime($frame->from)) != date('H:i', strtotime($frame->to))) ? date('H:i', strtotime($frame->from)) . '-' . date('H:i', strtotime($frame->to)) : '',
                            'description' => $option->description,
                            'details' => $frame->details,
                            'shipperOptionsWithValue' => $shipperOptions,
                            'price' => number_format($option->price, 2, ',', ''),
                            'price_raw' => $option->price,
                        ];

                        ## Sorteer opties op laagste prijs
                        usort($items, function ($a, $b) {
                            return $a->price_raw - $b->price_raw;
                        });

                    }

                }

                #}

            }

        }

        ## Frames opslaan in sessie voor bepalen kosten
        $_SESSION['montapacking-pickups'] = $items;

        return $items;

    }

    public function shipping()
    {

        include 'views/choice.php';

    }

}
