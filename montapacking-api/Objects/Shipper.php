<?php
class Shipper {

	public $name;
	public $code;

	public function __construct($name, $code){

		$this->setName($name);
		$this->setCode($code);

	}

	public function setName($name){
		$this->name = $name;
		return $this;
	}

	public function setCode($code){
		$this->code = $code;
		return $this;
	}

	public function toArray(){

		$shipper = [
			'code' => $this->code,
			'name' => $this->name
		];

		return $shipper;

	}

}