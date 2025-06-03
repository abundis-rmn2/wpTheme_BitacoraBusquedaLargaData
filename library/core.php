<?php


// include the main.js script in the header on the front-end.
function p_scripts() {
	wp_enqueue_script( 'main-js', get_stylesheet_directory_uri() . '/js/main.js', array( 'jquery' ), false, true );
}
add_action( 'wp_enqueue_scripts', 'p_scripts' );

add_action('admin_menu', 'theme_configurations_menu');

function theme_configurations_menu() {
    // Add main menu item
    add_menu_page(
        'Configuraciones',          // Page title
        'Configuraciones',          // Menu title
        'manage_options',           // Capability
        'configuraciones',          // Menu slug
        'configuraciones_page',
        'dashicons-admin-generic',  // Icon (cog icon)
        60                          // Position
    );
    add_submenu_page(
        'configuraciones',          // Parent slug
        'Coordinadas',              // Page title
        'Coordinadas',              // Menu title
        'manage_options',           // Capability
        'tejer_red_api_settings',   // Menu slug
        'tejer_red_api_settings_page' // Callback function
    );
}

add_action('admin_enqueue_scripts', function($hook) {
    // Load media uploader only on the "Configuraciones" page
    if ($hook === 'toplevel_page_configuraciones') {
        wp_enqueue_media();
        wp_enqueue_script('jquery');
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
    }
});
