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
    private $requesturl = '';
    private $googlekey = '';
    private $http = 'https://';

    private $basic = null;
    private $order = null;
    private $address = null;
    private $shippers = null;
    private $products = null;
    private $allowedshippers = null;
    private $logger = null;

    public function __construct($origin, $user, $pass, $googlekey, $test = false)
    {

        $this->origin = $origin;
        $this->user = $user;
        $this->pass = $pass;
        $this->googlekey = $googlekey;
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

    public function setLogger($logger)
    {
        $this->logger = $logger;
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


    public function checkStock($sku)
    {
        $url = "https://api.montapacking.nl/rest/v5/product/" . $sku . "/stock";

        $this->pass = htmlspecialchars_decode($this->pass);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERPWD, $this->user . ":" . $this->pass);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result);


        if (property_exists($result, 'Message') && $result->Message == 'Zero products found with sku ' . $sku) {
            return false;
        } else {
            return true;
        }

    }


    public function getPickupOptions($onstock = true, $mailbox = false, $mailboxfit = false, $trackingonly = false, $insurance = false,$skus = array(), $afhOnly = false)
    {


        $this->basic = array_merge($this->basic, [
            'OnlyPickupPoints' => 'true',
            //'MaxNumberOfPickupPoints' => 3,
            'ProductsOnStock' => ($onstock) ? 'TRUE' : 'FALSE',
            'MailboxShipperMandatory' => $mailbox,
            'TrackingMandatory' => $trackingonly,
            'InsuranceRequired' => $insurance,
            'ShipmentFitsThroughDutchMailbox' => $mailboxfit,
        ]);

        if ($afhOnly){
            $this->allowedshippers = ['AFH'];
        } else {
            //$this->allowedshippers = ['PAK', 'DHLservicepunt', 'DPDparcelstore', 'DHLParcelConnectPickupPoint', 'UPSAP', 'DHLservicepuntGroot', 'DHLFYPickupPoint', 'DPDparcelstoreGroot', 'GLSPickupPoint'];
        }

        $this->address->setLongLat($this->googlekey);

        ## Timeframes omzetten naar bruikbaar object


        if (count($skus))
        {
            $result = $this->call('ShippingOptions', ['basic', 'shippers', 'order', 'address', 'products'], $skus);
        }
        else{
            $result = $this->call('ShippingOptions', ['basic', 'shippers', 'order', 'address', 'products']);
        }

        if (isset($result->Timeframes)) {

            ## Shippers omzetten naar shipper object
            foreach ($result->Timeframes as $timeframe) {

                $pickups[] = new MontaCheckout_PickupPoint(
                    $timeframe->From,
                    $timeframe->To,
                    $timeframe->TypeCode,
                    $timeframe->PickupPointDetails,
                    $timeframe->ShippingOptions,
                    $this->requesturl
                );

            }

        }

        return $pickups;

    }

    public function getShippingOptions($onstock = true, $mailbox = false, $mailboxfit = false, $trackingonly = false, $insurance = false, $skus = array())
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

        if (count($skus))
        {
            $result = $this->call('ShippingOptions', ['basic', 'shippers', 'order', 'address', 'products'], $skus);
        }
        else{
            $result = $this->call('ShippingOptions', ['basic', 'shippers', 'order', 'address', 'products']);
        }
        ## Timeframes omzetten naar bruikbaar object


        if (trim($this->address->postalcode) && (trim($this->address->housenumber) || trim($this->address->street))) {

            if (isset($result->Timeframes)) {

                ## Shippers omzetten naar shipper object
                foreach ($result->Timeframes as $timeframe) {

                    $timeframes[] = new MontaCheckout_TimeFrame(
                        $timeframe->From,
                        $timeframe->To,
                        $timeframe->TypeCode,
                        $timeframe->TypeDescription,
                        $timeframe->ShippingOptions,
                        $timeframe->FromToTypeCode,
                        $this->requesturl
                    );

                }

            }
        }

        return $timeframes;

    }

    public function call($method, $send = null, $skus = array())
    {
        $request = '?';
        if ($send != null) {
            ## Request needed data
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

        if($this->allowedshippers != null) {
            $index = 0;
            foreach ($this->allowedshippers as $shipper){
                $request .= '&AllowedShippers[' . $index  . "]=" . $shipper;
                $index++;
            }
        }

        $logger = $this->logger;
        $context = array('source' => 'Monta Checkout');
        $logger->critical($request, $context);
        if (count($skus)){
            foreach ($skus as $key => $value)
            {
                $request .= "&Products[".$key."].Sku=".$value[0]."&Products[".$key."].Quantity=".$value[1];
            }
        }

        $method = strtolower($method);

        $url = "https://api.montapacking.nl/rest/v5/" . $method;

        //$this->debug = true;
        if ($this->debug) {
            echo $url;
            echo str_replace('&', "$\n", $request);
        }

        $this->requesturl = $url.$request;

        $this->pass = htmlspecialchars_decode($this->pass);


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . '?' . $request);
        curl_setopt($ch, CURLOPT_USERPWD, $this->user . ":" . $this->pass);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result);

        if ($this->debug) {
            echo '<pre>';
            print_r(json_decode($result));
            echo '<pre>';
        }


        if (null === $result) {

            if (null !== $this->logger) {
                $logger = $this->logger;
                $context = array('source' => 'Monta Checkout');
                $logger->critical("Webshop was unable to connect to Monta REST api. Retry #1 is starting in 1 seconds", $context);
            }

            sleep(3);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url . '?' . $request);
            curl_setopt($ch, CURLOPT_USERPWD, $this->user . ":" . $this->pass);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);

            $result = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($result);
        }

        if (null !== $this->logger && null === $result) {
            $logger = $this->logger;
            $context = array('source' => 'Monta Checkout');
            $logger->critical("Webshop was unable to connect to Monta REST api. Please contact Monta", $context);
        }

        if (null !== $this->logger && $result->Warnings) {

            foreach ($result->Warnings as $warning) {

                $logger = $this->logger;
                $context = array('source' => 'Monta Checkout');

                if (null !== $warning->ShipperCode) {
                    $logger->notice($warning->ShipperCode . " - " . $warning->Message, $context);
                } else {
                    $logger->notice($warning->Message, $context);
                }


            }
        }

        if (null !== $this->logger && $result->Notices) {

            foreach ($result->Notices as $notice) {
                $logger = $this->logger;
                $context = array('source' => 'Monta Checkout');

                if (null !== $notice->ShipperCode) {
                    $logger->notice($notice->ShipperCode . " - " . $notice->Message, $context);
                } else {
                    $logger->notice($notice->Message, $context);
                }


            }
        }

        if (null !== $this->logger && $result->ImpossibleShipperOptions) {

            foreach ($result->ImpossibleShipperOptions as $impossibleoption) {
                foreach ($impossibleoption->Reasons as $reason) {

                    $logger = $this->logger;
                    $context = array('source' => 'Monta Checkout');
                    $logger->notice($impossibleoption->ShipperCode . " - " . $reason->Code . " | " . $reason->Reason, $context);
                }
            }

        }

        return $result;

    }


}