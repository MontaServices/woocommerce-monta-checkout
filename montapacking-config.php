<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

include('montapacking-api/MontapackingShipping.php');

define('MONTA_SHOP', esc_attr( get_option('monta_shop') ));
define('MONTA_USER', esc_attr( get_option('monta_username') ));
define('MONTA_PASS', esc_attr( get_option('monta_password') ));
define('MONTA_GOOGLE_KEY', esc_attr( get_option('monta_google_key') ));

#define('MONTA_TKEY', 'montapacking-checkout');