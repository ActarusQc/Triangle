<?php
// Vérifier si ce fichier est appelé directement
if (!defined('ABSPATH')) {
    exit; // Sortie si accédé directement
}

function afficher_points_utilisateur() {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return ''; // Ne rien afficher si l'utilisateur n'est pas connecté
    }

    // Remplacez 'nom_de_la_meta_sumo' par le nom correct de la métadonnée utilisée par SUMO Reward Points
    $points = get_user_meta($user_id, 'nom_de_la_meta_sumo', true);

    if ($points !== '') {
        $output = '<div style="text-align: center; margin: 20px auto; padding: 10px; font-size: 16px; font-weight: bold; background-color: #e7f3ff; border: 2px solid #b3d7ff; border-radius: 5px; max-width: 80%;">';
        $output .= "Vous avez actuellement $points point(s) (repas)";
        $output .= '</div>';
        return $output;
    }

    return ''; // Ne rien afficher si aucun point n'est trouvé
}

// Assurez-vous que cette ligne est présente pour enregistrer le shortcode
add_shortcode('points_utilisateur_sumo', 'afficher_points_utilisateur');
// Enregistrer la fonction comme un shortcode
add_shortcode('points_utilisateur_sumo', 'afficher_points_utilisateur');
   
