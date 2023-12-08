<?php declare(strict_types=1);

use Monta\CheckoutApiWrapper\MontapackingShipping;
use Monta\CheckoutApiWrapper\Objects\Settings;
use PHPUnit\Framework\TestCase;

require_once('montapacking-class.php');

final class StackTest extends TestCase
{
    public function testPushAndPop(): void
    {
        $settings = new Settings('Demoshop.nl', 'wilco@montapacking.nl', '', true, 10, '', 2);

        $api = new MontapackingShipping($settings, 'nl-NL');

        $api->setAddress(
            'Papland',
            16,
            '',
            '4206L',
            'Gorinchem',
            '',
            'NL'
        );
        $api->addProduct('croc', 1);

        $results = $api->getShippingOptions();

        $this->assertNotNull($results);
        $this->assertTrue( sizeof($results['DeliveryOptions']) > 0);
        $this->assertTrue( sizeof($results['PickupOptions']) > 0);
    }
}