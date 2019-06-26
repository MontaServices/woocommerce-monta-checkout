<?php

class Address {

    public $street;
    public $housenumber;
    public $housenumberaddition;
    public $postalcode;
    public $city;
    public $state;
    public $countrycode;
    public $longitude;
    public $latitude;

    public function __construct( $street, $housenumber, $housenumberaddition, $postalcode, $city, $state, $countrycode ) {

        $this->setStreet( $street );
        $this->setHousenumber( $housenumber );
        $this->setHousenumberAddition( $housenumberaddition );
        $this->setPostalcode( $postalcode );
        $this->setCity( $city );
        $this->setState( $state );
        $this->setCountry( $countrycode );
        $this->setLongLat();

    }

    public function setLongLat() {

        // Get lat and long by address
        $address  = $this->street . ' ' . $this->housenumber . ' ' . $this->housenumberaddition . ', ' . $this->postalcode . ' ' . $this->countrycode . ''; // Google HQ
        $prepAddr = str_replace( '  ', ' ', $address );
        $prepAddr = str_replace( ' ', '+', $prepAddr );
        $geocode  = file_get_contents( 'https://maps.google.com/maps/api/geocode/json?address=' . $prepAddr . '&sensor=false' );
        $output   = json_decode( $geocode );

        $result = end( $output->results );
        if ( isset( $result->geometry ) ) {
            $latitude  = $result->geometry->location->lat;
            $longitude = $result->geometry->location->lng;
        } else {
            $latitude  = 0;
            $longitude = 0;
        }

        $this->longitude = $longitude;
        $this->latitude  = $latitude;

    }

    public function setStreet( $street ) {
        $this->street = $street;

        return $this;
    }

    public function setHousenumber( $housenumber ) {
        $this->housenumber = $housenumber;

        return $this;
    }

    public function setHousenumberAddition( $housenumberaddition ) {
        $this->housenumberaddition = $housenumberaddition;

        return $this;
    }

    public function setPostalcode( $postalcode ) {
        $this->postalcode = $postalcode;

        return $this;
    }

    public function setCity( $city ) {
        $this->city = $city;

        return $this;
    }

    public function setState( $state ) {
        $this->state = $state;

        return $this;
    }

    public function setCountry( $country ) {
        $this->countrycode = $country;

        return $this;
    }

    public function toArray() {

        $address = [
            'Address.Street' => $this->street,
            'Address.HouseNumber' => $this->housenumber,
            'Address.HouseNumberAddition' => $this->housenumberaddition,
            'Address.PostalCode' => $this->postalcode,
            'Address.City' => $this->city,
            'Address.State' => $this->state,
            'Address.CountryCode' => $this->countrycode,
            'Address.Latitude' => $this->latitude,
            'Address.Longitude' => $this->longitude,
        ];

        return $address;

    }

}