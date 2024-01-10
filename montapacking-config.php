<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}


require_once('vendor/autoload.php');

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__));
$dotenv->load();