<?php
// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

// Ajouter l'onglet Gestion des élèves
function ajouter_onglet_gestion_eleves($tabs) {
    $tabs['gestion_eleves'] = 'Gestion des élèves';
    return $tabs;
}
add_filter('triangle_admin_tabs', 'ajouter_onglet_gestion_eleves');

// Contenu de l'onglet Gestion des élèves
function afficher_contenu_gestion_eleves() {
    ?>
    <h2>Gestion des élèves</h2>
    <p>Bienvenue dans la section de gestion des élèves.</p>
    
    <!-- Ajoutez ici le contenu de votre onglet, par exemple : -->
    <form method="post" action="">
        <h3>Ajouter un nouvel élève</h3>
        <label for="nom_eleve">Nom de l'élève :</label>
        <input type="text" id="nom_eleve" name="nom_eleve" required>
        
        <label for="classe_eleve">Classe :</label>
        <input type="text" id="classe_eleve" name="classe_eleve" required>
        
        <input type="submit" name="ajouter_eleve" value="Ajouter l'élève">
    </form>

    <?php
    // Logique pour ajouter un élève
    if (isset($_POST['ajouter_eleve'])) {
        $nom = sanitize_text_field($_POST['nom_eleve']);
        $classe = sanitize_text_field($_POST['classe_eleve']);
        // Ajoutez ici la logique pour sauvegarder l'élève dans la base de données
        echo "<p>Élève ajouté : $nom (Classe : $classe)</p>";
    }

    // Afficher la liste des élèves (à implémenter)
    echo "<h3>Liste des élèves</h3>";
    // Ajoutez ici le code pour récupérer et afficher la liste des élèves
}
add_action('triangle_admin_content_gestion_eleves', 'afficher_contenu_gestion_eleves');