<script>
    var site_url = '<?php echo site_url();?>';
    var sustainableDeliveryText = '<?php _e('Sustainably delivered', 'montapacking-checkout');?>';

    <?php /*
    jQuery(document).ready(function () {
        setTimeout(function () {
            var radios = document.getElementsByName("montapacking[shipment][type]");

            radios[1].click();

            var val = localStorage.getItem('montapacking[shipment][type]');
            for (var i = 0; i < radios.length; i++) {

                if (radios[i].value == val) {
                    radios[i].click();
                    //radios[i].checked = true;
                }
            }

        }, 1000);

        jQuery('.selectshipment').on('click', function () {
            localStorage.setItem('montapacking[shipment][type]', jQuery(this).val());
        });


    });
    */ ?>

</script>

<input id="maxpickuppoints" type="hidden" name="maxpickuppoints"
       value="<?php echo esc_attr(get_option('monta_max_pickuppoints')) <= 0 ? 3 : esc_attr(get_option('monta_max_pickuppoints')); ?>">

<input id="zero-costs-as-free" type="hidden" name="zero-costs-as-free"
       value="<?php echo esc_attr(get_option('monta_show_zero_costs_as_free')) ?>">

<input id="afh-image" type="hidden" name="afh-image" value="<?php echo esc_attr(get_option('monta_afh_image_path')) ?>">

<div class="woocommerce-shipping-fields montapacking-shipping">

    <h3><?php _e('Shipping method', 'montapacking-checkout'); ?></h3>

    <p id="monta-address-required"><?php _e("Please fill in an address before selecting a shipping method", 'montapacking-checkout') ?></p>

    <div class="monta-options <?php echo esc_attr(get_option('monta_disablepickup') && get_option('monta_disablecollect')) ? "monta-hide" : "" ?>"
         id="tabselector">

        <div class="monta-option monta-disabled monta-option-delivery <?php echo esc_attr(get_option('monta_disabledelivery')) ? "monta-hide" : "" ?>">

            <label>
                <input type="radio" name="montapacking[shipment][type]" value="delivery" class="selectshipment"
                       autocomplete="on"/>
                <span class="block">
					<?php _e('Delivery', 'montapacking-checkout'); ?>
				</span>
            </label>

        </div>
        <div class="monta-option monta-disabled monta-option-pickup <?php echo esc_attr(get_option('monta_disablepickup')) ? "monta-hide" : "" ?>">

            <label>
                <input type="radio" name="montapacking[shipment][type]" value="pickup" class="selectshipment"
                       autocomplete="on"/>
                <span class="block">
					<?php _e('Pickup', 'montapacking-checkout'); ?>
				</span>
            </label>

        </div>
        <div class="monta-option monta-disabled monta-option-pickup <?php echo esc_attr(get_option('monta_disablecollect')) ? "monta-hide" : "" ?>">

            <label>
                <input type="radio" name="montapacking[shipment][type]" value="collect" class="selectshipment"
                       autocomplete="on"/>
                <span class="block">
                    <?php if (!empty(esc_attr(get_option('monta_collect_name')))) : ?>
                        <?php echo esc_attr(get_option('monta_collect_name')) ?>
                    <?php else : ?>
                        <?php _e('Store Collect', 'montapacking-checkout'); ?>
                    <?php endif ?>
				</span>
            </label>

        </div>
    </div>

    <div class="monta-loading"></div>

    <div class="monta-shipment-delivery">

        <div class="monta-times-croppped">

            <label>
                <div id="imglogo" class="imglogo"></div>

                <div class="information">

                    <span class="delivery-time"><?php _e('Delivery time and date', 'montapacking-checkout'); ?></span>
                    <span class="send-time"><?php _e('Send date', 'montapacking-checkout'); ?></span>

                    <span class="deliveryinfo deliveryinformation">
                        <?php _e('Your order will be delivered with', 'montapacking-checkout'); ?>
                        <strong class="shippingtype"></strong>
                    </span>


                    <div style="clear:both"></div>

                    <a href="javascript:;"
                       id="othersendmethod"><?php _e('Click here to choose another delivery option', 'montapacking-checkout'); ?></a>

                </div>

                <div style="clear:both"></div>
            </label>

            <div class="clear:both"></div>
        </div>


        <div class="monta-times-extended" style="display:none">
            <div class="monta-shipment-standard-shipper"></div>

            <span class="monta-times-extended-title"><?php _e('Select a shipping option', 'montapacking-checkout'); ?></span><br>
            <div class="monta-times">

                <a class="toggle-left"><</a>
                <div class="montascroller">

                    <div class="mover">
                        <ul></ul>
                    </div>

                </div>
                <a class="toggle-right">></a>

            </div>

            <div class="monta-shipment-shipper"></div>
        </div>

    </div>

    <div class="monta-shipment-pickup">

        <!-- First 3 pickup point options -->
        <div class="monta-pickup-initial-points">
            <div id="monta-pickups" class="bh-sl-map-container">

                <div class="monta-locations" style="width: 100% !important;">

                    <div class="bh-sl-loc-list">
                        <ul id="initialPickupsList">
                            <!-- pick-up points -->
                        </ul>
                        <input style="display: none;" type="radio" name="initialPickupPointRadio"
                               id="initialPickupRadioDummy">
                    </div>

                    <div class="monta-pickup-selected"></div>

                    <div id="PCPostNummer">
                        <label for="DHLPCPostNummer" class="">Postnummer&nbsp;<abbr class="required"
                                                                                    title="verplicht">*</abbr></label>
                        <input type="text" id="DHLPCPostNummer" name="montapacking[pickup][postnumber]" value=""
                               style="width:100%" disabled="disabled">
                    </div>


                    <p>
                        <a class="monta-more-pickup-points"><?php _e('Show more options', 'montapacking-checkout'); ?></a>
                    </p>

                </div>

            </div>
        </div>


        <!--span class="monta-pickup-selected-title" style="display:none"><?php _e('Selected pickup point', 'montapacking-checkout'); ?></span><br>-->


        <input id="montapackingpickupcode" type="hidden" name="montapacking[pickup][code]"
               class="monta-pickup-input-code monta-pickup-fields">
        <input id="montapackingpickupshipper" type="hidden" name="montapacking[pickup][shipper]"
               class="monta-pickup-input-shipper monta-pickup-fields">
        <input id="montapackingpickupshippingoptions" type="hidden" name="montapacking[pickup][shippingOptions]"
               class="monta-pickup-input-shippingOptions monta-pickup-fields">
        <input id="montapackingpickupcompany" type="hidden" name="montapacking[pickup][company]"
               class="monta-pickup-input-company monta-pickup-fields">
        <input id="montapackingpickupstreet" type="hidden" name="montapacking[pickup][street]"
               class="monta-pickup-input-street monta-pickup-fields">
        <input id="montapackingpickuphousenumber" type="hidden" name="montapacking[pickup][houseNumber]"
               class="monta-pickup-input-houseNumber monta-pickup-fields">
        <input id="montapackingpickuppostal" type="hidden" name="montapacking[pickup][postal]"
               class="monta-pickup-input-postal monta-pickup-fields">
        <input id="montapackingpickupcity" type="hidden" name="montapacking[pickup][city]"
               class="monta-pickup-input-city monta-pickup-fields">
        <input id="montapackingpickupdescription" type="hidden" name="montapacking[pickup][description]"
               class="monta-pickup-input-description monta-pickup-fields">
        <input id="montapackingpickupcountry" type="hidden" name="montapacking[pickup][country]"
               class="monta-pickup-input-country monta-pickup-fields">
        <input id="montapackingpickupprice" type="hidden" name="montapacking[pickup][price]"
               class="monta-pickup-input-price monta-pickup-fields">

    </div>

    <div class="monta-shipment-extras">
        <span><?php _e('Extra options:', 'montapacking-checkout'); ?></span><br>
        <div class="monta-shipment-extra-options"></div>
    </div>

    <div class="monta-times-croppped-error-deliveries monta-times-cropped-error" style="display:none">
        <?php _e('No deliveries available for the chosen delivery address.', 'montapacking-checkout'); ?>
        <div class="clear:both"></div>
    </div>

    <div class="monta-times-croppped-error-pickup monta-times-cropped-error" style="display:none">
        <?php _e('No pickups available for the chosen delivery address', 'montapacking-checkout'); ?>
        <div class="clear:both"></div>
    </div>

</div>

<div class="monta-extra-template">
    <label>

        <div class="checkbox">
            <input type="checkbox" name="montapacking[shipment][extras][]" value="{.code}">
        </div>

        <div class="information">
            {.name}
        </div>
        <div class="pricemonta">
            {.price}
        </div>
        <div class="clearboth"></div>
    </label>
</div>


<ul class="monta-choice-template">
    <li>
        <label>
            <input type="radio" name="montapacking[shipment][time]" value="{.id}" data-preferred="{.preferred}"
                   class="montapackingshipmenttime {.sameday}">
            <span>
                <span class="dayname">{.dayname}</span>
				<span class="day">{.day}</span>
                {.discount}
			</span>
        </label>
    </li>
</ul>


<!-- STORE LOCATOR -->

<div class="monta-cover monta-hide">
    <div class="monta-window">

        <div class="monta-pickup-loading"><?php _e('Loading...', 'montapacking-checkout'); ?></div>

        <div class="monta-pickup-active monta-hide bh-sl-container">

            <a class="monta-close-pickup">x</a>
            <div style="flex-direction: row; display: flex;">
                <div class="monta-options bh-sl-form-container" style="width: 65%">
                    <!--Old shipper filter-->
                    <div class="bh-sl-filters-container">

                        <ul id="category-filters" class="bh-sl-filters" style="white-space: nowrap; overflow-x: auto;">
                        </ul>

                    </div>
                </div>
                <div class="montapacking-search-zipcode-container">
                    <label for="montapacking-search-zipcode"
                           class="montapacking-search-zipcode-label"><?php _e('Search using postal code', 'montapacking-checkout'); ?></label>
                    <input type="text" name="montapacking-search-zipcode" id="montapacking-search-zipcode"
                           title="<?php _e('Postcode', 'montapacking-checkout'); ?>"
                           placeholder="<?php _e('Postcode', 'montapacking-checkout'); ?>"
                           class="montapacking-search-zipcode-input">
                    <button type="button" class="button montapacking-search-zipcode-button"
                            id="montapacking-search-zipcode-button"><?php _e('Search', 'montapacking-checkout'); ?></button>
                </div>
            </div>
            <a class="monta-select-pickup"><?php _e('Use selection', 'montapacking-checkout'); ?></a>
            <div id="monta-stores" class="bh-sl-map-container">

                <div class="monta-locations">

                    <div class="scroller bh-sl-loc-list">
                        <ul class="list">
                            <!-- pick-up points -->
                        </ul>
                    </div>


                </div>
                <div class="monta-map">
                    <div id="bh-sl-map" class="bh-sl-map"></div>
                </div>

            </div>

        </div>

    </div>
</div>