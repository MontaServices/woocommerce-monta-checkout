# Changelog

### 1.58.52

* Added hide dhl packstation option

### 1.58.51

* Ignored more pickup point checks when item is virtual product

### 1.58.50

* Added france translations

### 1.58.49

* Fixed a null reference bug when checking products by SKU is disabled, but no dimensions are provided.

### 1.58.48

* Added French translation for the WooCommerce Monta module.
* Fixed a bug where the length, height, and depth parameters were not correctly set when the product SKU check was disabled. Please check the setting CheckProductsOnSku and assure it is set correctly to your desire before updating the package.

### 1.58.47

* Fixed pickup points null reference error when no house number is provided by postNL

### 1.58.46

* Fixed validation issue for virtual products where customer could not finish the checkout

### 1.58.45

* Changed translations for datepicker block

### 1.58.44

* Bugfix invoice shpping price pickup points

### 1.58.43

* Bugfix invoice shpping price

### 1.58.42

Bugfixes for double shipping costs and uploading store collect image

### 1.58.41

* Possibility to show text "free" when zero costs
* Possibility to override text of store collect button
* Possibility to upload your own image for store collect

### 1.58.40

* Added setting to hide delivery options

### 1.58.39

* Bugfix store collect

### 1.58.38

* Bugfix shipping costs fallback shipper

### 1.58.37

* Show standard shipper when enabled

### 1.58.36

* Bugfix shipping costs

### 1.58.35

* Added support for Woo Subscriptions

### 1.58.34

* Fixed some php warnings for servers that have php warnings configured in php.ini

### 1.58.33

* Fixed issue where evening delivery option was not correctly read in MontaPortal

### 1.58.32

* Support for High-Performance Order Storage

### 1.58.31

* Fixed price issues when store collect was selected

### 1.58.30

* Switched phone and email fields in Woocommerce order display

### 1.58.29

* Fixed issue where alternative delivery address fields are read incorrectly

### 1.58.28

* Added multi-currency support

### 1.58.27

* Made it possible to exclude shipping discount for logged in user with a specific role

### 1.58.26

* Fixed issue where validation result could be wrong when selecting alternative delivery address

### 1.58.25

* Made it possible to show extra fields for shipping phone and mailadres for shipping to other address

### 1.58.24

* Fixed issue where total shipping cost field would not be displayed before the checkout has been completed when calling the custom hook

### 1.58.23

* Fixed issue where price would not be added correctly

### 1.58.22

* Added hook for shipping_calculate html output

### 1.58.21

* Made it possible to only show pickup points without having delivery options enabled in the MontaPortal

### 1.58.20

* Make use of wp_load to prevent warning logging

### 1.58.19

* Resolved some PHP warnings

### 1.58.18

* Added store collect points as separate list, so it will be always visible, even when the location is far away

### 1.58.17

* Fixed store collect set shipping costs and fixed to prevent 'extra options' text from being shown incorrectly

### 1.58.16

* Fixed set 0 shipping costs when free shipping is used

### 1.58.15

* Fixed date in datepicker to be in the incorrect culture format

### 1.58.14

* Fixed show store collect inside pickup points list

### 1.58.13

* Fixed user other postcode in pickup points modal

### 1.58.12

* Fixed pickup points inside store collect list

### 1.58.11

* Updated vendor

### 1.58.10

* Fixed blocked pickuppoint issue in rare occasions

### 1.58.9

* Bugfixes

### 1.58.8

* Fixed timepicker issue when cutoff time has been reached for same day delivery shippers

### 1.58.7

* Added new shipper images

### 1.58.6

* Bugfix datepicker for null date

### 1.58.5

* Small bugfix pickuppoints

### 1.58.4

* Small version upgrade for Wordpress Plugin Store

### 1.58.3

* Small bugfixes

### 1.58.2

* Fix shipping costs when using multiple plugins

### 1.58.1

* Replaced PHP $_SESSION to WC()->Session

### 1.58.0

* Major upgrade to Monta Rest V6 Backend

### 1.56.1

* Fixed incidentally selecting wrong preferred shipper time

### 1.56

* Added Asendia

### 1.55.3

* Fixed warehouse collect not saving to order correctly

### 1.55.2

* Fix bundles causing api request to fail

### 1.55.1

* Fix issue in fallback when either coupon or price condition is on

### 1.55

* Added option to fallback to WooCommerce shipping options on API error

### 1.54.1

* Fixed pickuppoint shipper sometimes appearing in shipping tab

### 1.54

* PostNL now shows if delivery is sustainable
* Added possibility to make sending at later dates cheaper

### 1.53

* Added support for PostNL sustainable delivery indications
* Added fallback in case API request to Monta is faulty

### 1.50

* Fixed issue with different shipping addresses and pickuppoints
* Added support for preferred shipper and shipper display names
* Added seperate tab for in store/ warehouse pickup

### 1.49

* Fixed wrong data in order summary delivery field
* Added field in storelocator to search for pickuppoints based on different postcode/zipcode
* Better fallback for missing google key. You can now show pickuppoints without a Google maps key. The missing Google maps key will only disable the map view.

### 1.48

* Automatically switch to pickup if only pickup options

### 1.47

* Fixed cart array issue

### 1.46

* Fixed Mollie plugin Klarna nog working
* Added Izipack, Seabourne and Cycloon

### 1.35

* Added postnumber validation for DHL packstations in Germany
* Layout change in pickup points

### 1.39

* German language fix

### 1.40

* Added PostNL Pakjes Tracked

== Upgrade Notice ==