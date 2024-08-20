<?php
function diagnose_menu_mensuel_page() {
    $url = home_url('/menu-mensuel/');
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        echo "Erreur lors de l'accès à la page : " . $response->get_error_message();
        return;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    echo "Code de statut HTTP : " . $status_code . "\n";

    if ($status_code != 200) {
        echo "La page ne se charge pas correctement.\n";
    }

    // Vérifier si le contenu attendu est présent
    if (strpos($body, 'Menu Mensuel') === false) {
        echo "Le contenu 'Menu Mensuel' n'est pas trouvé sur la page.\n";
        
        // Afficher les 500 premiers caractères du contenu de la page
        echo "Début du contenu de la page :\n";
        echo substr($body, 0, 500) . "...\n\n";
    }

    // Vérifier la page dans la base de données
    $page = get_page_by_path('menu-mensuel');
    if ($page) {
        echo "ID de la page : " . $page->ID . "\n";
        echo "Titre de la page : " . $page->post_title . "\n";
        echo "Statut de la page : " . $page->post_status . "\n";
        echo "Template utilisé : " . get_page_template_slug($page->ID) . "\n";
        echo "Contenu de la page (premiers 500 caractères) :\n";
        echo substr($page->post_content, 0, 500) . "...\n\n";
    } else {
        echo "La page 'menu-mensuel' n'existe pas dans la base de données.\n";
    }

    // Vérifier les shortcodes utilisés
    if ($page && has_shortcode($page->post_content, 'votre_shortcode')) {
        echo "Le shortcode [votre_shortcode] est utilisé dans la page.\n";
    }

    // Vérifier les hooks pertinents
    $hooks = ['the_content', 'wp_head', 'wp_footer', 'template_redirect'];
    foreach ($hooks as $hook) {
        $callbacks = $GLOBALS['wp_filter'][$hook] ?? [];
        if (!empty($callbacks)) {
            echo "Hooks attachés à '{$hook}':\n";
            print_r($callbacks);
        }
    }
}
?>