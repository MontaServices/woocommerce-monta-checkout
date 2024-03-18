/**
 * Format the price with a currency symbol.
 *
 * @param float price The product price.
 * @param mixed wc_settings_args WooCommerce price options defined.
 * https://github.com/homescriptone/wc-price-js/blob/main/wc_price.js
 * @return float The formatted product price.
 */
function wc_price(price, wc_settings_args) {
    var default_args = {
        decimal_sep: wc_settings_args.decimal_separator,
        currency_position: wc_settings_args.currency_position,
        currency_symbol: wc_settings_args.currency_symbol,
        trim_zeros: wc_settings_args.currency_format_trim_zeros,
        num_decimals: wc_settings_args.currency_format_num_decimals,
        html: false
    };
    if (default_args.num_decimals > 0) {
        var wc_price__length = parseInt(price).toString().length;
        // var wc_int_end_sep = wc_price_length + default_args.num_decimals;
        // price = price.toString().substr(0, wc_int_end_sep + 1);
        price = parseFloat(price).toFixed(default_args.num_decimals);
    } else {
        price = parseInt(price);
    }
    price = price.toString().replace('.', default_args.decimal_sep);
    var formatted_price = price;
    var formatted_symbol = default_args.html ? '<span class="woocommerce-Price-currencySymbol">' + default_args.currency_symbol + '</span>' : default_args.currency_symbol;
    if ('left' === default_args.currency_position) {
        formatted_price = formatted_symbol + formatted_price;
    } else if ('right' === default_args.currency_position) {
        formatted_price = formatted_price + formatted_symbol;
    } else if ('left_space' === default_args.currency_position) {
        formatted_price = formatted_symbol + ' ' + formatted_price;
    } else if ('right_space' === default_args.currency_position) {
        formatted_price = formatted_price + ' ' + formatted_symbol;
    }
    formatted_price = default_args.html ? '<span class="woocommerce-Price-amount amount">' + formatted_price + '</span>' : formatted_price;
    return formatted_price;
}