<?php

if (!defined('ABSPATH')) exit;

require_once SE_PLUGIN_PATH . 'includes/ajax-handler.php';


add_action('wp_enqueue_scripts', 'se_load_assets');
function se_load_assets() {

    if (is_singular() && has_shortcode(get_post()->post_content, 'service_estimator')) {

        wp_enqueue_style(
            'service-estimator-style',
            SE_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            filemtime(SE_PLUGIN_PATH . 'assets/css/frontend.css')
        );
        $paypal_opts = get_option('se_paypal_settings', ['client_id' => '', 'currency' => 'USD','redirect_url' => '']);
        if (!empty($paypal_opts['client_id'])) {
            // Cargamos el script oficial de PayPal
            wp_enqueue_script(
                'paypal-sdk', 
                'https://www.paypal.com/sdk/js?client-id=' . esc_attr($paypal_opts['client_id']) . '&currency=' . esc_attr($paypal_opts['currency']), 
                [], 
                null, 
                true // Cargar en el footer
            );
        }

        wp_enqueue_script(
            'service-estimator-script',
            SE_PLUGIN_URL . 'assets/js/frontend.js',
            ['jquery'],
            filemtime(SE_PLUGIN_PATH . 'assets/js/frontend.js'),
            true
        );

        wp_localize_script('service-estimator-script', 'calc_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('calc_form_nonce_action'),
            'currency' => $paypal_opts['currency'],
            'redirect_url' => $paypal_opts['redirect_url']
        ]);
    }
}

add_shortcode('service_estimator', 'se_render_calculator_shortcode');
function se_render_calculator_shortcode() {
    ob_start();
    include SE_PLUGIN_PATH . 'templates/calculator-form.php';
    return ob_get_clean();
}