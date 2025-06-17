<?php
/**
 * @author Jacco.Amersfoort <jacco.amersfoort@monta.nl>
 * @created 17/06/2025 16:02
 */
namespace Monta\Helper;

use Monta\CheckoutApiWrapper\Objects\Settings;

class System
{
    /** Collection system information for this installation
     *
     * @return string[]
     */
    public static function getInfo(): array
    {
        $moduleName = 'monta/woocommerce-checkout';
        return [
            Settings::CORE_SOFTWARE => 'WooCommerce',
            Settings::MODULE_NAME => $moduleName,
            // TODO and the rest
        ];
    }
}