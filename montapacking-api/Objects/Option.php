<?php
class Option {

    public $code;
    public $name;
    public $price;
    public $currency;

    public function __construct($code, $name, $price, $currency){

        $this->setCode($code);
        $this->setName($name);
        $this->setPrice($price);
        $this->setCurrency($currency);

    }

    public function setName($name){

        if ($this->code == "SignatureOnDelivery") {

            $this->name = __( "The customer needs to sign upon delivery." , TKEY);

        } else if ($this->code == "NoNeighbour") {

            $this->name = __( "Do not deliver at the neighbours." , TKEY);

        } else {

            $this->name = $name;

        }

        //$this->name = $name;
        return $this;
    }

    public function setCode($code){
        $this->code = $code;
        return $this;
    }

    public function setPrice($price){
        $this->price = $price;
        return $this;
    }

    public function setCurrency($currency){
        $this->currency = $currency;
        return $this;
    }

    public function toArray(){

        $option = null;
        foreach ($this as $key => $value){
            $option[$key] = $value;
        }

        return $option;

    }

}