<?php

// Cargar Meta Box como librería interna
if ( file_exists( get_template_directory() . '/inc/meta-box/meta-box.php' ) ) {
    require_once get_template_directory() . '/inc/meta-box/meta-box.php';
}

function enqueue_map_scripts() {
    // MapLibre
    wp_enqueue_style('maplibre-css', 'https://unpkg.com/maplibre-gl@3.3.0/dist/maplibre-gl.css');
    wp_enqueue_script('maplibre-js', 'https://unpkg.com/maplibre-gl@3.3.0/dist/maplibre-gl.js', [], null, true);
    
    // Custom Map JS
    wp_enqueue_script('custom-map-js', get_stylesheet_directory_uri() . '/js/map.js', ['maplibre-js'], null, true);
}
add_action('admin_enqueue_scripts', 'enqueue_map_scripts');

add_action('init', function () {

    // CPT Bitácora de Campo
    register_post_type('bitacora', [
        'label' => 'Bitácoras de Campo',
        'public' => true,
        'show_in_rest' => true,
        'supports' => ['title', 'editor', 'thumbnail'],
        'menu_icon' => 'dashicons-location-alt',
        'has_archive' => true  // Enable archive
    ]);

    // CPT Fosa
    register_post_type('fosa', [
        'label' => 'Fosas',
        'public' => true,
        'show_in_rest' => true,
        'supports' => ['title', 'editor', 'thumbnail'],
        'menu_icon' => 'dashicons-marker',
        'has_archive' => true  // Enable archive
    ]);

    // CPT Indicio
    register_post_type('indicio', [
        'label' => 'Indicios',
        'public' => true,
        'show_in_rest' => true,
        'supports' => ['title', 'thumbnail'],
        'menu_icon' => 'dashicons-tag',
        'has_archive' => true  // Enable archive
    ]);
});

// Zona taxonomy now applied to both bitacora and fosa
register_taxonomy('zona', ['bitacora', 'fosa'], [
    'label' => 'Zona de Reporte',
    'rewrite' => ['slug' => 'zona'],
    'hierarchical' => true,
    'show_in_rest' => true
]);

// Taxonomies for Indicios
register_taxonomy('tipo_prenda', 'indicio', [
    'label' => 'Tipo de Prenda',
    'rewrite' => ['slug' => 'tipo-prenda'],
    'hierarchical' => false,
    'show_in_rest' => true
]);

register_taxonomy('color', 'indicio', [
    'label' => 'Color',
    'rewrite' => ['slug' => 'color'],
    'hierarchical' => false,
    'show_in_rest' => true
]);

register_taxonomy('marca', 'indicio', [
    'label' => 'Marca',
    'rewrite' => ['slug' => 'marca'],
    'hierarchical' => false,
    'show_in_rest' => true
]);

register_taxonomy('talla', 'indicio', [
    'label' => 'Talla',
    'rewrite' => ['slug' => 'talla'],
    'hierarchical' => false,
    'show_in_rest' => true
]);

register_taxonomy('material', 'indicio', [
    'label' => 'Material',
    'rewrite' => ['slug' => 'material'],
    'hierarchical' => false,
    'show_in_rest' => true
]);

add_filter('rwmb_meta_boxes', function ($meta_boxes) {
    
    // 🔥 Bitácora with Map Field
    $meta_boxes[] = [
        'title'      => 'Mapa de Ubicación',
        'post_types' => ['bitacora', 'fosa'],
        'fields'     => [
            [
                'id'   => 'galeria',
                'name' => 'Galería',
                'type' => 'image_advanced', 
                'max_file_uploads' => 10,  
                'mime_types' => 'jpg,jpeg,png',
            ],
            [
                'id'    => 'latitud',
                'name'  => 'Latitud',
                'type'  => 'text',
                'placeholder' => 'Ej: 20.6768',
                'attributes' => [
                    'id' => 'latitud'
                ],
                'save_field' => true  // ✅ Ensure it saves
            ],
            [
                'id'    => 'longitud',
                'name'  => 'Longitud',
                'type'  => 'text',
                'placeholder' => 'Ej: -103.3478',
                'attributes' => [
                    'id' => 'longitud'
                ],
                'save_field' => true  // ✅ Ensure it saves
            ],
            [
                'id'   => 'map_location',
                'name' => 'Ubicación en Mapa',
                'type' => 'custom_html',
                'std'  => '<div id="map" style="height: 400px; width: 100%;"></div>
                           <input type="text" id="address-search" placeholder="Buscar dirección..." style="width:100%; margin: 5px 0;">
                           <button type="button" id="geocode-btn">Buscar</button>',
            ]
        ],

    ];


    // 🔥 Campos para CPT Indicio
    $meta_boxes[] = [
        'title'      => 'Datos del Indicio',
        'post_types' => ['indicio'],
        'aside'      => true,
        'fields'     => [
            [
                'id'         => 'fosa_relacionada',
                'name'       => 'Fosa Relacionada',
                'type'       => 'post',
                'post_type'  => 'fosa',
                'field_type' => 'select',
            ],
            [
                'id'   => 'fecha_descubrimiento',
                'name' => 'Fecha de Descubrimiento',
                'type' => 'date',
            ],
            [
                'id'   => 'descripcion',
                'name' => 'Descripción',
                'type' => 'textarea',
            ]
        ],
    ];

    return $meta_boxes;
});

// ===========================
// ✅ Exponer MetaBox en la API REST
// ===========================
/*
add_filter('rest_prepare_bitacora', function ($response, $post) {
    $response->data['meta'] = get_post_meta($post->ID);
    return $response;
}, 10, 2);

add_filter('rest_prepare_fosa', function ($response, $post) {
    $response->data['meta'] = get_post_meta($post->ID);
    return $response;
}, 10, 2);

add_filter('rest_prepare_indicio', function ($response, $post) {
    $response->data['meta'] = get_post_meta($post->ID);
    return $response;
}, 10, 2);
*/

// ✅ Save coordinates when post is saved
add_action('save_post', function($post_id) {
    if (get_post_type($post_id) !== 'bitacora') {
        return;
    }

    // Check if lat/lng fields are present
    if (isset($_POST['latitud']) && isset($_POST['longitud'])) {
        update_post_meta($post_id, 'latitud', sanitize_text_field($_POST['latitud']));
        update_post_meta($post_id, 'longitud', sanitize_text_field($_POST['longitud']));
    }
});


add_action('after_switch_theme', function () {
    global $wp_rewrite;
    $wp_rewrite->set_permalink_structure('/%postname%/');
    $wp_rewrite->flush_rules();
});


add_action('after_setup_theme', function () {
    register_nav_menu('menu_principal', 'Menú Principal');
});

add_action('after_switch_theme', function () {
    $menu_name = 'Menú Principal';
    $menu_exists = wp_get_nav_menu_object($menu_name);

    if (!$menu_exists) {
        // Crear menú
        $menu_id = wp_create_nav_menu($menu_name);

        // Agregar elementos
        wp_update_nav_menu_item($menu_id, 0, [
            'menu-item-title' => 'Inicio',
            'menu-item-url' => home_url('/'),
            'menu-item-status' => 'publish'
        ]);

        wp_update_nav_menu_item($menu_id, 0, [
            'menu-item-title' => 'Noticias',
            'menu-item-url' => home_url('/#'),
            'menu-item-status' => 'publish'
        ]);

        wp_update_nav_menu_item($menu_id, 0, [
            'menu-item-title' => 'Indicios',
            'menu-item-url' => home_url('/indicio/'),
            'menu-item-status' => 'publish'
        ]);

        wp_update_nav_menu_item($menu_id, 0, [
            'menu-item-title' => 'Fosas',
            'menu-item-url' => home_url('/fosa/'),
            'menu-item-status' => 'publish'
        ]);

        wp_update_nav_menu_item($menu_id, 0, [
            'menu-item-title' => 'Contacto',
            'menu-item-url' => home_url('/#'),
            'menu-item-status' => 'publish'
        ]);

        // Asignar menú a ubicación del tema
        set_theme_mod('nav_menu_locations', ['menu_principal' => $menu_id]);
    }
});
