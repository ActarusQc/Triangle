jQuery(document).ready(function($) {
    console.log("Script semaine-switcher.js chargé");

    $('.semaine-btn').on('click', function(e) {
        e.preventDefault();
        var buttonText = $(this).text().trim();
        console.log("Bouton cliqué : " + buttonText);

        // Extraire le numéro de semaine du texte du bouton
        var weekNumber = buttonText.match(/Semaine (\d+)/)[1];
        console.log("Numéro de semaine : " + weekNumber);

        // Obtenir l'URL actuelle
        var currentUrl = new URL(window.location.href);

        // Mettre à jour les paramètres dans l'URL
        currentUrl.searchParams.set('mois', '9'); // Remplacez par le mois actuel ou dynamique
        currentUrl.searchParams.set('annee', '2024'); // Remplacez par l'année actuelle ou dynamique
        currentUrl.hash = "category-semaine-" + weekNumber;

        console.log("Nouvelle URL : " + currentUrl.toString());

        // Mettre à jour l'URL sans recharger la page
        window.history.pushState({}, '', currentUrl.toString());

        // Déclencher un événement personnalisé
        $(document).trigger('weekChanged', [weekNumber]);

        // Essayer de déclencher le filtrage Orderable manuellement
        if (typeof orderable !== 'undefined' && typeof orderable.filterProducts === 'function') {
            orderable.filterProducts();
        }
    });
});