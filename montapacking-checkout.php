<?php
/**
 * Plugin Name: Montapacking Checkout WooCommerce Extension
 * Plugin URI: https://github.com/Montapacking/woocommerce-monta-checkout
 * Description: Montapacking Check-out extension
 * Version: 1.0.1
 * Author: Montapacking
 * Author URI: https://www.montapacking.nl/
 * Developer: Montapacking
 * Developer URI: https://www.montapacking.nl/
 * Text Domain: woocommerce-extension
 * Domain Path: /languages
 *
 * WC requires at least: 3.6.5
 * WC tested up to: 3.6.5
 *
 * Copyright: © 2009-2019 WooCommerce.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

include( 'montapacking-config.php' );

## Check of we in woocommerce zijn
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    ## Add config actions
    add_action( 'admin_menu', 'init_menu' );

    add_action( 'admin_init', function () {
        register_setting( 'montapacking-plugin-settings', 'monta_shop' );
        register_setting( 'montapacking-plugin-settings', 'monta_username' );
        register_setting( 'montapacking-plugin-settings', 'monta_password' );
        register_setting( 'montapacking-plugin-settings', 'monta_google_key' );
    } );

    function plugin_add_settings_link( $links ) {
        $settings_link[] = '<a href="options-general.php?page=montapacking-settings">' . __( 'Settings' ) . '</a>';

        #array_push( $links, $settings_link );
        return array_merge( $settings_link, $links );
    }

    // Include installed Language packs
    load_plugin_textdomain( TKEY, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

    $plugin = plugin_basename( __FILE__ );
    add_filter( "plugin_action_links_$plugin", 'plugin_add_settings_link' );

    ## Standaard woocommerce verzending uitschakelen
    add_filter( 'woocommerce_shipping_calculator_enable_postcode', false );
    add_filter( 'woocommerce_shipping_calculator_enable_city', false );

    ## Shipping form in checkout plaatsen
    add_action( 'woocommerce_before_order_notes', array( 'montapacking', 'shipping' ), 10 );

    ## Shipping package toevoegen
    add_action( 'woocommerce_cart_shipping_packages', array( 'montapacking', 'shipping_package' ), 10 );

    ## Shipping cost calculation
    add_action( 'woocommerce_package_rates', array( 'montapacking', 'shipping_calculate' ), 10 );

    ## Shipping cost calculation
    add_action( 'woocommerce_review_order_before_shipping', array( 'montapacking', 'shipping_calculate' ), 10 );
    add_filter( 'woocommerce_cart_get_total', array( 'montapacking', 'shipping_total' ), 10, 1 );
    add_filter( 'woocommerce_cart_get_shipping_total', array( 'montapacking', 'shipping_total' ), 10, 1 );

    ## Shipping cost calculation
    update_option( 'woocommerce_enable_shipping_calc', 'no' );
    update_option( 'woocommerce_shipping_cost_requires_address', 'no' );

    ## Validation rules
    add_action( 'woocommerce_after_checkout_validation', array( 'montapacking', 'checkout_validate' ), 10, 2 );

    ## Shipment data opslaan bij order
    add_action( 'woocommerce_checkout_create_order', array( 'montapacking', 'checkout_store' ), 10, 2 );

    // CSS/JS scripts registreren
    function enqueue_scripts(){
        // CSS
        wp_enqueue_style( 'montapacking_checkout_storelocator', plugins_url( 'montapacking-checkout/assets/css/monta-storelocator.css' ), date("h:i:s") );
        wp_enqueue_style( 'montapacking_checkout_plugin', plugins_url( 'montapacking-checkout/assets/css/monta-shipping.css' ), date("h:i:s") );

        // Javascript
        wp_enqueue_script( 'montapacking_checkout_plugin_map', 'https://maps.google.com/maps/api/js?key=' . MONTA_GOOGLE_KEY, [ 'jquery' ] );
        wp_enqueue_script( 'montapacking_checkout_plugin_handlebars', plugins_url( 'montapacking-checkout/assets/js/monta-handlebars.js' ), [ 'jquery' ], date("h:i:s") );
        wp_enqueue_script( 'montapacking_checkout_plugin_storelocator_js', plugins_url( 'montapacking-checkout/assets/js/monta-storelocator.js' ), [ 'jquery' ], date("h:i:s")  );
        wp_enqueue_script( 'montapacking_checkout_plugin_monta', plugins_url( 'montapacking-checkout/assets/js/monta-shipping.js' ), [ 'jquery' ], date("h:i:s") );
    }
    add_action('wp_enqueue_scripts', 'enqueue_scripts');

    ## Ajax actions
    add_action( 'wp_ajax_monta_shipping_options', array( 'montapacking', 'shipping_options' ) );
    add_action( 'wp_ajax_nopriv_monta_shipping_options', array( 'montapacking', 'shipping_options' ) );

    ## Init session usage
    add_action( 'init', 'register_session' );

    add_action( 'wp_footer', 'montapacking_footer' );

    function montapacking_footer() {
        require_once 'views/pickup.php';
    }

    class Montapacking {

        public static function shipping_package() {

            return [];

        }

        public function checkout_validate( $data, $errors ) {

            $type     = $_POST['montapacking'];
            $pickup   = $type['pickup'];
            $shipment = $type['shipment'];

            $time    = $shipment['time'];
            $shipper = $shipment['shipper'];

            $items = null;

            if ( ! isset( $shipment['type'] ) || $shipment['type'] == '' ) {
                $errors->add( 'shipment', __( 'Select a shipping method.', TKEY ) );
            }

            switch ( $shipment['type'] ) {
                case 'delivery':

                    $frames = self::get_frames( 'delivery' );
                    if ( $frames !== null ) {

                        ## Frames naar handige array zetten
                        $items = self::format_frames( $frames );

                    }

                    break;
                case 'pickup':

                    if ( ! isset( $pickup ) || ! isset( $pickup['code'] ) || $pickup['code'] == '' ) {
                        $errors->add( 'shipment', __( 'Select a pickup location.', TKEY ) );
                    }

                    break;
            }

            ## Check of timeframes bekend zijn en niet van een te oude sessie
            if ( $items !== null ) {

                $error = false;
                if ( isset( $items[ $time ] ) ) {

                    ## Check of timeframe opties heeft
                    $frame = $items[ $time ];
                    if ( isset( $frame->options ) ) {

                        ## Gekozen shipper ophalen
                        $found = false;
                        foreach ( $frame->options as $option ) {

                            if ( $option->code == $shipper ) {

                                $found = true;
                                break;

                            }

                        }

                        ## Check of optie is gevonden
                        if ( ! $found ) {
                            $error = true;
                        }

                    } else {

                        $error = true;

                    }

                } else {

                    $error = true;

                }

                if ( $error ) {
                    $errors->add( 'shipment', __( 'The shipment option(s) you choose are not available at this time, please select an other option.', TKEY ) );
                }

            }

        }

        public function checkout_store( $order ) {

            ## Shipping regel aanmaken bij order
            $item = new WC_Order_Item_Shipping();
            $item->set_props( array(
                'method_title' => 'Monta Shipping',
                'method_id' => 0,
                'total' => wc_format_decimal( self::get_shipping_total( $_POST ) )
            ) );

            ## Ingevulde meta data opslaan
            $type     = $_POST['montapacking'];
            $shipment = $type['shipment'];

            $time    = $shipment['time'];
            $shipper = $shipment['shipper'];
            $extras  = $shipment['extras'];

            $pickup  = $type['pickup'];

            $items = null;
            switch ( $shipment['type'] ) {
                case 'delivery':

                    $frames = self::get_frames( 'delivery' );
                    if ( $frames !== null ) {

                        ## Frames naar handige array zetten
                        $items = self::format_frames( $frames, $time );

					}

                    break;
                case 'pickup':
                    // setting up address in a nice array
                    $arr = array();
                    $arr[] = $pickup['shipper'];
                    $arr[] = $pickup['code'];
                    $arr[] = $pickup['company'];
                    $arr[] = $pickup['street']." ".$pickup['houseNumber'];
                    $arr[] = $pickup['postal']." ".$pickup['city']." (".$pickup['country'].")";

                    $arr = implode("\n\r", $arr);

                    $item->add_meta_data( 'Pickup Data', $arr, true );

                    break;
            }

            ## Check of gekozen timeframe bestaat
            if ( isset( $items[ $time ] ) ) {

                $method = null;
                $shipperCode = null;

                ## Check of timeframe opties heeft
                $frame = $items[ $time ];
                if ( isset( $frame->options ) ) {

                    ## Gekozen shipper ophalen
                    foreach ( $frame->options as $option ) {

                        if($option->code == $shipper){

                            $shipperCode = implode( ',', $option->codes );
                            $method = $option;
                            break;

                        }

                    }

                }

                ## Check of verzendmethode is gevonden
                if ( $method !== null ) {

                    ## Gekozen optie met datum en tijd toevoegen
                    $item->add_meta_data( 'Shipmentmethod', $shipperCode, true );
                    $item->add_meta_data( 'Delivery date', $frame->date, true );
                    $item->add_meta_data( 'Delivery Timeframe', $method->from . ' ' . $method->to, true );

                    if(is_array($extras)){

                        if(!empty($method->optionCodes)){

                            foreach ($method->optionCodes as $optionCode) {

                                array_push($extras, $optionCode);

                            }

                        }

                        $item->add_meta_data( 'Extras', $extras, true );

                    }else if(!empty($method->optionCodes)){

                        $extras = array();

                        foreach($method->optionCodes as $optionCode) {

                            array_push($extras, $optionCode);

                        }

                        $item->add_meta_data( 'Extras', $extras, true );

                    }

                }

            }

            // Add item to order and save.
            $order->add_item( $item );

        }

        public function shipping_total( $wc_price = 0 ) {

            $data = null;
            if ( isset( $_POST['montapacking'] ) ) {
                $data = $_POST;
            }

            $price = self::get_shipping_total( $data );

            return $wc_price + $price;
        }

        public function shipping_calculate() {

            $data = null;
            if ( isset( $_POST['montapacking'] ) ) {
                $data = $_POST;
            }

            $price = self::get_shipping_total( $data );
            ?>
            <tr>
                <th><?php _e( 'Shipping', 'woocommerce' ); ?></th>
                <?php
                    if($price == 0){
                        ?>
                            <td><?php _e( 'Choose a shipping method', TKEY ); ?></td>
                        <?php
                    }else{
                        ?>
                            <td>&euro; <?php echo number_format( $price, 2, ',', '' ); ?></td>
                        <?php
                    }
                ?>
            </tr>
            <?php

        }

        public function get_shipping_total( $data = null ) {

            if ( $data === null ) {
                parse_str( $_POST['post_data'], $data );
            }

            $price = 0;

            $monta   = null;
            $time    = null;
            $shipper = null;
            $extras  = null;

            ## Postdata ophalen
            if ( isset( $data['montapacking'] ) ) {

                $monta = $data['montapacking'];

                $shipment = $monta['shipment'];
                $pickup   = $monta['pickup'];

                $type    = $shipment['type'];
                $time    = $shipment['time'];
                $shipper = $shipment['shipper'];
                $extras  = $shipment['extras'];

            }

            ## Check by type
            if ( $type == 'delivery' ) {

                ## Timeframes uit sessie ophalen
                $frames = $_SESSION['montapacking-frames'];
                if ( is_array( $frames ) ) {

                    ## Check of gekozen timeframe bestaat
                    if ( isset( $frames[ $time ] ) ) {

                        $method = null;

                        ## Check of timeframe opties heeft
                        $frame = $frames[ $time ];
                        if ( isset( $frame->options ) ) {

                            ## Gekozen shipper ophalen
                            foreach ( $frame->options as $option ) {

                                if ( $option->code == $shipper ) {

                                    $method = $option;
                                    break;

                                }

                            }

                        }

                        ## Check of verzendmethode is gevonden
                        if ( $method !== null ) {

                            ## Basis prijs bepalen
                            $price += $method->price_raw;

                            ## Eventuele extra's bijvoeren
                            if ( is_array( $extras ) ) {

                                ## Extra's toeveogen
                                foreach ( $extras as $extra ) {

                                    if ( isset( $method->extras[ $extra ] ) ) {

                                        ## Extra bedrag toevoegen
                                        $price += $method->extras[ $extra ]->price_raw;

                                    }

                                }

                            }

                        }

                    }

                }

            } else if ( $type == 'pickup' ) {

                $price = $pickup['price'];

            }

            return $price;

        }

        public function shipping_options() {

            $type     = $_POST['montapacking'];
            $shipment = $type['shipment'];

            switch ( $shipment['type'] ) {
                case 'delivery':

                    $frames = self::get_frames( 'delivery' );
                    if ( $frames !== null ) {

                        ## Frames naar handige array zetten
                        $items = self::format_frames( $frames );
                        if ( $items !== null ) {

                            header( 'Content-Type: application/json' );
                            echo json_encode( [
                                'success' => true,
                                'frames' => $items
                            ] );

                        } else {

                            header( 'Content-Type: application/json' );
                            echo json_encode( [
                                'success' => false,
                                'message' => $frames
                                //'message' => translate( $frames, TKEY )
                                //'message' => translate( '1 No deliveries available for the chosen delivery address, please try again ', TKEY )
                            ] );

                        }

                    } else {

                        header( 'Content-Type: application/json' );
                        echo json_encode( [
                            'success' => false,
                            'message' => $frames
                            //'message' => translate( $frames, TKEY )
                            //'message' => translate( '2 No deliveries available for the chosen delivery address, please try again ', TKEY )
                        ] );

                    }

                    break;
                case 'pickup':

                    #echo '<pre>';
                    $frames = self::get_frames( 'pickup' );
                    if ( $frames !== null ) {

                        ## Frames naar handige array zetten
                        $items = self::format_pickups( $frames );
                        #print_r($items);
                        if ( $items !== null ) {

                            ## Get order location
                            // Get lat and long by address
                            if ( isset( $_POST['ship_to_different_address'] ) && $_POST['ship_to_different_address'] == 1 ) {

                                $address = $_POST['shipping_address_1'] . ' ' .
                                    $_POST['shipping_address_2'] . ' ' .
                                    $_POST['shipping_postcode'] . ', ' .
                                    $_POST['shipping_city'] . ' ' .
                                    $_POST['shipping_country'] . '';

                            } else {

                                $address = $_POST['billing_address_1'] . ' ' .
                                    $_POST['billing_address_2'] . ' ' .
                                    $_POST['billing_postcode'] . ', ' .
                                    $_POST['billing_city'] . ' ' .
                                    $_POST['billing_country'] . '';

                            }
                            $prepAddr = str_replace( '  ', ' ', $address );
                            $prepAddr = str_replace( ' ', '+', $prepAddr );

                            $geocode = file_get_contents( 'https://maps.google.com/maps/api/geocode/json?address=' . $prepAddr . '&sensor=false&key=' . MONTA_GOOGLE_KEY );
                            $output  = json_decode( $geocode );

                            $result = end( $output->results );
                            if ( isset( $result->geometry ) ) {
                                $latitude  = $result->geometry->location->lat;
                                $longitude = $result->geometry->location->lng;
                            } else {
                                $latitude  = 0;
                                $longitude = 0;
                            }

                            header( 'Content-Type: application/json' );
                            echo json_encode( [
                                'success' => true,
                                'default' => (object) [
                                    'lat' => $latitude,
                                    'lng' => $longitude,
                                ],
                                'pickups' => $items
                            ] );

                        } else {

                            header( 'Content-Type: application/json' );
                            echo json_encode( [
                                'success' => false,
                                'message' => translate( '1 No pickups available for the chosen delivery address, please try again ', TKEY )
                            ] );

                        }

                    } else {

                        header( 'Content-Type: application/json' );
                        echo json_encode( [
                            'success' => false,
                            'message' => translate( '2 No pickups available for the chosen delivery address, please try again ', TKEY )
                        ] );

                    }

                    break;
            }

            wp_die();

        }

        public static function get_frames( $type = 'delivery' ) {

            global $woocommerce;

            ## Postdata escapen
            $data = $_POST;
            foreach ( $data as $field => $value ) {
                $data[ $field ] = ( $value != '' && $value != null && ! is_array( $value ) ) ? htmlspecialchars( $value ) : $value;
            }
            $data = (object) $data;

            ## Monta packing API aanroepen
            $api = new MontapackingShipping( MONTA_SHOP, MONTA_USER, MONTA_PASS, false );
            #$api->debug = true;

            if ( ! isset( $data->ship_to_different_address ) ) {

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
            foreach ( $items as $item => $values ) {

                $sku = get_post_meta( $values['product_id'], '_sku', true );

                $weight = get_post_meta( $values['product_id'], '_weight', true );
                $length = get_post_meta( $values['product_id'], '_length', true );
                $width  = get_post_meta( $values['product_id'], '_width', true );

                ## Add product
                if ( $sku != '' ) {
                    $api->addProduct( $sku, $values['quantity'], $length, $width, $weight );
                }

            }

            ## Type timeframes ophalen
            if ( $type == 'delivery' ) {

                return $api->getShippingOptions();

            } else if ( $type == 'pickup' ) {

                return $api->getPickupOptions();

            }

        }

        public static function format_frames( $frames ) {

            $items = null;

            ## Check of er meerdere timeframes zijn, wanneer maar één dan enkel shipper keuze zonder datum/tijd
            if ( is_array( $frames ) || is_object( $frames ) ) {

                foreach ( $frames as $nr => $frame ) {

                    ## Alleen als er van en tot tijd bekend is (skipped nu DPD en UPS)
                    if ( $frame->from != '' && $frame->to != '' ) {

                        ## Loop trough options
                        $selected = null;

                        ## Lowest price
                        $lowest = 9999999;

                        ## Currency symbol
                        $curr = '&euro;';

                        ## Shipper opties ophalen
                        $options = null;
                        foreach ( $frame->options as $onr => $option ) {
                            $from = $option->from;
                            $to   = $option->to;

                            ## Check of maximale besteltijd voorbij is
                            if ( time() < strtotime( $option->date ) && $selected == null ) {

                                $selected = $option;

                            }

                            $extras = null;
                            if ( isset( $option->extras ) && count( $option->extras ) > 0 ) {

                                foreach ( $option->extras as $extra ) {

                                    ## Currency symbol
                                    $curr = '&euro;';

                                    ## Extra optie toevoegen
                                    $extras[ $extra->code ] = (object) [
                                        'code' => $extra->code,
                                        'name' => $extra->name,
                                        'price' => $curr . ' ' . number_format( $extra->price, 2, ',', '' ),
                                        'price_raw' => $extra->price,
                                    ];

                                }

                            }

                            ## Shipper optie toevoegen
                            $options[ $onr ] = (object) [
                                'code' => $option->code,
                                'codes' => $option->codes,
                                'optionCodes' => $option->optioncodes,
                                'name' => $option->description,
                                'price' => $curr . ' ' . number_format( $option->price, 2, ',', '' ),
                                'price_raw' => $option->price,
                                'from' => date( 'H:i', strtotime( $from . ' +1 hour' ) ),
                                'to' => date( 'H:i', strtotime( $to . ' +1 hour' ) ),
                                'extras' => $extras,
                            ];

                            ## Check if we have a lower price
                            if ( $option->price < $lowest ) {
                                $lowest = $option->price;
                            }

                        }


                        ## Check of er een prijs is
                        if ( $options !== null ) {

                            $items[ $nr ] = (object) [
                                'code' => $frame->code,
                                'date' => date( 'd-m-Y', strtotime( $frame->from ) ),
                                'time' => ( date( 'H:i', strtotime( $frame->from ) ) != date( 'H:i', strtotime( $frame->to ) ) ) ? date( 'H:i', strtotime( $frame->from ) ) . '-' . date( 'H:i', strtotime( $frame->to ) ) : '',
                                'description' => $frame->description,
                                'price' => $curr . ' ' . number_format( $lowest, 2, ',', '' ),
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
                        foreach ( $frame->options as $nr => $option ) {

                            $extras = null;
                            if ( isset( $option->extras ) && count( $option->extras ) > 0 ) {

                                foreach ( $option->extras as $extra ) {

                                    ## Currency symbol
                                    $curr = '&euro;';

                                    ## Extra optie toevoegen
                                    $extras[ $extra->code ] = (object) [
                                        'code' => $extra->code,
                                        'name' => $extra->name,
                                        'price' => $curr . ' ' . number_format( $extra->price, 2, ',', '' ),
                                        'price_raw' => $extra->price,
                                    ];

                                }

                            }

                            $options[] = (object) [
                                'codes' => $option->codes,
                                'name' => $option->description,
                                'price' => $curr . ' ' . number_format( $option->price, 2, ',', '' ),
                                'price_raw' => $option->price,
                                'from' => null,
                                'to' => null,
                                'extras' => $extras,
                            ];

                            ## Check if we have a lower price
                            if ( $option->price < $lowest ) {
                                $lowest = $option->price;
                            }

                        }

                        ## Create item
                        $items[1] = (object) [
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

        public static function format_pickups( $frames ) {

            $items = null;

            ## Check of er meerdere timeframes zijn, wanneer maar één dan enkel shipper keuze zonder datum/tijd
            if ( is_array( $frames ) || is_object( $frames ) ) {

                foreach ( $frames as $nr => $frame ) {

                    ## Loop trough options
                    $selected = null;

                    ## Pickup optie ophalen
                    $option = end( $frame->options );
                    if ( isset( $option->codes ) ) {

                        $extras = null;
                        if ( isset( $option->extras ) && count( $option->extras ) > 0 ) {

                            foreach ( $option->extras as $extra ) {

                                ## Extra optie toevoegen
                                $extras[ $extra->code ] = (object) [
                                    'code' => $extra->code,
                                    'name' => $extra->name,
                                    'price' => number_format( $extra->price, 2, ',', '' ),
                                    'price_raw' => $extra->price,
                                ];

                            }

                        }

                        ## Check of er een prijs is
                        if ( $option !== null ) {

                            // Maak een string van alle shipperoptions
                            $shipperOptions = "";
                            foreach ($option->optionsWithValue as $key=>$value){
                                $shipperOptions .= $key . "_" . $value . ",";
                            }
                            $shipperOptions = rtrim($shipperOptions, " ,");

                            $items[ $nr ] = (object) [
                                'code' => implode( ',', $option->codes ),
                                'date' => date( 'd-m-Y', strtotime( $option->date ) ),
                                'time' => ( date( 'H:i', strtotime( $frame->from ) ) != date( 'H:i', strtotime( $frame->to ) ) ) ? date( 'H:i', strtotime( $frame->from ) ) . '-' . date( 'H:i', strtotime( $frame->to ) ) : '',
                                'description' => $option->description,
                                'details' => $frame->details,
                                'shipperOptionsWithValue' => $shipperOptions,
                                'price' => number_format( $option->price, 2, ',', '' ),
                                'price_raw' => $option->price,
                            ];

                            ## Sorteer opties op laagste prijs
                            usort( $items, function ( $a, $b ) {
                                return $a->price_raw - $b->price_raw;
                            } );

                        }

                    }

                    #}

                }

            }

            ## Frames opslaan in sessie voor bepalen kosten
            $_SESSION['montapacking-pickups'] = $items;

            return $items;

        }

        public function shipping() {

            include 'views/choice.php';

        }

    }

    function register_session() {
        if ( ! session_id() ) {
            session_start();
        }
    }

    function init_menu() {
        add_submenu_page( 'options-general.php', 'Montapacking', 'Montapacking', 'manage_options', 'montapacking-settings', 'render_montapacking_settings' );
    }

    function render_montapacking_settings() {
        // Check that the user is allowed to update options
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'You do not have sufficient permissions to access this page.' );
        }
        ?>

        <div class="wrap">
            <form action="options.php" method="post">

                <?php
                settings_fields( 'montapacking-plugin-settings' );
                do_settings_sections( 'montapacking-plugin-settings' );
                ?>
                <h1>Montapacking API Settings</h1>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="monta_shop">Shop</label></th>
                        <td><input type="text" name="monta_shop" value="<?php echo esc_attr( get_option( 'monta_shop' ) ); ?>" size="50"/></td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="monta_username">Username</label></th>
                        <td><input type="text" name="monta_username" value="<?php echo esc_attr( get_option( 'monta_username' ) ); ?>" size="50"/></td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="monta_password">Password</label></th>
                        <td><input type="password" name="monta_password" value="<?php echo esc_attr( get_option( 'monta_password' ) ); ?>" size="50"/></td>
                    </tr>

                </table>

                <h1>Google API Settings</h1>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="monta_google_key">API Key</label></th>
                        <td><input type="text" name="monta_google_key" value="<?php echo esc_attr( get_option( 'monta_google_key' ) ); ?>" size="50"/></td>
                    </tr>
                </table>

                <?php submit_button(); ?>

            </form>
        </div>
        <?php
    }
}