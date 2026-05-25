if ( 'undefined' !== typeof wp.i18n ) {
    var __ = wp.i18n.__;
} else {
    // Create a dummy fallback function incase i18n library isn't available.
    var __ = ( text, textDomain ) => {
        return text;
    }
}

jQuery(document).ready(function($) {
    jQuery(document).on('click', '#icegram-mailer-send-test-email', function (e) {
        e.preventDefault();
        var sendButton = $(this);
        var test_email = $('#icegram-mailer-test-email').val();
        if (test_email) {
            var params = {
                action: 'icegram-mailer',
                method: 'send_test_email',
                handler: 'settings',
                data: {
                    test_email,
                },
                _wpnonce: icegram_mailer_admin_js_data._wpnonce
            };
            $(sendButton).find('.loader').removeClass('hidden');
            $(sendButton).find('.button-text').html(__( 'Sending', 'icegram-mailer' ));
            jQuery.ajax({
                method: 'POST',
                url: ajaxurl,
                data: params,
                dataType: 'json',
                success: function (response) {
                    if (response && typeof response.status !== 'undefined' && response.status == "success") {
                        let successMessageHTML = '<span style="color:green">' + __( 'Email has been sent. Please check your inbox', 'icegram-mailer' ) + '</span>';
                        //$('#es-send-test').parent().find('.helper').html(successMessageHTML);
                        $('#icegram-mailer-send-result-message').html(successMessageHTML);	
                    } else {
                        let errorMessageHTML = '<span style="color:#e66060"><strong>' + __( 'Sending error', 'icegram-mailer' ) + '</strong>: ' + ( Array.isArray( response.message ) ? response.message.join() : response.message ) + '</span>';
                        //$('#icegram-mailer-send-result-message').parent().find('.helper').html(errorMessageHTML);
                        $('#icegram-mailer-send-result-message').html(errorMessageHTML);	
                    }

                    $(sendButton).find('.loader').addClass('hidden');
                    $(sendButton).find('.button-text').html(__( 'Send Email', 'icegram-mailer' ));
                },

                error: function (err) {
                    $(sendButton).find('.loader').addClass('hidden');
                    $(sendButton).find('.button-text').html(__( 'Send Email', 'icegram-mailer' ));
                }
            });
        } else {
            confirm(__('Add test email','icegram-mailer' ));
        }

    });

    jQuery(document).on('click', '.icegram-mailer-admin-notice.notice[data-notice-id] .notice-dismiss, .icegram-mailer-admin-notice.notice[data-notice-id] .icegram-mailer-dismiss-notice', function(e) {
        var notice   = jQuery(this).closest('.notice');
        var noticeId = notice.data('notice-id');
        
        jQuery.post(ajaxurl, {
            action: 'dismiss_' + noticeId + '_notice',
            _wpnonce: icegram_mailer_admin_js_data._wpnonce
        });

        jQuery(notice).fadeTo(100, 0, function() {
            jQuery(notice).slideUp(100, function() {
                jQuery(notice).remove()
            })
        });
    });

    // Custom modal functions
    var modalActions = {};
    
    function showLoadingModal(message) {
        var modalHtml = '<div class="icegram-mailer-modal-overlay icegram-mailer-loading-modal">' +
            '<div class="icegram-mailer-modal">' +
            '<div class="icegram-mailer-modal-body" style="text-align: center; padding: 32px 24px;">' +
            '<div class="icegram-mailer-spinner"></div>' +
            '<p style="margin-top: 16px; font-weight: 500;">' + message + '</p>' +
            '</div>' +
            '</div></div>';
        
        jQuery('body').append(modalHtml);
        
        setTimeout(function() {
            jQuery('.icegram-mailer-loading-modal').addClass('show');
        }, 10);
    }
    
    function hideLoadingModal() {
        jQuery('.icegram-mailer-loading-modal').removeClass('show');
        setTimeout(function() {
            jQuery('.icegram-mailer-loading-modal').remove();
        }, 300);
    }
    
    function showIcegramModal(title, message, type, buttons) {
        var modalId = 'modal-' + Date.now();
        var modalHtml = '<div class="icegram-mailer-modal-overlay" data-modal-id="' + modalId + '">' +
            '<div class="icegram-mailer-modal">' +
            '<div class="icegram-mailer-modal-header ' + type + '">' +
            '<h3>' + title + '</h3>' +
            '</div>' +
            '<div class="icegram-mailer-modal-body">' +
            '<p>' + message + '</p>' +
            '</div>' +
            '<div class="icegram-mailer-modal-footer">';
        
        modalActions[modalId] = {};
        buttons.forEach(function(btn, index) {
            var btnId = 'btn-' + index;
            modalActions[modalId][btnId] = btn.action;
            modalHtml += '<button class="' + btn.class + '" data-btn-id="' + btnId + '">' + btn.text + '</button>';
        });
        
        modalHtml += '</div></div></div>';
        
        jQuery('body').append(modalHtml);
        
        setTimeout(function() {
            jQuery('.icegram-mailer-modal-overlay[data-modal-id="' + modalId + '"]').addClass('show');
        }, 10);
    }
    
    function closeIcegramModal() {
        var modalId = jQuery('.icegram-mailer-modal-overlay').data('modal-id');
        if (modalId && modalActions[modalId]) {
            delete modalActions[modalId];
        }
        jQuery('.icegram-mailer-modal-overlay').removeClass('show');
        setTimeout(function() {
            jQuery('.icegram-mailer-modal-overlay').remove();
        }, 300);
    }

    /**
     * Helper function to reset button state
     * 
     * @param {jQuery} button - The button element
     * @param {jQuery} svg - The SVG icon element
     * @param {string} buttonText - The text to set on the button
     */
    function resetButtonState(button, svg, buttonText) {
        button.prop('disabled', false);
        button.removeClass('opacity-50 cursor-not-allowed');
        svg.removeClass('animate-spin');
        button.contents().filter(function() {
            return this.nodeType === 3; // Text node
        }).last().replaceWith(' ' + buttonText);
    }

    /**
     * Helper function to set button loading state
     * 
     * @param {jQuery} button - The button element
     * @param {jQuery} svg - The SVG icon element
     * @param {string} loadingText - The loading text to display
     */
    function setButtonLoadingState(button, svg, loadingText) {
        button.prop('disabled', true);
        button.addClass('opacity-50 cursor-not-allowed');
        svg.addClass('animate-spin');
        button.contents().filter(function() {
            return this.nodeType === 3; // Text node
        }).last().replaceWith(' ' + loadingText);
    }

    // Handle modal button clicks
    jQuery(document).on('click', '.icegram-mailer-modal-footer button', function() {
        var btnId = jQuery(this).data('btn-id');
        var modalId = jQuery(this).closest('.icegram-mailer-modal-overlay').data('modal-id');
        
        if (modalId && modalActions[modalId] && modalActions[modalId][btnId]) {
            var action = modalActions[modalId][btnId];
            closeIcegramModal();
            if (action) {
                action();
            }
        } else {
            closeIcegramModal();
        }
    });
    
    // Close modal on overlay click
    jQuery(document).on('click', '.icegram-mailer-modal-overlay', function(e) {
        if (jQuery(e.target).hasClass('icegram-mailer-modal-overlay')) {
            closeIcegramModal();
        }
    });

    // Handle resend email action
    jQuery(document).on('click', '.icegram-mailer-resend-email', function(e) {
        e.preventDefault();
        
        var emailId = jQuery(this).data('email-id');
        
        // Show custom confirmation modal
        showIcegramModal(
            __('Confirm Resend', 'icegram-mailer'),
            __('Are you sure you want to resend this email?', 'icegram-mailer'),
            'confirm',
            [
                {
                    text: __('Cancel', 'icegram-mailer'),
                    class: 'icegram-mailer-modal-btn-secondary',
                    action: null
                },
                {
                    text: __('OK', 'icegram-mailer'),
                    class: 'icegram-mailer-modal-btn-primary',
                    action: function() {
                        // Show loading modal
                        showLoadingModal(__('Resending Email...', 'icegram-mailer'));
                        
                        jQuery.ajax({
                            type: 'POST',
                            url: ajaxurl,
                            data: {
                                action: 'icegram-mailer',
                                handler: 'dashboard',
                                method: 'resend_email',
                                security: icegram_mailer_admin_js_data._wpnonce,
                                data: {
                                    email_id: emailId
                                }
                            },
                            dataType: 'json',
                            success: function(response) {
                                hideLoadingModal();
                                if (response && response.status === 'success') {
                                    showIcegramModal(
                                        __('Success', 'icegram-mailer'),
                                        __('Email resent successfully!', 'icegram-mailer'),
                                        'success',
                                        [
                                            {
                                                text: __('OK', 'icegram-mailer'),
                                                class: 'icegram-mailer-modal-btn-primary',
                                                action: function() {
                                                    location.reload();
                                                }
                                            }
                                        ]
                                    );
                                } else {
                                    showIcegramModal(
                                        __('Error', 'icegram-mailer'),
                                        (response.message || __('Unknown error occurred', 'icegram-mailer')),
                                        'error',
                                        [
                                            {
                                                text: __('OK', 'icegram-mailer'),
                                                class: 'icegram-mailer-modal-btn-primary',
                                                action: null
                                            }
                                        ]
                                    );
                                }
                            },
                            error: function(xhr, status, error) {
                                hideLoadingModal();
                                showIcegramModal(
                                    __('Error', 'icegram-mailer'),
                                    __('An error occurred. Please try again.', 'icegram-mailer'),
                                    'error',
                                    [
                                        {
                                            text: __('OK', 'icegram-mailer'),
                                            class: 'icegram-mailer-modal-btn-primary',
                                            action: null
                                        }
                                    ]
                                );
                            }
                        });
                    }
                }
            ]
        );
    });

    // Handle refresh now button click
    jQuery(document).on('click', '#refresh-now-btn', function(e) {
        e.preventDefault();
        
        var refreshButton = jQuery(this); 
        var svg = refreshButton.find('svg');
        var originalButtonText = __('Refresh now', 'icegram-mailer');

        // Set button loading state
        setButtonLoadingState(refreshButton, svg, __('Refreshing...', 'icegram-mailer'));
      
        jQuery.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                action: 'icegram-mailer',
                handler: 'dashboard',
                method: 'sync_account_stats',
                security: icegram_mailer_admin_js_data._wpnonce
            },
            dataType: 'json',
            success: function(response) { 
                
                // Reset button state
                resetButtonState(refreshButton, svg, originalButtonText);
            
                if (response && response.status === 'success') {
                    // Show success message
                    var successNotice = '<div class="notice notice-success is-dismissible" style="margin: 20px 0; background: #fff; border-left: 4px solid #46b450; padding: 12px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">' +
                        '<p style="margin: 0;">' + __('Usage data refreshed successfully.', 'icegram-mailer') + '</p>' +
                        '<button type="button" class="notice-dismiss" style="position: absolute; top: 0; right: 0; padding: 9px; background: none; border: none; cursor: pointer;"><span class="screen-reader-text">' + __('Dismiss this notice.', 'icegram-mailer') + '</span></button>' +
                        '</div>';
                    
                    // Remove any existing notices
                    jQuery('.notice.notice-success, .notice.notice-error').remove();
                    
                    // Use .one() to avoid duplicate handlers
                    jQuery(document).one('click', '.notice-dismiss', function() {
                        jQuery(this).closest('.notice').fadeOut();
                    });
                    
                    // Update UI with pre-calculated server data - NO calculations needed!
                    if (response.data) {
                        var data = response.data;
                        
                        // Update email sent text
                        jQuery('#ig-mailer-email-sent').html(data.email_sent_text);
                        
                        // Update remaining text
                        jQuery('#ig-mailer-remaining').html(data.remaining_text);
                        
                        // Update progress bar
                        var progressBar = jQuery('#ig-mailer-progress-bar');
                        progressBar.css('width', data.percentage_used + '%');
                        progressBar.removeClass('bg-indigo-600 bg-yellow-600 bg-orange-600 bg-red-600');
                        progressBar.addClass(data.progress_bar_class);
                        
                        // Update reset date
                        if (data.next_reset) {
                            jQuery('#ig-mailer-next-reset').html(__('resets on ', 'icegram-mailer') + data.next_reset);
                        }
                    }
                    
                    // Auto-dismiss notice after 3 seconds
                    setTimeout(function() {
                        jQuery('.notice.notice-success').fadeOut(function() {
                            jQuery(this).remove();
                        });
                    }, 3000);
                     
                } else {
                    // Show error message
                    var errorMessage = response && response.message ? response.message : __('Failed to refresh usage data.', 'icegram-mailer');
                    alert(errorMessage);
                }
            },
            error: function(xhr, status, error) {
                resetButtonState(refreshButton, svg, originalButtonText);
                alert(__('An error occurred while refreshing. Please try again.', 'icegram-mailer'));
            }
        });
    });

    // Show success/error messages after bulk resend
    if (window.location.search.indexOf('resent=') > -1 || window.location.search.indexOf('deleted=') > -1) {
        var urlParams = new URLSearchParams(window.location.search);
        var resentCount = parseInt(urlParams.get('resent')) || 0;
        var failedCount = parseInt(urlParams.get('failed')) || 0;
        var deletedCount = parseInt(urlParams.get('deleted')) || 0;

        var message = '';        
        if (resentCount > 0) {
            message += resentCount + ' ' + __('email(s) resent successfully.', 'icegram-mailer');
        }
        if (failedCount > 0) {
            if (message) message += ' ';
            message += failedCount + ' ' + __('email(s) failed to resend.', 'icegram-mailer');
        }
        if (deletedCount > 0) {
            if (message) message += ' ';
            message += deletedCount + ' ' + __('email(s) deleted successfully.', 'icegram-mailer');
        }
        
        if (message) {
            var noticeClass = (failedCount > 0) ? 'notice-warning' : 'notice-success';
            jQuery('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>')
                .insertAfter('.wp-header-end')
                .delay(5000)
                .fadeOut();
        }
        
        // Clean URL
        var cleanUrl = new URL(window.location.href);
        cleanUrl.searchParams.delete('resent');
        cleanUrl.searchParams.delete('failed');
        cleanUrl.searchParams.delete('deleted');
        window.history.replaceState({}, document.title, cleanUrl.toString());
    }

    // Handle delete email action
    jQuery(document).on('click', '.icegram-mailer-delete-email', function(e) {
        e.preventDefault();
        
        var emailId = jQuery(this).data('email-id');
        var row = jQuery(this).closest('tr');

        // Get the email status from the row before deletion
        var statusCell = row.find('.icegram-mailer-email-sent, .icegram-mailer-email-failed');
        var emailStatus = '';
        if (statusCell.hasClass('icegram-mailer-email-sent')) {
            emailStatus = 'sent';
        } else if (statusCell.hasClass('icegram-mailer-email-failed')) {
            emailStatus = 'failed';
        }
        
        // Show custom confirmation modal
        showIcegramModal(
            __('Confirm Delete', 'icegram-mailer'),
            __('Are you sure you want to delete this email log?', 'icegram-mailer'),
            'confirm',
            [
                {
                    text: __('Cancel', 'icegram-mailer'),
                    class: 'icegram-mailer-modal-btn-secondary',
                    action: null
                },
                {
                    text: __('Delete', 'icegram-mailer'),
                    class: 'icegram-mailer-modal-btn-primary',
                    action: function() {
                        // Show loading modal
                        showLoadingModal(__('Deleting Email...', 'icegram-mailer'));
                        
                        jQuery.ajax({
                            type: 'POST',
                            url: ajaxurl,
                            data: {
                                action: 'icegram-mailer',
                                handler: 'dashboard',
                                method: 'delete_email',
                                security: icegram_mailer_admin_js_data._wpnonce,
                                data: {
                                    email_id: emailId
                                }
                            },
                            dataType: 'json',
                            success: function(response) {
                                hideLoadingModal();
                                if (response && response.status === 'success') {
                                    // Close modal and remove row
                                    closeIcegramModal();
                                    
                                    // Remove the row with animation
                                    row.fadeOut(400, function() {
                                        row.remove();

                                        // Update filter counts
                                        updateFilterCounts(emailStatus); 
                                        
                                        // Check if any data rows remain
                                        var remainingRows = jQuery('#the-list tr').length;
                                        if (remainingRows === 0) {
                                            location.reload();
                                        }
                                    });
                                } else {
                                    showIcegramModal(
                                        __('Error', 'icegram-mailer'),
                                        (response.message || __('Unknown error occurred', 'icegram-mailer')),
                                        'error',
                                        [
                                            {
                                                text: __('OK', 'icegram-mailer'),
                                                class: 'icegram-mailer-modal-btn-primary',
                                                action: null
                                            }
                                        ]
                                    );
                                }
                            },
                            error: function(xhr, status, error) {
                                hideLoadingModal();
                                showIcegramModal(
                                    __('Error', 'icegram-mailer'),
                                    __('An error occurred. Please try again.', 'icegram-mailer'),
                                    'error',
                                    [
                                        {
                                            text: __('OK', 'icegram-mailer'),
                                            class: 'icegram-mailer-modal-btn-primary',
                                            action: null
                                        }
                                    ]
                                );
                            }
                        });
                    }
                }
            ]
        );
    });    


    // Helper function to update filter counts after deletion
    function updateFilterCounts(emailStatus) {
        // Update "All" count
        var allCountElement = jQuery('.subsubsub a[href*="icegram_mailer_dashboard"]:not([href*="status="]) .count');
        if (allCountElement.length > 0) {
            var currentCount = parseInt(allCountElement.text().replace(/[()]/g, ''));
            if (currentCount > 0) {
                allCountElement.text('(' + (currentCount - 1) + ')');
            }
        }
        
        // Update status-specific count (Sent or Failed)
        if (emailStatus === 'sent' || emailStatus === 'failed') {
            var statusCountElement = jQuery('.subsubsub a[href*="status=' + emailStatus + '"] .count');
            if (statusCountElement.length > 0) {
                var currentStatusCount = parseInt(statusCountElement.text().replace(/[()]/g, ''));
                if (currentStatusCount > 0) {
                    statusCountElement.text('(' + (currentStatusCount - 1) + ')');
                }
            }
        }
    }
});
