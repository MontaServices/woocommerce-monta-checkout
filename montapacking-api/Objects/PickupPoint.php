<?php
require_once("ShippingOption.php");

class MontaCheckout_PickupPoint
{

    public $from;
    public $to;
    public $code;
    public $details = [];
    public $options = [];

    public function __construct($from, $to, $code, $details, $options)
    {

        $this->setFrom($from);
        $this->setTo($to);
        $this->setCode($code);
        $this->setDetails($details);
        $this->setOptions($options);

    }

    public function setFrom($from)
    {
        $this->from = $from;
        return $this;
    }

    public function setTo($to)
    {
        $this->to = $to;
        return $this;
    }

    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    public function setDetails($details)
    {

        $pickup = null;
        if (is_object($details)) {

            $today = date("l");
            $times = $details->OpeningTimes;

            $arr = array();
            foreach ($times as $key => $values) {
                if ($values->Day == $today) {
                    foreach ($values->OpeningTimes as $timekey => $times) {

                        $array = array();
                        $array['from'] = $times->From;
                        $array['to'] = $times->To;
                        $arr[] = $array;
                    }
                }
            }

            $pickup = (object)[
                'code' => $details->Code,
                'name' => $details->Company,
                'street' => $details->Street,
                'houseNumber' => $details->HouseNumber,
                'zipcode' => $details->PostalCode,
                'place' => $details->City,
                'country' => $details->CountryCode,
                'phone' => $details->Phone,

                'distance' => $details->DistanceMeters,
                'lat' => $details->Latitude,
                'lng' => $details->Longitude,
                'openingtimes' => json_encode($arr),
                'image' => $details->ImageUrl,
            ];

        }

        $this->details = $pickup;

        return $this;
    }

    public function setOptions($options)
    {

        $list = null;

        if (is_array($options)) {

            foreach ($options as $option) {

                $list[] = new MontaCheckout_ShippingOption(
                    $option->Code,
                    $option->ShipperCodes,
                    $option->ShipperOptionCodes,
                    $option->ShipperOptionsWithValue,
                    $option->Description,
                    $option->IsMailbox,
                    $option->SellPrice,
                    $option->SellPriceCurrency,
                    $option->From,
                    $option->To,
                    $option->Options,
                    $option->ShippingDeadline
                );

            }

        }

        $this->options = $list;
        return $this;

    }

    public function toArray()
    {

        $option = null;
        foreach ($this as $key => $value) {
            $option[$key] = $value;
        }

        return $option;

    }

}