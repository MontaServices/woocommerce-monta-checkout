<div class="monta-cover monta-hide">
    <div class="monta-window">

        <div class="monta-pickup-loading">Laden..</div>
        <!--<div class="monta-pickup-loading">Loading..</div>-->

        <div class="monta-pickup-active monta-hide bh-sl-container">

            <a class="monta-close-pickup">x</a>
            <div class="monta-options bh-sl-form-container">

                <?php if (false) { ?>
                    <form id="bh-sl-user-location" method="post" action="#">

                        <label for="bh-sl-address"><?php _e( 'Zoek op lokale postcode', TKEY ); ?></label><br />
                        <!--<label for="bh-sl-address"><?php /*_e( 'Search nearby postal code', TKEY ); */?></label><br />-->
                        <input type="text" id="bh-sl-address" name="bh-sl-address">

                        <button id="bh-sl-submit" type="submit"><?php _e( 'Zoeken', TKEY ); ?></button>
                        <!--<button id="bh-sl-submit" type="submit"><?php /*_e( 'Search', TKEY ); */?></button>-->

                    </form>
                <?php } ?>

                <div class="bh-sl-filters-container">

                    <ul id="category-filters" class="bh-sl-filters">
                    </ul>

                </div>

            </div>

            <div id="monta-stores" class="bh-sl-map-container">

                <div class="monta-locations">

                    <div class="scroller bh-sl-loc-list">
                        <ul class="list">
                            <!-- pick-up points -->
                        </ul>
                    </div>
                    <a class="monta-select-pickup"><?php _e( 'Gebruik selectie', TKEY ); ?></a>
                    <!--<a class="monta-select-pickup"><?php /*_e( 'Use selection', TKEY ); */?></a>-->

                </div>
                <div class="monta-map">
                    <div id="bh-sl-map" class="bh-sl-map"></div>
                </div>

            </div>

        </div>

    </div>
</div>
