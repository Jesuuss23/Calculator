jQuery(document).ready(function($) {
    'use strict';

    // ======================================================
    // 1. LÓGICA VISUAL (Selectores, Inputs, Addons)
    // ======================================================

    // Selectores Personalizados
    $('.custom-select-wrapper').each(function() {
        const wrapper = $(this);
        const trigger = wrapper.find('.custom-select-trigger, .numeric-trigger');
        const options = wrapper.find('.custom-option');
        const closeBtn = wrapper.find('.close-options-btn');
        const overlay = wrapper.find('.custom-select-options');
        const hiddenInput = wrapper.next('input[type="hidden"]');
        const valueDisplay = wrapper.find('.numeric-value');

        trigger.on('click', function() {
            $('.custom-select-wrapper').not(wrapper).removeClass('open');
            $('body').removeClass('custom-select-open');
            wrapper.addClass('open');
            $('body').addClass('custom-select-open');
        });

        function closeSelector() {
            wrapper.removeClass('open');
            $('body').removeClass('custom-select-open');
        }

        closeBtn.on('click', closeSelector);
        overlay.on('click', function(e) {
            if ($(e.target).is(overlay)) closeSelector();
        });

        options.on('click', function() {
            if (wrapper.attr('id') === 'addons-selector') return;

            const selectedValue = $(this).data('value');
            const selectedName = $(this).find('strong').text();
            
            let valueToStore = selectedValue;
            if (wrapper.attr('id') === 'service-selector' && typeof selectedValue === 'object') {
                valueToStore = selectedValue.id; 
            }

            hiddenInput.val(valueToStore);
            
            if (valueDisplay.length) {
                valueDisplay.text(selectedValue);
            } else {
                wrapper.find('.custom-select-trigger span').text(selectedName).css('color', '#111827');
            }
            closeSelector();
        });
    });

    // Redondeo de SQFT
    $('#sqft_input').on('blur', function() {
        const input = $(this);
        let value = parseInt(input.val(), 10);
        if (isNaN(value) || value < 1) {
            input.val(650);
            return;
        }
        const roundedValue = Math.ceil(value / 50) * 50;
        input.val(roundedValue);
    });

    // Addons: Mostrar detalles
    $('.addon-main-label input[type="checkbox"]').on('change', function() {
        const detailsDiv = $(this).closest('.addon-item').find('.addon-details');
        if ($(this).is(':checked')) detailsDiv.slideDown();
        else detailsDiv.slideUp();
    });

    // Addons: Botones +/-
    $('.stepper-btn').on('click', function() {
        const targetId = $(this).data('target');
        const targetInput = $('#' + targetId);
        if (targetInput.length) {
            let currentValue = parseInt(targetInput.val());
            const min = parseInt(targetInput.attr('min')) || 0;
            if ($(this).hasClass('plus')) currentValue++;
            else currentValue--;
            targetInput.val(Math.max(min, currentValue));
        }
    });


    // ======================================================
    // 2. FUNCIÓN PARA RENDERIZAR PAYPAL
    // ======================================================
function renderPayPalButton(price, detailsObject) {
        $('#paypal-button-container').empty(); 
        $('#paypal-payment-section').fadeIn();

        if (typeof paypal === 'undefined') {
            console.error('PayPal SDK not loaded.');
            return;
        }

        const currencyCode = calc_ajax.currency || 'USD';

        // 1. Construimos el TÍTULO PRINCIPAL (Lo que se ve en negrita)
        // Incluimos Servicio y Nombre del Cliente para que sea imposible no verlo.
        // Ej: "Deep Cleaning - Juan Perez"
        let mainTitle = `${detailsObject.service} - ${detailsObject.client}`;

        // 2. Construimos la DESCRIPCIÓN DETALLADA
        // Incluimos Teléfono, Email y Extras.
        // Ej: "Tel: 555123 | Email: juan@x.com | Extras: Oven"
        let subDescription = `Tel: ${detailsObject.phone} | E-mail: ${detailsObject.email}`;
        if(detailsObject.addons && detailsObject.addons !== 'None') {
            subDescription += ` | +: ${detailsObject.addons}`;
        }

        let safeTitle = mainTitle.substring(0, 127);
        let safeDesc = subDescription.substring(0, 127);

        paypal.Buttons({
            style: { shape: 'rect', color: 'gold', layout: 'vertical', label: 'pay' },
            
            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        description: safeTitle, 
                        
                        amount: {
                            currency_code: currencyCode,
                            value: price,
                            breakdown: {
                                item_total: { currency_code: currencyCode, value: price }
                            }
                        },                     
                        items: [{
                            name: safeTitle, 
                            description: safeDesc, 
                            
                            unit_amount: { currency_code: currencyCode, value: price },
                            quantity: "1",
                            category: "DIGITAL_GOODS"
                        }]
                    }]
                });
            },
            onApprove: function(data, actions) {
                return actions.order.capture().then(function(details) {
                    alert('Payment completed ' + details.payer.name.given_name);
                    if (calc_ajax.redirect_url && calc_ajax.redirect_url.trim() !== "") {
                        window.location.href = calc_ajax.redirect_url;
                    }
                });
            },
            onError: function (err) {
                console.error('PayPal Error:', err);
                alert('The payment could not be processed.');
            }
        }).render('#paypal-button-container');
    }


    // ======================================================
    // 3. ENVÍO DEL FORMULARIO (CÁLCULO + PAYPAL)
    // ======================================================
    $("#calcForm").on("submit", function(e) {
        e.preventDefault();
        const resultDiv = $("#calc-result");
        const priceDisplay = $("#price-display");
        const submitButton = $("#calculate-btn");
        
        // Ocultar PayPal al recalcular para evitar duplicados
        $('#paypal-payment-section').hide(); 
        $('#paypal-button-container').empty();

        // Validación de ZIP
        const allowedZipCodes = [33301, 33302, 33303, 33304, 33305, 33306, 33307, 33308, 33309, 33310, 33311, 33312, 33313, 33314, 33315, 33316, 33317, 33318, 33319, 33320, 33321, 33322, 33323, 33324, 33325, 33326, 33327, 33328, 33329, 33330, 33331, 33332, 33334, 33335, 33336, 33337, 33338, 33339, 33340, 33345, 33346, 33348, 33349, 33351, 33355, 33359, 33394];
        const userZip = parseInt($('input[name="zip"]').val(), 10);
        
        if (!userZip || !allowedZipCodes.includes(userZip)) {
            priceDisplay.text('$0');
            resultDiv.html("<p style='color:red;'>Sorry, we do not service this ZIP code.</p>").show();
            return;
        }

        submitButton.prop('disabled', true).text('Calculating...');
        resultDiv.hide().html('');
        
        let formData = $(this).serialize() + "&action=process_form_step_by_step"; 
        
        $.ajax({
            url: calc_ajax.ajax_url,
            type: "POST", data: formData, dataType: 'json',
            success: function(response) {
                if (response.success) {
                    priceDisplay.text('$' + response.data.final_price);
                    resultDiv.html(`<p style='color:green;'>${response.data.message}</p>`).show();
                    
let orderDetails = {
                        service: response.data.service_name,
                        client: response.data.client_name,
                        email: response.data.client_email,
                        phone: response.data.client_phone,
                        last_clean: response.data.last_cleaning,
                        addons: response.data.addons_list
                    };
                    

                    renderPayPalButton(response.data.final_price, orderDetails);

                } else {
                    priceDisplay.text('$0');
                    resultDiv.html(`<p style='color:red;'>Error: ${response.data.message}</p>`).show();
                }
            },
            error: function() {
                priceDisplay.text('$0');
                resultDiv.html("<p style='color:red;'>An unexpected error occurred.</p>").show();
            },
            complete: function() {
                submitButton.prop('disabled', false).text('Get instant price');
            }
        });
    });
});