/**
 * Main JavaScript for Bookstore & Stationery
 * Handles common interactions and dynamic features
 */

$(document).ready(function() {
    // Auto-hide success/error messages after 5 seconds
    setTimeout(function() {
        $('.success-message, .error-messages, .info-message').fadeOut('slow');
    }, 5000);

    // Search functionality
    $('#search-form').on('submit', function(e) {
        var searchTerm = $('#search-input').val().trim();
        if (searchTerm === '') {
            e.preventDefault();
            alert('Please enter a search term');
            return false;
        }
    });

    // Quantity validation
    $('input[type="number"][name="quantity"]').on('change', function() {
        var max = $(this).attr('max');
        var min = $(this).attr('min');
        var value = parseInt($(this).val());
        
        if (max && value > max) {
            $(this).val(max);
            alert('Maximum quantity available: ' + max);
        }
        if (min && value < min) {
            $(this).val(min);
        }
    });

    // Image preview for file uploads
    $('input[type="file"][accept*="image"]').on('change', function(e) {
        var file = e.target.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var preview = $('<img>').attr('src', e.target.result).css({
                    'max-width': '200px',
                    'margin-top': '10px',
                    'border-radius': '4px'
                });
                $(this).closest('.form-group').find('.image-preview').remove();
                $(this).closest('.form-group').append($('<div class="image-preview"></div>').append(preview));
            }.bind(this);
            reader.readAsDataURL(file);
        }
    });

    // Confirm delete actions
    $('.delete-form, .delete-cart-item-form').on('submit', function(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
            return false;
        }
    });

    // Cart update with AJAX and quantity +/- controls
    function sendCartUpdate(cartId, quantity, $row) {
        $.post('/cart/update.php', { cart_id: cartId, quantity: quantity }, function(resp) {
            // If server returns JSON, update UI accordingly
            if (resp && typeof resp === 'object') {
                if (resp.success) {
                    // Update subtotal text if provided
                    if (resp.subtotal_html) {
                        $row.find('.cart-item-subtotal').text(resp.subtotal_html);
                    }
                    // Optionally update overall cart UI (reload cart total or update DOM)
                    if (resp.cart_total_html) {
                        // Update cart total if present in the page
                        $('.cart-total').text(resp.cart_total_html);
                    }
                    // Update cart count badge if provided
                    if (typeof resp.cart_count !== 'undefined') {
                        $('.cart-link .count-badge').text(resp.cart_count);
                    }
                } else if (resp.error) {
                    alert(resp.error);
                    // revert input to previous qty if provided
                    if (resp.current_quantity) {
                        $row.find('input[name="quantity"]').val(resp.current_quantity);
                    }
                }
            } else {
                // Fallback: reload page to reflect changes
                window.location = '/cart/view.php';
            }
        }, 'json').fail(function() {
            // On failure, reload as fallback
            window.location = '/cart/view.php';
        });
    }

    // Handle +/- buttons
    $(document).on('click', '.qty-increase, .qty-decrease', function() {
        const $btn = $(this);
        const cartId = $btn.data('cart-id');
        const $form = $btn.closest('.update-quantity-form');
        const $input = $form.find('input[name="quantity"]');
        const max = parseInt($input.attr('max')) || 9999;
        const min = parseInt($input.attr('min')) || 0;
        let val = parseInt($input.val()) || min;
        if ($btn.hasClass('qty-increase')) {
            val = Math.min(max, val + 1);
            $input.val(val);
            sendCartUpdate(cartId, val, $form.closest('.cart-item'));
        } else {
            if (val - 1 <= 0) {
                // prompt confirm removal
                if (confirm('Quantity is 0. Do you want to remove this product from your cart?')) {
                    // Delete via AJAX
                    $.post('/cart/delete.php', { cart_id: cartId }, function(delResp) {
                        if (delResp && delResp.success) {
                            $form.closest('.cart-item').remove();
                            if (typeof delResp.cart_count !== 'undefined') {
                                $('.cart-link .count-badge').text(delResp.cart_count);
                            }
                        } else if (delResp && delResp.error) {
                            alert(delResp.error);
                        } else {
                            window.location = '/cart/view.php';
                        }
                    }, 'json').fail(function() { window.location = '/cart/view.php'; });
                } else {
                    // revert to 1
                    val = 1;
                    $input.val(val);
                    sendCartUpdate(cartId, val, $form.closest('.cart-item'));
                }
            } else {
                val = Math.max(min, val - 1);
                $input.val(val);
                sendCartUpdate(cartId, val, $form.closest('.cart-item'));
            }
        }
    });

    // Handle direct input changes (debounce)
    let cartTimer = null;
    $(document).on('input', '.update-quantity-form input[name="quantity"]', function() {
        const $input = $(this);
        const $form = $input.closest('.update-quantity-form');
        const cartId = $form.find('input[name="cart_id"]').val();
        const max = parseInt($input.attr('max')) || 9999;
        const min = parseInt($input.attr('min')) || 0;
        let val = parseInt($input.val());
        if (isNaN(val)) val = min;
        if (val < 0) val = 0;
        if (val > max) val = max;
        $input.val(val);
        clearTimeout(cartTimer);
        cartTimer = setTimeout(function() {
            if (val === 0) {
                if (confirm('Quantity is 0. Do you want to remove this product from your cart?')) {
                    $.post('/cart/delete.php', { cart_id: cartId }, function(delResp) {
                        if (delResp && delResp.success) {
                            $form.closest('.cart-item').remove();
                            if (typeof delResp.cart_count !== 'undefined') {
                                $('.cart-link .count-badge').text(delResp.cart_count);
                            }
                        } else if (delResp && delResp.error) {
                            alert(delResp.error);
                        } else {
                            window.location = '/cart/view.php';
                        }
                    }, 'json').fail(function() { window.location = '/cart/view.php'; });
                } else {
                    // revert to 1 and update
                    $input.val(1);
                    sendCartUpdate(cartId, 1, $form.closest('.cart-item'));
                }
            } else {
                sendCartUpdate(cartId, val, $form.closest('.cart-item'));
            }
        }, 400);
    });

    // Prevent normal submit (we handle via AJAX). Keep fallback for no-JS via noscript.
    $(document).on('submit', '.update-quantity-form', function(e) {
        e.preventDefault();
    });

    // Password toggle
    $('.toggle-password').on('click', function() {
        const target = $($(this).data('target'));
        if (!target.length) return;
        const type = target.attr('type') === 'password' ? 'text' : 'password';
        target.attr('type', type);
    });

    // Autocomplete suggestions for search inputs (products)
    $('input[name="search"]').each(function() {
        const $input = $(this);
        // Wrap input so suggestion list can be absolutely positioned relative to it
        $input.wrap('<div class="search-autocomplete-wrapper" style="position:relative;display:inline-block;width:100%"></div>');
        const $container = $input.parent();
        const $suggest = $('<div class="suggestion-list" style="position:absolute; z-index:9999; left:0; right:0; background:#fff; border:1px solid #ddd; display:none; max-height:240px; overflow:auto;"></div>');
        $container.append($suggest);

        let timer = null;
        $input.on('input', function() {
            clearTimeout(timer);
            const q = $(this).val().trim();
            if (q.length < 2) {
                $suggest.hide().empty();
                return;
            }
            timer = setTimeout(function() {
                $.getJSON('/ajax/search_suggestions.php', { q: q, type: 'product' })
                    .done(function(data) {
                        $suggest.empty();
                        if (!data || !data.length) {
                            $suggest.append($('<div style="padding:8px;color:#666;">No matches</div>')).show();
                            return;
                        }
                        data.forEach(function(item) {
                            const $row = $('<div class="suggestion-item" style="padding:8px;cursor:pointer;border-bottom:1px solid #f1f1f1;"></div>').text(item.label);
                            $row.on('click', function() { window.location = item.url; });
                            $suggest.append($row);
                        });
                        $suggest.show();
                    });
            }, 200);
        });

        // hide on outside click
        $(document).on('click', function(e) {
            if (!$(e.target).closest($container).length) {
                $suggest.hide();
            }
        });

        // hide on enter
        $input.on('keydown', function(e) {
            if (e.key === 'Enter') {
                $suggest.hide();
            }
        });
    });

    // Favorite toggle (heart)
    $(document).on('click', '.favorite-btn', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const productId = $btn.data('product-id');
        if (!productId) return;
        $.post('/ajax/toggle_favorite.php', { product_id: productId })
            .done(function(resp) {
                if (resp && resp.success) {
                    if (resp.action === 'added') {
                        $btn.addClass('active');
                        if (resp.product_name) alert(resp.product_name + ' has been added to favourites.');
                    } else {
                        $btn.removeClass('active');
                        if (resp.product_name) alert(resp.product_name + ' has been removed from favourites.');
                    }
                    if (typeof resp.favorites_count !== 'undefined') {
                        $('.favorites-link .count-badge').text(resp.favorites_count);
                    }
                } else if (resp && resp.error) {
                    alert(resp.error);
                }
            })
            .fail(function() {
                alert('Failed to update favourites.');
            });
    });

});

