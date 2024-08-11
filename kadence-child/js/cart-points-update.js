jQuery(document).ready(function($) {
    var pointsElement = $('.sumo-rewards-points-balance'); // Assurez-vous que cette classe correspond à l'élément affichant les points
    
    // Fonction pour mettre à jour l'affichage des points
    function updatePointsDisplay(points) {
        pointsElement.text(points);
    }
    
    // Gestion de l'ajout au panier
    $(document.body).on('added_to_cart', function(event, fragments, cart_hash, button) {
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'update_sumo_points',
                update_type: 'decrease'
            },
            success: function(response) {
                if(response.success) {
                    updatePointsDisplay(response.data.points);
                }
            }
        });
    });
    
    // Gestion de la suppression du panier
    $(document.body).on('click', '.remove_from_cart_button', function(e) {
        e.preventDefault();
        var cart_item_key = $(this).data('cart_item_key');
        
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'update_sumo_points',
                update_type: 'increase',
                cart_item_key: cart_item_key
            },
            success: function(response) {
                if(response.success) {
                    updatePointsDisplay(response.data.points);
                }
            }
        });
    });
});