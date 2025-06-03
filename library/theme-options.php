<?php

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

    // Handle logo removal
    if (isset($_POST['remove_logo']) && check_admin_referer('guardar_logo_nonce', 'guardar_logo_nonce_field')) {
        delete_option('theme_logo');
        echo '<div class="updated"><p>Logotipo eliminado correctamente.</p></div>';
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

    // Handle custom.css reset
    if (isset($_POST['reset_custom_css']) && check_admin_referer('reset_custom_css_nonce', 'reset_custom_css_nonce_field')) {
        reset_custom_css();
        echo '<div class="updated"><p>Archivo custom.css eliminado y colores restablecidos a los valores predeterminados.</p></div>';
    }

    // Get current logo
    $logo_id = get_option('theme_logo');
    $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';

    // Get current colors
    $color_principal = get_option('theme_color_principal', '#000000');
    $color_secundario = get_option('theme_color_secundario', '#ffffff');
    $color_base = get_option('theme_color_base', '#cccccc');

    // Check if custom.css exists
    $css_file_path = get_template_directory() . '/css/custom.css';
    $custom_css_exists = file_exists($css_file_path);
    $custom_css_url = $custom_css_exists ? get_template_directory_uri() . '/css/custom.css' : '';

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
            <?php if ($logo_url): ?>
                <p>
                    <input type="submit" name="remove_logo" class="button button-secondary" style="background-color: #d9534f; color: white;" value="Eliminar Logotipo">
                </p>
            <?php endif; ?>
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
    <?php if ($custom_css_exists): ?>
        <form method="POST">
            <?php wp_nonce_field('reset_custom_css_nonce', 'reset_custom_css_nonce_field'); ?>
            <fieldset>
                <legend><strong>Restablecer Archivo CSS:</strong></legend>
                <p>
                    <input type="submit" name="reset_custom_css" class="button button-secondary" style="background-color: #d9534f; color: white;" value="Eliminar custom.css">
                </p>
            </fieldset>
        </form>
        <p><a href="<?php echo esc_url($custom_css_url); ?>" target="_blank" class="button">Ver custom.css</a></p>
    <?php endif; ?>
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

    body footer {
        background-color: var(--color-secundario);
    }
    html body {
        background-color: var(--color-base);
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

function reset_custom_css() {
    $css_file = get_template_directory() . '/css/custom.css';

    // Check if the file exists and delete it
    if (file_exists($css_file)) {
        unlink($css_file);
    }

    // Reset colors to default values
    update_option('theme_color_principal', '#674ea7'); // Default purple
    update_option('theme_color_secundario', '#000000'); // Default black
    update_option('theme_color_base', '#cccccc'); // Default grey
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
