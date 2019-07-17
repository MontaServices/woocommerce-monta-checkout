<script>
    var site_url = '<?php echo site_url();?>';
</script>
<div class="woocommerce-shipping-fields montapacking-shipping">

    <!--<h3><?php /*_e( 'Shipping method', TKEY ); */?></h3>-->
    <h3><?php _e( 'Verzend methode', TKEY ); ?></h3>
    <div class="monta-options">

        <div class="monta-option monta-disabled">

            <label>
                <input type="radio" name="montapacking[shipment][type]" value="delivery" disabled/>
                <span class="block">
					<strong><?php _e( 'Bezorging', TKEY ); ?></strong>
					<!--<strong style="display: none;"><?php /*_e( 'Delivery', TKEY ); */?></strong>-->
					<!--<span class="text">
						<?php /*_e( 'Your order will be delivered to the delivery address.
						In the next step you can choose delivery date and time', TKEY ); */?>
					</span>-->
				</span>
            </label>

        </div>
        <div class="monta-option monta-disabled">

            <label>
                <input type="radio" name="montapacking[shipment][type]" value="pickup" disabled/>
                <span class="block">
					<strong><?php _e( 'Ophalen', TKEY ); ?></strong>
					<!--<strong style="display: none;"><?php /*_e( 'Pickup', TKEY ); */?></strong>-->
					<!--<span class="text">
						<?php /*_e( 'Pick up your order when it suits you.
						In the next step you choose where.', TKEY ); */?>
					</span>-->
				</span>
            </label>

        </div>
    </div>

    <div class="monta-loading">
        <?php _e( 'Laden..', TKEY ); ?>
        <?php /*_e( 'Loading..', TKEY ); */?>
    </div>

    <div class="monta-shipment-delivery">

        <br/>

        <h3><?php _e( 'Wanneer wilt u de verzending ontvangen?', TKEY ); ?></h3>
        <!--<h3><?php /*_e( 'When do you want your order to be delivered?', TKEY ); */?></h3>-->
        <div class="monta-times">

            <a class="toggle-left"><?php _e( 'Eerder', TKEY ); ?></a>
            <!--<a class="toggle-left"><?php /*_e( 'Earlier', TKEY ); */?></a>-->
            <div class="scroller">

                <div class="mover">

                    <ul></ul>

                </div>

            </div>
            <a class="toggle-right"><?php _e( 'Later', TKEY ); ?></a>

        </div>

        <div class="monta-shipment-shipper"></div>

    </div>

    <div class="monta-shipment-pickup">

        <br/>

        <!-- First 3 pickup point options -->
        <div class="monta-pickup-initial-points">
            <div id="monta-pickups" class="bh-sl-map-container">

                <div class="monta-locations" style="width: 100% !important;">

                    <a class="monta-more-pickup-points"><?php _e( 'Bekijk meer opties', TKEY ); ?></a>

                    <div class="scroller bh-sl-loc-list">
                        <ul id="initialPickupsList" class="list">
                            <!-- pick-up points -->
                        </ul>
                    </div>
                    <a class="monta-select-pickup" style="position: relative !important;"><?php _e( 'Gebruik selectie', TKEY ); ?></a>
                    <!--<a class="monta-select-pickup"><?php /*_e( 'Use selection', TKEY ); */?></a>-->

                </div>

            </div>
        </div>



        <h3><?php _e( 'Geselecteerde ophaalpunt:', TKEY ); ?></h3>
        <!--<h3><?php /*_e( 'Where do you want to pick up your order?', TKEY ); */?></h3>-->
        <div style="display: none;" class="monta-pickup-selected"></div>

        <input type="hidden" name="montapacking[pickup][code]" class="monta-pickup-input-code">
        <input type="hidden" name="montapacking[pickup][shipper]" class="monta-pickup-input-shipper">
        <input type="hidden" name="montapacking[pickup][shippingOptions]" class="monta-pickup-input-shippingOptions">
        <input type="hidden" name="montapacking[pickup][company]" class="monta-pickup-input-company">
        <input type="hidden" name="montapacking[pickup][street]" class="monta-pickup-input-street">
        <input type="hidden" name="montapacking[pickup][houseNumber]" class="monta-pickup-input-houseNumber">
        <input type="hidden" name="montapacking[pickup][postal]" class="monta-pickup-input-postal">
        <input type="hidden" name="montapacking[pickup][city]" class="monta-pickup-input-city">
        <input type="hidden" name="montapacking[pickup][country]" class="monta-pickup-input-country">
        <input type="hidden" name="montapacking[pickup][price]" class="monta-pickup-input-price">

    </div>

    <div class="monta-shipment-extras">

        <br/>

        <h3><?php _e( 'Extra opties:', TKEY ); ?></h3>
        <!--<h3><?php /*_e( 'Any specific requirements for your delivery?', TKEY ); */?></h3>-->
        <div class="monta-shipment-extra-options"></div>

    </div>

</div>

<ul class="monta-choice-template">
    <li>

        <label>
            <input type="radio" name="montapacking[shipment][time]" value="{.id}">
            <span>
				<span class="day">{.day}</span>
				<span class="description">{.description}</span>
				<span class="price">{.price}</span>
			</span>
        </label>

    </li>
</ul>

<div class="monta-shipper-template">
    <label>
        <input type="radio" name="montapacking[shipment][shipper]" value="{.code}">
        <span>
			{.img}
			<div class="name">{.name} {.time}</div>
			<span class="price">{.price}</span>
		</span>
    </label>
</div>

<div class="monta-extra-template">
    <label>
        <input type="checkbox" name="montapacking[shipment][extras][]" value="{.code}">
        <span>
			{.name}
			<span class="price">{.price}</span>
		</span>
    </label>
</div>
