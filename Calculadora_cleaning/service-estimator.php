<?php
/**
 * Plugin Name: Service Estimator
 * Description: Un calculador de precios de servicios con integración PayPal.
 * Version: 1.3
 * Author: Tu Nombre
 */

if (!defined('ABSPATH')) exit; 

define('SE_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SE_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once SE_PLUGIN_PATH . 'includes/setup.php';

// ===================================================================
// == CÓDIGO DEL PANEL DE ADMINISTRACIÓN
// ===================================================================

add_action('admin_menu', 'se_add_admin_menu');

function se_add_admin_menu() {
    add_menu_page(
        'Ajustes de la Calculadora', 
        'Calculadora',               
        'manage_options',            
        'se-calculator-settings',    
        'se_settings_page_html',     
        'dashicons-money-alt',       
        20                           
    );
}

function se_settings_page_html() {
    if (!current_user_can('manage_options')) return;

    // --- DATOS POR DEFECTO ---
    $default_services = [ 'deep_cleaning' => ['name' => 'Deep cleaning', 'description' => 'A complete clean', 'precio_minimo' => 300, 'personas' => 2], 'move_out' => ['name' => 'Move out', 'description' => 'For relocating', 'precio_minimo' => 450, 'personas' => 2], 'move_in' => ['name' => 'Move in', 'description' => 'Ready for move-in', 'precio_minimo' => 375, 'personas' => 2], 'post_construction' => ['name' => 'Post construction', 'description' => 'After renovations', 'precio_minimo' => 800, 'personas' => 2], 'basic_on_demand' => ['name' => 'Basic on demand', 'description' => 'Quick maintenance', 'precio_minimo' => 140, 'personas' => 1] ];
    $default_addons = [ 'inside_fridge' => ['name' => 'Inside Fridge', 'price' => 50.00, 'unit' => '/each', 'description' => '', 'has_quantity' => 'yes', 'has_details' => 'yes'], 'inside_oven' => ['name' => 'Inside Oven', 'price' => 50.00, 'unit' => '/each', 'description' => '', 'has_quantity' => 'yes', 'has_details' => 'yes'], 'balcony_clean' => ['name' => 'Balcony Clean', 'price' => 55.00, 'unit' => '/each', 'description' => '', 'has_quantity' => 'yes', 'has_details' => 'yes'], 'garage' => ['name' => 'Garage', 'price' => 55.00, 'unit' => '/each', 'description' => '', 'has_quantity' => 'yes', 'has_details' => 'yes'] ];
    $default_last_cleaning = [ '1m' => ['name' => '1 Month ago or less', 'efficiency_default' => 550, 'efficiency_post_construction' => 350], '2m' => ['name' => '2 Months ago', 'efficiency_default' => 550, 'efficiency_post_construction' => 350], '3m' => ['name' => 'More than 3 months ago', 'efficiency_default' => 350, 'efficiency_post_construction' => 200], '6m' => ['name' => 'More than 6 months ago', 'efficiency_default' => 250, 'efficiency_post_construction' => 150] ];
    $default_rates = [ 1 => ['others' => 70.00, 'post_construction' => 80.00], 2 => ['others' => 150.00, 'post_construction' => 160.00], 3 => ['others' => 225.00, 'post_construction' => 240.00], 4 => ['others' => 300.00, 'post_construction' => 320.00] ];
    
    // --- PROCESO DE GUARDADO ---
    if (isset($_POST['submit']) && check_admin_referer('se_save_settings_nonce')) {
        
        // 1. Guardar Servicios
        $services_to_save = [];
        if (isset($_POST['services']) && is_array($_POST['services'])) {
            foreach ($_POST['services'] as $key => $s) {
                if (isset($s['delete'])) continue;
                $current_id = !empty($s['id']) ? sanitize_key($s['id']) : sanitize_key($key);
                if (empty($current_id) || empty($s['name'])) continue;
                $services_to_save[$current_id] = [
                    'name' => sanitize_text_field($s['name']),
                    'description' => sanitize_textarea_field($s['description']),
                    'precio_minimo' => floatval($s['precio_minimo']),
                    'personas' => intval($s['personas'])
                ];
            }
        }
        update_option('se_services', $services_to_save);

        // 2. Guardar Addons
        $addons_to_save = [];
        if (isset($_POST['addons']) && is_array($_POST['addons'])) {
            foreach ($_POST['addons'] as $key => $a) {
                if (isset($a['delete'])) continue;
                $current_id = !empty($a['id']) ? sanitize_key($a['id']) : sanitize_key($key);
                if (empty($current_id) || empty($a['name'])) continue;
                $addons_to_save[$current_id] = [
                    'name' => sanitize_text_field($a['name']),
                    'price' => floatval($a['price']),
                    'unit' => sanitize_text_field($a['unit']),
                    'has_quantity' => isset($a['has_quantity']) ? 'yes' : 'no',
                    'has_details' => isset($a['has_details']) ? 'yes' : 'no'
                ];
            }
        }
        update_option('se_addons', $addons_to_save);
        
        // 3. Guardar Frecuencia
        $last_cleaning_to_save = [];
        if(isset($_POST['last_cleaning']) && is_array($_POST['last_cleaning'])){
            foreach($_POST['last_cleaning'] as $id => $lc){
                $last_cleaning_to_save[sanitize_key($id)] = [
                    'name' => sanitize_text_field($lc['name']),
                    'efficiency_default' => intval($lc['efficiency_default']),
                    'efficiency_post_construction' => intval($lc['efficiency_post_construction'])
                ];
            }
        }
        update_option('se_last_cleaning', $last_cleaning_to_save);
        
        // 4. Guardar Tarifas
        $rates_to_save = [];
        if(isset($_POST['rates']) && is_array($_POST['rates'])){
            foreach($_POST['rates'] as $persons => $values){
                $rates_to_save[intval($persons)] = [
                    'others' => floatval($values['others']),
                    'post_construction' => floatval($values['post_construction'])
                ];
            }
        }
        update_option('se_hourly_rates', $rates_to_save);

        // 5. GUARDAR CONFIGURACIÓN DE PAYPAL (NUEVO)
        if (isset($_POST['paypal'])) {
            update_option('se_paypal_settings', [
                'client_id' => sanitize_text_field($_POST['paypal']['client_id']),
                'currency' => sanitize_text_field($_POST['paypal']['currency']),
                'redirect_url' => esc_url_raw($_POST['paypal']['redirect_url']) 
            ]);
        }

        echo '<div class="notice notice-success is-dismissible"><p>¡Ajustes guardados!</p></div>';
    }

    // --- CARGAR DATOS PARA MOSTRAR ---
    $services = get_option('se_services', $default_services);
    $addons = get_option('se_addons', $default_addons);
    $last_cleaning = get_option('se_last_cleaning', $default_last_cleaning);
    $rates = get_option('se_hourly_rates', $default_rates);
    // Cargar opciones de PayPal
    $paypal_opts = get_option('se_paypal_settings', ['client_id' => '', 'currency' => 'USD']);

    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form method="POST" action="">
            
            <h2>Servicios</h2>
            <table class="widefat fixed">
                <thead><tr><th>ID</th><th>Name</th><th>Description</th><th>Minimum Price ($)</th><th>Pers. Mín.<th><th>Eliminate</th></tr></thead>
                <tbody>
                <?php foreach ($services as $id => $s) : ?>
                    <tr>
                        <td><strong><?php echo esc_attr($id); ?></strong></td>
                        <td><input type="text" name="services[<?php echo esc_attr($id); ?>][name]" value="<?php echo esc_attr($s['name']); ?>" class="regular-text"></td>
                        <td><textarea name="services[<?php echo esc_attr($id); ?>][description]" rows="2"><?php echo esc_textarea($s['description'] ?? ''); ?></textarea></td>
                        <td><input type="number" step="0.01" name="services[<?php echo esc_attr($id); ?>][precio_minimo]" value="<?php echo esc_attr($s['precio_minimo']); ?>" class="small-text"></td>
                        <td><input type="number" step="1" name="services[<?php echo esc_attr($id); ?>][personas]" value="<?php echo esc_attr($s['personas']); ?>" class="small-text"></td>
                        <td><input type="checkbox" name="services[<?php echo esc_attr($id); ?>][delete]"></td>
                    </tr>
                <?php endforeach; ?>
                <tr style="background-color:#f9f9f9;">
                    <td><input type="text" name="services[new_service][id]" placeholder="nuevo_id"></td>
                    <td><input type="text" name="services[new_service][name]"></td>
                    <td><textarea name="services[new_service][description]"></textarea></td>
                    <td><input type="number" step="0.01" name="services[new_service][precio_minimo]"></td>
                    <td><input type="number" step="1" name="services[new_service][personas]"></td>
                    <td><em>Add</em></td>
                </tr>
                </tbody>
            </table>

            <hr style="margin: 2em 0;">

            <h2>Addons (Extras)</h2>
            <table class="widefat fixed">
                <thead><tr><th>ID</th><th>Name</th><th>Price ($)</th><th>Unit</th><th>Amount?</th><th>Details?</th><th>Eliminate</th></tr></thead>
                <tbody>
                <?php foreach ($addons as $id => $a) : ?>
                    <tr>
                        <td><strong><?php echo esc_attr($id); ?></strong></td>
                        <td><input type="text" name="addons[<?php echo esc_attr($id); ?>][name]" value="<?php echo esc_attr($a['name']); ?>"></td>
                        <td><input type="number" step="0.01" name="addons[<?php echo esc_attr($id); ?>][price]" value="<?php echo esc_attr($a['price']); ?>" class="small-text"></td>
                        <td><input type="text" name="addons[<?php echo esc_attr($id); ?>][unit]" value="<?php echo esc_attr($a['unit']); ?>" class="small-text"></td>
                        <td><input type="checkbox" name="addons[<?php echo esc_attr($id); ?>][has_quantity]" value="yes" <?php checked($a['has_quantity'] ?? 'no', 'yes'); ?>></td>
                        <td><input type="checkbox" name="addons[<?php echo esc_attr($id); ?>][has_details]" value="yes" <?php checked($a['has_details'] ?? 'no', 'yes'); ?>></td>
                        <td><input type="checkbox" name="addons[<?php echo esc_attr($id); ?>][delete]"></td>
                    </tr>
                <?php endforeach; ?>
                <tr style="background-color:#f9f9f9;">
                    <td><input type="text" name="addons[new_addon][id]" placeholder="nuevo_id"></td>
                    <td><input type="text" name="addons[new_addon][name]"></td>
                    <td><input type="number" step="0.01" name="addons[new_addon][price]"></td>
                    <td><input type="text" name="addons[new_addon][unit]"></td>
                    <td><input type="checkbox" name="addons[new_addon][has_quantity]" value="yes"></td>
                    <td><input type="checkbox" name="addons[new_addon][has_details]" value="yes"></td>
                    <td><em>Add</em></td>
                </tr>
                </tbody>
            </table>

            <hr style="margin: 2em 0;">

            <h2>Cleaning Frequency (Efficiency)</h2>
            <table class="form-table">
                <?php foreach($last_cleaning as $id => $lc): ?>
                <tr>
                    <th scope="row"><label>ID "<?php echo $id; ?>": <input type="text" name="last_cleaning[<?php echo esc_attr($id); ?>][name]" value="<?php echo esc_attr($lc['name']); ?>"></label></th>
                    <td>
                        Normal Efficiency: <input type="number" name="last_cleaning[<?php echo esc_attr($id); ?>][efficiency_default]" value="<?php echo esc_attr($lc['efficiency_default']); ?>" class="small-text">
                        Post-Construction Efficiency: <input type="number" name="last_cleaning[<?php echo esc_attr($id); ?>][efficiency_post_construction]" value="<?php echo esc_attr($lc['efficiency_post_construction']); ?>" class="small-text">
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>

            <hr style="margin: 2em 0;">

            <h2>Hourly Rates</h2>
            <table class="form-table">
                <?php foreach ($rates as $persons => $values) : ?>
                <tr>
                    <th scope="row"><label>Rate for <?php echo $persons; ?> People</label></th>
                    <td>
                        Normal: $<input type="number" step="0.01" name="rates[<?php echo esc_attr($persons); ?>][others]" value="<?php echo esc_attr($values['others']); ?>" class="small-text">
                        Post-Const.: $<input type="number" step="0.01" name="rates[<?php echo esc_attr($persons); ?>][post_construction]" value="<?php echo esc_attr($values['post_construction']); ?>" class="small-text">
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>

            <hr style="margin: 2em 0;">

            <h2>Payment Settings (PayPal)</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">PayPal Client ID</th>
                    <td>
                        <input type="text" name="paypal[client_id]" value="<?php echo esc_attr($paypal_opts['client_id']); ?>" class="regular-text code">
                        <p class="description">Paste your PayPal Developer Client ID (Sandbox or Live) here.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Currency</th>
                    <td>
                        <input type="text" name="paypal[currency]" value="<?php echo esc_attr($paypal_opts['currency']); ?>" class="small-text" placeholder="USD">
                    </td>
                </tr>
                <tr>
                    <th scope="row">Redirect after Successful Payment</th>
                    <td>
                        <input type="url" name="paypal[redirect_url]" value="<?php echo esc_attr($paypal_opts['redirect_url'] ?? ''); ?>" class="regular-text" placeholder="https://YourDomain.com/ThankYou">
                        <p class="description">Paste the full URL. If you leave it blank, there will be no redirection.</p>
                    </td>
                </tr>
            </table>
            
            <?php 
            wp_nonce_field('se_save_settings_nonce'); 
            submit_button('Save All Changes'); 
            ?>
        </form>
    </div>
    <?php
}