/**
 * User Feedback & Bug Reports Plugin JavaScript
 */

// ===== QUICK FEEDBACK COLLECTOR: ERROR CAPTURE =====
// This needs to run immediately to capture errors from page load
var userFeedbackConsoleErrors = [];
if (typeof userFeedback !== 'undefined' && userFeedback.quickCollector && userFeedback.quickCollector.captureErrors) {
    window.addEventListener('error', function(event) {
        if (userFeedbackConsoleErrors.length < 10) { // Limit to 10 errors
            userFeedbackConsoleErrors.push({
                message: event.message,
                source: event.filename,
                line: event.lineno,
                column: event.colno,
                timestamp: new Date().toISOString()
            });
        }
    });
    
    // Capture console errors
    var originalConsoleError = console.error;
    console.error = function() {
        if (userFeedbackConsoleErrors.length < 10) {
            userFeedbackConsoleErrors.push({
                message: Array.from(arguments).join(' '),
                source: 'console.error',
                timestamp: new Date().toISOString()
            });
        }
        originalConsoleError.apply(console, arguments);
    };
}

jQuery(document).ready(function($) {
    
    // ===== FILE UPLOAD HANDLING =====
    var uploadedAttachmentId = 0;
    // Make pastedFile globally accessible for use across different scopes
    if (typeof window.pastedFile === 'undefined') {
        window.pastedFile = null;
    }
    
    function getAllowedMimeTypes() {
        if (typeof userFeedback === 'undefined') {
            return [];
        }
        return Array.isArray(userFeedback.allowedMimeTypes) ? userFeedback.allowedMimeTypes : [];
    }

    function getAllowedFileTypes() {
        if (typeof userFeedback === 'undefined') {
            return [];
        }
        return Array.isArray(userFeedback.allowedFileTypes) ? userFeedback.allowedFileTypes : [];
    }

    function getMaxFileSizeBytes() {
        if (typeof userFeedback === 'undefined') {
            return 5 * 1024 * 1024;
        }
        var maxBytes = parseInt(userFeedback.maxFileSizeBytes, 10);
        if (isNaN(maxBytes) || maxBytes <= 0) {
            maxBytes = 5 * 1024 * 1024;
        }
        return maxBytes;
    }

    // Clipboard paste handler - Capture pasted images
    $(document).on('paste', '.user-feedback-form, #user-feedback-quick-form', function(e) {
        var items = (e.clipboardData || e.originalEvent.clipboardData).items;
        
        for (var i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image') !== -1) {
                var blob = items[i].getAsFile();
                var $form = $(e.target).closest('form, .user-feedback-form-container, .user-feedback-quick-content');
                var $fileInput = $form.find('.user-feedback-file-input');
                var $preview = $form.find('.user-feedback-file-preview, .user-feedback-file-preview-quick');
                
                // Create a File object from the blob
                var fileName = 'pasted-image-' + Date.now() + '.png';
                window.pastedFile = new File([blob], fileName, { type: blob.type });
                
                // Show preview
                var reader = new FileReader();
                reader.onload = function(event) {
                    $preview.find('img').attr('src', event.target.result);
                    $preview.show();
                    
                    // Show notification
                    var $container = $form.closest('.user-feedback-form-container, .user-feedback-quick-content');
                    var $message = $container.find('.user-feedback-message');
                    $message.removeClass('error').addClass('success')
                           .text('Image pasted from clipboard! Click submit when ready.')
                           .show()
                           .delay(3000)
                           .fadeOut();
                };
                reader.readAsDataURL(blob);
                
                e.preventDefault();
                break;
            }
        }
    });
    
    // File input change handler - Show preview
    $('.user-feedback-file-input').on('change', function() {
        // Clear any pasted file when user selects a new file
        window.pastedFile = null;
        var $input = $(this);
        var $container = $input.closest('.user-feedback-field, .user-feedback-form-container, .user-feedback-quick-content');
        var $preview = $container.find('.user-feedback-file-preview, .user-feedback-file-preview-quick');
        var file = this.files[0];
        
        if (file) {
            // Validate file type
            var allowedTypes = getAllowedMimeTypes();
            if (allowedTypes.length && allowedTypes.indexOf(file.type) === -1) {
                var allowedExtensions = getAllowedFileTypes().join(', ');
                alert('Please select an allowed image type (' + (allowedExtensions || 'JPEG, PNG, GIF, WebP') + ')');
                $input.val('');
                return;
            }
            
            // Validate file size
            var maxSize = getMaxFileSizeBytes();
            if (file.size > maxSize) {
                var maxMb = Math.round((maxSize / 1024 / 1024) * 10) / 10;
                alert('File size exceeds maximum allowed size (' + maxMb + ' MB)');
                $input.val('');
                return;
            }
            
            // Show preview
            var reader = new FileReader();
            reader.onload = function(e) {
                $preview.find('img').attr('src', e.target.result);
                $preview.show();
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Remove file button
    $(document).on('click', '.user-feedback-remove-file, .user-feedback-remove-file-quick', function() {
        var $button = $(this);
        var $container = $button.closest('.user-feedback-field, .user-feedback-form-container, .user-feedback-quick-content');
        var $preview = $button.closest('.user-feedback-file-preview, .user-feedback-file-preview-quick');
        var $input = $container.find('.user-feedback-file-input');
        
        $input.val('');
        $preview.hide();
        $preview.find('img').attr('src', '');
        uploadedAttachmentId = 0;
        window.pastedFile = null;
    });
    
    // Function to upload file via AJAX
    // Expose globally so it can be accessed from different scopes
    window.uploadScreenshot = function uploadScreenshot($fileInput, callback) {
        var fileToUpload = null;
        
        // Check for pasted file first
        if (window.pastedFile) {
            fileToUpload = window.pastedFile;
        }
        // Then check file input
        else if ($fileInput && $fileInput.length > 0 && $fileInput[0] && $fileInput[0].files && $fileInput[0].files.length > 0) {
            fileToUpload = $fileInput[0].files[0];
        }
        
        // No file to upload
        if (!fileToUpload) {
            callback(null);
            return;
        }
        
        var formData = new FormData();
        formData.append('action', 'user_feedback_upload_screenshot');
        formData.append('nonce', userFeedback.nonce);
        formData.append('screenshot', fileToUpload);
        
        $.ajax({
            url: userFeedback.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    callback(response.data.attachment_id);
                } else {
                    alert('Upload failed: ' + response.data.message);
                    callback(null);
                }
            },
            error: function() {
                alert('Upload error. Please try again.');
                callback(null);
            }
        });
    }
    
    // ===== FRONTEND: SUBMISSION FORM =====
    $('.user-feedback-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $container = $form.closest('.user-feedback-form-container');
        var $submitButton = $form.find('.user-feedback-submit');
        var $message = $form.find('.user-feedback-message');
        
        // Check if user is logged in
        if (!userFeedback.isLoggedIn) {
            $message.removeClass('success').addClass('error').text('You must be logged in to submit feedback.').show();
            return;
        }
        
        var $fileInput = $form.find('.user-feedback-file-input');
        
        // Validate
        if (!$form.find('[name="subject"]').val() || !$form.find('[name="message"]').val()) {
            $message.removeClass('success').addClass('error').text('Please fill in all required fields.').show();
            return;
        }
        
        // Disable submit button
        $submitButton.prop('disabled', true).text('Uploading...');
        $message.hide();
        
        // Upload file first if present
        uploadScreenshot($fileInput, function(attachmentId) {
            $submitButton.text('Submitting...');
            
            // Get form data
            var data = {
                action: 'user_feedback_submit',
                nonce: userFeedback.nonce,
                type: $container.data('type'),
                context_id: $container.data('context-id') || '',
                subject: $form.find('[name="subject"]').val(),
                message: $form.find('[name="message"]').val(),
                attachment_id: attachmentId || 0
            };
            
            // Submit via AJAX
            $.ajax({
                url: userFeedback.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        $message.removeClass('error').addClass('success').text(response.data.message).show();
                        $form[0].reset();
                        $('.user-feedback-file-preview').hide();
                        window.pastedFile = null;
                    } else {
                        $message.removeClass('success').addClass('error').text(response.data.message).show();
                    }
                },
                error: function() {
                    $message.removeClass('success').addClass('error').text('An error occurred. Please try again.').show();
                },
                complete: function() {
                    $submitButton.prop('disabled', false).text(
                        $submitButton.data('original-text') || 'Submit'
                    );
                }
            });
        });
    });
    
    // Store original button text
    $('.user-feedback-submit').each(function() {
        $(this).data('original-text', $(this).text());
    });
    
    // ===== ADMIN: DASHBOARD =====
    if (userFeedback.isAdmin) {
        
        // Reply Button
        var currentSubmissionId = null;
        
        $(document).on('click', '.reply-button', function() {
            currentSubmissionId = $(this).data('id');
            $('#reply-modal').fadeIn();
            $('#reply-message').val('');
            $('#canned-response-selector').val('');
        });
        
        // Close Modal
        $('.modal-close, #cancel-reply-button, #cancel-status-button').on('click', function() {
            $('.user-feedback-modal').fadeOut();
            currentSubmissionId = null;
        });
        
        // Close modal when clicking outside
        $('.user-feedback-modal').on('click', function(e) {
            if ($(e.target).hasClass('user-feedback-modal')) {
                $(this).fadeOut();
                currentSubmissionId = null;
            }
        });
        
        // Canned Response Selection
        $('#canned-response-selector').on('change', function() {
            var responseId = $(this).val();
            if (!responseId) return;
            
            // Get canned response content via AJAX
            $.ajax({
                url: userFeedback.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'user_feedback_get_canned_response',
                    nonce: userFeedback.nonce,
                    response_id: responseId
                },
                success: function(response) {
                    if (response.success) {
                        $('#reply-message').val(response.data.content);
                    }
                }
            });
        });
        
        // Send Reply Button
        $('#send-reply-button').on('click', function() {
            var reply = $('#reply-message').val().trim();
            
            if (!reply) {
                alert('Please enter a reply message.');
                return;
            }
            
            var $button = $(this);
            $button.prop('disabled', true).text('Sending...');
            
            $.ajax({
                url: userFeedback.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'user_feedback_send_reply',
                    nonce: userFeedback.nonce,
                    submission_id: currentSubmissionId,
                    reply: reply
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        $('#reply-modal').fadeOut();
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Send Reply');
                }
            });
        });
        
        // Status Selector
        var currentStatus = null;
        
        $(document).on('change', '.status-selector', function() {
            var $select = $(this);
            var submissionId = $select.data('id');
            var newStatus = $select.val();
            
            if (!newStatus) {
                return;
            }
            
            currentSubmissionId = submissionId;
            currentStatus = newStatus;
            
            // Show status update modal
            $('#status-display').text(newStatus.replace('_', ' ').toUpperCase());
            
            // Show resolution notes section if status is resolved
            if (newStatus === 'resolved') {
                $('#resolution-notes-section').show();
                $('#resolution-notes').val('');
            } else {
                $('#resolution-notes-section').hide();
            }
            
            $('#status-modal').fadeIn();
            
            // Reset select
            $select.val('');
        });
        
        // Update Status Button
        $('#update-status-button').on('click', function() {
            var $button = $(this);
            $button.prop('disabled', true).text('Updating...');
            
            var data = {
                action: 'user_feedback_update_status',
                nonce: userFeedback.nonce,
                submission_id: currentSubmissionId,
                status: currentStatus
            };
            
            if (currentStatus === 'resolved') {
                data.resolution_notes = $('#resolution-notes').val().trim();
            }
            
            $.ajax({
                url: userFeedback.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        $('#status-modal').fadeOut();
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Update Status');
                }
            });
        });
        
        // Delete Button
        $(document).on('click', '.delete-button', function() {
            if (!confirm('Are you sure you want to delete this submission? This action cannot be undone.')) {
                return;
            }
            
            var submissionId = $(this).data('id');
            var $item = $(this).closest('.submission-item');
            
            $.ajax({
                url: userFeedback.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'user_feedback_delete_submission',
                    nonce: userFeedback.nonce,
                    submission_id: submissionId
                },
                success: function(response) {
                    if (response.success) {
                        $item.fadeOut(function() {
                            $(this).remove();
                        });
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        });
    }
});

// AJAX handler for getting canned response content
jQuery(document).ready(function($) {
    if (typeof userFeedback !== 'undefined' && userFeedback.isAdmin) {
        // Register AJAX action for getting canned response
        $(document).on('user_feedback_get_canned_response', function() {
            // This is handled by the change event above
        });
    }
});

// ===== QUICK FEEDBACK COLLECTOR =====
jQuery(document).ready(function($) {
    
    // Function to collect technical metadata
    // Expose globally so menu links can access it
    window.collectTechnicalMetadata = function collectTechnicalMetadata() {
        var metadata = {
            pageUrl: window.location.href,
            pageTitle: document.title,
            referrer: document.referrer || 'Direct',
            timestamp: new Date().toISOString(),
            userAgent: navigator.userAgent,
            screenWidth: screen.width,
            screenHeight: screen.height,
            viewportWidth: window.innerWidth,
            viewportHeight: window.innerHeight,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            language: navigator.language,
            platform: navigator.platform
        };
        
        // Add console errors if capture is enabled
        if (typeof userFeedback !== 'undefined' && 
            userFeedback.quickCollector && 
            userFeedback.quickCollector.captureErrors && 
            userFeedbackConsoleErrors.length > 0) {
            metadata.consoleErrors = userFeedbackConsoleErrors;
        }
        
        return metadata;
    }
    
    // Function to display technical metadata
    // Expose globally so menu links can access it
    window.displayTechnicalMetadata = function displayTechnicalMetadata(metadata) {
        var html = '';
        
        html += '<div class="technical-detail-item">';
        html += '<span class="technical-detail-label">Page URL:</span>';
        html += '<span class="technical-detail-value">' + escapeHtml(metadata.pageUrl) + '</span>';
        html += '</div>';
        
        html += '<div class="technical-detail-item">';
        html += '<span class="technical-detail-label">Page Title:</span>';
        html += '<span class="technical-detail-value">' + escapeHtml(metadata.pageTitle) + '</span>';
        html += '</div>';
        
        if (metadata.referrer && metadata.referrer !== 'Direct') {
            html += '<div class="technical-detail-item">';
            html += '<span class="technical-detail-label">Referrer:</span>';
            html += '<span class="technical-detail-value">' + escapeHtml(metadata.referrer) + '</span>';
            html += '</div>';
        }
        
        html += '<div class="technical-detail-item">';
        html += '<span class="technical-detail-label">Browser:</span>';
        html += '<span class="technical-detail-value">' + escapeHtml(metadata.userAgent) + '</span>';
        html += '</div>';
        
        html += '<div class="technical-detail-item">';
        html += '<span class="technical-detail-label">Screen Size:</span>';
        html += '<span class="technical-detail-value">' + metadata.screenWidth + ' x ' + metadata.screenHeight + '</span>';
        html += '</div>';
        
        html += '<div class="technical-detail-item">';
        html += '<span class="technical-detail-label">Viewport Size:</span>';
        html += '<span class="technical-detail-value">' + metadata.viewportWidth + ' x ' + metadata.viewportHeight + '</span>';
        html += '</div>';
        
        html += '<div class="technical-detail-item">';
        html += '<span class="technical-detail-label">Timezone:</span>';
        html += '<span class="technical-detail-value">' + escapeHtml(metadata.timezone) + '</span>';
        html += '</div>';
        
        html += '<div class="technical-detail-item">';
        html += '<span class="technical-detail-label">Timestamp:</span>';
        html += '<span class="technical-detail-value">' + escapeHtml(metadata.timestamp) + '</span>';
        html += '</div>';
        
        // Add console errors if present
        if (metadata.consoleErrors && metadata.consoleErrors.length > 0) {
            html += '<div class="technical-detail-item">';
            html += '<span class="technical-detail-label">Console Errors:</span>';
            html += '<div class="console-errors-list">';
            metadata.consoleErrors.forEach(function(error) {
                html += '<div class="console-error-item">';
                html += escapeHtml(error.message);
                if (error.source && error.line) {
                    html += '<br><small>' + escapeHtml(error.source) + ':' + error.line + '</small>';
                }
                html += '</div>';
            });
            html += '</div>';
            html += '</div>';
        }
        
        return html;
    }
    
    // HTML escape function
    // Expose globally so menu links can access it
    window.escapeHtml = function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Open Quick Feedback Modal when admin bar button is clicked
    function openQuickFeedbackModal() {
        $('#user-feedback-quick-modal').fadeIn();

        if (typeof userFeedback !== 'undefined' &&
            userFeedback.quickCollector &&
            userFeedback.quickCollector.showDetails) {
            var metadata = collectTechnicalMetadata();
            var html = displayTechnicalMetadata(metadata);
            $('#ufq-technical-preview').html(html);
        }

        $('#ufq-subject').focus();
    }

    $(document).on('click', '#wp-admin-bar-user-feedback-quick a', function(e) {
        e.preventDefault();
        openQuickFeedbackModal();
    });

    $(document).on('click', '.user-feedback-floating-button', function(e) {
        e.preventDefault();
        openQuickFeedbackModal();
    });
    
    // Close modal
    $('#user-feedback-quick-modal .modal-close, .user-feedback-cancel').on('click', function() {
        $('#user-feedback-quick-modal').fadeOut();
        $('#user-feedback-quick-form')[0].reset();
        $('.user-feedback-message').hide();
    });
    
    // Close modal when clicking outside
    $('#user-feedback-quick-modal').on('click', function(e) {
        if ($(e.target).hasClass('user-feedback-quick-modal')) {
            $(this).fadeOut();
            $('#user-feedback-quick-form')[0].reset();
            $('.user-feedback-message').hide();
        }
    });
    
    // Toggle technical details
    $('.technical-details-toggle').on('click', function() {
        $(this).toggleClass('active');
        $('.technical-details-content').slideToggle();
    });
    
    // Submit quick feedback form
    $('#user-feedback-quick-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitButton = $form.find('.user-feedback-submit');
        var $message = $form.find('.user-feedback-message');
        var $fileInput = $('#ufq-screenshot');
        
        // Validate
        if (!$('#ufq-subject').val() || !$('#ufq-message').val()) {
            $message.removeClass('success').addClass('error')
                   .text('Please fill in all required fields.').show();
            return;
        }
        
        // Disable submit button
        $submitButton.prop('disabled', true).text('Uploading...');
        $message.hide();
        
        // Upload file first if present
        uploadScreenshot($fileInput, function(attachmentId) {
            $submitButton.text('Submitting...');
            
            // Collect form data
            var formData = {
                action: 'user_feedback_submit',
                nonce: userFeedback.nonce,
                type: $('#ufq-type').val(),
                subject: $('#ufq-subject').val(),
                message: $('#ufq-message').val(),
                context_id: '', // Quick collector doesn't use context_id
                metadata: JSON.stringify(collectTechnicalMetadata()),
                attachment_id: attachmentId || 0
            };
            
            // Submit via AJAX
            $.ajax({
                url: userFeedback.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $message.removeClass('error').addClass('success')
                               .text(response.data.message).show();
                        $form[0].reset();
                        $('.user-feedback-file-preview-quick').hide();
                        window.pastedFile = null;
                        
                        // Close modal after 2 seconds
                        setTimeout(function() {
                            $('#user-feedback-quick-modal').fadeOut();
                            $message.hide();
                        }, 2000);
                    } else {
                        $message.removeClass('success').addClass('error')
                               .text(response.data.message).show();
                    }
                },
                error: function() {
                    $message.removeClass('success').addClass('error')
                           .text('An error occurred. Please try again.').show();
                },
                complete: function() {
                    $submitButton.prop('disabled', false).text('Submit Feedback');
                }
            });
        });
    });
});

