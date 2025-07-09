jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize M-Pesa payment functionality
    var MpesaGateway = {
        
        init: function() {
            this.bindEvents();
            this.validatePhoneNumber();
        },
        
        bindEvents: function() {
            // Handle payment initiation on thank you page
            $(document).on('click', '#initiate-mpesa-payment', this.initiatePayment);
            
            // Handle phone number validation on checkout
            $(document).on('input', '#mpesa_phone_number', this.validatePhoneInput);
            
            // Handle payment method selection
            $(document).on('change', 'input[name="payment_method"]', this.toggleMpesaFields);
            
            // Auto-format phone number
            $(document).on('input', '#mpesa_phone_number', this.formatPhoneNumber);
        },
        
        validatePhoneNumber: function() {
            var phoneInput = $('#mpesa_phone_number');
            if (phoneInput.length) {
                phoneInput.attr('pattern', '^(\\+?254|0)[7][0-9]{8}$');
                phoneInput.attr('title', 'Please enter a valid Kenyan phone number (e.g., 0722123456)');
            }
        },
        
        validatePhoneInput: function(e) {
            var phone = $(this).val();
            var isValid = /^(\+?254|0)[7][0-9]{8}$/.test(phone);
            
            $(this).toggleClass('invalid', !isValid && phone.length > 0);
            
            // Show/hide validation message
            var validationMsg = $(this).siblings('.phone-validation-msg');
            if (!validationMsg.length) {
                validationMsg = $('<small class="phone-validation-msg" style="color: red;"></small>');
                $(this).after(validationMsg);
            }
            
            if (!isValid && phone.length > 0) {
                validationMsg.text(wc_mpesa_params.invalid_phone_text).show();
            } else {
                validationMsg.hide();
            }
        },
        
        formatPhoneNumber: function(e) {
            var phone = $(this).val().replace(/\D/g, ''); // Remove non-digits
            
            // Auto-format common patterns
            if (phone.startsWith('254')) {
                // Already has country code
                $(this).val(phone);
            } else if (phone.startsWith('0') && phone.length === 10) {
                // Convert 0722123456 to 254722123456
                $(this).val('254' + phone.substring(1));
            } else if (phone.length === 9 && phone.startsWith('7')) {
                // Convert 722123456 to 254722123456
                $(this).val('254' + phone);
            } else {
                $(this).val(phone);
            }
        },
        
        toggleMpesaFields: function() {
            var selectedMethod = $('input[name="payment_method"]:checked').val();
            var mpesaForm = $('.wc-mpesa-form');
            
            if (selectedMethod === 'mpesa') {
                mpesaForm.slideDown();
                $('#mpesa_phone_number').focus();
            } else {
                mpesaForm.slideUp();
            }
        },
        
        initiatePayment: function(e) {
            e.preventDefault();
            
            var button = $(this);
            var orderId = button.data('order-id');
            var phone = button.data('phone');
            var amount = button.data('amount');
            var statusDiv = $('#mpesa-payment-status');
            
            // Validate phone number
            if (!phone || !MpesaGateway.isValidPhoneNumber(phone)) {
                MpesaGateway.showError(statusDiv, wc_mpesa_params.invalid_phone_text);
                return;
            }
            
            // Disable button and show loading
            button.prop('disabled', true).text(wc_mpesa_params.processing_text);
            statusDiv.html('<div class="mpesa-loading">Processing payment request...</div>').show();
            
            // Make AJAX request
            $.ajax({
                url: wc_mpesa_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'mpesa_stk_push',
                    order_id: orderId,
                    phone: phone,
                    amount: amount,
                    nonce: wc_mpesa_params.nonce
                },
                success: function(response) {
                    if (response.success) {
                        MpesaGateway.showSuccess(statusDiv, response.data.message);
                        MpesaGateway.startPaymentStatusCheck(orderId, response.data.checkout_request_id);
                    } else {
                        MpesaGateway.showError(statusDiv, response.data.message);
                        button.prop('disabled', false).text('Pay with M-Pesa');
                    }
                },
                error: function(xhr, status, error) {
                    MpesaGateway.showError(statusDiv, wc_mpesa_params.error_text);
                    button.prop('disabled', false).text('Pay with M-Pesa');
                }
            });
        },
        
        startPaymentStatusCheck: function(orderId, checkoutRequestId) {
            var attempts = 0;
            var maxAttempts = 30; // Check for 5 minutes (30 * 10 seconds)
            
            var checkStatus = function() {
                attempts++;
                
                $.ajax({
                    url: wc_mpesa_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'mpesa_check_status',
                        order_id: orderId,
                        checkout_request_id: checkoutRequestId,
                        nonce: wc_mpesa_params.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            if (response.data.status === 'completed') {
                                MpesaGateway.showSuccess($('#mpesa-payment-status'), 
                                    'Payment completed successfully! Your order is being processed.');
                                setTimeout(function() {
                                    location.reload();
                                }, 2000);
                                return;
                            } else if (response.data.status === 'failed') {
                                MpesaGateway.showError($('#mpesa-payment-status'), 
                                    'Payment failed. Please try again.');
                                $('#initiate-mpesa-payment').prop('disabled', false).text('Pay with M-Pesa');
                                return;
                            }
                        }
                        
                        // Continue checking if still pending and within limits
                        if (attempts < maxAttempts) {
                            setTimeout(checkStatus, 10000); // Check every 10 seconds
                        } else {
                            MpesaGateway.showWarning($('#mpesa-payment-status'), 
                                'Payment status check timed out. Please refresh the page to check your payment status.');
                            $('#initiate-mpesa-payment').prop('disabled', false).text('Pay with M-Pesa');
                        }
                    },
                    error: function() {
                        if (attempts < maxAttempts) {
                            setTimeout(checkStatus, 10000);
                        }
                    }
                });
            };
            
            // Start checking after 5 seconds
            setTimeout(checkStatus, 5000);
        },
        
        isValidPhoneNumber: function(phone) {
            return /^(\+?254|0)?[7][0-9]{8}$/.test(phone);
        },
        
        showSuccess: function(container, message) {
            container.html('<div class="mpesa-message mpesa-success">' + 
                '<span class="dashicons dashicons-yes-alt"></span>' + message + '</div>').show();
        },
        
        showError: function(container, message) {
            container.html('<div class="mpesa-message mpesa-error">' + 
                '<span class="dashicons dashicons-no-alt"></span>' + message + '</div>').show();
        },
        
        showWarning: function(container, message) {
            container.html('<div class="mpesa-message mpesa-warning">' + 
                '<span class="dashicons dashicons-warning"></span>' + message + '</div>').show();
        }
    };
    
    // Initialize on page load
    MpesaGateway.init();
    
    // Checkout form validation
    $('form.checkout').on('checkout_place_order_mpesa', function() {
        var phoneNumber = $('#mpesa_phone_number').val();
        
        if (!phoneNumber) {
            alert(wc_mpesa_params.enter_phone_text);
            $('#mpesa_phone_number').focus();
            return false;
        }
        
        if (!MpesaGateway.isValidPhoneNumber(phoneNumber)) {
            alert(wc_mpesa_params.invalid_phone_text);
            $('#mpesa_phone_number').focus();
            return false;
        }
        
        return true;
    });
    
    // Auto-scroll to payment section on thank you page
    if ($('#mpesa-payment-section').length) {
        setTimeout(function() {
            $('html, body').animate({
                scrollTop: $('#mpesa-payment-section').offset().top - 100
            }, 1000);
        }, 500);
    }
    
    // Handle admin test connection
    $(document).on('click', '#test-mpesa-connection', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var resultDiv = $('#test-connection-result');
        
        button.prop('disabled', true).text('Testing...');
        resultDiv.html('');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mpesa_test_connection',
                nonce: mpesa_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    resultDiv.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                } else {
                    resultDiv.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                }
                button.prop('disabled', false).text('Test Connection');
            },
            error: function() {
                resultDiv.html('<div class="notice notice-error"><p>Connection test failed.</p></div>');
                button.prop('disabled', false).text('Test Connection');
            }
        });
    });
});