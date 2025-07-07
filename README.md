# Monta Checkout

## Description

WooCommerce plugin to integrate the Monta shipping options

## Installation procedure

1. Upload files in `wp-content/plugins` folder
1. Make sure plugin folder is called `montapacking-checkout-woocommerce-extension`
1. Make sure that `WordPress Address (URL)` & `Site Address (URL)` fields in the `Settings > General` tab are identical
1. Run `composer install` from plugin folder
1. Enable plugin in WordPress settings
1. Module is ready to be configured!

## Configuration

#### Monta API settings:

Visit `Plugins > MontaCheckout > Settings`.
Enter your Username/Password combination for the Monta API here.

#### WooCommerce Classic

Wij maken gebruik van Woocommerce Classic dus:

1. Add Page (empty)
1. Insert _Shortcode_ element
1. Type `[woocommerce-checkout]`
1. _Save Page_
1. _Woocommerce > Settings > Advanced > Page Setup > Checkout Page_
    1. Select your newly created Checkout page
    1. Save Changes

### Google API:

Specify a valid Google Maps API key here. Can be requested
here: https://developers.google.com/maps/documentation/javascript/get-api-key.
Needed for the map with pickup points.

### Webshop/origin:

Name of your webshop as known by Monta.
This determines which shippers are allowed, cutofftime etc.

### Enable shippers:

Configure prices in Montaportal:
_Menu bar > Settings > Checkout options > Edit for a webshop._