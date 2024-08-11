jQuery(document).ready(function($) {
    var pointsElement = $('#sumo-rewards-points-balance .points-value');
    
    function updatePointsDisplay() {
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'update_sumo_points_display'
            },
            success: function(response) {
                if(response.success) {
                    pointsElement.text(response.data.points);
                } else {
                    console.error('Error updating points:', response.data.message);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
            }
        });
    }
    
    // Mettre à jour les points après l'ajout ou la suppression d'articles du panier
    $(document.body).on('added_to_cart removed_from_cart', function() {
        updatePointsDisplay();
    });
});