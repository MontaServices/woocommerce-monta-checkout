<?php declare(strict_types=1);

namespace Monta\Checkout\Test;

use Monta\CheckoutApiWrapper\MontapackingShipping;
use Monta\CheckoutApiWrapper\Objects\Settings;
use PHPUnit\Framework\TestCase;
//use Shopware\Core\DevOps\Environment\EnvironmentHelper;

/**
 * @covers Calculator
 */
class MontaApiTest extends TestCase
{
    private MontapackingShipping $oApi;

    private string $monta_origin;
    private string $monta_username;
    private string $monta_password;
    private bool $monta_enable_pickup_points;
    private int $monta_max_pickup_points;
    private string $monta_google_api_key;
    private float $monta_default_costs;

    public function __construct()
    {
        parent::__construct();

        $this->monta_origin = (string) $_ENV['MONTA_ORIGIN'];
        $this->monta_username = (string) $_ENV['MONTA_USERNAME'];
        $this->monta_password = (string) $_ENV['MONTA_PASSWORD'];
        $this->monta_enable_pickup_points = (bool) $_ENV['MONTA_ENABLE_PICKUP_POINTS'];
        $this->monta_max_pickup_points = (int) $_ENV['MONTA_MAX_PICKUP_POINTS'];
        $this->monta_google_api_key = (string) $_ENV['MONTA_GOOGLE_API_KEY'];
        $this->monta_default_costs = (float) $_ENV['MONTA_DEFAULT_COSTS'];

        $settings = new Settings(
            $this->monta_origin,
            $this->monta_username,
            $this->monta_password,
            $this->monta_enable_pickup_points,
            $this->monta_max_pickup_points,
            $this->monta_google_api_key,
            $this->monta_default_costs);

        $this->oApi = new MontapackingShipping($settings, 'nl-NL');

        $this->oApi->setAddress(
            'Papland',
            '16',
            '',
            '4206 CL',
            'Gorinchem',
            '',
            'nl'
        );

        $this->oApi->addProduct('croc', 1, 100, 100, 100, 500, price: 100);
    }

    public function testIfAddressObjectExists(): void
    {
        $address = $this->oApi->address;

        if($address->street == 'Papland'
            && $address->houseNumber == '16'
            && $address->houseNumberAddition == ''
            && $address->postalCode == '4206 CL'
            && $address->city == 'Gorinchem'
            && $address->state == ''
            && $address->countryCode == 'nl') {

            $this->assertTrue(true);
            return;
        }

        $this->assertTrue(false);
    }

    public function testOptionsArrayGreaterThenNull(): void
    {
        $timeframes = $this->oApi->getShippingOptions();
        $deliveryOptions = $timeframes['DeliveryOptions'];

        if(count($deliveryOptions) <= 1) {
            $this->fail();
        }

        $this->assertTrue(true);
    }

    public function testOptionsProperties(): void
    {
        $timeframes = $this->oApi->getShippingOptions();
        $deliveryOptions = $timeframes['DeliveryOptions'];

        foreach($deliveryOptions as $timeframe) {
            foreach($timeframe->options as $option) {
                if($option->displayName == '') {
                    $this->fail('Did not contain a display name');
                }
                if($option->priceFormatted == '') {
                    $this->fail('Did not contain a formatted price');
                }
            }
        }

        $this->assertTrue(true);
    }

    public function testMaxPickupPoints(): void
    {
        $timeframes = $this->oApi->getShippingOptions();
        $deliveryOptions = $timeframes['PickupOptions'];

        if(count($deliveryOptions) != $this->monta_max_pickup_points) {
            $this->fail('Number of pickup points did not return the expected result');
        }

        $this->assertTrue(true);
    }

    public function testDisabledPickupPoints(): void
    {
        $oldValue = $this->monta_enable_pickup_points;
        $this->monta_enable_pickup_points = false;

        $settings = new Settings(
            $this->monta_origin,
            $this->monta_username,
            $this->monta_password,
            $this->monta_enable_pickup_points,
            $this->monta_max_pickup_points,
            $this->monta_google_api_key,
            $this->monta_default_costs);

        $this->oApi->setSettings($settings);

        $timeframes = $this->oApi->getShippingOptions();
        $deliveryOptions = $timeframes['PickupOptions'];

        if($this->monta_enable_pickup_points == false && count($deliveryOptions) > 0) {
            $this->fail('Expected 0 results when pickup points are disabled');
        } elseif (count($deliveryOptions) != $this->oApi->getSettings()->getMaxPickupPoints()) {
            $this->fail('Number of pickup points did not return the expected result got ' . count($deliveryOptions)  . ' expected ' . $this->monta_max_pickup_points);
        }

        $settings = new Settings(
            $this->monta_origin,
            $this->monta_username,
            $this->monta_password,
            $oldValue,
            $this->monta_max_pickup_points,
            $this->monta_google_api_key,
            $this->monta_default_costs);

        $this->oApi->setSettings($settings);

        $this->assertTrue(true);
    }
}