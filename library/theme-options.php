<?php

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

    // Add submenu item
    add_submenu_page(
        'configuraciones',          // Parent slug
        'Graficas',                 // Page title
        'Graficas',                 // Menu title
        'manage_options',           // Capability
        'conf_graficas',            // Menu slug
        'conf_graficas_page'        // Callback function
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

function configuraciones_page() {
    echo '<h1>Configuraciones del Tema</h1>';
    echo '<p>Bienvenido a las configuraciones del tema. Aquí puedes ajustar las opciones principales para personalizar tu sitio.</p>';

    // Handle logo save
    if (isset($_POST['submit_logo']) && check_admin_referer('guardar_logo_nonce', 'guardar_logo_nonce_field')) {
        if (!empty($_POST['logo_id'])) {
            update_option('theme_logo', intval($_POST['logo_id']));
            echo '<div class="updated"><p>Logotipo guardado correctamente.</p></div>';
        }
    }

    // Handle color save
    if (isset($_POST['submit_colors']) && check_admin_referer('guardar_colores_nonce', 'guardar_colores_nonce_field')) {
        update_option('theme_color_principal', sanitize_hex_color($_POST['color_principal']));
        update_option('theme_color_secundario', sanitize_hex_color($_POST['color_secundario']));
        update_option('theme_color_base', sanitize_hex_color($_POST['color_base']));

        // Generate custom.css file
        generate_dynamic_css();

        echo '<div class="updated"><p>Colores guardados correctamente.</p></div>';
    }

    // Get current logo
    $logo_id = get_option('theme_logo');
    $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';

    // Get current colors
    $color_principal = get_option('theme_color_principal', '#000000');
    $color_secundario = get_option('theme_color_secundario', '#ffffff');
    $color_base = get_option('theme_color_base', '#cccccc');

    // URL to view the custom.css file
    $custom_css_url = get_template_directory_uri() . '/css/custom.css';

    // Display form
    ?>
    <form method="POST">
        <?php wp_nonce_field('guardar_logo_nonce', 'guardar_logo_nonce_field'); ?>
        <fieldset>
            <legend><strong>Subir Logotipo:</strong></legend>
            <div id="logo-preview">
                <?php if ($logo_url): ?>
                    <p>Logotipo actual:</p>
                    <img src="<?php echo esc_url($logo_url); ?>" alt="Logotipo" style="max-width: 200px; height: auto;">
                <?php endif; ?>
            </div>
            <p>
                <button type="button" class="button" id="upload_logo_button">Seleccionar Logotipo</button>
                <input type="hidden" name="logo_id" id="logo_id" value="<?php echo esc_attr($logo_id); ?>">
            </p>
            <p id="save_logo_button_container" style="display: none;">
                <input type="submit" name="submit_logo" class="button button-primary" value="Guardar Logotipo">
            </p>
        </fieldset>
    </form>
    <form method="POST">
        <?php wp_nonce_field('guardar_colores_nonce', 'guardar_colores_nonce_field'); ?>
        <fieldset>
            <legend><strong>Configurar Colores:</strong></legend>
            <p>
                <label for="color_principal">Color Principal:</label><br>
                <input type="text" id="color_principal" name="color_principal" value="<?php echo esc_attr($color_principal); ?>" class="color-picker" data-default-color="#000000">
            </p>
            <p>
                <label for="color_secundario">Color Secundario:</label><br>
                <input type="text" id="color_secundario" name="color_secundario" value="<?php echo esc_attr($color_secundario); ?>" class="color-picker" data-default-color="#ffffff">
            </p>
            <p>
                <label for="color_base">Color Base:</label><br>
                <input type="text" id="color_base" name="color_base" value="<?php echo esc_attr($color_base); ?>" class="color-picker" data-default-color="#cccccc">
            </p>
            <p><input type="submit" name="submit_colors" class="button button-primary" value="Guardar Colores"></p>
        </fieldset>
    </form>
    <p><a href="<?php echo esc_url($custom_css_url); ?>" target="_blank" class="button">Ver custom.css</a></p>
    <script>
        jQuery(document).ready(function($) {
            let mediaUploader;
            const currentLogoId = $('#logo_id').val();

            $('#upload_logo_button').click(function(e) {
                e.preventDefault();
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                mediaUploader = wp.media({
                    title: 'Seleccionar Logotipo',
                    button: { text: 'Usar este logotipo' },
                    multiple: false
                });
                mediaUploader.on('select', function() {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#logo_id').val(attachment.id);
                    $('#logo-preview').html('<p>Logotipo seleccionado:</p><img src="' + attachment.url + '" alt="Logotipo" style="max-width: 200px; height: auto;">');
                    
                    // Show "Guardar Logotipo" button only if the selected logo is different from the current one
                    if (attachment.id !== currentLogoId) {
                        $('#save_logo_button_container').show();
                    } else {
                        $('#save_logo_button_container').hide();
                    }
                });
                mediaUploader.open();
            });

            $('.color-picker').wpColorPicker();
        });
    </script>
    <?php
}

function generate_dynamic_css() {
    $color_principal = get_option('theme_color_principal', '#000000');
    $color_secundario = get_option('theme_color_secundario', '#ffffff');
    $color_base = get_option('theme_color_base', '#cccccc');

    $css_content = "
    /* Custom Theme Colors */
    :root {
        --color-principal: {$color_principal};
        --color-secundario: {$color_secundario};
        --color-base: {$color_base};
    }

    body > div > * h1 {
        color: var(--color-principal);
    }

    /* Elements using color-principal */
    h1 {
        color: var(--color-principal);
    }

    body > * a {
        color: var(--color-principal);
        text-decoration: none;
    }

    body > * a:hover {
        color: var(--color-principal);
        text-decoration: underline;
    }

    ul#menu-menu-principal.nav-menu {
        background: var(--color-principal);
    }

    ul#menu-menu-principal.nav-menu li:hover a, ul#menu-menu-principal.nav-menu li.open a {
    background-color: #20202047;
        }
    .gform_wrapper input:not([type=radio]):not([type=checkbox]):not([type=submit]):not([type=button]):not([type=image]):not([type=file]):focus,
    .gform_wrapper textarea:focus {
        border: 3px solid var(--color-principal);
    }

    .gform_wrapper span.ginput_total {
        color: var(--color-principal);
    }

    .quarter.sidebar .widget_nav_menu ul li a:hover {
        color: var(--color-principal);
    }

    .gform_wrapper .gform_footer input[type=submit],
    .gform_wrapper .gform_footer input[type=button],
    .gform_wrapper .gform_footer input[type=reset],
    .gform_wrapper .gform_footer button {
        background-color: var(--color-principal);
    }
    ";

    $css_dir = get_template_directory() . '/css';
    $css_file = $css_dir . '/custom.css';

    // Ensure the directory exists
    if (!file_exists($css_dir)) {
        if (!mkdir($css_dir, 0755, true)) {
            error_log("Error: Could not create directory {$css_dir}. Check permissions.");
            return;
        }
    }

    // Check if the directory is writable
    if (!is_writable($css_dir)) {
        error_log("Error: Directory {$css_dir} is not writable. Check permissions.");
        return;
    }

    // Write the CSS file
    if (file_put_contents($css_file, $css_content) === false) {
        error_log("Error: Could not write to file {$css_file}. Check permissions.");
    } else {
        error_log("Success: custom.css file created at {$css_file}");
    }
}

function enqueue_custom_css_if_exists() {
    $css_file_path = get_template_directory() . '/css/custom.css';
    $css_file_url = get_template_directory_uri() . '/css/custom.css';

    // Check if the file exists before enqueuing
    if (file_exists($css_file_path)) {
        wp_enqueue_style('custom-css', $css_file_url, [], null, 'all');
    }
}
add_action('wp_enqueue_scripts', 'enqueue_custom_css_if_exists', 100); // Priority 100 to ensure it loads last

function conf_graficas_page() {
    echo '<h1>Configuración de Gráficas</h1>';
    // Add content for the "Graficas" submenu page here
}

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
        return false;
    } else {
        $code = wp_remote_retrieve_response_code($response);
        return ($code === 200);
    }
}

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
    }

    if (isset($_POST['probar_conexion_tejer_red']) && check_admin_referer('probar_conexion_tejer_red_nonce')) {
        $resultado = tejer_red_test_api_connection();
        if ($resultado === true) {
            echo '<div class="updated"><p>Prueba de conexión exitosa.</p></div>';
        } else {
            echo '<div class="error"><p>Prueba de conexión fallida. Revisa la configuración.</p></div>';
        }
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
        <form method="POST">
            <?php wp_nonce_field('probar_conexion_tejer_red_nonce'); ?>
            <input type="submit" name="probar_conexion_tejer_red" class="button button-secondary" value="Probar Conexión">
        </form>
    </div>
    <?php
}

?>
