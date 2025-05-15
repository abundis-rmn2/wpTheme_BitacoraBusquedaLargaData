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

function tejer_red_test_api_connection() {
    $external_api_url = get_option('tejer_red_external_api_url', 'http://192.168.1.71:9999/instancias.php');
    $site_url = get_site_url();
    $api_key = get_option('tejer_red_api_key');
    
    if (!$api_key) {
        $api_key = wp_generate_password(32, true, true);
        update_option('tejer_red_api_key', $api_key);
    }
    
    $payload = [
        'url'     => $site_url,
        'api_key' => $api_key,
        'endpoints' => []
    ];
    
    // Asegúrate de incluir los endpoints configurados
    $expuestos = get_option('tejer_red_endpoints_expuestos', []);
    if ($expuestos['bitacoras'] ?? false) {
        $payload['endpoints']['bitacoras'] = $site_url . '/wp-json/wp/v2/bitacora';
    }
    if ($expuestos['fosas'] ?? false) {
        $payload['endpoints']['fosas'] = $site_url . '/wp-json/wp/v2/fosa';
    }
    if ($expuestos['indicios'] ?? false) {
        $payload['endpoints']['indicios'] = $site_url . '/wp-json/wp/v2/indicio';
    }

    // Log para depuración
    error_log('Enviando a API: ' . print_r($payload, true));
    
    $response = wp_remote_post($external_api_url, [
        'method'  => 'POST',
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ],
        'body'    => json_encode($payload),
        'sslverify' => false,
        'timeout' => 15
    ]);

    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        error_log('Error de conexión: ' . $error_message);
        echo '<div class="error"><p>Error de conexión a la API: ' . $error_message . '</p></div>';
        return false;
    } else {
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        error_log("Respuesta API: $code - $body");
        
        if ($code === 200) {
            echo '<div class="updated"><p>Conexión exitosa a la API!</p></div>';
            return true;
        } else {
            echo '<div class="error"><p>Error al conectar con la API. Código: ' . $code . ' - Mensaje: ' . esc_html($body) . '</p></div>';
            return false;
        }
    }
}

add_action('admin_menu', function() {
    add_options_page(
        'Configuración de la API de Tejer',
        'API de Tejer',
        'manage_options',
        'tejer_red_api_settings',
        'tejer_red_api_settings_page'
    );
});

// Página de configuración en el admin
function tejer_red_api_settings_page() {
    if (isset($_POST['tejer_red_api_settings_nonce']) && wp_verify_nonce($_POST['tejer_red_api_settings_nonce'], 'guardar_tejer_red_api_settings')) {
        $expuestos = [
            'bitacoras' => isset($_POST['exponer_bitacoras']) ? 1 : 0,
            'fosas'     => isset($_POST['exponer_fosas'])     ? 1 : 0,
            'indicios'  => isset($_POST['exponer_indicios'])  ? 1 : 0,
        ];
        update_option('tejer_red_endpoints_expuestos', $expuestos);

        if (isset($_POST['external_api_url'])) {
            update_option('tejer_red_external_api_url', esc_url_raw($_POST['external_api_url']));
        }

        echo '<div class="updated"><p>Configuración guardada y enviada a la API externa.</p></div>';
        tejer_red_registrar_api_externa(); // Enviar configuración a la API externa
    }

    $expuestos = get_option('tejer_red_endpoints_expuestos', ['bitacoras' => 1, 'fosas' => 1, 'indicios' => 1]);
    $external_api_url = get_option('tejer_red_external_api_url', 'http://192.168.1.71:9999/instancias.php');
    ?>
    <div class="wrap">
        <h1>Tejer.Red – Configuración</h1>
        <form method="POST">
            <?php wp_nonce_field('guardar_tejer_red_api_settings', 'tejer_red_api_settings_nonce'); ?>
            <fieldset>
                <legend><strong>Selecciona qué tipos de entrada quieres compartir:</strong></legend>
                <label><input type="checkbox" name="exponer_bitacoras" <?php checked($expuestos['bitacoras'], 1); ?>> Bitácoras</label><br>
                <label><input type="checkbox" name="exponer_fosas" <?php checked($expuestos['fosas'], 1); ?>> Fosas</label><br>
                <label><input type="checkbox" name="exponer_indicios" <?php checked($expuestos['indicios'], 1); ?>> Indicios</label><br>
            </fieldset>
            <br>
            <fieldset>
                <legend><strong>Definir URL de la API externa:</strong></legend>
                <label for="external_api_url">URL del servidor que coordina bases de datos:</label><br>
                <input type="url" id="external_api_url" name="external_api_url" value="<?php echo esc_attr($external_api_url); ?>" style="width: 100%;" required>
            </fieldset>
            <br>
            <input type="submit" class="button button-primary" value="Guardar Configuración">
        </form>
        <br>
        <h2>Acciones Manuales</h2>
        <form method="POST">
            <?php wp_nonce_field('probar_conexion_tejer_red_nonce'); ?>
            <input type="submit" name="probar_conexion_tejer_red" class="button button-secondary" value="Probar Configuración de API Externa">
        </form>
        <form method="POST">
            <?php wp_nonce_field('reenviar_config_tejer_red_nonce'); ?>
            <input type="submit" name="reenviar_config_tejer_red" class="button button-secondary" value="Reenviar Configuración a API Externa">
        </form>
        <?php
        if (isset($_POST['probar_conexion_tejer_red']) && check_admin_referer('probar_conexion_tejer_red_nonce')) {
            $resultado = tejer_red_test_api_connection();
            if ($resultado === true) {
                echo '<div class="updated"><p>Prueba de conexión exitosa.</p></div>';
            } else {
                echo '<div class="error"><p>Prueba de conexión fallida. Revisa la configuración.</p></div>';
            }
        }
        if (isset($_POST['reenviar_config_tejer_red']) && check_admin_referer('reenviar_config_tejer_red_nonce')) {
            $resultado = tejer_red_registrar_api_externa();
            if ($resultado === true) {
                echo '<div class="updated"><p>Configuración reenviada a la API externa.</p></div>';
            } else {
                echo '<div class="error"><p>Error: ' . esc_html($resultado) . '</p></div>';
            }
        }
        ?>
    </div>
    <?php
}

function generar_api_key_segura() {
    $caracteres = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $api_key = '';
    for ($i = 0; $i < 32; $i++) {
        $api_key .= $caracteres[rand(0, strlen($caracteres) - 1)];
    }
    return $api_key;
}
function tejer_red_verificar_api_key() {
    $api_key = get_option('tejer_red_api_key');
    if (!$api_key) return false;
    
    // Verificar longitud
    if (strlen($api_key) !== 32) {
        error_log('API key longitud incorrecta: '.strlen($api_key));
        return false;
    }
    
    // Verificar caracteres
    if (preg_match('/[^a-zA-Z0-9]/', $api_key)) {
        error_log('API key contiene caracteres inválidos');
        return false;
    }
    
    return true;
}

// Usar en tu código:
if (!tejer_red_verificar_api_key()) {
    // Regenerar key inválida
    update_option('tejer_red_api_key', generar_api_key_segura());
}
// Función central para registrar el sitio y exponer los endpoints
function tejer_red_registrar_api_externa() {
    $external_api_url = get_option('tejer_red_external_api_url', 'http://192.168.1.71:9999/instancias.php');
    $site_url = get_site_url();

    // Generar clave si no existe
    $api_key = get_option('tejer_red_api_key');
    if (!$api_key) {
        $api_key = generar_api_key_segura();
        update_option('tejer_red_api_key', $api_key);
    }

    // Endpoints seleccionados
    $seleccion = get_option('tejer_red_endpoints_expuestos', ['bitacoras' => 1, 'fosas' => 1, 'indicios' => 1]);

    $endpoints = [];
    if (!empty($seleccion['bitacoras'])) $endpoints['bitacoras'] = $site_url . '/wp-json/wp/v2/bitacora';
    if (!empty($seleccion['fosas']))     $endpoints['fosas']     = $site_url . '/wp-json/wp/v2/fosa';
    if (!empty($seleccion['indicios']))  $endpoints['indicios']  = $site_url . '/wp-json/wp/v2/indicio';

    $payload = [
        'url' => $site_url,
        'api_key' => $api_key,
        'endpoints' => $endpoints
    ];

    $response = wp_remote_post($external_api_url, [
        'method'  => 'POST',
        'headers' => ['Content-Type' => 'application/json'],
        'body'    => json_encode($payload),
        'sslverify' => false // ¡Solo para entornos de desarrollo!
    ]);

    if (is_wp_error($response)) {
        return $response->get_error_message();
    }

    $code = wp_remote_retrieve_response_code($response);
    return ($code === 200) ? true : "Respuesta HTTP: $code";
}

// Hooks: al activar tema, actualizar algo o por cron
function tejer_red_hook_registro() {
    tejer_red_registrar_api_externa();
}
add_action('after_switch_theme', 'tejer_red_hook_registro');
add_action('upgrader_process_complete', function($upgrader_object, $options) {
    if (isset($options['type']) && in_array($options['type'], ['plugin', 'theme', 'core'])) {
        tejer_red_hook_registro();
    }
}, 10, 2);

// Cron programado
if (!wp_next_scheduled('tejer_red_cron_hook')) {
    wp_schedule_event(time(), 'daily', 'tejer_red_cron_hook');
}
add_action('tejer_red_cron_hook', 'tejer_red_hook_registro');

// ✅ Custom REST API Endpoint
add_action('rest_api_init', function () {
    error_log('Registrando endpoint '); // Mensaje de depuración
    register_rest_route('personalizado/v1', '/info/(?P<post_type>[a-zA-Z0-9_-]+)/(?P<id>\d+)', [
        'methods'  => WP_REST_Server::READABLE,
        'callback' => function ($data) {
            $post_type = sanitize_text_field($data['post_type']);
            $post_id = intval($data['id']);

            if (!post_type_exists($post_type)) {
                return new WP_Error('invalid_post_type', 'Invalid post type', ['status' => 404]);
            }

            $post = get_post($post_id);
            if (!$post || $post->post_type !== $post_type) {
                return new WP_Error('invalid_post', 'Invalid post ID or post type mismatch', ['status' => 404]);
            }

            $meta = get_post_meta($post_id);
            return rest_ensure_response($meta);
        },
        'permission_callback' => '__return_true',
    ]);
});
