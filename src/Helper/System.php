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
        $info = self::getPluginInfo();
        return [
            Settings::CORE_SOFTWARE => 'WooCommerce',
            Settings::CORE_VERSION => WC_VERSION,
            Settings::CHECKOUT_API_WRAPPER_VERSION => "0.0", // TODO get dynamically
            Settings::MODULE_NAME => $moduleName,
            Settings::MODULE_VERSION => $info['Version'] ?? "0.0",
        ];
    }

    /**
     * @return array
     */
    protected static function getPluginInfo()
    {
        // Read own plugin file, information is in header, similar to how WC reads it
        return get_plugin_data(__DIR__ . "/../../montapacking-checkout.php");
    }
}