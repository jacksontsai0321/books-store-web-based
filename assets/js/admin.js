/**
 * Admin Panel JavaScript
 * Handles admin-specific interactions
 */

$(document).ready(function() {
    // Auto-hide messages
    setTimeout(function() {
        $('.success-message, .error-messages, .info-message').fadeOut('slow');
    }, 5000);

    // Confirm delete actions
    $('.delete-form, .btn-danger').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
            e.preventDefault();
            return false;
        }
    });

    // Image preview for product uploads
    $('input[type="file"][name="image"]').on('change', function(e) {
        var file = e.target.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var preview = $('<img>').attr('src', e.target.result).css({
                    'max-width': '300px',
                    'margin-top': '10px',
                    'border-radius': '4px',
                    'border': '1px solid #ddd'
                });
                $(this).closest('.form-group').find('.image-preview').remove();
                $(this).closest('.form-group').append($('<div class="image-preview"></div>').append(preview));
            }.bind(this);
            reader.readAsDataURL(file);
        }
    });

    // Form validation
    $('form').on('submit', function(e) {
        var isValid = true;
        $(this).find('input[required], select[required], textarea[required]').each(function() {
            if ($(this).val().trim() === '') {
                isValid = false;
                $(this).css('border-color', '#e74c3c');
            } else {
                $(this).css('border-color', '#ddd');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields');
            return false;
        }
    });

    // Numeric input validation
    $('input[type="number"]').on('input', function() {
        var min = $(this).attr('min');
        var max = $(this).attr('max');
        var value = parseFloat($(this).val());
        
        if (min !== undefined && value < parseFloat(min)) {
            $(this).val(min);
        }
        if (max !== undefined && value > parseFloat(max)) {
            $(this).val(max);
        }
    });
});

