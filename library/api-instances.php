<?php
/**
 * Página de configuración para la integración con Tejer.Red.
 * Permite seleccionar los endpoints a exponer, definir la URL de la API externa,
 * guardar la configuración y probar la conexión con la API externa.
 */

 function tejer_red_test_api_connection() {
    $external_api_url = get_option('tejer_red_external_api_url', 'http://192.168.1.71:9999');
    $site_url = get_site_url();
    $api_key = get_option('tejer_red_api_key');
    
    if (!$api_key) {
        $api_key = wp_generate_password(32, true, true);
        update_option('tejer_red_api_key', $api_key);
    }
    
    $payload = [
        'url'       => $site_url,
        'api_key'   => $api_key,
        'endpoints' => [],
        'action'    => 'test_connection'
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

    // Construye la URL del nuevo endpoint
    $api_endpoint = rtrim($external_api_url, '/') . '/registrarInstancia';
    
    $response = wp_remote_post($api_endpoint, [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ],
        'body'    => json_encode($payload),
        'sslverify' => false,
        'timeout' => 15
    ]);

    if (is_wp_error($response)) {
        error_log('Error de conexión: ' . $response->get_error_message());
        return false;
    }
    
    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    if ($code === 200) {
        $data = json_decode($body, true);
        return ($data['success'] ?? false);
    }
    
    error_log("Error en la conexión. Código: {$code}, Respuesta: {$body}");
    return false;
}

function tejer_red_slim_api_registrar_instancia($domain, $expuestos = [], &$mensaje_respuesta = '') {
    $url = "http://{$domain}/registrarInstancia";
    $site_url = get_site_url();
    $api_key = get_option('tejer_red_api_key', '');
    
    if (empty($api_key)) {
        $api_key = wp_generate_password(32, true, true);
        update_option('tejer_red_api_key', $api_key);
    }
    
    $payload = [
        'url'       => $site_url,
        'api_key'   => $api_key,
        'endpoints' => [],
        'action'    => 'register_instance'
    ];
    
    if ($expuestos['bitacoras'] ?? false) {
        $payload['endpoints']['bitacoras'] = $site_url . '/wp-json/wp/v2/bitacora';
    }
    if ($expuestos['fosas'] ?? false) {
        $payload['endpoints']['fosas'] = $site_url . '/wp-json/wp/v2/fosa';
    }
    if ($expuestos['indicios'] ?? false) {
        $payload['endpoints']['indicios'] = $site_url . '/wp-json/wp/v2/indicio';
    }

    $response = wp_remote_post($url, [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ],
        'body'    => json_encode($payload),
        'sslverify' => false,
        'timeout' => 15
    ]);

    if (is_wp_error($response)) {
        $mensaje_respuesta = 'Error de conexión: ' . $response->get_error_message();
        error_log($mensaje_respuesta);
        return false;
    }
    
    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if ($code === 200 && isset($data['success'])) {
        $mensaje_respuesta = $data['message'] ?? 'Registro exitoso';
        return $data['success'];
    }
    
    $mensaje_respuesta = "Error en el registro. Código: {$code}";
    if (isset($data['message'])) {
        $mensaje_respuesta .= " - " . $data['message'];
    }
    
    error_log($mensaje_respuesta . " - Respuesta completa: " . $body);
    return false;
}

function tejer_red_api_settings_page() {
    // Manejar el guardado de configuración
    if (isset($_POST['tejer_red_api_settings_nonce']) && wp_verify_nonce($_POST['tejer_red_api_settings_nonce'], 'guardar_tejer_red_api_settings')) {
        $expuestos = [
            'bitacoras' => isset($_POST['exponer_bitacoras']) ? 1 : 0,
            'fosas'     => isset($_POST['exponer_fosas'])     ? 1 : 0,
            'indicios'  => isset($_POST['exponer_indicios'])  ? 1 : 0,
        ];
        update_option('tejer_red_endpoints_expuestos', $expuestos);

        if (isset($_POST['external_api_url'])) {
            // Actualizar para usar solo la URL base (sin instancias.php)
            $api_url = esc_url_raw($_POST['external_api_url']);
            $api_url = str_replace('/instancias.php', '', $api_url);
            update_option('tejer_red_external_api_url', $api_url);
        }

        // Registrar automáticamente en todos los concentradores
        $concentradores = get_option('tejer_red_concentradores', []);
        foreach ($concentradores as $concentrador) {
            $mensaje = '';
            tejer_red_slim_api_registrar_instancia($concentrador['domain'], $expuestos, $mensaje);
        }

        echo '<div class="updated"><p>Configuración guardada y replicada a los concentradores.</p></div>';
    }

    // Probar conexión
    if (isset($_POST['probar_conexion_tejer_red']) && check_admin_referer('probar_conexion_tejer_red_nonce')) {
        $resultado = tejer_red_test_api_connection();
        if ($resultado === true) {
            echo '<div class="updated"><p>Prueba de conexión exitosa.</p></div>';
        } else {
            echo '<div class="error"><p>Prueba de conexión fallida. Revisa la configuración.</p></div>';
        }
    }

    // Obtener configuración actual
    $expuestos = get_option('tejer_red_endpoints_expuestos', ['bitacoras' => 1, 'fosas' => 1, 'indicios' => 1]);
    $external_api_url = get_option('tejer_red_external_api_url', 'http://192.168.1.71:9999');
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
                <label for="external_api_url">URL base del servidor coordinador (ej: http://servidor:9999):</label><br>
                <input type="url" id="external_api_url" name="external_api_url" value="<?php echo esc_attr($external_api_url); ?>" style="width: 100%;" required>
            </fieldset>
            <br>
            <input type="submit" class="button button-primary" value="Guardar Configuración">
        </form>
        <form method="POST">
            <?php wp_nonce_field('probar_conexion_tejer_red_nonce'); ?>
            <input type="submit" name="probar_conexion_tejer_red" class="button button-secondary" value="Probar Conexión">
        </form>

        <!-- Sección de concentradores (manteniendo toda la funcionalidad original) -->
        <div style="margin-top:2em; padding:1em; border:1px solid #ccc; background:#fafafa;">
            <h2>Añadir concentrador</h2>
            <form method="POST" id="form_concentrador">
                <?php wp_nonce_field('tejer_red_slim_api_test_concentrador_nonce', 'tejer_red_slim_api_test_concentrador_nonce_field'); ?>
                <label for="concentrador_dominio"><strong>Dominio del concentrador:</strong></label>
                <input type="text" id="concentrador_dominio" name="concentrador_dominio"
                    placeholder="concentrador.aliado.com"
                    style="width:300px;"
                    required
                    value="<?php
                        if (
                            isset($_POST['concentrador_dominio'], $_POST['tejer_red_slim_api_test_concentrador_nonce_field']) &&
                            wp_verify_nonce($_POST['tejer_red_slim_api_test_concentrador_nonce_field'], 'tejer_red_slim_api_test_concentrador_nonce')
                        ) {
                            $dominio = trim($_POST['concentrador_dominio']);
                            $resultado = tejer_red_slim_api_test_concentrador($dominio, $mensaje_concentrador, $domain_concentrador, $valid_concentrador, $timestamp_concentrador);
                            if ($resultado === true && $valid_concentrador && !empty($domain_concentrador)) {
                                echo esc_attr($domain_concentrador);
                            } else {
                                echo esc_attr($dominio);
                            }
                        }
                    ?>"
                >
                <button type="submit" class="button button-secondary">Probar conexión</button>
            </form>
            <?php
            $concentradores_guardados = get_option('tejer_red_concentradores', []);
            // Añadir a lista
            if (
                isset($_POST['add_concentrador']) && $_POST['add_concentrador'] === '1'
                && isset($_POST['concentrador_dominio'], $_POST['mensaje_concentrador'], $_POST['domain_concentrador'], $_POST['timestamp_concentrador'], $_POST['valid_concentrador'])
                && $_POST['valid_concentrador'] === '1'
                && wp_verify_nonce($_POST['tejer_red_slim_api_test_concentrador_nonce_field'], 'tejer_red_slim_api_test_concentrador_nonce')
            ) {
                $nuevo = [
                    'domain' => sanitize_text_field($_POST['domain_concentrador']),
                    'mensaje' => sanitize_text_field($_POST['mensaje_concentrador']),
                    'timestamp' => sanitize_text_field($_POST['timestamp_concentrador'])
                ];
                $ya_existe = false;
                foreach ($concentradores_guardados as $c) {
                    if ($c['domain'] === $nuevo['domain']) {
                        $ya_existe = true;
                        break;
                    }
                }
                if (!$ya_existe) {
                    $concentradores_guardados[] = $nuevo;
                    update_option('tejer_red_concentradores', $concentradores_guardados);
                    echo '<div class="updated" style="margin-top:1em;"><p>Concentrador añadido a la lista.</p></div>';
                    // Registrar automáticamente usando el nuevo endpoint
                    $mensaje_registro = '';
                    tejer_red_slim_api_registrar_instancia($nuevo['domain'], get_option('tejer_red_endpoints_expuestos', []), $mensaje_registro);
                } else {
                    echo '<div class="notice notice-warning" style="margin-top:1em;"><p>El concentrador ya está en la lista.</p></div>';
                }
            }
            // Probar conexión y mostrar resultado
            elseif (
                isset($_POST['concentrador_dominio'], $_POST['tejer_red_slim_api_test_concentrador_nonce_field'])
                && !isset($_POST['add_concentrador'])
                && wp_verify_nonce($_POST['tejer_red_slim_api_test_concentrador_nonce_field'], 'tejer_red_slim_api_test_concentrador_nonce')
            ) {
                $dominio = trim($_POST['concentrador_dominio']);
                $resultado = tejer_red_slim_api_test_concentrador($dominio, $mensaje_concentrador, $domain_concentrador, $valid_concentrador, $timestamp_concentrador);
                if ($resultado === true) {
                    echo '<div class="updated" style="margin-top:1em;">';
                    echo '<p><strong>Mensaje:</strong> ' . esc_html($mensaje_concentrador) . '</p>';
                    echo '<p><strong>Dominio:</strong> ' . esc_html($domain_concentrador) . '</p>';
                    echo '<p><strong>Valid:</strong> ' . ($valid_concentrador ? 'Sí' : 'No') . '</p>';
                    echo '<p><strong>Timestamp:</strong> ' . esc_html($timestamp_concentrador) . '</p>';
                    echo '</div>';
                    if ($valid_concentrador) {
                        ?>
                        <form method="POST" style="margin-top:1em;">
                            <?php wp_nonce_field('tejer_red_slim_api_test_concentrador_nonce', 'tejer_red_slim_api_test_concentrador_nonce_field'); ?>
                            <input type="hidden" name="concentrador_dominio" value="<?php echo esc_attr($domain_concentrador); ?>">
                            <input type="hidden" name="add_concentrador" value="1">
                            <input type="hidden" name="mensaje_concentrador" value="<?php echo esc_attr($mensaje_concentrador); ?>">
                            <input type="hidden" name="domain_concentrador" value="<?php echo esc_attr($domain_concentrador); ?>">
                            <input type="hidden" name="timestamp_concentrador" value="<?php echo esc_attr($timestamp_concentrador); ?>">
                            <input type="hidden" name="valid_concentrador" value="1">
                            <button type="submit" class="button button-primary">Añadir a lista de concentradores</button>
                        </form>
                        <?php
                    }
                } else {
                    echo '<div class="error" style="margin-top:1em;"><p>Error de conexión o respuesta inválida del concentrador.</p></div>';
                }
            }
            // Mostrar lista de concentradores guardados
            if (!empty($concentradores_guardados)) {
                echo '<div style="margin-top:2em;"><h3>Lista de concentradores añadidos</h3><ul>';
                foreach ($concentradores_guardados as $i => $c) {
                    ?>
                    <li>
                        <strong>Dominio:</strong> <?php echo esc_html($c['domain']); ?>
                        <span style="color:gray;">
                            (<?php echo esc_html($c['mensaje']) . ' - ' . esc_html($c['timestamp']); ?>)
                        </span>
                        <form method="POST" style="display:inline; margin-left:1em;">
                            <?php wp_nonce_field('tejer_red_slim_api_delete_concentrador_nonce', 'tejer_red_slim_api_delete_concentrador_nonce_field'); ?>
                            <input type="hidden" name="delete_concentrador" value="1">
                            <input type="hidden" name="concentrador_domain_delete" value="<?php echo esc_attr($c['domain']); ?>">
                            <button type="submit" class="button button-small" style="color:#fff; background:#d9534f; border:none;">Eliminar</button>
                        </form>
                        <form method="POST" style="display:inline; margin-left:0.5em;">
                            <?php wp_nonce_field('tejer_red_slim_api_registrar_bitacora_nonce', 'tejer_red_slim_api_registrar_bitacora_nonce_field'); ?>
                            <input type="hidden" name="registrar_bitacora" value="1">
                            <input type="hidden" name="concentrador_domain_registrar" value="<?php echo esc_attr($c['domain']); ?>">
                            <?php
                            $expuestos = get_option('tejer_red_endpoints_expuestos', ['bitacoras' => 1, 'fosas' => 1, 'indicios' => 1]);
                            ?>
                            <input type="hidden" name="exponer_bitacoras" value="<?php echo $expuestos['bitacoras'] ? '1' : '0'; ?>">
                            <input type="hidden" name="exponer_fosas" value="<?php echo $expuestos['fosas'] ? '1' : '0'; ?>">
                            <input type="hidden" name="exponer_indicios" value="<?php echo $expuestos['indicios'] ? '1' : '0'; ?>">
                            <button type="submit" class="button button-small" style="color:#fff; background:#5cb85c; border:none;">Registrar Bitácora</button>
                        </form>
                    </li>
                    <?php
                }
                echo '</ul></div>';
            }

            // Procesar borrado de concentrador
            if (
                isset($_POST['delete_concentrador'], $_POST['concentrador_domain_delete'], $_POST['tejer_red_slim_api_delete_concentrador_nonce_field']) &&
                $_POST['delete_concentrador'] === '1' &&
                wp_verify_nonce($_POST['tejer_red_slim_api_delete_concentrador_nonce_field'], 'tejer_red_slim_api_delete_concentrador_nonce')
            ) {
                $domain_to_delete = sanitize_text_field($_POST['concentrador_domain_delete']);
                $concentradores_guardados = get_option('tejer_red_concentradores', []);
                $nuevos = [];
                foreach ($concentradores_guardados as $c) {
                    if ($c['domain'] !== $domain_to_delete) {
                        $nuevos[] = $c;
                    }
                }
                update_option('tejer_red_concentradores', $nuevos);
                echo '<div class="updated" style="margin-top:1em;"><p>Concentrador eliminado de la lista.</p></div>';
            }

            // Procesar registro de bitácora
            if (
                isset($_POST['registrar_bitacora'], $_POST['concentrador_domain_registrar'], $_POST['tejer_red_slim_api_registrar_bitacora_nonce_field']) &&
                $_POST['registrar_bitacora'] === '1' &&
                wp_verify_nonce($_POST['tejer_red_slim_api_registrar_bitacora_nonce_field'], 'tejer_red_slim_api_registrar_bitacora_nonce')
            ) {
                $domain_registrar = sanitize_text_field($_POST['concentrador_domain_registrar']);
                $expuestos = [
                    'bitacoras' => (isset($_POST['exponer_bitacoras']) && ($_POST['exponer_bitacoras'] === '1' || $_POST['exponer_bitacoras'] === 'on')) ? 1 : 0,
                    'fosas'     => (isset($_POST['exponer_fosas']) && ($_POST['exponer_fosas'] === '1' || $_POST['exponer_fosas'] === 'on')) ? 1 : 0,
                    'indicios'  => (isset($_POST['exponer_indicios']) && ($_POST['exponer_indicios'] === '1' || $_POST['exponer_indicios'] === 'on')) ? 1 : 0,
                ];
                $mensaje_respuesta = '';
                $resultado_registro = tejer_red_slim_api_registrar_instancia($domain_registrar, $expuestos, $mensaje_respuesta);
                if ($resultado_registro === true) {
                    echo '<div class="updated" style="margin-top:1em;"><p>Registro de bitácora enviado correctamente.</p>';
                    if ($mensaje_respuesta) {
                        echo '<p><strong>Respuesta del servidor:</strong> ' . esc_html($mensaje_respuesta) . '</p>';
                    }
                    echo '</div>';
                } else {
                    echo '<div class="error" style="margin-top:1em;"><p>Error al registrar la bitácora en el concentrador.</p>';
                    if ($mensaje_respuesta) {
                        echo '<p><strong>Respuesta del servidor:</strong> ' . esc_html($mensaje_respuesta) . '</p>';
                    }
                    echo '</div>';
                }
            }
            ?>
        </div>
    </div>
    <?php
}