jQuery(document).ready(function($) {
    $('.semaine-btn').on('click', function(e) {
        console.log("Bouton cliqué : " + $(this).text().trim());
        e.preventDefault();
        var buttonText = $(this).text().trim();
        
        // Masquer tous les contenus de semaine
        $('.entry-content > h2').each(function() {
            $(this).hide();
            $(this).nextUntil('h2').hide();
        });
        
        // Afficher le contenu de la semaine sélectionnée
        var $targetHeading = $('.entry-content > h2').filter(function() {
            return $(this).text().trim().startsWith(buttonText.split('-')[0].trim());
        });

        if ($targetHeading.length) {
            console.log("Titre trouvé : " + $targetHeading.text());
            $targetHeading.show();
            $targetHeading.nextUntil('h2').show();
            
            // Faire défiler jusqu'au contenu
            $('html, body').animate({
                scrollTop: $targetHeading.offset().top - 100
            }, 500);
        } else {
            console.log("Contenu non trouvé pour : " + buttonText);
        }
        
        // Ajouter une classe active au bouton cliqué et la retirer des autres
        $('.semaine-btn').removeClass('active');
        $(this).addClass('active');
    });

    // Afficher par défaut le contenu de la première semaine
    $('.semaine-btn:first').click();
});