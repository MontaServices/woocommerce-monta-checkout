<?php

class MontaCheckout_Product
{

    public $sku;
    public $length;
    public $width;
    public $height;
    public $weight;
    public $quantity;

    public function __construct($sku, $length, $width, $height, $weight, $quantity)
    {

        $this->setSku($sku);
        $this->setLength($length);
        $this->setWidth($width);
        $this->setHeight($height);
        $this->setWeight($weight);
        $this->setQuantity($quantity);

    }

    public function setSku($sku)
    {
        $this->sku = $sku;
        return $this;
    }

    public function setLength($length)
    {
        $this->length = $length;
        return $this;
    }

    public function setWidth($width)
    {
        $this->width = $width;
        return $this;
    }

    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
    }

    public function setWeight($weight)
    {
        $this->weight = $weight;
        return $this;
    }

    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function toArray()
    {

        $product = [
            'SKU' => $this->sku,
            'LengthMm' => $this->length,
            'WidthMm' => $this->width,
            'HeightMm' => $this->height,
            'WeightGrammes' => $this->weight,
            'Quantity' => $this->quantity,
        ];

        return $product;

    }

}