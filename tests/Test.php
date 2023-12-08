<?php declare(strict_types=1);

use Monta\CheckoutApiWrapper\MontapackingShipping;
use Monta\CheckoutApiWrapper\Objects\Settings;
use PHPUnit\Framework\TestCase;

require_once('montapacking-class.php');

final class StackTest extends TestCase
{
    public function testPushAndPop(): void
    {
//        $montacking = new Montapacking;

        $stack = [];
        $this->assertSame(0, count($stack));

        array_push($stack, 'foo');
        $this->assertSame('foo', $stack[count($stack)-1]);
        $this->assertSame(1, count($stack));

        $this->assertSame('foo', array_pop($stack));
        $this->assertSame(0, count($stack));

        $settings = new Settings('Demoshop.nl', 'wilco@montapacking.nl', '4%9_BYN%MCGP', true, 10, 'AIzaSyDc1ruiAmgYhuU6d925mrPCaBsyMAHT0LI', 2);

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

//        $test1 = $montacking->get_frames();

//        $this->assertNotSame(null, $results);
        $this->assertNotNull($results);
        $this->assertTrue( sizeof($results['DeliveryOptions']) > 0);
        $this->assertTrue( sizeof($results['PickupOptions']) > 0);
    }
}