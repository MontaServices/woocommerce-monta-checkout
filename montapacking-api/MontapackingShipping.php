<?php
require_once("Objects/Address.php");
require_once("Objects/Order.php");
require_once("Objects/Product.php");
require_once("Objects/Shipper.php");
require_once("Objects/TimeFrame.php");
require_once("Objects/PickupPoint.php");

class MontapackingShipping
{

    public $debug = false;

    private $user = '';
    private $pass = '';
    private $url = '';
    private $http = 'https://';

    private $basic = null;
    private $order = null;
    private $address = null;
    private $shippers = null;
    private $products = null;
    private $allowedshippers = null;

    public function __construct($origin, $user, $pass, $test = false)
    {

        $this->origin = $origin;
        $this->user = $user;
        $this->pass = $pass;
        $this->modus = ($test) ? 'api-test' : 'api';

        $this->url = $this->modus . '.montapacking.nl/rest/v5/';

        // Set language for the api call
        $siteLocale = substr(get_locale(), 0, 2);

        $this->basic = [
            'Origin' => $origin,
            'Currency' => 'EUR',
            'Language' => $siteLocale,
        ];

    }

    public function checkConnection()
    {

        $result = $this->call('info', ['basic']);

        if (null === $result) {
            return false;
        }
        return true;
    }

    public function setOrder($total_incl, $total_excl)
    {

        $this->order = new MontaCheckout_Order($total_incl, $total_excl);

    }

    public function setAddress($street, $housenumber, $housenumberaddition, $postalcode, $city, $state, $countrycode)
    {

        $this->address = new MontaCheckout_Address(
            $street,
            $housenumber,
            $housenumberaddition,
            $postalcode,
            $city,
            $state,
            $countrycode
        );

    }

    public function setShippers($shippers = null)
    {

        if (is_array($shippers)) {
            $this->shippers = $shippers;
        } else {
            $this->shippers[] = $shippers;
        }

    }

    public function addProduct($sku, $quantity, $length = 0, $width = 0, $height = 0, $weight = 0)
    {

        $this->products['products'][] = new MontaCheckout_Product($sku, $length, $width, $height, $weight, $quantity);

    }

    public function getShippers()
    {

        $shippers = null;

        $result = $this->call('info', ['basic']);
        if (isset($result->Origins)) {

            $origins = null;

            ## Array goedzetten
            if (is_array($result->Origins)) {
                $origins = $result->Origins;
            } else {
                $origins[] = $result->Origins;
            }

            ## Shippers omzetten naar shipper object
            foreach ($origins as $origin) {

                ## Check of shipper options object er is
                if (isset($origin->ShipperOptions)) {

                    foreach ($origin->ShipperOptions as $shipper) {

                        $shippers[] = new MontaCheckout_Shipper(
                            $shipper->ShipperDescription,
                            $shipper->ShipperCode
                        );

                    }

                }

            }

            return $origins;

        }

        return $shippers;

    }

    public function getPickupOptions($onstock = true, $mailbox = false, $mailboxfit = false, $trackingonly = false, $insurance = false)
    {


        $this->basic = array_merge($this->basic, [
            'OnlyPickupPoints' => 'true',
            //'MaxNumberOfPickupPoints' => 3,
            'ProductsOnStock' => ($onstock) ? 'TRUE' : 'FALSE',
            'MaiboxShipperMandatory' => $mailbox,
            'TrackingMandatory' => $trackingonly,
            'InsuranceRequired' => $insurance,
            'ShipmentFitsThroughDutchMailbox' => $mailboxfit,
        ]);

        $this->allowedshippers = ['PAK', 'DHLservicepunt', 'DPDparcelstore'];

        ## Timeframes omzetten naar bruikbaar object
        $result = $this->call('ShippingOptions', ['basic', 'shippers', 'order', 'address', 'products', 'allowedshippers']);
        if (isset($result->Timeframes)) {

            ## Shippers omzetten naar shipper object
            foreach ($result->Timeframes as $timeframe) {

                $pickups[] = new MontaCheckout_PickupPoint(
                    $timeframe->From,
                    $timeframe->To,
                    $timeframe->TypeCode,
                    $timeframe->PickupPointDetails,
                    $timeframe->ShippingOptions
                );

            }

        }

        return $pickups;

    }

    public function getShippingOptions($onstock = true, $mailbox = false, $mailboxfit = false, $trackingonly = false, $insurance = false)
    {

        ## Basis gegevens uitbreiden met shipping option specifieke data
        $this->basic = array_merge($this->basic, [
            'ProductsOnStock' => ($onstock) ? 'TRUE' : 'FALSE',
            'MaiboxShipperMandatory' => $mailbox,
            'TrackingMandatory' => $trackingonly,
            'MaxNumberOfPickupPoints' => 0,
            'InsuranceRequired' => $insurance,
            'ShipmentFitsThroughDutchMailbox' => $mailboxfit,
        ]);

        $timeframes = null;

        ## Timeframes omzetten naar bruikbaar object
        $result = $this->call('ShippingOptions', ['basic', 'shippers', 'order', 'address', 'products']);

        if (trim($this->address->postalcode) && (trim($this->address->housenumber) || trim($this->address->street))) {
            if (isset($result->Timeframes)) {

                ## Shippers omzetten naar shipper object
                foreach ($result->Timeframes as $timeframe) {

                    $timeframes[] = new MontaCheckout_TimeFrame(
                        $timeframe->From,
                        $timeframe->To,
                        $timeframe->TypeCode,
                        $timeframe->TypeDescription,
                        $timeframe->ShippingOptions
                    );

                }

            }
        }

        return $timeframes;

    }

    public function call($method, $send = null)
    {

        $request = '?';
        if ($send != null) {

            ## Request neede data
            foreach ($send as $data) {

                if (isset($this->{$data}) && $this->{$data} != null) {

                    if (!is_array($this->{$data})) {

                        $request .= '&' . http_build_query($this->{$data}->toArray());

                    } else {
                        $request .= '&' . http_build_query($this->{$data});

                    }

                }

            }

        }

        $method = strtolower($method);
        //$url = $this->http . $this->user . ':' . $this->pass . '@' . $this->url . $method;
        $url = "https://api.montapacking.nl/rest/v5/" . $method;

        if ($this->debug) {
            echo $url;
            echo str_replace('&', "$\n", $request);
        }

        $ch = curl_init();

        $this->pass = htmlspecialchars_decode($this->pass);

        curl_setopt($ch, CURLOPT_URL, $url . '?' . $request);
        curl_setopt($ch, CURLOPT_USERPWD, $this->user . ":" . $this->pass);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        $result = curl_exec($ch);

        if ($this->debug) {
            echo '<pre>';
            print_r(json_decode($result));
            echo '<pre>';
        }

        curl_close($ch);

        return json_decode($result);

    }


}