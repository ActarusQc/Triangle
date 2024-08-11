<?php
if (!defined('ABSPATH')) exit; // Sécurité

// Fonction pour obtenir l'école d'un enfant
function get_ecole_enfant($nom_enfant) {
    if (empty($nom_enfant)) {
        return '';
    }

    global $wpdb;
    $enfants_meta = $wpdb->get_col("
        SELECT meta_value 
        FROM {$wpdb->prefix}usermeta 
        WHERE meta_key = 'enfants'
    ");
    
    $ecoles = get_liste_ecoles();
    
    foreach ($enfants_meta as $enfants_json) {
        $enfants = maybe_unserialize($enfants_json);
        if (is_array($enfants)) {
            foreach ($enfants as $enfant) {
                if (isset($enfant['nom']) && $enfant['nom'] === $nom_enfant) {
                    return isset($enfant['ecole']) && isset($ecoles[$enfant['ecole']]) ? $enfant['ecole'] : '';
                }
            }
        }
    }
    return '';
}

// Fonction pour ajouter la gestion des enfants dans la page edit-account
function ajouter_gestion_enfants() {
    wp_enqueue_script('jquery');
    $user_id = get_current_user_id();
    $enfants = get_user_meta($user_id, 'enfants', true);
    if (!is_array($enfants)) {
        $enfants = array();
    }
    $ecoles = get_liste_ecoles();
    ?>
    <h3>Gestion des enfants</h3>
    <div id="gestion-enfants">
        <?php foreach ($enfants as $index => $enfant) : ?>
            <div class="enfant-item">
                <input type="text" name="enfants[<?php echo $index; ?>][nom]" value="<?php echo esc_attr($enfant['nom']); ?>" placeholder="Nom de l'enfant">
                <select name="enfants[<?php echo $index; ?>][ecole]">
                    <option value="">Sélectionnez une école</option>
                    <?php foreach ($ecoles as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($enfant['ecole'], $value); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="enfants[<?php echo $index; ?>][classe]" value="<?php echo esc_attr($enfant['classe']); ?>" placeholder="Numéro de classe">
                <button type="button" class="remove-enfant">Supprimer</button>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="button" id="ajouter-enfant" class="button">Ajouter un enfant</button>

    <style>
        .enfant-item {
            margin-bottom: 10px;
        }
        .enfant-item input,
        .enfant-item select {
            margin-right: 10px;
        }
        #ajouter-enfant {
            margin-top: 10px;
        }
    </style>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var enfantIndex = <?php echo count($enfants); ?>;
        var ecoles = <?php echo json_encode($ecoles); ?>;

        $('#ajouter-enfant').on('click', function() {
            var ecoleOptions = '<option value="">Sélectionnez une école</option>';
            $.each(ecoles, function(value, label) {
                ecoleOptions += '<option value="' + value + '">' + label + '</option>';
            });

            var newEnfant = '<div class="enfant-item">' +
                '<input type="text" name="enfants[' + enfantIndex + '][nom]" placeholder="Nom de l\'enfant">' +
                '<select name="enfants[' + enfantIndex + '][ecole]">' + ecoleOptions + '</select>' +
                '<input type="text" name="enfants[' + enfantIndex + '][classe]" placeholder="Numéro de classe">' +
                '<button type="button" class="remove-enfant">Supprimer</button>' +
                '</div>';
            $('#gestion-enfants').append(newEnfant);
            enfantIndex++;
        });

        $(document).on('click', '.remove-enfant', function() {
            $(this).parent().remove();
        });
    });
    </script>
    <?php
}

// Fonction pour sauvegarder les informations des enfants
function sauvegarder_enfants($user_id) {
    if (isset($_POST['enfants']) && is_array($_POST['enfants'])) {
        $enfants = array_values(array_filter($_POST['enfants'], function($enfant) {
            return !empty($enfant['nom']);
        }));
        update_user_meta($user_id, 'enfants', $enfants);
    }
}

// Rediriger vers la page "menu-mensuel" après la connexion
function custom_login_redirect($redirect_to, $user) {
    // Remplacez 'menu-mensuel' par le slug de votre page
    $redirect_to = home_url('/mon-compte/modifier-compte/');
    return $redirect_to;
}
add_filter('woocommerce_login_redirect', 'custom_login_redirect', 10, 2);


// Rediriger vers la page "Détails du compte" après l'inscription
function custom_registration_redirect($redirect) {
    $redirect = wc_get_account_endpoint_url('edit-account');
    return $redirect;
}
add_filter('woocommerce_registration_redirect', 'custom_registration_redirect');




// Ajouter les actions
add_action('woocommerce_edit_account_form', 'ajouter_gestion_enfants');
add_action('woocommerce_save_account_details', 'sauvegarder_enfants');