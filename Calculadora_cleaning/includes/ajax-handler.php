<?php
// /includes/ajax-handler.php
if (!defined('ABSPATH')) exit;

function round_up_to_half($n) { return ceil($n * 2) / 2; }

add_action('wp_ajax_process_form_step_by_step', 'se_handle_form_submission');
add_action('wp_ajax_nopriv_process_form_step_by_step', 'se_handle_form_submission');

function se_handle_form_submission() {
    check_ajax_referer('calc_form_nonce_action', 'calc_form_nonce');

    // --- DATOS POR DEFECTO ---
    $default_services = [ 'deep_cleaning' => ['name' => 'Deep cleaning', 'description' => 'A complete clean', 'precio_minimo' => 300, 'personas' => 2], 'move_out' => ['name' => 'Move out', 'description' => 'For relocating', 'precio_minimo' => 450, 'personas' => 2], 'move_in' => ['name' => 'Move in', 'description' => 'Ready for move-in', 'precio_minimo' => 375, 'personas' => 2], 'post_construction' => ['name' => 'Post construction', 'description' => 'After renovations', 'precio_minimo' => 800, 'personas' => 2], 'basic_on_demand' => ['name' => 'Basic on demand', 'description' => 'Quick maintenance', 'precio_minimo' => 140, 'personas' => 1] ];
    $default_addons = [ 'inside_fridge' => ['name' => 'Inside Fridge', 'price' => 50.00, 'unit' => '/each', 'has_quantity' => 'yes', 'has_details' => 'yes'], 'inside_oven' => ['name' => 'Inside Oven', 'price' => 50.00, 'unit' => '/each', 'has_quantity' => 'yes', 'has_details' => 'yes'], 'balcony_clean' => ['name' => 'Balcony Clean', 'price' => 55.00, 'unit' => '/each', 'has_quantity' => 'yes', 'has_details' => 'yes'], 'garage' => ['name' => 'Garage', 'price' => 55.00, 'unit' => '/each', 'has_quantity' => 'yes', 'has_details' => 'yes'] ];
    $default_last_cleaning = [ '1m' => ['name' => '1 Month ago or less', 'efficiency_default' => 550, 'efficiency_post_construction' => 350], '2m' => ['name' => '2 Months ago', 'efficiency_default' => 550, 'efficiency_post_construction' => 350], '3m' => ['name' => 'More than 3 months ago', 'efficiency_default' => 350, 'efficiency_post_construction' => 200], '6m' => ['name' => 'More than 6 months ago', 'efficiency_default' => 250, 'efficiency_post_construction' => 150] ];
    $default_rates = [ 1 => ['others' => 70.00, 'post_construction' => 80.00], 2 => ['others' => 150.00, 'post_construction' => 160.00], 3 => ['others' => 225.00, 'post_construction' => 240.00], 4 => ['others' => 300.00, 'post_construction' => 320.00] ];
    
    // --- CARGAR DATOS ---
    $services_from_db = get_option('se_services', $default_services);
    $services_data = ['One-Time' => is_array($services_from_db) ? array_values($services_from_db) : []];
    $last_cleaning_data = get_option('se_last_cleaning', $default_last_cleaning);
    $addons_data = get_option('se_addons', $default_addons);
    $hourly_rates = get_option('se_hourly_rates', $default_rates);
    $staffing_rules = [ 1 => ['min_hours' => 2.0, 'max_hours' => 3.99], 2 => ['min_hours' => 4.0, 'max_hours' => 8.49], 3 => ['min_hours' => 8.5, 'max_hours' => 12.49], 4 => ['min_hours' => 12.5, 'max_hours' => 16.49] ];

    // VALIDACIÓN ZIP
    $allowed_zip_codes = [ 33301, 33302, 33303, 33304, 33305, 33306, 33307, 33308, 33309, 33310, 33311, 33312, 33313, 33314, 33315, 33316, 33317, 33318, 33319, 33320, 33321, 33322, 33323, 33324, 33325, 33326, 33327, 33328, 33329, 33330, 33331, 33332, 33334, 33335, 33336, 33337, 33338, 33339, 33340, 33345, 33346, 33348, 33349, 33351, 33355, 33359, 33394 ];
    $zip_code_input = isset($_POST['zip']) ? sanitize_text_field($_POST['zip']) : '';
    $zip_code = intval($zip_code_input);

    if (empty($zip_code) || !in_array($zip_code, $allowed_zip_codes)) {
        wp_send_json_error(['message' => 'Sorry, we do not service this ZIP code.']);
        wp_die();
    }
    
    // RECOGER DATOS
    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : 'Friend';
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    $sqft = isset($_POST['sqft']) ? intval($_POST['sqft']) : 0;
    $service_id = isset($_POST['service']) ? sanitize_text_field($_POST['service']) : '';
    $last_cleaning_id = isset($_POST['last_cleaning']) ? sanitize_text_field($_POST['last_cleaning']) : '';
    $addons_input = isset($_POST['addons']) && is_array($_POST['addons']) ? $_POST['addons'] : [];

    $service_info = null;
    if (!empty($services_from_db[$service_id])) {
        $service_info = $services_from_db[$service_id];
        $service_info['id'] = $service_id;
    }

    if ($service_info === null || empty($last_cleaning_id) || $sqft < 1) {
        wp_send_json_error(['message' => 'Please select a valid service, cleaning frequency, and property size.']);
    }

    // CÁLCULOS
    $is_post_construction = ($service_id === 'post_construction');
    $efficiency_key = $is_post_construction ? 'efficiency_post_construction' : 'efficiency_default';
    $rendimiento_1_persona = $last_cleaning_data[$last_cleaning_id][$efficiency_key] ?? 0;
    if (empty($rendimiento_1_persona)) { wp_send_json_error(['message' => 'Error: Configuration missing.']); }

    $horas_base_1_persona = ($rendimiento_1_persona > 0) ? $sqft / $rendimiento_1_persona : 0;
    $personas_finales = 1;
    foreach ($staffing_rules as $persons => $rule) {
        if ($horas_base_1_persona >= $rule['min_hours'] && $horas_base_1_persona <= $rule['max_hours']) {
            $personas_finales = $persons;
            break;
        }
    }
    if ($horas_base_1_persona > end($staffing_rules)['max_hours']) { $personas_finales = max(array_keys($staffing_rules)); }
    
    $rendimiento_equipo_final = $rendimiento_1_persona * $personas_finales;
    $duracion_final_base = ($rendimiento_equipo_final > 0) ? $sqft / $rendimiento_equipo_final : 0;
    $duracion_final_redondeada = round_up_to_half($duracion_final_base);
    
    $rate_key = $is_post_construction ? 'post_construction' : 'others';
    $precio_minimo = floatval($service_info['precio_minimo']);
    if (!isset($hourly_rates[$personas_finales][$rate_key])) { wp_send_json_error(['message' => "Error: Rate missing."]); }
    $tarifa_equipo = floatval($hourly_rates[$personas_finales][$rate_key]);
    
    $precio_calculado = $duracion_final_redondeada * $tarifa_equipo;
    $costo_laboral = max($precio_calculado, $precio_minimo);
    
    $addons_total_price = 0;
    $addons_list_text = ""; 

    foreach ($addons_input as $id => $details) {
        if (isset($details['selected']) && isset($addons_data[$id])) {
            $addon_info = $addons_data[$id];
            $price = floatval($addon_info['price']);
            $qty = 1;
            if (($addon_info['has_quantity'] ?? 'no') === 'yes' && isset($details['quantity'])) {
                $qty = intval($details['quantity']);
                $price *= $qty;
            }
            $addons_total_price += $price;
            $addons_list_text .= "<li>" . esc_html($addon_info['name']) . " (Qty: $qty) - $" . $price . "</li>";
        }
    }
    $precio_final = $costo_laboral + $addons_total_price;


    $to_company = 'jesuscarhuancho23@gmail.com';
    $to_client = $email; 
    
    $subject = 'Cleaning Estimate: ' . $service_info['name'];
    
    $body = '
    <html>
    <body style="font-family: Arial, sans-serif; color: #333;">
        <h2 style="color:#2c3e50;">Client Details</h2>
        <p><strong>Name:</strong> '.esc_html($name).'</p>
        <p><strong>E-mail:</strong> '.esc_html($email).'</p>
        <p><strong>Phone:</strong> '.esc_html($phone).'</p>
        <p><strong>Zip Code:</strong> '.esc_html($zip_code_input).'</p>
        
        <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
        
        <h2 style="color:#2c3e50;">Service Details</h2>
        <p><strong>Service:</strong> '.esc_html($service_info['name']).'</p>
        <p><strong>Size:</strong> '.esc_html($sqft).' sqft</p>
        <p><strong>Last Cleaning:</strong> '.esc_html($last_cleaning_data[$last_cleaning_id]['name'] ?? $last_cleaning_id).'</p>
        
        '. ($addons_list_text ? '<h3>Add-ons:</h3><ul>'.$addons_list_text.'</ul>' : '') .'
        
        <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
        
        <h2 style="color:#27ae60;">Final Calculation</h2>
        <p style="font-size:18px;"><strong>Estimated Total Price: $'.round($precio_final).'</strong></p>
        <p><strong>Estimated Duration:</strong> '.$duracion_final_redondeada.' Hours</p>
        <p><strong>Recommended Staff:</strong> '.$personas_finales.' People</p>
        
        <p style="margin-top:30px; font-size:12px; color:#888;">This is an automatic estimate.</p>
    </body>
    </html>
    ';

    // d) Configurar Cabeceras
    $headers = array('Content-Type: text/html; charset=UTF-8');
    $headers[] = 'From: NG Cleaning Services <noreply@' . parse_url(home_url(), PHP_URL_HOST) . '>';
    
    wp_mail($to_company, $subject . ' NG Cleaning Services', $body, $headers);

    if (is_email($to_client)) {
        wp_mail($to_client, $subject, $body, $headers);
    }

    $addons_summary_array = [];
    foreach ($addons_input as $id => $details) {
        if (isset($details['selected']) && isset($addons_data[$id])) {
            // ... (tu lógica de cálculo de precio sigue igual) ...
            
            // Guardamos el nombre para el resumen
            $qty_str = ($addons_data[$id]['has_quantity'] ?? 'no') === 'yes' && isset($details['quantity']) ? ' (x'.intval($details['quantity']).')' : '';
            $addons_summary_array[] = $addons_data[$id]['name'] . $qty_str;
        }
    }
    $addons_text_list = !empty($addons_summary_array) ? implode(', ', $addons_summary_array) : 'None';

    // B) Obtener nombre legible de la última limpieza
    $last_cleaning_name = $last_cleaning_data[$last_cleaning_id]['name'] ?? 'not specified';
// Al final de ajax-handler.php
    wp_send_json_success([
'message'           => "Thanks, $name! Your estimate is ready.",
        'final_price'       => round($precio_final),
        
        // DATOS PARA PAYPAL (NUEVOS)
        'client_name'       => $name,
        'client_email'      => $email,
        'client_phone'      => $phone,
        'service_name'      => $service_info['name'],
        'last_cleaning'     => $last_cleaning_name,
        'addons_list'       => $addons_text_list, // Ej: "Fridge (x1), Oven"
        
        'estimated_hours'   => $duracion_final_redondeada,
        'recommended_staff' => $personas_finales,
    ]);
}
