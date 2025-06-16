# Monta Checkout
Contributors: monta
Tags: monta, checkout, woocommerce, extension, monta
Requires at least: 4.0.1
Tested up to: 6.4.1
Stable tag: 1.58.52
Requires PHP: 8.0
License: GPLv3 or later License
License URI: https://www.gnu.org/licenses/gpl-2.0.html

## Description
WooCommerce plugin to integrate the Monta shipping options

## Installation procedure

* Upload files in wp-content/plugins folder
* Make sure plugin folder is called 'montapacking-checkout-woocommerce-extension'
* Make sure that 'WordPress Address (URL)' & 'Site Address (URL)' fields in the Settings>General tab are identical
* Run `composer install` from plugin folder
* Enable plugin in WordPress settings
* Configure plugin Settings

## Configuration

#### Monta API settings: 
Specify your username/password for Monta's API here

### Google API:
Specify a valid Google Maps API key here. Can be requested here: https://developers.google.com/maps/documentation/javascript/get-api-key.
Needed for the map with pickup points.

### Webshop/origin:
Name of your webshop as known by Monta. This determines which shippers are allowed, cutofftime etc.

### To enable shippers:
Configure prices in Montaportal: 
Blue menu bar > Settings > Checkout options > Edit for a webshop.