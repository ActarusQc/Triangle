jQuery(document).ready(function($) {
    function updatePointsDisplay() {
        $.ajax({
            url: wc_add_to_cart_params.ajax_url,
            type: 'POST',
            data: {
                action: 'update_points_display'
            },
            success: function(response) {
                if (response.success) {
                    $('.orderable-mini-cart__points span').text(response.data.points);
                    console.log('Points updated:', response.data.points);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error updating points:', error);
            }
        });
    }

    $(document.body).on('added_to_cart', function(event, fragments, cart_hash, button) {
        setTimeout(updatePointsDisplay, 1000); // Attendre 1 seconde avant de mettre à jour les points
    });

    $(document.body).on('removed_from_cart', updatePointsDisplay);
});