(function($) {
    // Fonction pour masquer les montants "0.00$"
    function hideZeroDollarAmount() {
        // Sélectionner tous les éléments contenant des prix
        $('.woocommerce-Price-amount.amount').each(function() {
            var priceText = $(this).text().trim();
            if (priceText === "0.00$" || priceText === "0.00 $" || priceText === "0,00$" || priceText === "0,00 $") {
                // Masquer l'élément parent spécifique contenant "0.00$"
                $(this).closest('.orderable-product__actions-price, .woocommerce-mini-cart-item, .price').hide();
            }
        });
    }

    // Exécuter la fonction au chargement de la page
    $(document).ready(hideZeroDollarAmount);

    // Observer les changements dans le DOM pour masquer "0.00$" en temps réel
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' || mutation.type === 'subtree') {
                hideZeroDollarAmount();
            }
        });
    });

    // Configurer l'observateur sur le body
    observer.observe(document.body, {
        childList: true,
        subtree: true,
    });

    // Re-exécuter la fonction après chaque mise à jour via AJAX
    $(document.body).on('wc_fragment_refresh updated_cart_totals wc_fragments_loaded', hideZeroDollarAmount);

})(jQuery);
