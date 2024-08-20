<?php

// Sécurisation: Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

function add_custom_robots_txt_columns($columns) {
    $columns['robots_txt_status'] = 'Statut robots.txt';
    return $columns;
}

function display_custom_robots_txt_status_column($column, $post_id) {
    if ($column == 'robots_txt_status') {
        $robots_txt_file = ABSPATH . 'robots.txt';
        $robots_txt_content = file_get_contents($robots_txt_file);
        $post_url = get_permalink($post_id);
        $post_path = parse_url($post_url, PHP_URL_PATH);
        
        $is_disallowed = stripos($robots_txt_content, "Disallow: $post_path") !== false;
        $is_allowed = stripos($robots_txt_content, "Allow: $post_path") !== false;

        if ($is_disallowed) {
            echo '<span style="color: red;">Disallow</span>';
        } elseif ($is_allowed) {
            echo '<span style="color: green;">Allow</span>';
        } else {
            echo '<span style="color: gray;">Aucune directive</span>';
        }
    }
}

function hook_robots_txt_columns_for_selected_post_types() {
    $selected_post_types = get_option('robots_txt_content_types', []);
    foreach ($selected_post_types as $post_type) {
        add_filter("manage_{$post_type}_posts_columns", 'add_custom_robots_txt_columns');
        add_action("manage_{$post_type}_posts_custom_column", 'display_custom_robots_txt_status_column', 10, 2);
    }
}

?>