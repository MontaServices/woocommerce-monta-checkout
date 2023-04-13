jQuery(document).ready(function() {
    jQuery(function($) {

        try {

            const ajax_url = woocommerce_params.ajax_url;

            const monta_shipping = {
                $checkout_form: $('form.checkout'),
                $monta_cover: $('div.monta-cover'),
                $body: $(' body '),

                timeSize: 0,
                maxMove: 0,
                moveAmount: 0,

                viewAt: 0,

                frames: null,
                pickups: null,
                pickup_default: null,
                pickup_selected: null,
                first_preferred: null,

                pickupLocator: null,

                init: function () {
                    window.tooltips = {}
                    $(document).ready(function () {
                        $("#billing_postcode").trigger("change");
                    });

                    this.$checkout_form.on('click', '#ship-to-different-address input', this.updateDeliveries);
                    this.$checkout_form.on('change', '.country_select', this.updateDeliveries);

                    let elms = '';
                    elms += '#billing_address_1,';
                    elms += '#billing_house_number,';
                    elms += '#billing_street_name,';
                    elms += '#billing_postcode,';
                    elms += '#billing_city,';
                    elms += '#shipping_country,';
                    elms += '#billing_country,';
                    elms += '#shipping_address_1,';
                    elms += '#shipping_postcode,';
                    elms += '#shipping_city,';
                    elms += '#shipping_house_number,';
                    elms += '#ship-to-different-address-checkbox';

                    this.$body.on('change', elms, this.checkAddress);

                    this.$checkout_form.on('click', '.toggle-left', this.movePrevious);
                    this.$checkout_form.on('click', '.toggle-right', this.moveNext);
                    this.$checkout_form.on('click', '.monta-options input[type=radio]', this.setMethod);
                    this.$checkout_form.on('click', '.monta-options input[type=radio]', this.disableRadio);
                    this.$checkout_form.on('click', '.monta-more-pickup-points', this.showPickupMap);

                    this.$monta_cover.on('click', '.bh-sl-filters input[type=checkbox]', this.sortInitialPickups);

                    this.$checkout_form.on('click', '.monta-times input[type=radio]', this.setTimeframe);
                    this.$checkout_form.on('click', '.monta-shipment-shipper input[type=radio]', this.setShipper);
                    this.$checkout_form.on('click', '.monta-shipment-extras input[type=checkbox]', this.updateWooCheckout);

                    this.updateSlider();

                },

                checkAddress: function () {

                    if ($('#billing_street_name:visible').length) {
                        var address = $('#billing_street_name').val();
                    } else {
                        var address = $('#billing_address_1').val();
                    }

                    if ($('#billing_street_name:visible').length) {
                        var housenumber = $('#billing_house_number').val();
                    } else {
                        var housenumber = $('#billing_address_1').val();
                    }


                    const zipcode = $('#billing_postcode').val();
                    const place = $('#billing_city').val();
                    const country = $('#billing_country').val();
                    const other = $('#ship-to-different-address-checkbox').prop('checked');

                    const ship_address = $('#shipping_address_1').val();

                    if ($('#shipping_house_number:visible').length) {
                        var ship_housenumber = $('#shipping_house_number').val();
                    } else {
                        var ship_housenumber = $('#shipping_address_1').val();
                    }

                    const ship_zipcode = $('#shipping_postcode').val();
                    const ship_place = $('#shipping_city').val();
                    const ship_country = $('#shipping_country').val();

                    if (other) {
                        if (ship_zipcode !== '' && ship_housenumber !== '') {
                            monta_shipping.enableRadio();
                            monta_shipping.hideAddressMsg();

                            //Select first shipping option
                            monta_shipping.selectDeliveryOption();
                        } else {
                            monta_shipping.disableRadio();
                            monta_shipping.showAddressMsg();

                            monta_shipping.deSelectDeliveryOption();
                        }
                    } else {
                        if (zipcode !== '' && housenumber !== '') {
                            monta_shipping.enableRadio();
                            monta_shipping.hideAddressMsg();

                            //Select first shipping option
                            monta_shipping.selectDeliveryOption();
                        } else {
                            monta_shipping.disableRadio();
                            monta_shipping.showAddressMsg();

                            monta_shipping.deSelectDeliveryOption();
                        }
                    }
                    monta_shipping.storeLocatorDestroy();
                },

                updateWooCheckout: function () {
                    $(document.body).trigger('update_checkout');
                },

                updateSlider: function () {

                    const times = $('.monta-times');
                    const viewing = times.find('.mover').width();

                    monta_shipping.timeSize = times.find('li').outerWidth(true);
                    monta_shipping.maxMove = times.find('li').length;

                    monta_shipping.moveAmount = Math.floor((viewing / monta_shipping.timeSize));

                },

                movePrevious: function () {

                    const moving = $('.monta-times ul');
                    const times = $('.monta-times');
                    const viewing = times.find('.mover').width();

                    monta_shipping.moveAmount = Math.floor((viewing / monta_shipping.timeSize));
                    monta_shipping.viewAt -= monta_shipping.moveAmount;
                    if (monta_shipping.viewAt < 0) {
                        monta_shipping.viewAt = 0;
                    }

                    moving.stop().animate({
                        left: (monta_shipping.viewAt * monta_shipping.timeSize) * -1
                    }, 500);

                },

                moveNext: function () {

                    const moving = $('.monta-times ul');
                    const times = $('.monta-times');
                    const viewing = times.find('.mover').width();

                    monta_shipping.moveAmount = Math.floor((viewing / monta_shipping.timeSize));
                    monta_shipping.viewAt += monta_shipping.moveAmount;

                    if (monta_shipping.viewAt > monta_shipping.maxMove - monta_shipping.moveAmount) {
                        monta_shipping.viewAt = monta_shipping.maxMove - monta_shipping.moveAmount;
                    }

                    moving.animate({
                        left: (monta_shipping.viewAt * monta_shipping.timeSize) * -1
                    }, 500);

                },
                updateDeliveries: function (callback) {
                    let data = $('form.checkout').serialize();
                    data += '&action=monta_shipping_options';

                    $.post(ajax_url, data).done(function (result) {
                        if (result.success) {
                            // Frames onthouden
                            if (result.frames !== undefined) {
                                monta_shipping.frames = result.frames;
                            } else if (result.pickups !== undefined) {
                                monta_shipping.pickups = result.pickups;
                                monta_shipping.pickup_default = result.default;
                            }
                        } else {
                            // Show error message
                            $('.monta-times .mover').html('<div class="error">' + result.message + '</div>');
                        }
                        $('.monta-loading').removeClass('active');

                        monta_shipping.enableRadio();

                        if ($.isFunction(callback)) {
                            callback(result.success, result);
                        }
                    });
                }, setMethod: function () {
                    $('.monta-shipment-delivery').removeClass('active');
                    $('.monta-shipment-shipper').html('');

                    const checked = $('.monta-options input[type=radio]:checked').val();

                    if (checked === "pickup") {
                        $('.monta-loading').addClass('active');

                        $('.monta-shipment-extras').removeClass('active');
                    } else {
                        $('.monta-loading').addClass('active');
                        $(".monta-shipment-pickup").removeClass("active");
                    }

                    monta_shipping.updateDeliveries(function (success, result) {

                        $(".monta-times-cropped-error").css("display", "none");

                        if (success) {

                            if (checked === 'delivery') {
                                // empty fields when pickup was already choosen
                                $(".monta-pickup-fields").val("");
                                //$("#initialPickupRadioDummy").removeAttribute("checked");

                                $('.monta-shipment-delivery').addClass('active');

                                const mover = $('.monta-times .mover');
                                mover.html('<ul></ul>');

                                const times = mover.find('ul');

                                // get the date of today , this is for validation if the checkbox needed to be checked (sameday or not sameday)
                                const d = new Date();
                                const date_today = d.toLocaleDateString('nl-NL', {
                                    month: '2-digit',
                                    day: '2-digit',
                                    year: 'numeric'
                                });

                                // Frames tonen in lijst

                                let hidedatebar = true;

                                let firstdaycounter = 0;

                                let daysavailablecounter = 0;

                                monta_shipping.first_preferred = null;
                                $.each(monta_shipping.frames, function (key, item) {
                                    daysavailablecounter++;
                                    if(monta_shipping.first_preferred == null && item.options[0].is_preferred){
                                        monta_shipping.first_preferred = item;
                                    }
                                });

                                $.each(monta_shipping.frames, function (key, item) {
                                    if (item.date !== "") {
                                        hidedatebar = false;
                                    }

                                    if (date_today !== item.date) {
                                        firstdaycounter++;
                                    }

                                    let showhtml = true;
                                    if (firstdaycounter === 1 && daysavailablecounter > 1) {
                                        showhtml = false;
                                    }

                                    const option = item.options[0];

                                    if (option !== null && option !== undefined) {

                                        let html = $('.monta-choice-template').html();
                                        html = html.replace(/{.id}/g, key);
                                        html = html.replace(/{.code}/g, (item.code !== null) ? item.code : '');
                                        html = html.replace(/{.day}/g, item.date);
                                        html = html.replace(/{.dayname}/g, item.datename);
                                        html = html.replace(/{.time}/g, item.time);
                                        html = html.replace(/{.description}/g, (item.displayname !== null) ? item.displayname : (item.description !== null) ? item.description : '');
                                        html = html.replace(/{.price}/g, item.price);

                                        if(monta_shipping.first_preferred != null && monta_shipping.first_preferred.date === item.date){
                                            html = html.replace(/{.preferred}/g, true);
                                        }

                                        // exclude the checking of today delivery, this is not the most wanted option for clients
                                        if (date_today === item.date) {
                                            html = html.replace(/{.sameday}/g, 'sameday');
                                        } else {
                                            html = html.replace(/{.sameday}/g, 'otherday');
                                        }

                                        // to disable client fix enable line below here
                                        showhtml = !(key === 'NOTIMES' && daysavailablecounter > 1);

                                        if (showhtml === true) {
                                            times.append(html);
                                        }

                                    }

                                });

                                if (hidedatebar === true && monta_shipping.first_preferred === null)
                                    $('.monta-times').addClass('monta-hide');

                                // Select first option
                                if(monta_shipping.first_preferred != null) {
                                    mover.find('input[type=radio][data-preferred=\'true\']:not(.sameday):first').prop('checked', true).click();
                                } else {
                                    mover.find('input[type=radio]:not(.sameday):first').prop('checked', true).click();
                                }

                                // Update slider for scrolling
                                monta_shipping.updateSlider();

                                monta_shipping.storeLocatorDestroy();

                            } else {

                                $('.monta-shipment-extras').removeClass('active');

                            }

                            updateDeliveryTextBlock();

                            // Process pickups
                            if (checked === 'pickup') {
                                monta_shipping.pickup_selected = null;
                                let markers = formatMarkers(monta_shipping.pickups);
                                InitializeStoreLocator(markers, monta_shipping.pickup_default.lat, monta_shipping.pickup_default.lng, monta_shipping);
                            }

                            if (checked === 'collect') {
                                monta_shipping.pickup_selected = null;
                                let markers = formatMarkers(monta_shipping.pickups);
                                InitializeStoreLocator(markers, monta_shipping.pickup_default.lat, monta_shipping.pickup_default.lng, monta_shipping);
                            }
                        } else {
                            if (checked === 'pickup') {
                                $(".monta-times-croppped-error-pickup").css("display", "block")
                            } else {
                                $('#tabselector input[type=radio]:not(:checked)').click();
                                $('#tabselector').addClass('monta-hide');
                            }
                            monta_shipping.storeLocatorDestroy();
                        }
                    })
                    monta_shipping.updateWooCheckout();
                },
                showPickupMap: function () {
                    const checked = $('.monta-options input[type=radio]:checked').val();

                    if (checked === "pickup" || checked === 'collect') {
                        $('body').addClass('monta-cover-open');

                        $('.monta-cover').removeClass('monta-hide');
                        $('.monta-cover .monta-pickup-loading').addClass('monta-hide');
                        $('.monta-cover .monta-pickup-active').removeClass('monta-hide');
                    }

                    $('.monta-close-pickup').on('click', function () {
                        $('body').removeClass('monta-cover-open');

                        $('.monta-cover').addClass('monta-hide');
                        $('.monta-cover .monta-pickup-loading').addClass('monta-hide');
                        $('.monta-cover .monta-pickup-active').removeClass('monta-hide');

                        // Remove list-focus class from the initial pickup locations
                        $("#initialPickupsList li.list-focus").removeClass("list-focus");
                        $("#initialPickupRadioDummy").prop("checked", true);
                    });

                    document.getElementById('montapacking-search-zipcode-button').addEventListener('click', (e) => {
                        let zipcode = document.getElementById('montapacking-search-zipcode').value;
                        if (zipcode.length > 0) {
                            let data = $('form.checkout').serializeArray();
                            data.push({name: "ship_to_different_address", value: 1});
                            data.push({name: "action", value: "monta_shipping_options"});

                            data.find(input => input.name === 'shipping_address_1').value = "";
                            data.find(input => input.name === 'shipping_address_2').value = "";
                            data.find(input => input.name === 'shipping_city').value = "";
                            data.find(input => input.name === 'shipping_postcode').value = zipcode;

                            $.post(ajax_url, $.param(data)).done(function (result) {
                                if (result.success) {
                                    if (result.pickups !== undefined) {
                                        monta_shipping.pickups = result.pickups;
                                        monta_shipping.pickup_default = result.default;

                                        document.getElementById('category-filters').style.visibility = 'hidden';
                                        let markers = formatMarkers(result.pickups);
                                        InitializeStoreLocator(markers, result.default.lat, result.default.lng, monta_shipping);
                                    }
                                }
                            });
                        }
                    })
                },
                selectPickup: function (location, markerId) {
                    monta_shipping.pickup_selected = location;

                    $('.monta-select-pickup').addClass('active');

                    if (monta_shipping.pickup_selected !== null) {
                        //initialPickupPointRadio
                        const loc = monta_shipping.pickup_selected;

                        const n = loc.raw.shipperOptionsWithValue.includes("_packStation");
                        const m = loc.raw.shipperOptionsWithValue.includes("DHLPCPostNummer_");

                        if (n && !m) {
                            $("#PCPostNummer").css("display", "block");
                            $("#PCPostNummer input").removeAttr("disabled");

                        } else {
                            $("#PCPostNummer").css("display", "none");
                            $("#PCPostNummer input").attr("disabled", "disabled");
                        }

                        const imageUrl = site_url + '/wp-content/plugins/montapacking-checkout-woocommerce-extension/assets/img/' + loc.raw.code + ".png";

                        let html = '';
                        html += '<div class="loadedimage"><img src="' + imageUrl + '" style="width:30px;"></div>';
                        html += '<div class="loadedinformation">';
                        html += '<strong>' + loc.name + '</strong><br />';
                        html += loc.displayname + '<br />';
                        html += loc.street + ' ' + loc.houseNumber + '<br />';
                        html += loc.postal + ' ' + loc.city;
                        html += '</div>';
                        html += '<div class="loadedprice">&euro; ' + loc.price + '</div>';
                        html += '<div class="clear"></div>';

                        $('.monta-pickup-selected').html(html);
                        if (markerId > ($("#maxpickuppoints").val() - 1)) {
                            $('.monta-pickup-selected').show();
                            $('.monta-pickup-selected-title').show();
                            $('.monta-selected-pickup').show();

                            $(".bh-sl-loc-list").hide();
                            $("#monta-stores .bh-sl-loc-list").show();

                        } else {
                            $('.monta-pickup-selected').hide();
                            $('.monta-pickup-selected-title').hide();
                            $('.monta-selected-pickup').hide();

                            $(".bh-sl-loc-list").show();
                            $("#monta-stores .bh-sl-loc-list").show();
                        }

                        $('.monta-shipment-pickup').addClass('active');

                        $('.monta-pickup-input-code').val(loc.code);
                        $('.monta-pickup-input-shipper').val(loc.category);
                        $('.monta-pickup-input-shippingOptions').val(loc.shippingOptions);
                        $('.monta-pickup-input-company').val(loc.name);
                        $('.monta-pickup-input-street').val(loc.street);
                        $('.monta-pickup-input-houseNumber').val(loc.houseNumber);
                        $('.monta-pickup-input-description').val(loc.description);
                        $('.monta-pickup-input-displayname').val(loc.displayname);
                        $('.monta-pickup-input-postal').val(loc.postal);
                        $('.monta-pickup-input-city').val(loc.city);
                        $('.monta-pickup-input-country').val(loc.country);
                        $('.monta-pickup-input-price').val(loc.price_raw);

                        $('.monta-select-pickup').on('click', function () {
                            $('body').removeClass('monta-cover-open');

                            $('.monta-cover').addClass('monta-hide');
                            $('.monta-cover .monta-pickup-loading').addClass('monta-hide');
                            $('.monta-cover .monta-pickup-active').removeClass('monta-hide');

                            // Remove list-focus class from the initial pickup locations
                            //$("#initialPickupsList li.list-focus").removeClass("list-focus");
                            $("#initialPickupRadioDummy").prop("checked", true);
                        });
                        monta_shipping.updateWooCheckout();
                    }
                },
                storeLocatorDestroy: function () {
                    $('#monta-stores .bh-sl-loc-list .list').html('');
                    $('#monta-stores .bh-sl-map').removeClass('bh-sl-map-open').html('');
                    $('#monta-stores .monta-select-pickup').removeClass('active');

                    $('#monta-stores').storeLocator('destroy');
                },
                setTimeframe: function () {
                    const value = $(this).val();
                    const shippers = $('.monta-shipment-shipper');
                    const options = monta_shipping.frames[value].options;

                    // Empty shippers
                    shippers.html('');

                    if (options !== undefined && options !== null) {
                        $.each(options, function (key, item) {
                            const realCode = item.code;

                            let code = '';
                            $.each(item.codes, function (key, item) {
                                code += ((code !== '') ? ',' : '') + item;
                            });

                            // The API returns format like (00:00 to 00:00 for DPD, exclude them here)
                            let time = '<strong>' + item.from + ' - ' + item.to + '</strong>';
                            if (item.from === item.to) {
                                time = '';
                            }

                            let html = '<label>\n\n' +
                                '        <span style="display:none" class="cropped_name">{.name}</span>\n' +
                                '        <span style="display:none" class="cropped_time">{.time}</span>\n' +
                                '        <span style="display:none" class="cropped_image">{.img}</span>\n' +
                                '        <span style="display:none" class="cropped_type_text">{.type_text}</span>\n' +
                                '        <span style="display:none" class="cropped_type">{.type}</span>\n\n' +
                                '        <span class="radiobutton">\n' +
                                '            <input type="radio" name="montapacking[shipment][shipper]" value="{.code}" class="montapackingshipmentshipper">\n' +
                                '            <input type="hidden" name="montapacking[shipment][{.code}][name]" value="{.name}" data-preferred="{.preferred}">\n' +
                                '        </span>\n\n' +
                                '        <div class="image">\n' +
                                '            {.img}\n' +
                                '        </div>\n\n' +
                                '        <div class="information">\n' +
                                '            {.name} {.time} <span>{.ships_on}</span> {.is_sustainable}\n' +
                                '        </div>\n' +
                                '        <div class="pricemonta">\n' +
                                '            {.price}\n' +
                                '        </div>\n' +
                                '        <div class="clearboth"></div>\n\n' +
                                '    </label>';
                            html = html.replace(/{.code}/g, realCode);
                            html = html.replace(/{.img}/g, '<img class="loadedLogo" src="' + site_url + '/wp-content/plugins/montapacking-checkout-woocommerce-extension/assets/img/' + code + '.png">');
                            html = html.replace(/{.name}/g, item.displayname);
                            html = html.replace(/{.preferred}/g, item.is_preferred);
                            html = html.replace(/{.time}/g, time);
                            html = html.replace(/{.price}/g, item.price);
                            html = html.replace(/{.type}/g, item.type);
                            html = html.replace(/{.type_text}/g, item.type_text);
                            html = html.replace(/{.ships_on}/g, item.ships_on);

                            if(item.is_sustainable) {
                                html = html.replace(/{.is_sustainable}/g, ' <img aria-describedby="sustainabletooltip-' + realCode + '" id="sustainable-' + realCode + '" style="z-index:100; width: 20px; height: 20px; margin-left: 5px;" src="' + site_url + '/wp-content/plugins/montapacking-checkout-woocommerce-extension/assets/img/sustainable.png"/><div class="tooltip" id="sustainabletooltip-' + realCode + '" role="tooltip">' + sustainableDeliveryText + '<div class="arrow" id="arrow-' + realCode + '" data-popper-arrow></div></div>');
                            } else {
                                html = html.replace(/{.is_sustainable}/g, '');
                            }

                            if (code !== 'TBQ') {
                                shippers.append(html);
                            }

                            if(item.is_sustainable) {
                                addTooltip('sustainable-' + realCode, 'sustainabletooltip-' + realCode);
                            }

                            $(".loadedLogo").on("error", function () {
                                $(this).attr("src", site_url + "/wp-content/plugins/montapacking-checkout-woocommerce-extension/assets/img/none.png");
                            });

                        });
                        shippers.find('input[type=radio]:first').prop('checked', true);

                        monta_shipping.setShipper();
                    } else {
                        shippers.html('');
                    }
                    monta_shipping.updateWooCheckout();
                },
                setShipper: function () {
                    const shipper = $('.monta-shipment-shipper input[type=radio]:checked').val();
                    const frame = $('.monta-times input[type=radio]:checked').val();
                    const extras = $('.monta-shipment-extra-options');

                    // Specify selected option
                    let options = null;

                    $.each(monta_shipping.frames[frame].options, function (key, item) {
                        const realCode = item.code;

                        if (realCode === shipper) {
                            options = item.extras;
                            return false;
                        }
                    });

                    if (options !== undefined && options !== null) {
                        $('.monta-shipment-extras').addClass('active');

                        extras.html('');
                        $.each(options, function (key, item) {

                            let code = '';
                            $.each(item.codes, function (key, item) {
                                code += ((code !== '') ? ',' : '') + item;
                            });

                            let html = $('.monta-extra-template').html();
                            html = html.replace(/{.code}/g, item.code);
                            html = html.replace(/{.name}/g, item.name);
                            html = html.replace(/{.price}/g, item.price);

                            extras.append(html);
                        });
                    } else {
                        $('.monta-shipment-extras').removeClass('active');
                        extras.html('');
                    }

                    monta_shipping.updateWooCheckout();
                },

                disableRadio: function () {
                    $('.monta-options .monta-option').addClass('monta-disabled');
                    $('.monta-options input[type="radio"]').prop('disabled', true);

                    $(".monta-shipment-delivery").removeClass("active");
                    $(".monta-shipment-extras").removeClass("active");
                    $(".monta-shipment-pickup").removeClass("active");
                },

                enableRadio: function () {
                    $('.monta-options .monta-option').removeClass('monta-disabled');
                    $('.monta-options input[type="radio"]').prop('disabled', false);
                },

                hideAddressMsg: function () {
                    $("#monta-address-required").hide();
                },

                showAddressMsg: function () {
                    $("#monta-address-required").show();
                },

                sortInitialPickups: function () {
                    const liExists = setInterval(function () {
                        if ($("#initialPickupsList").length) {
                            $('#initialPickupsList li:gt(' + ($("#maxpickuppoints").val() - 1) + ')').remove();

                            //Add radio buttons for initial pickups
                            $("#initialPickupsList  > li").each(function () {
                                $(this).prepend("<input name='initialPickupPointRadio' class='initialPickupRadio' type='radio'>");
                            });

                            if ($("#initialPickupsList").children().length > 0) {
                                $("#monta-pickups").show(100);
                                $('.monta-loading').removeClass('active');
                                $('.monta-shipment-pickup').addClass('active');
                                $(".monta-pickup-initial-points").removeClass("monta-hide");
                                monta_shipping.enableRadio();

                                clearInterval(liExists);
                            }
                        }
                    }, 100);
                },
                selectDeliveryOption: function () {
                    //Check and click delivery button
                    $('.monta-options input[value=delivery]').prop('checked', true).click();
                },
                deSelectDeliveryOption: function () {
                    //Un-check and click delivery button
                    $('.monta-options input[value=delivery]').prop('checked', false);
                },
            };
            monta_shipping.init();
        } catch (e) {}
    });

    function updateDeliveryTextBlock() {
        const day = jQuery("input.montapackingshipmenttime:checked").parent("label").find(".day").text();
        const dayname = jQuery("input.montapackingshipmenttime:checked").parent("label").find(".dayname").text();
        jQuery("strong.date").text(dayname + " " + day);

        const shippingtype = jQuery("input.montapackingshipmentshipper:checked").parents("label").find(".cropped_type").text();

        if (shippingtype === 'shippingdate') {
            jQuery(".send-time").css("display", "block");
            jQuery(".delivery-time").css("display", "none");
        } else {
            jQuery(".delivery-time").css("display", "block");
            jQuery(".send-time").css("display", "none");
        }


        const shippingtype_text = jQuery("input.montapackingshipmentshipper:checked").parents("label").find(".cropped_type_text").text();
        jQuery("strong.shippingtype").text(shippingtype_text);

        const shipper = jQuery("input.montapackingshipmentshipper:checked").parents("label").find(".cropped_name").text();
        jQuery("strong.shipper").text(shipper);

        const datetime = jQuery("input.montapackingshipmentshipper:checked").parents("label").find(".cropped_time").text();
        jQuery("strong.datetime").text(datetime);

        const image = jQuery("input.montapackingshipmentshipper:checked").parents("label").find(".cropped_image").html();
        jQuery("div.imglogo").html(image);


        jQuery(".dateinformation").css("display", "none");

        if (day !== 'null' && day !== '') {
            jQuery(".dateinformation").css("display", "block");
        }

        jQuery(".timeinformation").css("display", "none");
        if (datetime.trim()) {
            jQuery(".timeinformation").css("display", "block");
        }

        jQuery(".monta-times-croppped").find(".loadedLogo").on("error", function() {
            jQuery(this).attr("src", site_url + "/wp-content/plugins/montapacking-checkout-woocommerce-extension/assets/img/none.png");
        });
    }

    function InitializeStoreLocator(markers, defaultLat, defaultLng, monta_shipping) {
        $ = jQuery;
        monta_shipping.storeLocatorDestroy();

        const config = {
            'debug': false,
            'pagination': false,
            'infowindowTemplatePath': site_url + '/wp-content/plugins/montapacking-checkout-woocommerce-extension/assets/js/templates/infowindow-description.html',
            'listTemplatePath': site_url + '/wp-content/plugins/montapacking-checkout-woocommerce-extension/assets/js/templates/list-location.html',
            'distanceAlert': -1,
            'dataType': "json",
            'dataRaw': JSON.stringify(markers, null, 2),
            'slideMap': false,
            'inlineDirections': true,
            'originMarker': true,
            'dragSearch': false,
            'defaultLoc': true,
            'defaultLat': defaultLat,
            'defaultLng': defaultLng,
            'lengthUnit': 'km',
            'exclusiveFiltering': true,
            'taxonomyFilters': {
                'category': 'category-filters',
            },
            catMarkers: {
                'PAK': [site_url + '/wp-content/plugins/montapacking-checkout-woocommerce-extension/assets/img/PostNL.png', 32, 32],
                'AFH': [site_url + '/wp-content/plugins/montapacking-checkout-woocommerce-extension/assets/img/AFH.png', 32, 32],
                'DHLservicepunt': [site_url + '/wp-content/plugins/montapacking-checkout-woocommerce-extension/assets/img/DHLservicepunt.png', 32, 32],
                'DHLFYPickupPoint': [site_url + '/wp-content/plugins/montapacking-checkout-woocommerce-extension/assets/img/DHLFYPickupPoint.png', 32, 32],
                'DPDparcelstore': [site_url + '/wp-content/plugins/montapacking-checkout-woocommerce-extension/assets/img/DPD.png', 32, 32]
            },
            callbackMarkerClick: function (marker, markerId, $selectedLocation, location) {
                monta_shipping.selectPickup(location, markerId);
            },
            callbackListClick: function (markerId, selectedMarker, location) {
                monta_shipping.selectPickup(location, markerId);
            },
            callbackSorting: () => {
                runListTrim(monta_shipping);
            },
            callbackNotify: function (error) {
                $('#monta-stores').storeLocator('mapping',{ lat: defaultLat, lng: defaultLng });
                document.getElementsByClassName('monta-more-pickup-points')[0].style.display = 'none';
            }
        };
        monta_shipping.pickupLocator = $('#monta-stores').storeLocator(config);
        runListTrim(monta_shipping);
    }

    function runListTrim(monta_shipping){
        const liExists = setInterval(function () {
            if ($("#initialPickupsList").length) {

                $('#initialPickupsList li:gt(' + ($("#maxpickuppoints").val() - 1) + ')').remove();

                //Add radio buttons for initial pickups
                $("#initialPickupsList  > li").each(function () {
                    const element = $("input");
                    const radioButtons = $(this).find(element);

                    if (radioButtons.length < 1) {
                        $(this).prepend("<input name='initialPickupPointRadio' class='initialPickupRadio' type='radio'>");
                    }
                });

                //if ($("#initialPickupsList").children().length === 3) {
                if ($("#initialPickupsList").children().length > 0) {
                    $("#monta-pickups").show(100);
                    $('.monta-loading').removeClass('active');
                    $('.monta-shipment-pickup').addClass('active');
                    $(".monta-pickup-initial-points").removeClass("monta-hide");
                    monta_shipping.enableRadio();

                    clearInterval(liExists);
                }
            }
        }, 100);
    }

    function formatMarkers(pickups) {
        let markers = [];
        //document.getElementById('category-filters').innerHTML = "";
        jQuery.each(pickups, function (key, item) {
            const openingtimes = JSON.parse(item.details.openingtimes);
            const allopeningtimes = [];
            for (let k in openingtimes) {
                const times = openingtimes[k];
                const timeline = times.from + " - " + times.to + " uur";

                allopeningtimes.push(timeline);
            }

            allopeningtimes.join(" / ");

            markers.push({
                'id': '1',
                'code': item.code + '_' + item.details.code,
                'shippingOptions': item.shipperOptionsWithValue,
                'category': item.code,
                'name': item.details.name,
                'lat': item.details.lat,
                'lng': item.details.lng,
                'street': item.details.street,
                'houseNumber': item.details.houseNumber,
                'city': item.details.place,
                'postal': item.details.zipcode,
                'country': item.details.country,
                'description': item.description,
                'displayname': item.displayname,
                'distance': item.details.distance / 1000,
                'price': item.price,
                'openingtimes': allopeningtimes,
                'image': site_url + '/wp-content/plugins/montapacking-checkout-woocommerce-extension/assets/img/' + item.code + ".png",
                'price_raw': item.price_raw,
                'raw': item
            });

            if (jQuery('.cat-' + item.code + '').length === 0) {
                jQuery('#category-filters').append('<li class="cat-' + item.code + '"><label><input class="monta-shipper-filter" type="checkbox" checked="checked" name="category" value="' + item.code + '"> ' + item.displayname + '</label></li>');
            }
        });
        return markers;
    }

    jQuery("input.montapackingshipmentshipper, input.montapackingshipmenttime ").on("change", function() {
        updateDeliveryTextBlock();
    });

    jQuery('body').on('click', 'input.montapackingshipmentshipper', function() {
        jQuery('.montapackingshipmentshipper').parents('label').removeClass("checked");
        jQuery(this).parents('label').addClass("checked");
    });

    jQuery('body').on('click', '#othersendmethod', function() {
        if (jQuery(".monta-times-croppped").css('display') === 'block') {
            jQuery(".monta-times-croppped").css('display', "none");
            jQuery(".monta-times-extended").css('display', "block");
        } else {
            jQuery(".monta-times-croppped").css('display', "block");
            jQuery(".monta-times-extended").css('display', "none");
        }
    });
});

function addTooltip(rootElementId, tooltipId){
    const rootElement = document.querySelector('#' + rootElementId);
    const tooltip = document.querySelector('#' + tooltipId);

    if(window.tooltips[tooltipId] !== null || window.tooltips[tooltipId] !== undefined){
        delete window.tooltips[tooltipId];
    }
    window.tooltips[tooltipId] = window.Popper.createPopper(rootElement, tooltip, {
        placement: 'right',
        modifiers: [
        {
            name: 'offset',
            options: {
            offset: [0, 8],
            },
        },
        ],
    });

    const showEvents = ['mouseenter', 'focus'];
    const hideEvents = ['mouseleave', 'blur'];
    
    showEvents.forEach((event) => {
        rootElement.addEventListener(event, showTooltip);
    });
    
    hideEvents.forEach((event) => {
        rootElement.addEventListener(event, hideTooltip);
    });
}

function showTooltip() {
    let tooltip = document.getElementById(jQuery(this).attr('aria-describedby'));
    tooltip.setAttribute('data-show', '');
    window.tooltips[jQuery(this).attr('aria-describedby')].update();
}

function hideTooltip() {
    let tooltip = document.getElementById(jQuery(this).attr('aria-describedby'));
    tooltip.removeAttribute('data-show');
}