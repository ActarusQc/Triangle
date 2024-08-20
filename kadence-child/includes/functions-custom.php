<?php
if (!function_exists('get_foundation_points')) {
    function get_foundation_points($child_name) {
        $user_id = get_current_user_id();
        $enfants = get_user_meta($user_id, 'enfants', true);
        if (is_array($enfants)) {
            foreach ($enfants as $enfant) {
                if ($enfant['nom'] === $child_name) {
                    return intval(get_user_meta($user_id, 'points_repas_' . $child_name, true));
                }
            }
        }
        return 0;
    }
}