<script>
    var site_url = '<?php echo site_url();?>';
</script>
<div class="woocommerce-shipping-fields montapacking-shipping">

    <h3><?php _e( 'Shipping method', TKEY ); ?></h3>
    <div class="monta-options">

        <div class="monta-option monta-disabled">

            <label>
                <input type="radio" name="montapacking[shipment][type]" value="delivery" disabled/>
                <span class="block">
					<strong><?php _e( 'Delivery', TKEY ); ?></strong>
					<span class="text">
						<?php _e( 'Your order will be delivered to the delivery address.
						In the next step you can choose delivery date and time', TKEY ); ?>
					</span>
				</span>
            </label>

        </div>
        <div class="monta-option monta-disabled">

            <label>
                <input type="radio" name="montapacking[shipment][type]" value="pickup" disabled/>
                <span class="block">
					<strong><?php _e( 'Pickup', TKEY ); ?></strong>
					<span class="text">
						<?php _e( 'Pick up your order when it suits you.
						In the next step you choose where.', TKEY ); ?>
					</span>
				</span>
            </label>

        </div>
    </div>

    <div class="monta-loading">
        <?php _e( 'Loading..', TKEY ); ?>
    </div>

    <div class="monta-shipment-delivery">

        <br/>

        <h3><?php _e( 'When do you want your order to be delivered?', TKEY ); ?></h3>
        <div class="monta-times">

            <a class="toggle-left"><?php _e( 'Earlier', TKEY ); ?></a>
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

        <h3><?php _e( 'Where do you want to pick up your order?', TKEY ); ?></h3>
        <div class="monta-pickup-selected"></div>

        <input type="hidden" name="montapacking[pickup][code]" class="monta-pickup-input-code">
        <input type="hidden" name="montapacking[pickup][shipper]" class="monta-pickup-input-shipper">
        <input type="hidden" name="montapacking[pickup][company]" class="monta-pickup-input-company">
        <input type="hidden" name="montapacking[pickup][address]" class="monta-pickup-input-address">
        <input type="hidden" name="montapacking[pickup][postal]" class="monta-pickup-input-postal">
        <input type="hidden" name="montapacking[pickup][city]" class="monta-pickup-input-city">
        <input type="hidden" name="montapacking[pickup][country]" class="monta-pickup-input-country">
        <input type="hidden" name="montapacking[pickup][price]" class="monta-pickup-input-price">
<!--
        <input type="hidden" name="montapacking[pickup][code]" class="monta-pickup-input-code">
        <input type="hidden" name="montapacking[pickup][company]" class="monta-pickup-input-company">
        <input type="hidden" name="montapacking[pickup][address]" class="monta-pickup-input-address">
        <input type="hidden" name="montapacking[pickup][price]" class="monta-pickup-input-price">
-->
    </div>

    <div class="monta-shipment-extras">

        <br/>

        <h3><?php _e( 'Any specific requirements for your delivery?', TKEY ); ?></h3>
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
