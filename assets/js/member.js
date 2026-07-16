/**
 * Member-specific JavaScript
 * Handles member profile and account interactions
 */

$(document).ready(function() {
    // Password strength indicator (optional)
    $('#new_password, #password').on('input', function() {
        var password = $(this).val();
        var strength = 0;
        
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
        if (password.match(/\d/)) strength++;
        if (password.match(/[^a-zA-Z\d]/)) strength++;
        
        // Could add visual indicator here
    });

    // Confirm password match
    $('#confirm_password').on('blur', function() {
        var password = $('#new_password').val();
        var confirm = $(this).val();
        
        if (password !== confirm) {
            $(this).css('border-color', '#e74c3c');
            alert('Passwords do not match');
        } else {
            $(this).css('border-color', '#27ae60');
        }
    });

    // Photo upload preview
    $('input[type="file"][name="photo"]').on('change', function(e) {
        var file = e.target.files[0];
        if (file) {
            if (file.size > 2097152) { // 2MB
                alert('File size must be less than 2MB');
                $(this).val('');
                return;
            }
            
            var reader = new FileReader();
            reader.onload = function(e) {
                $('.profile-photo-preview').remove();
                $('.form-group').first().after(
                    $('<div class="profile-photo-preview"></div>').append(
                        $('<img>').attr('src', e.target.result).css({
                            'max-width': '200px',
                            'border-radius': '50%',
                            'margin-top': '10px'
                        })
                    )
                );
            };
            reader.readAsDataURL(file);
        }
    });
});

