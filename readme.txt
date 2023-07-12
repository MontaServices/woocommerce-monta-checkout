=== Monta Checkout ===
Contributors: monta
Donate link: 
Tags: monta, checkout, woocommerce, extension, monta
Requires at least: 4.0.1
Tested up to: 6.1.1
Stable tag: 1.56.1
Requires PHP: 5.6
License: GPLv3 or later License
License URI: https://www.gnu.org/licenses/gpl-2.0.html



== Description ==

WooCommerce plugin to integrate the Monta shipping options

== Installation ==

# Installation procedure

* Upload files in wp-content/plugins folder
* Make sure plugin folder is called 'montapacking-checkout-woocommerce-extension'
* Make sure that 'WordPress Address (URL)' & 'Site Address (URL)' fields in the Settings>General tab are identical
* Enable plugin in WordPress settings
* Configure plugin Settings

# Configuration

Monta API Settings:

* Specify your username and password for the Monta's API here

Google API:

* Specify a valid Google Maps API key here. A key can be created here: https://developers.google.com/maps/documentation/javascript/get-api-key.
This key is needed for the map with pickup points.


* Webshop/Origin:

Name of your webshop as known by Monta. This determines which shippers are allowed, cutofftime etc.


* Enable shippers:

Configure prices in Montaportal: 
Blue menu bar > Settings > Checkout options > Edit for a webshop.

== Frequently Asked Questions ==


== Screenshots ==


== Changelog ==

#1.56.1

* Fixed incidentally selecting wrong preferred shipper time

#1.56 

* Added Asendia

#1.55.3

* Fixed warehouse collect not saving to order correctly

#1.55.2

* Fix bundles causing api request to fail

#1.55.1

* Fix issue in fallback when either coupon or price condition is on

#1.55

* Added option to fallback to WooCommerce shipping options on API error

#1.54.1

* Fixed pickuppoint shipper sometimes appearing in shipping tab

#1.54

* PostNL now shows if delivery is sustainable
* Added possibility to make sending at later dates cheaper

#1.53

* Added support for PostNL sustainable delivery indications
* Added fallback in case API request to Monta is faulty

#1.50

* Fixed issue with different shipping addresses and pickuppoints
* Added support for preferred shipper and shipper display names
* Added seperate tab for in store/ warehouse pickup

#1.49

* Fixed wrong data in order summary delivery field
* Added field in storelocator to search for pickuppoints based on different postcode/zipcode
* Better fallback for missing google key. You can now show pickuppoints without a Google maps key. The missing Google maps key will only disable the map view.

#1.48

* Automatically switch to pickup if only pickup options

#1.47

* Fixed cart array issue

#1.46

* Fixed Mollie plugin Klarna nog working
* Added Izipack, Seabourne and Cycloon

#1.35

* Added postnumber validation for DHL packstations in Germany
* Layout change in pickup points

#1.39

* German language fix

#1.40

* Added PostNL Pakjes Tracked

== Upgrade Notice ==

