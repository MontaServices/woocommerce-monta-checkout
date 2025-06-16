<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class FirstCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    // tests
    public function tryToTest(AcceptanceTester $I)
    {
        /**
         * Go to the product page with a known sku
         */
        // $I->click(["xpath" => "//a[@data-product_sku='crocs']"]);
        $I->amOnPage('/product/crocs/');

        /**
         * Skip all cookie and warning popups
         */
        $I->click('.ct-cookies-accept-button');
        $I->click('.woocommerce-store-notice__dismiss-link');
        $I->click('.single_add_to_cart_button');

        /**
         * Wait for 5 seconds because the page is making an Ajax call to the backend
         */
        $I->wait(5);

        /**
         * Click on button to add to cart
         */
        $I->click('.added_to_cart');

        /**
         * Scroll to the checkout button and wait a small moment
         */
        $I->scrollTo('.checkout-button', 0, 200);
        $I->wait(0.5);

        /**
         * Go to the checkout
         */
        $I->click('.checkout-button');

        /**
         * Fill the user information
         */
        $I->fillField('billing_first_name', 'Kevin');
        $I->fillField('billing_last_name', 'Kroos');
        $I->fillField('billing_company', 'Monta');
        $I->selectOption('billing_country', 'Nederland');
        $I->fillField('billing_address_1', 'Papland 16');
        $I->fillField('billing_postcode', '4206 CL');
        $I->fillField('billing_city', 'Gorinchem');
        $I->fillField('billing_phone', '0613000842');
        $I->fillField('billing_email', 'kevin.kroos@monta.nl');

        $I->wait(10);

        $I->canSeeElement('.monta-times-croppped');
        $I->canSeeElement('#othersendmethod');

        $I->scrollTo('#othersendmethod', 0, 200);
        $I->wait(0.5);
        $I->click('#othersendmethod');

        $I->click('.monta-shipment-shipper label');

        $I->click('.monta-option-pickup');

        $I->wait(5);
    }
}
