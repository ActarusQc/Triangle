jQuery(document).ready(function($) {
    // Ouvrir le popup
    $('.orderable-product__select-button').click(function(e) {
        e.preventDefault();
        var productId = $(this).data('product-id');
        $('#product-id').val(productId);
        $('#product-options-modal').show();
    });

    // Fermer le popup avec le X
    $('#product-options-modal .close').click(function() {
        $('#product-options-modal').hide();
    });

    // Fermer le popup en cliquant en dehors
    $(window).click(function(event) {
        if ($(event.target).is('#product-options-modal')) {
            $('#product-options-modal').hide();
        }
    });

    // Soumettre le formulaire
    $('#product-options-form').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();

        $.ajax({
            url: orderable_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'add_to_cart_custom',
                form_data: formData
            },
            success: function(response) {
                if (response.success) {
                    $('#product-options-modal').hide();
                    // Mettre à jour le mini-cart ici
                    if (typeof(updateMiniCart) === "function") {
                        updateMiniCart();
                    }
                } else {
                    alert('Erreur lors de l\'ajout au panier');
                }
            }
        });
    });
});