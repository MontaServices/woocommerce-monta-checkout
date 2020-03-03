jQuery( function( $ ) {

    try {

        var ajax_url = woocommerce_params.ajax_url;

        var monta_shipping = {
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

            pickupLocator: null,

            init: function () {

                $(document).ready(function() {
                    $("#billing_postcode").trigger("change");
                });

                // Hide option on start
                $("#ship-to-different-address").hide();

                this.$checkout_form.on('click', '#ship-to-different-address input', this.updateDeliveries);
                this.$checkout_form.on('change', '.country_select', this.updateDeliveries);

                var elms = '';
                elms += '#billing_address_1,';
                elms += '#billing_postcode,';
                elms += '#billing_city,';
                elms += '#shipping_country,';
                elms += '#billing_country,';
                elms += '#shipping_address_1,';
                elms += '#shipping_postcode,';
                elms += '#shipping_city,';
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

                var address = $('#billing_address_1').val();
                var zipcode = $('#billing_postcode').val();
                var place = $('#billing_city').val();
                var country = $('#billing_country').val();
                var other = $('#ship-to-different-address-checkbox').prop('checked');

                var ship_address = $('#shipping_address_1').val();
                var ship_zipcode = $('#shipping_postcode').val();
                var ship_place = $('#shipping_city').val();
                var ship_country = $('#shipping_country').val();

                if (other) {

                    if (ship_zipcode !== '') {

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

                    if (zipcode !== '') {

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

                $('#monta-stores').storeLocator('destroy');

            },

            updateWooCheckout: function () {

                $(document.body).trigger('update_checkout');

            },

            updateSlider: function () {

                var times = $('.monta-times');
                var viewing = times.find('.mover').width();

                monta_shipping.timeSize = times.find('li').outerWidth(true);
                monta_shipping.maxMove = times.find('li').length;

                monta_shipping.moveAmount = Math.floor((viewing / monta_shipping.timeSize));

            },

            movePrevious: function () {

                var moving = $('.monta-times ul');
                var times = $('.monta-times');
                var viewing = times.find('.mover').width();

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

                var moving = $('.monta-times ul');
                var times = $('.monta-times');
                var viewing = times.find('.mover').width();

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

                var data = $('form.checkout').serialize();
                data += '&action=monta_shipping_options';

                $.post(ajax_url, data)
                    .done(function (result) {

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
            },

            setMethod: function () {

                $('.monta-shipment-delivery').removeClass('active');
                $('.monta-shipment-shipper').html('');

                var checked = $('.monta-options input[type=radio]:checked').val();

                if (checked === "pickup") {

                    // Hide alternate billing address option
                    $("#ship-to-different-address").hide();
                    $("#ship-to-different-address").next().hide();
                    $("#ship-to-different-address input").prop("checked", false);

                    $('.monta-loading').addClass('active');

                    $('.monta-shipment-extras').removeClass('active');

                } else {

                    $("#ship-to-different-address").show();

                    $('.monta-loading').addClass('active');
                    $(".monta-shipment-pickup").removeClass("active");

                }

                monta_shipping.updateDeliveries(function (success, result) {

                    if (success) {

                        if (checked === 'delivery') {

                            $('.monta-shipment-delivery').addClass('active');

                            var mover = $('.monta-times .mover');
                            mover.html('<ul></ul>');

                            var times = mover.find('ul');

                            // get the date of today , this is for validation if the checkbox needed to be checked (sameday or not sameday)
                            var d = new Date();
                            var date_today = d.toLocaleDateString('nl-NL', { month: '2-digit', day: '2-digit', year: 'numeric' });

                            // Frames tonen in lijst

                            $.each(monta_shipping.frames, function (key, item) {

                                if (item.code === 'NOTIMES') {
                                    $('.monta-times').addClass('monta-hide');
                                }

                                var option = item.options[0];
                                if (option !== null && option !== undefined) {

                                    var html = $('.monta-choice-template').html();
                                    html = html.replace(/{.id}/g, key);
                                    html = html.replace(/{.code}/g, (item.code !== null) ? item.code : '');
                                    html = html.replace(/{.day}/g, item.date);
                                    html = html.replace(/{.time}/g, item.time);
                                    html = html.replace(/{.description}/g, (item.description !== null) ? item.description : '');
                                    html = html.replace(/{.price}/g, item.price);

                                    // exclude the checking of today delivery, this is not the most wanted option for clients
                                    if (date_today == item.date) {
                                        html = html.replace(/{.sameday}/g, 'sameday');
                                    } else {
                                        html = html.replace(/{.sameday}/g, 'otherday');
                                    }

                                    times.append(html);

                                }

                            });

                            // Select first option
                            mover.find('input[type=radio]:not(.sameday):first').prop('checked', true).click();

                            // Update slider for scrolling
                            monta_shipping.updateSlider();

                            monta_shipping.storeLocatorDestroy();

                        } else {

                            $('.monta-shipment-extras').removeClass('active');

                        }

                        // Process pickups
                        if (checked === 'pickup') {

                            var markers = [];

                            $('#category-filters').html('');
                            $.each(monta_shipping.pickups, function (key, item) {

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
                                    'price': item.price,
                                    'price_raw': item.price_raw,
                                    'raw': item
                                });

                                if ($('.cat-' + item.code + '').length === 0) {

                                    $('#category-filters').append('<li class="cat-' + item.code + '"><label><input class="monta-shipper-filter" type="checkbox" checked="checked" name="category" value="' + item.code + '"> ' + item.description + '</label></li>');

                                }

                            });

                            $('#monta-stores').storeLocator('reset');

                            var config = {
                                //'debug': true,
                                'pagination': false,
                                'infowindowTemplatePath': site_url + '/wp-content/plugins/montapacking-checkout/assets/js/templates/infowindow-description.html',
                                'listTemplatePath': site_url + '/wp-content/plugins/montapacking-checkout/assets/js/templates/list-location.html',
                                'distanceAlert': -1,
                                'dataType': "json",
                                'dataRaw': JSON.stringify(markers, null, 2),
                                'slideMap': false,
                                'inlineDirections': true,
                                'originMarker': true,
                                'dragSearch': false,
                                'defaultLoc': true,
                                'defaultLat': monta_shipping.pickup_default.lat,
                                'defaultLng': monta_shipping.pickup_default.lng,
                                'lengthUnit': 'km',
                                'exclusiveFiltering': true,
                                'taxonomyFilters': {
                                    'category': 'category-filters',
                                },
                                catMarkers: {
                                    'PAK': [site_url + '/wp-content/plugins/montapacking-checkout/assets/img/PostNL.png', 32, 32],
                                    'DHLservicepunt': [site_url + '/wp-content/plugins/montapacking-checkout/assets/img/dhl.svg', 32, 32],
                                    'DPDparcelstore': [site_url + '/wp-content/plugins/montapacking-checkout/assets/img/DPD.png', 32, 32]
                                },
                                callbackMarkerClick: function (marker, markerId, $selectedLocation, location) {

                                    monta_shipping.selectPickup(location);

                                },
                                callbackListClick: function (markerId, selectedMarker, location) {

                                    monta_shipping.selectPickup(location);

                                },
                                callbackNotify: function (notifyText) {

                                    // Show error message
                                    console.log(notifyText);

                                    monta_shipping.storeLocatorDestroy();

                                }
                            };

                            monta_shipping.pickupLocator = $('#monta-stores').storeLocator(config);

                            var liExists = setInterval(function () {

                                if($("#initialPickupsList").length){

                                    $('#initialPickupsList li:gt(2)').remove();

                                    //Add radio buttons for initial pickups
                                    $("#initialPickupsList  > li").each(function () {

                                        var element = $("input");
                                        var radioButtons = $(this).find(element);

                                        if (radioButtons.length < 1) {

                                            $(this).prepend("<input name='initialPickupPointRadio' class='initialPickupRadio' type='radio'>");

                                        }

                                    });

                                    if ($("#initialPickupsList").children().length === 3) {

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

                    } else {

                        // Show error message
                        console.log(result.message);

                        monta_shipping.storeLocatorDestroy();

                    }

                });

                monta_shipping.updateWooCheckout();

            },

            showPickupMap: function () {

                var checked = $('.monta-options input[type=radio]:checked').val();

                if (checked === "pickup") {

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

            },

            selectPickup: function (location) {

                monta_shipping.pickup_selected = location;

                $('.monta-select-pickup').addClass('active');

                    if (monta_shipping.pickup_selected !== null) {

                        var loc = monta_shipping.pickup_selected;
                        var html = '<strong>' + loc.name + '</strong><br />';
                        html += '' + loc.street + '<br />';
                        html += '' + loc.postal + ' ' + loc.city + '<br />';
                        html += '&euro; ' + loc.price + '<br />';

                        $('.monta-pickup-selected').html(html);

                        $('.monta-pickup-selected').show();

                        $('.monta-shipment-pickup').addClass('active');

                        $('.monta-pickup-input-code').val(loc.code);
                        $('.monta-pickup-input-shipper').val(loc.category);
                        $('.monta-pickup-input-shippingOptions').val(loc.shippingOptions);
                        $('.monta-pickup-input-company').val(loc.name);
                        $('.monta-pickup-input-street').val(loc.street);
                        $('.monta-pickup-input-houseNumber').val(loc.houseNumber);
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
                            $("#initialPickupsList li.list-focus").removeClass("list-focus");
                            $("#initialPickupRadioDummy").prop("checked", true);

                        });

                        monta_shipping.updateWooCheckout();

                    }

                //});

            },

            storeLocatorDestroy: function () {

                $('#monta-stores .bh-sl-loc-list .list').html('');
                $('#monta-stores .bh-sl-map').removeClass('bh-sl-map-open').html('');
                $('#monta-stores .monta-select-pickup').removeClass('active');

                $('#monta-stores').storeLocator('destroy');
                // fix 2020-03-02
                //monta_shipping.pickupLocator.storeLocator('destroy');

            },

            setTimeframe: function () {

                var value = $(this).val();
                var shippers = $('.monta-shipment-shipper');
                var options = monta_shipping.frames[value].options;

                // Empty shippers
                shippers.html('');

                if (options !== undefined && options !== null) {

                    $.each(options, function (key, item) {

                        var realCode = item.code;

                        var code = '';
                        $.each(item.codes, function (key, item) {
                            code += ((code !== '') ? ',' : '') + item;
                        });

                        // The API returns format like (00:00 to 00:00 for DPD, exclude them here)
                        var time = '<strong>' + item.from + ' - ' + item.to + '</strong>';
                        if (item.from === item.to) {
                            time = '';
                        }

                        var html = $('.monta-shipper-template').html();
                        html = html.replace(/{.code}/g, realCode);
                        html = html.replace(/{.img}/g, '<img src="' + site_url + '/wp-content/plugins/montapacking-checkout/assets/img/' + code + '.png">');
                        html = html.replace(/{.name}/g, item.name);
                        html = html.replace(/{.time}/g, time);
                        html = html.replace(/{.price}/g, item.price);

                        shippers.append(html);

                    });

                    shippers.find('input[type=radio]:first').prop('checked', true);

                    monta_shipping.setShipper();

                } else {

                    shippers.html('');

                }

                monta_shipping.updateWooCheckout();

            },

            setShipper: function () {

                var shipper = $('.monta-shipment-shipper input[type=radio]:checked').val();
                var frame = $('.monta-times input[type=radio]:checked').val();
                var extras = $('.monta-shipment-extra-options');

                // Specify selected option
                var options = null;

                $.each(monta_shipping.frames[frame].options, function (key, item) {

                    var realCode = item.code;

                    if (realCode === shipper) {

                        options = item.extras;
                        return false;

                    }

                });

                if (options !== undefined && options !== null) {

                    $('.monta-shipment-extras').addClass('active');

                    extras.html('');
                    $.each(options, function (key, item) {

                        var code = '';
                        $.each(item.codes, function (key, item) {
                            code += ((code !== '') ? ',' : '') + item;
                        });

                        var html = $('.monta-extra-template').html();
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

                var liExists = setInterval(function () {

                    if($("#initialPickupsList").length){

                        $('#initialPickupsList li:gt(2)').remove();

                        //Add radio buttons for initial pickups
                        $("#initialPickupsList  > li").each(function () {

                            $(this).prepend("<input name='initialPickupPointRadio' class='initialPickupRadio' type='radio'>");

                        });

                        if ($("#initialPickupsList").children().length === 3) {

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


    }catch (e){
    }

});