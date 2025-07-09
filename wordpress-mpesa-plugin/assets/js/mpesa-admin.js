jQuery(document).ready(function($) {
    'use strict';
    
    // Admin functionality for M-Pesa Gateway
    var MpesaAdmin = {
        
        init: function() {
            this.bindEvents();
            this.initTooltips();
        },
        
        bindEvents: function() {
            // Test connection button
            $(document).on('click', '#test-mpesa-connection', this.testConnection);
            
            // Clear logs button
            $(document).on('click', '#clear-mpesa-logs', this.clearLogs);
            
            // Export logs button
            $(document).on('click', '#export-mpesa-logs', this.exportLogs);
            
            // View transaction details
            $(document).on('click', '.view-details', this.viewTransactionDetails);
            
            // Settings validation
            $('form[id^="mainform"]').on('submit', this.validateSettings);
            
            // Auto-hide notices
            this.autoHideNotices();
        },
        
        initTooltips: function() {
            // Add tooltips to help icons
            $('.woocommerce-help-tip').each(function() {
                $(this).attr('title', $(this).data('tip'));
            });
        },
        
        testConnection: function(e) {
            e.preventDefault();
            
            var button = $(this);
            var resultDiv = $('#test-connection-result');
            var originalText = button.text();
            
            // Show loading state
            button.prop('disabled', true)
                  .addClass('loading')
                  .text('Testing...');
            
            resultDiv.html('<div class="notice notice-info"><p>Testing connection...</p></div>');
            
            // Get nonce value - try multiple possible sources
            var nonce = $('#mpesa_admin_nonce').val() || 
                       (typeof mpesa_admin_params !== 'undefined' ? mpesa_admin_params.nonce : '') ||
                       $('input[name="_wpnonce"]').val() || 
                       'mpesa_test_connection_fallback';
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mpesa_test_connection',
                    nonce: nonce
                },
                timeout: 30000,
                success: function(response) {
                    console.log('Test connection response:', response);
                    
                    if (response.success) {
                        var message = response.data.message;
                        if (response.data.environment) {
                            message += '<br><small>Environment: ' + response.data.environment + '</small>';
                        }
                        if (response.data.token_preview) {
                            message += '<br><small>Token: ' + response.data.token_preview + '</small>';
                        }
                        resultDiv.html('<div class="notice notice-success"><p><span class="dashicons dashicons-yes-alt"></span> ' + message + '</p></div>');
                    } else {
                        var errorMessage = response.data.message || 'Connection test failed.';
                        if (response.data.debug_info) {
                            errorMessage += '<br><small>' + response.data.debug_info + '</small>';
                        }
                        resultDiv.html('<div class="notice notice-error"><p><span class="dashicons dashicons-no-alt"></span> ' + errorMessage + '</p></div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', xhr, status, error);
                    
                    var errorMsg = 'Connection test failed.';
                    if (status === 'timeout') {
                        errorMsg = 'Connection test timed out. Please check your API credentials and server connectivity.';
                    } else if (xhr.status === 403) {
                        errorMsg = 'Access denied. Please refresh the page and try again.';
                    } else if (xhr.status === 500) {
                        errorMsg = 'Server error. Please check your error logs.';
                    } else if (xhr.responseText) {
                        try {
                            var errorResponse = JSON.parse(xhr.responseText);
                            if (errorResponse.data && errorResponse.data.message) {
                                errorMsg = errorResponse.data.message;
                            }
                        } catch (e) {
                            errorMsg += ' Response: ' + xhr.responseText.substring(0, 100);
                        }
                    }
                    
                    resultDiv.html('<div class="notice notice-error"><p><span class="dashicons dashicons-no-alt"></span> ' + errorMsg + '</p></div>');
                },
                complete: function() {
                    button.prop('disabled', false)
                          .removeClass('loading')
                          .text(originalText);
                }
            });
        },
        
        clearLogs: function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to clear all M-Pesa logs? This action cannot be undone.')) {
                return;
            }
            
            var button = $(this);
            var originalText = button.text();
            
            button.prop('disabled', true).text('Clearing...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mpesa_clear_logs',
                    nonce: $('#mpesa_admin_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        $('#mpesa-logs-content').html('<p>No logs available.</p>');
                        MpesaAdmin.showNotice('Logs cleared successfully.', 'success');
                    } else {
                        MpesaAdmin.showNotice('Failed to clear logs.', 'error');
                    }
                },
                error: function() {
                    MpesaAdmin.showNotice('Failed to clear logs.', 'error');
                },
                complete: function() {
                    button.prop('disabled', false).text(originalText);
                }
            });
        },
        
        exportLogs: function(e) {
            e.preventDefault();
            
            var date = $('#log-date-filter').val() || '';
            var url = ajaxurl + '?action=mpesa_export_logs&nonce=' + $('#mpesa_admin_nonce').val() + '&date=' + date;
            
            // Create temporary link and trigger download
            var link = document.createElement('a');
            link.href = url;
            link.download = 'mpesa-logs-' + (date || 'all') + '.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },
        
        viewTransactionDetails: function(e) {
            e.preventDefault();
            
            var transactionId = $(this).data('id');
            var modal = $('#transaction-details-modal');
            
            if (!modal.length) {
                // Create modal if it doesn't exist
                modal = $('<div id="transaction-details-modal" class="mpesa-modal"></div>');
                $('body').append(modal);
            }
            
            modal.html('<div class="mpesa-modal-content"><div class="mpesa-modal-loading">Loading...</div></div>').show();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mpesa_get_transaction_details',
                    transaction_id: transactionId,
                    nonce: $('#mpesa_admin_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        modal.html(response.data.html);
                    } else {
                        modal.html('<div class="mpesa-modal-content"><p>Failed to load transaction details.</p><button class="button close-modal">Close</button></div>');
                    }
                },
                error: function() {
                    modal.html('<div class="mpesa-modal-content"><p>Error loading transaction details.</p><button class="button close-modal">Close</button></div>');
                }
            });
        },
        
        validateSettings: function(e) {
            var errors = [];
            var isEnabled = $('#woocommerce_mpesa_enabled').is(':checked');
            
            if (!isEnabled) {
                return true; // No validation needed if disabled
            }
            
            var isSandbox = $('#woocommerce_mpesa_sandbox_mode').is(':checked');
            
            if (isSandbox) {
                // Validate sandbox credentials
                if (!$('#woocommerce_mpesa_sandbox_consumer_key').val()) {
                    errors.push('Sandbox Consumer Key is required.');
                }
                if (!$('#woocommerce_mpesa_sandbox_consumer_secret').val()) {
                    errors.push('Sandbox Consumer Secret is required.');
                }
                if (!$('#woocommerce_mpesa_sandbox_shortcode').val()) {
                    errors.push('Sandbox Business Short Code is required.');
                }
                if (!$('#woocommerce_mpesa_sandbox_passkey').val()) {
                    errors.push('Sandbox Pass Key is required.');
                }
            } else {
                // Validate production credentials
                if (!$('#woocommerce_mpesa_consumer_key').val()) {
                    errors.push('Consumer Key is required.');
                }
                if (!$('#woocommerce_mpesa_consumer_secret').val()) {
                    errors.push('Consumer Secret is required.');
                }
                if (!$('#woocommerce_mpesa_shortcode').val()) {
                    errors.push('Business Short Code is required.');
                }
                if (!$('#woocommerce_mpesa_passkey').val()) {
                    errors.push('Pass Key is required.');
                }
            }
            
            if (errors.length > 0) {
                e.preventDefault();
                var errorMsg = 'Please fix the following errors:\n\n' + errors.join('\n');
                alert(errorMsg);
                return false;
            }
            
            return true;
        },
        
        showNotice: function(message, type) {
            type = type || 'info';
            var notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wrap h1').after(notice);
            
            // Auto dismiss after 5 seconds
            setTimeout(function() {
                notice.fadeOut(500, function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        autoHideNotices: function() {
            setTimeout(function() {
                $('.notice.is-dismissible').each(function() {
                    if (!$(this).find('.notice-dismiss').length) {
                        $(this).append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
                    }
                });
            }, 1000);
        }
    };
    
    // Initialize admin functionality
    MpesaAdmin.init();
    
    // Modal close functionality
    $(document).on('click', '.close-modal, .mpesa-modal-overlay', function(e) {
        if (e.target === this) {
            $('.mpesa-modal').hide();
        }
    });
    
    // Escape key to close modal
    $(document).keyup(function(e) {
        if (e.keyCode === 27) { // Escape key
            $('.mpesa-modal').hide();
        }
    });
    
    // Settings tabs functionality
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        var targetTab = $(this).attr('href');
        
        // Update active tab
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Show target content
        $('.tab-content').hide();
        $(targetTab).show();
    });
    
    // Copy webhook URL functionality
    $(document).on('click', '.copy-webhook-url', function(e) {
        e.preventDefault();
        
        var webhookUrl = $(this).data('url');
        
        // Create temporary input to copy text
        var tempInput = $('<input>');
        $('body').append(tempInput);
        tempInput.val(webhookUrl).select();
        document.execCommand('copy');
        tempInput.remove();
        
        // Show confirmation
        MpesaAdmin.showNotice('Webhook URL copied to clipboard!', 'success');
    });
    
    // Enhanced form validation with real-time feedback
    $('#woocommerce_mpesa_sandbox_consumer_key, #woocommerce_mpesa_consumer_key').on('blur', function() {
        var value = $(this).val();
        var feedback = $(this).siblings('.field-feedback');
        
        if (!feedback.length) {
            feedback = $('<small class="field-feedback"></small>');
            $(this).after(feedback);
        }
        
        if (value.length > 0 && value.length < 10) {
            feedback.text('Consumer key seems too short').css('color', 'red');
        } else if (value.length >= 10) {
            feedback.text('✓ Valid format').css('color', 'green');
        } else {
            feedback.text('');
        }
    });
    
    // Phone number format helper
    $(document).on('focus', '#woocommerce_mpesa_test_phone', function() {
        $(this).attr('placeholder', '254722123456 or 0722123456');
    });
    
    // Auto-save draft settings
    var settingsTimer;
    $('input[id^="woocommerce_mpesa_"], textarea[id^="woocommerce_mpesa_"]').on('input', function() {
        clearTimeout(settingsTimer);
        settingsTimer = setTimeout(function() {
            // Could implement auto-save draft functionality here
        }, 2000);
    });
});