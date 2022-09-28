<?php

class MontaCheckout_Order
{

    public $total_incl;
    public $total_excl;

    public function __construct($incl, $excl)
    {

        $this->setIncl($incl);
        $this->setExcl($excl);

    }

    public function setIncl($incl)
    {
        $this->total_incl = $incl;
        return $this;
    }

    public function setExcl($excl)
    {
        $this->total_excl = $excl;
        return $this;
    }

    public function toArray()
    {

        $order = [
            'OrderValueInclVat' => $this->total_incl,
            'OrderValueExclVat' => $this->total_excl,
        ];

        return $order;

    }

}