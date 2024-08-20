jQuery(document).ready(function($) {
   $(document).on('click', '.orderable-product__add-to-order', function(e) {
    e.preventDefault();
    var $button = $(this);
    var productId = $button.data('orderable-product-id');
    var variationId = $button.data('orderable-variation-id');
    var $container = $button.closest('.orderable-product');
    var selectedChildren = $container.find('input[name="child_name[]"]:checked');
    var typeRepas = $container.find('select[name="attribute_pa_type-de-repas"]').val(); // Ajoutez cette ligne

    if (selectedChildren.length === 0) {
        alert('Veuillez sélectionner au moins un enfant.');
        return;
    }

    $button.prop('disabled', true);

  function addToCartSequentially(index) {
    if (index >= selectedChildren.length) {
        $button.prop('disabled', false);
        $(document.body).trigger('wc_fragment_refresh');
        return;
    }

    var childName = $(selectedChildren[index]).val();
    var typeRepas = $('input[name="attribute_pa_type-de-repas"]:checked').val();
    if (!typeRepas) {
        typeRepas = $('select[name="attribute_pa_type-de-repas"]').val();
    }
    console.log("Type de repas sélectionné :", typeRepas);

    var data = {
        action: 'orderable_add_to_cart',
        product_id: productId,
        variation_id: variationId,
        quantity: 1,
        child_name: childName,
        type_repas: typeRepas
    };

    console.log("Données envoyées au serveur:", data);

  $.ajax({
    url: orderable_vars.ajax_url,
    type: 'POST',
    data: {
        action: 'custom_add_to_cart',
        product_id: productId,
        variation_id: variationId,
        quantity: 1,
        child_name: childName,
        type_repas: $('input[name="attribute_pa_type-de-repas"]:checked').val() || $('select[name="attribute_pa_type-de-repas"]').val()
    },
    success: function(response) {
        console.log('Add to cart response:', response);
        // Gérer la réponse
    },
    error: function(xhr, status, error) {
        console.error('Add to cart error:', error);
        // Gérer l'erreur
    }
});

}


        addToCartSequentially(0);
    });
});




jQuery(document).ready(function($) {
    function updateButtonAndPrice(productContainer) {
        var $container = $(productContainer);
        var selectedChildren = $container.find('input[name="child_name[]"]:checked').length;
        var basePrice = parseFloat($container.find('.orderable-product__actions-price').data('base-price') || 0);
        var totalPrice = basePrice * selectedChildren;
        var freeMeals = 0;

        $container.find('input[name="child_name[]"]:checked').each(function() {
            freeMeals += Math.min(1, parseInt($(this).data('free-meals') || 0));
        });

        $container.find('.orderable-product__actions-price').html('€' + totalPrice.toFixed(2));
        
        var buttonText = selectedChildren > 0 ? 
            'Ajouter (' + selectedChildren + ' enfant' + (selectedChildren > 1 ? 's' : '') + ')' : 
            'Ajouter au panier';
        
        if (freeMeals > 0) {
            buttonText += ' (' + freeMeals + ' repas gratuit' + (freeMeals > 1 ? 's' : '') + ')';
        }
        
        $container.find('.orderable-product__add-to-order').text(buttonText);
        $container.find('.orderable-product__add-to-order').prop('disabled', selectedChildren === 0);
    }

    // Ouvrir le popup
    $('.orderable-product__select-button').click(function(e) {
        e.preventDefault();
        var productId = $(this).data('product-id');
        $('#product-id').val(productId);
        $('#product-options-modal').show();
        updateButtonAndPrice($('#product-options-modal'));
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

    // Gérer les changements de sélection d'enfants
    $(document).on('change', 'input[name="child_name[]"]', function() {
        updateButtonAndPrice($(this).closest('#product-options-modal'));
    });

    // Soumettre le formulaire
    $('#product-options-form').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serializeArray();
        var productId = $('#product-id').val();
        var childNames = [];

        formData.forEach(function(item) {
            if (item.name === 'child_name[]' && item.value) {
                childNames.push(item.value);
            }
        });

        // Désactiver le bouton pendant l'ajout au panier
        var $submitButton = $(this).find('button[type="submit"]');
        $submitButton.prop('disabled', true);

        // Ajouter un article au panier pour chaque enfant
        var ajaxCalls = childNames.map(function(childName) {
            return $.ajax({
                url: orderable_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'add_to_cart_custom',
                    product_id: productId,
                    child_name: childName
                }
            });
        });

        $.when.apply($, ajaxCalls).then(function() {
            $('#product-options-modal').hide();
            if (typeof(updateMiniCart) === "function") {
                updateMiniCart();
            }
            // Réactiver le bouton après l'ajout au panier
            $submitButton.prop('disabled', false);
        }).fail(function() {
            alert('Erreur lors de l\'ajout au panier');
            $submitButton.prop('disabled', false);
        });
    });

    // Réactiver les boutons après fermeture du mini-cart
    $(document.body).on('wc_fragments_refreshed', function() {
        $('.orderable-product__select-button').prop('disabled', false);
    });

    // Initialiser l'affichage du bouton et du prix au chargement
    updateButtonAndPrice($('#product-options-modal'));
});

function addToCart(productId, childName) {
    var variationId = $('input[name="variation_id"]').val();
    var typeRepas = $('input[name="attribute_pa_type-de-repas"]:checked').val() || $('select[name="attribute_pa_type-de-repas"]').val();

    console.log('Adding to cart:', {
        productId: productId,
        variationId: variationId,
        childName: childName,
        typeRepas: typeRepas
    });

    $.ajax({
        url: orderable_vars.ajax_url,
        type: 'POST',
        data: {
            action: 'custom_add_to_cart',
            product_id: productId,
            variation_id: variationId,
            child_name: childName,
            type_repas: typeRepas
        },
        success: function(response) {
            console.log('Add to cart response:', response);
            // Gérer la réponse
        },
        error: function(xhr, status, error) {
            console.error('Add to cart error:', error);
            // Gérer l'erreur
        }
    });
}



