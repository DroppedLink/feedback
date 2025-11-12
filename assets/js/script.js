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
    
    // ===== FORM BUILDER INTERFACE =====
    
    // Category Form Submission
    $('#userfeedback-category-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $message = $form.find('.userfeedback-form-message');
        var $submit = $form.find('button[type="submit"]');
        
        $submit.prop('disabled', true);
        $message.removeClass('success error').hide();
        
        $.ajax({
            url: userFeedback.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'userfeedback_save_category',
                nonce: userFeedback.nonce,
                category_id: $form.find('input[name="category_id"]').val() || '',
                name: $form.find('input[name="name"]').val(),
                slug: $form.find('input[name="slug"]').val(),
                description: $form.find('textarea[name="description"]').val()
            },
            success: function(response) {
                if (response.success) {
                    $message.addClass('success').text(response.data.message).show();
                    setTimeout(function() {
                        window.location.href = '?page=user-feedback-form-builder&tab=categories';
                    }, 1500);
                } else {
                    $message.addClass('error').text(response.data.message).show();
                    $submit.prop('disabled', false);
                }
            },
            error: function() {
                $message.addClass('error').text('An error occurred. Please try again.').show();
                $submit.prop('disabled', false);
            }
        });
    });
    
    // Category Deletion
    $('.userfeedback-delete-category').on('click', function() {
        if (!confirm('Are you sure you want to delete this category?')) {
            return;
        }
        
        var categoryId = $(this).data('id');
        var $button = $(this);
        
        $button.prop('disabled', true);
        
        $.ajax({
            url: userFeedback.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'userfeedback_delete_category',
                nonce: userFeedback.nonce,
                category_id: categoryId
            },
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert(response.data.message);
                    $button.prop('disabled', false);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $button.prop('disabled', false);
            }
        });
    });
    
    // Form Deletion
    $('.userfeedback-delete-form').on('click', function() {
        if (!confirm('Are you sure you want to delete this form?')) {
            return;
        }
        
        var formId = $(this).data('id');
        var $button = $(this);
        
        $button.prop('disabled', true);
        
        $.ajax({
            url: userFeedback.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'userfeedback_delete_form',
                nonce: userFeedback.nonce,
                form_id: formId
            },
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert(response.data.message);
                    $button.prop('disabled', false);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $button.prop('disabled', false);
            }
        });
    });
    
    // Category filter changes form dropdown in submissions dashboard
    $('#filter-category').on('change', function() {
        var categoryId = $(this).val();
        var $formSelect = $('#filter-form');
        
        $formSelect.html('<option value="">Loading...</option>').prop('disabled', true);
        
        if (!categoryId) {
            $formSelect.html('<option value="">All Forms</option>').prop('disabled', false);
            // Auto-submit the form to apply the filter
            $('#submissions-filter-form').submit();
            return;
        }
        
        $.ajax({
            url: userFeedback.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'userfeedback_get_forms_by_category',
                nonce: $(this).data('nonce'),
                category_id: categoryId
            },
            success: function(response) {
                $formSelect.html('<option value="">All Forms</option>');
                if (response.success && response.data.forms && response.data.forms.length) {
                    $.each(response.data.forms, function(i, form) {
                        $formSelect.append('<option value="' + form.id + '">' + form.name + '</option>');
                    });
                }
                $formSelect.prop('disabled', false);
                // Auto-submit the form to apply the filter
                $('#submissions-filter-form').submit();
            },
            error: function() {
                $formSelect.html('<option value="">All Forms</option>').prop('disabled', false);
            }
        });
    });
    
    // Form shortcode preview update
    $('#form-shortcode').on('input', function() {
        $('#shortcode-preview').text($(this).val() || 'your-form');
    });
    
    // Auto-generate slug from name
    $('#form-name, #category-name').on('input', function() {
        var $name = $(this);
        var $slug = $name.closest('form').find('#form-slug, #category-slug');
        
        if (!$slug.val() || $slug.data('auto-generated')) {
            var slug = $name.val()
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
            $slug.val(slug).data('auto-generated', true);
        }
    });
    
    $('#form-slug, #category-slug').on('input', function() {
        $(this).data('auto-generated', false);
    });
    
    // Auto-generate shortcode from name
    $('#form-name').on('input', function() {
        var $name = $(this);
        var $shortcode = $('#form-shortcode');
        
        if (!$shortcode.val() || $shortcode.data('auto-generated')) {
            var shortcode = $name.val()
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
            $shortcode.val(shortcode).data('auto-generated', true);
            $('#shortcode-preview').text(shortcode || 'your-form');
        }
    });
    
    $('#form-shortcode').on('input', function() {
        $(this).data('auto-generated', false);
    });
    
    // ===== FORM FIELD CONFIGURATION EDITOR =====
    
    var formFields = [];
    var fieldIdCounter = 0;
    
    // Initialize from existing data if editing
    if (typeof userfeedbackInitialFields !== 'undefined' && userfeedbackInitialFields && userfeedbackInitialFields.fields) {
        formFields = userfeedbackInitialFields.fields;
        fieldIdCounter = formFields.length;
        renderFields();
    }
    
    // Add field buttons
    $('.userfeedback-add-field-buttons button').on('click', function() {
        var fieldType = $(this).data('field-type');
        addField(fieldType);
    });
    
    function addField(type) {
        var field = {
            id: fieldIdCounter++,
            type: type,
            name: type + '_' + fieldIdCounter,
            label: getFieldTypeLabel(type),
            required: false
        };
        
        if (type === 'select') {
            field.options = ['Option 1', 'Option 2'];
        } else if (type === 'textarea') {
            field.rows = 6;
        }
        
        formFields.push(field);
        renderFields();
        updateFieldConfigJSON();
    }
    
    function getFieldTypeLabel(type) {
        var labels = {
            'select': 'Dropdown Field',
            'text': 'Text Field',
            'textarea': 'Text Area',
            'file': 'File Upload'
        };
        return labels[type] || type;
    }
    
    function renderFields() {
        var $container = $('#userfeedback-fields-container');
        $container.empty();
        
        if (formFields.length === 0) {
            $container.html('<p class="userfeedback-empty-message">No fields added yet. Click the buttons below to add fields.</p>');
            return;
        }
        
        $.each(formFields, function(index, field) {
            var $fieldHtml = $('<div class="userfeedback-field-config" data-field-id="' + field.id + '" draggable="true"></div>');
            
            // Header
            var $header = $('<div class="userfeedback-field-header"></div>');
            
            // Drag handle
            var $dragHandle = $('<span class="userfeedback-drag-handle" title="Drag to reorder">⋮⋮</span>');
            $header.append($dragHandle);
            
            $header.append('<span class="userfeedback-field-type">' + getFieldTypeLabel(field.type) + '</span>');
            
            var $controls = $('<div class="userfeedback-field-controls"></div>');
            if (index > 0) {
                $controls.append('<button type="button" class="button button-small move-up" title="Move up">↑</button>');
            }
            if (index < formFields.length - 1) {
                $controls.append('<button type="button" class="button button-small move-down" title="Move down">↓</button>');
            }
            $controls.append('<button type="button" class="button button-small button-link-delete remove-field">Remove</button>');
            $header.append($controls);
            
            $fieldHtml.append($header);
            
            // Body
            var $body = $('<div class="userfeedback-field-body"></div>');
            
            $body.append(
                '<div class="userfeedback-field-group">' +
                '   <label>Field Name (internal)</label>' +
                '   <input type="text" class="field-name" value="' + field.name + '" placeholder="field_name">' +
                '</div>'
            );
            
            $body.append(
                '<div class="userfeedback-field-group">' +
                '   <label>Label (shown to users)</label>' +
                '   <input type="text" class="field-label" value="' + field.label + '" placeholder="Field Label">' +
                '</div>'
            );
            
            $body.append(
                '<div class="userfeedback-field-group">' +
                '   <label><input type="checkbox" class="field-required" ' + (field.required ? 'checked' : '') + '> Required</label>' +
                '</div>'
            );
            
            if (field.type === 'select') {
                var optionsHtml = '<div class="userfeedback-field-options"><label>Options:</label><div class="options-list">';
                $.each(field.options || [], function(i, option) {
                    optionsHtml += '<div class="userfeedback-option-item">' +
                        '<input type="text" value="' + option + '" class="option-value" placeholder="Option ' + (i + 1) + '">' +
                        '<button type="button" class="button button-small remove-option">×</button>' +
                        '</div>';
                });
                optionsHtml += '</div><button type="button" class="button button-small add-option">+ Add Option</button></div>';
                $body.append(optionsHtml);
            } else if (field.type === 'textarea') {
                $body.append(
                    '<div class="userfeedback-field-group">' +
                    '   <label>Rows</label>' +
                    '   <input type="number" class="field-rows" value="' + (field.rows || 6) + '" min="2" max="20">' +
                    '</div>'
                );
            } else if (field.type === 'text' || field.type === 'textarea') {
                $body.append(
                    '<div class="userfeedback-field-group">' +
                    '   <label>Placeholder</label>' +
                    '   <input type="text" class="field-placeholder" value="' + (field.placeholder || '') + '" placeholder="Optional placeholder text">' +
                    '</div>'
                );
            }
            
            $fieldHtml.append($body);
            $container.append($fieldHtml);
        });
        
        attachFieldEventHandlers();
        attachDragDropHandlers();
    }
    
    function attachDragDropHandlers() {
        var dragSrcEl = null;
        
        $('.userfeedback-field-config').off('dragstart dragend dragover drop dragleave').each(function() {
            var $el = $(this);
            
            // Dragstart
            $el.on('dragstart', function(e) {
                dragSrcEl = this;
                e.originalEvent.dataTransfer.effectAllowed = 'move';
                e.originalEvent.dataTransfer.setData('text/html', this.innerHTML);
                $(this).addClass('dragging');
            });
            
            // Dragend
            $el.on('dragend', function(e) {
                $(this).removeClass('dragging');
                $('.userfeedback-field-config').removeClass('over');
            });
            
            // Dragover
            $el.on('dragover', function(e) {
                if (e.preventDefault) {
                    e.preventDefault();
                }
                e.originalEvent.dataTransfer.dropEffect = 'move';
                return false;
            });
            
            // Dragenter
            $el.on('dragenter', function(e) {
                if (this !== dragSrcEl) {
                    $(this).addClass('over');
                }
            });
            
            // Dragleave
            $el.on('dragleave', function(e) {
                $(this).removeClass('over');
            });
            
            // Drop
            $el.on('drop', function(e) {
                if (e.stopPropagation) {
                    e.stopPropagation();
                }
                
                if (dragSrcEl !== this) {
                    var srcId = $(dragSrcEl).data('field-id');
                    var targetId = $(this).data('field-id');
                    
                    var srcIndex = formFields.findIndex(f => f.id === srcId);
                    var targetIndex = formFields.findIndex(f => f.id === targetId);
                    
                    if (srcIndex !== -1 && targetIndex !== -1) {
                        // Remove from old position
                        var movedItem = formFields.splice(srcIndex, 1)[0];
                        // Insert at new position
                        formFields.splice(targetIndex, 0, movedItem);
                        
                        renderFields();
                        updateFieldConfigJSON();
                    }
                }
                
                $('.userfeedback-field-config').removeClass('over');
                return false;
            });
        });
    }
    
    function attachFieldEventHandlers() {
        // Update field data on input
        $('.field-name, .field-label, .field-placeholder, .field-rows').off('input').on('input', function() {
            updateFieldConfigJSON();
        });
        
        $('.field-required').off('change').on('change', function() {
            updateFieldConfigJSON();
        });
        
        // Move up
        $('.move-up').off('click').on('click', function() {
            var $fieldConfig = $(this).closest('.userfeedback-field-config');
            var fieldId = $fieldConfig.data('field-id');
            var index = formFields.findIndex(f => f.id === fieldId);
            
            if (index > 0) {
                [formFields[index], formFields[index - 1]] = [formFields[index - 1], formFields[index]];
                renderFields();
                updateFieldConfigJSON();
            }
        });
        
        // Move down
        $('.move-down').off('click').on('click', function() {
            var $fieldConfig = $(this).closest('.userfeedback-field-config');
            var fieldId = $fieldConfig.data('field-id');
            var index = formFields.findIndex(f => f.id === fieldId);
            
            if (index < formFields.length - 1) {
                [formFields[index], formFields[index + 1]] = [formFields[index + 1], formFields[index]];
                renderFields();
                updateFieldConfigJSON();
            }
        });
        
        // Remove field
        $('.remove-field').off('click').on('click', function() {
            if (!confirm('Remove this field?')) return;
            
            var $fieldConfig = $(this).closest('.userfeedback-field-config');
            var fieldId = $fieldConfig.data('field-id');
            formFields = formFields.filter(f => f.id !== fieldId);
            renderFields();
            updateFieldConfigJSON();
        });
        
        // Add option
        $('.add-option').off('click').on('click', function() {
            var $fieldConfig = $(this).closest('.userfeedback-field-config');
            var fieldId = $fieldConfig.data('field-id');
            var field = formFields.find(f => f.id === fieldId);
            
            if (!field.options) field.options = [];
            field.options.push('New Option');
            renderFields();
            updateFieldConfigJSON();
        });
        
        // Remove option
        $('.remove-option').off('click').on('click', function() {
            $(this).closest('.userfeedback-option-item').remove();
            updateFieldConfigJSON();
        });
        
        // Update option value
        $('.option-value').off('input').on('input', function() {
            updateFieldConfigJSON();
        });
    }
    
    function updateFieldConfigJSON() {
        // Update formFields array from DOM
        $('.userfeedback-field-config').each(function() {
            var $field = $(this);
            var fieldId = $field.data('field-id');
            var field = formFields.find(f => f.id === fieldId);
            
            if (field) {
                field.name = $field.find('.field-name').val();
                field.label = $field.find('.field-label').val();
                field.required = $field.find('.field-required').is(':checked');
                
                if (field.type === 'select') {
                    field.options = [];
                    $field.find('.option-value').each(function() {
                        var val = $(this).val().trim();
                        if (val) field.options.push(val);
                    });
                } else if (field.type === 'textarea') {
                    field.rows = parseInt($field.find('.field-rows').val()) || 6;
                }
                
                var placeholder = $field.find('.field-placeholder').val();
                if (placeholder) {
                    field.placeholder = placeholder;
                }
            }
        });
        
        // Update hidden field
        $('#field-config-json').val(JSON.stringify({fields: formFields}));
    }
    
    // Form Editor Submission
    $('#userfeedback-form-editor').on('submit', function(e) {
        e.preventDefault();
        
        updateFieldConfigJSON();
        
        var $form = $(this);
        var $message = $form.find('.userfeedback-form-message');
        var $submit = $form.find('button[type="submit"]');
        
        $submit.prop('disabled', true);
        $message.removeClass('success error').hide();
        
        $.ajax({
            url: userFeedback.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'userfeedback_save_form',
                nonce: userFeedback.nonce,
                form_id: $form.find('input[name="form_id"]').val() || '',
                category_id: $form.find('select[name="category_id"]').val(),
                name: $form.find('input[name="name"]').val(),
                slug: $form.find('input[name="slug"]').val(),
                shortcode: $form.find('input[name="shortcode"]').val(),
                description: $form.find('textarea[name="description"]').val(),
                is_active: $form.find('input[name="is_active"]').is(':checked') ? 1 : 0,
                field_config: $form.find('#field-config-json').val()
            },
            success: function(response) {
                if (response.success) {
                    $message.addClass('success').text(response.data.message).show();
                    setTimeout(function() {
                        var categoryId = $form.find('select[name="category_id"]').val();
                        window.location.href = '?page=user-feedback-form-builder&tab=forms&category=' + categoryId;
                    }, 1500);
                } else {
                    $message.addClass('error').text(response.data.message).show();
                    $submit.prop('disabled', false);
                }
            },
            error: function() {
                $message.addClass('error').text('An error occurred. Please try again.').show();
                $submit.prop('disabled', false);
            }
        });
    });
    
    // ===== DYNAMIC FORM SUBMISSION =====
    
    $('.userfeedback-dynamic-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $container = $form.closest('.userfeedback-form-container');
        var $submitButton = $form.find('.userfeedback-submit-btn');
        var $message = $form.find('.userfeedback-form-message');
        
        $submitButton.prop('disabled', true).text('Submitting...');
        $message.removeClass('error success').hide();
        
        // Collect form data
        var formData = {};
        $form.find('[data-field-name]').each(function() {
            var $field = $(this);
            var fieldName = $field.data('field-name');
            var value = $field.val();
            
            if (value) {
                formData[fieldName] = value;
            }
        });
        
        // Prepare submission
        var submissionData = {
            action: 'user_feedback_submit',
            nonce: userFeedback.nonce,
            form_id: $container.data('form-id'),
            subject: formData.subject || 'Form Submission',
            message: formData.description || formData.message || 'N/A',
            context_id: $container.data('context-id') || '',
            form_data: JSON.stringify(formData)
        };
        
        $.ajax({
            url: userFeedback.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: submissionData,
            success: function(response) {
                if (response.success) {
                    $message.addClass('success').text(response.data.message).show();
                    $form[0].reset();
                    
                    setTimeout(function() {
                        $message.fadeOut();
                    }, 5000);
                } else {
                    $message.addClass('error').text(response.data.message).show();
                }
            },
            error: function() {
                $message.addClass('error').text('An error occurred. Please try again.').show();
            },
            complete: function() {
                $submitButton.prop('disabled', false).text('Submit Feedback');
            }
        });
    });
    
    // Category filter in Forms tab
    $('#filter-category').on('change', function() {
        var categoryId = $(this).val();
        var currentUrl = new URL(window.location.href);
        
        if (categoryId) {
            currentUrl.searchParams.set('category', categoryId);
        } else {
            currentUrl.searchParams.delete('category');
        }
        
        window.location.href = currentUrl.toString();
    });
    
    // ===== COLLAPSIBLE SUBMISSIONS =====
    
    $('.submission-header').on('click', function(e) {
        // Don't collapse if clicking on buttons or links
        if ($(e.target).is('button, a, select, input, .button, .status-selector')) {
            return;
        }
        
        var $item = $(this).closest('.submission-item');
        $item.toggleClass('expanded');
    });
    
    // ===== COPY SHORTCODE BUTTON =====
    
    $(document).on('click', '.userfeedback-copy-shortcode', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var shortcode = $(this).data('shortcode');
        var $button = $(this);
        
        // Create temporary input to copy from
        var $temp = $('<input>');
        $('body').append($temp);
        $temp.val(shortcode).select();
        
        try {
            document.execCommand('copy');
            $button.text('✓ Copied!').addClass('copied');
            
            setTimeout(function() {
                $button.text('Copy').removeClass('copied');
            }, 2000);
        } catch (err) {
            alert('Failed to copy. Please copy manually: ' + shortcode);
        }
        
        $temp.remove();
    });
    
    // ===== INLINE QUICK REPLY =====
    
    $(document).on('click', '.quick-reply-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $item = $(this).closest('.submission-item');
        var $replyBox = $item.find('.quick-reply-box');
        
        if ($replyBox.is(':visible')) {
            $replyBox.slideUp(200);
            $(this).text('Quick Reply');
        } else {
            $replyBox.slideDown(200);
            $(this).text('Cancel Reply');
            $replyBox.find('textarea').focus();
        }
    });
    
    $(document).on('click', '.quick-reply-submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $item = $(this).closest('.submission-item');
        var $textarea = $item.find('.quick-reply-textarea');
        var submissionId = $item.data('id');
        var reply = $textarea.val();
        var $button = $(this);
        var $message = $item.find('.quick-reply-message');
        
        if (!reply.trim()) {
            $message.text('Please enter a reply.').addClass('error').show();
            return;
        }
        
        $button.prop('disabled', true).text('Sending...');
        $message.removeClass('error success').hide();
        
        $.ajax({
            url: userFeedback.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'user_feedback_send_reply',
                nonce: userFeedback.nonce,
                submission_id: submissionId,
                reply: reply
            },
            success: function(response) {
                if (response.success) {
                    $message.text(response.data.message).addClass('success').show();
                    $textarea.val('');
                    
                    // Add reply to display
                    var $replyDisplay = $('<div class="submission-reply"><strong>Your Reply:</strong><div>' + 
                                        reply.replace(/\n/g, '<br>') + '</div></div>');
                    $item.find('.submission-message').after($replyDisplay);
                    
                    setTimeout(function() {
                        $item.find('.quick-reply-box').slideUp(200);
                        $item.find('.quick-reply-toggle').text('Quick Reply');
                        $message.hide();
                    }, 2000);
                } else {
                    $message.text(response.data.message).addClass('error').show();
                }
            },
            error: function() {
                $message.text('An error occurred. Please try again.').addClass('error').show();
            },
            complete: function() {
                $button.prop('disabled', false).text('Send Reply');
            }
        });
    });
    
    // ===== BULK ACTIONS =====
    
    $('#bulk-select-all').on('change', function() {
        $('.submission-checkbox').prop('checked', $(this).is(':checked'));
        updateBulkActionButton();
    });
    
    $(document).on('change', '.submission-checkbox', function(e) {
        e.stopPropagation();
        updateBulkActionButton();
        
        var allChecked = $('.submission-checkbox:checked').length === $('.submission-checkbox').length;
        $('#bulk-select-all').prop('checked', allChecked);
    });
    
    function updateBulkActionButton() {
        var checkedCount = $('.submission-checkbox:checked').length;
        var $bulkBtn = $('#apply-bulk-action');
        
        if (checkedCount > 0) {
            $bulkBtn.prop('disabled', false).text('Apply to ' + checkedCount + ' selected');
        } else {
            $bulkBtn.prop('disabled', true).text('Bulk Action');
        }
    }
    
    $('#apply-bulk-action').on('click', function() {
        var action = $('#bulk-action-select').val();
        var checkedIds = [];
        
        $('.submission-checkbox:checked').each(function() {
            checkedIds.push($(this).val());
        });
        
        if (!action) {
            alert('Please select an action.');
            return;
        }
        
        if (checkedIds.length === 0) {
            alert('Please select at least one submission.');
            return;
        }
        
        if (action === 'delete' && !confirm('Are you sure you want to delete ' + checkedIds.length + ' submission(s)?')) {
            return;
        }
        
        var $button = $(this);
        $button.prop('disabled', true).text('Processing...');
        
        // Process each submission
        var processed = 0;
        var errors = 0;
        
        checkedIds.forEach(function(id) {
            var ajaxAction = action === 'delete' ? 'user_feedback_delete_submission' : 'user_feedback_update_status';
            var data = {
                action: ajaxAction,
                nonce: userFeedback.nonce,
                submission_id: id
            };
            
            if (action !== 'delete') {
                data.status = action;
            }
            
            $.ajax({
                url: userFeedback.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: data,
                success: function(response) {
                    if (response.success) {
                        processed++;
                    } else {
                        errors++;
                    }
                },
                error: function() {
                    errors++;
                },
                complete: function() {
                    if (processed + errors >= checkedIds.length) {
                        if (errors > 0) {
                            alert('Completed with ' + errors + ' errors. Refreshing...');
                        }
                        window.location.reload();
                    }
                }
            });
        });
    });
});

