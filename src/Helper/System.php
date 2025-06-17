<?php
/**
 * @author Jacco.Amersfoort <jacco.amersfoort@monta.nl>
 * @created 17/06/2025 16:02
 */
namespace Monta\Helper;

use Composer\InstalledVersions;
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
            Settings::CORE_VERSION => WC_VERSION, // WooCommerce version is defined as global constant
            Settings::CHECKOUT_API_WRAPPER_VERSION => self::getComposerVersion("monta/checkout-api-wrapper"),
            Settings::MODULE_NAME => $moduleName,
            Settings::MODULE_VERSION => $info['Version'] ?? "0.0",
        ];
    }

    /** TODO duplicate code in WooCommerce, Shopware and Magento projects. Merge centrally to CheckoutApiWrapper
     *
     * @param string $packageName
     * @return string|null
     */
    protected static function getComposerVersion(string $packageName)
    {
        try {
            return InstalledVersions::getVersion($packageName);
        } catch (\OutOfBoundsException $e) {
            // When module not installed, catch error and return empty string
            return "";
        }
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