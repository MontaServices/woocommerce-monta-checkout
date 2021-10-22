=== Montapacking Checkout WooCommerce Extension ===
Contributors: montapacking
Donate link: 
Tags: montapacking, checkout, woocommerce, extension, monta
Requires at least: 4.0.1
Tested up to: 5.7.2
Stable tag: 1.32
Requires PHP: 5.6
License: GPLv3 or later License
License URI: https://www.gnu.org/licenses/gpl-2.0.html



== Description ==

WooCommerce plugin to integrate the Montapacking shipping options

== Installation ==

# Installation procedure

* Upload files in wp-content/plugins folder
* Make sure plugin folder is called 'montapacking-checkout-woocommerce-extension'
* Make sure that 'WordPress Address (URL)' & 'Site Address (URL)' fields in the Settings>General tab are identical
* Enable plugin in WordPress settings
* Configure plugin Settings

# Configuration

Montapacking API Settings: 

* Specify your username and password for the Montapackings API here

Google API:

* Specify a valid Google Maps API key here. A key can be created here: https://developers.google.com/maps/documentation/javascript/get-api-key.
This key is needed for the map with pickup points.


* Webshop/Origin:

Name of your webshop as known by Montapacking. This determines which shippers are allowed, cutofftime etc.


* Enable shippers:

Configure prices in Montaportal: 
Blue menu bar > Settings > Checkout options > Edit for a webshop.

== Frequently Asked Questions ==


== Screenshots ==


== Changelog ==

#1.28

* PHP 8 compatible
* Added Budbee Logo
* Added default start value for price in shopping cart in stead of using the default 'no-connection' price
* Removed the 'leading stock' option in de the settings, woocommerce is always leading now
* Code cleanup


== Upgrade Notice ==

