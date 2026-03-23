<?php
if (!defined('ABSPATH')) exit;

$default_services = [
    'deep_cleaning' => ['name' => 'Deep cleaning', 'description' => 'A complete and detailed cleaning that goes beyond the basics.', 'precio_minimo' => 300, 'personas' => 2],
    'move_out' => ['name' => 'Move out', 'description' => 'Designed for those relocating, leaving your old property is spotless.', 'precio_minimo' => 450, 'personas' => 2],
    'move_in' => ['name' => 'Move in', 'description' => 'We leave your new home move-in ready and sanitized.', 'precio_minimo' => 375, 'personas' => 2],
    'post_construction' => ['name' => 'Post construction', 'description' => 'We remove dust, debris, and residues after renovations.', 'precio_minimo' => 800, 'personas' => 2],
    'basic_on_demand' => ['name' => 'Basic on demand', 'description' => 'A standard cleaning for small apartments or quick maintenance.', 'precio_minimo' => 140, 'personas' => 1],
];
$default_addons = [
    'inside_fridge' => ['name' => 'Inside Fridge', 'price' => 50.00, 'unit' => '/each', 'description' => null, 'has_quantity' => 'yes', 'has_details' => 'yes'],
    'inside_oven' => ['name' => 'Inside Oven', 'price' => 50.00, 'unit' => '/each', 'description' => null, 'has_quantity' => 'yes', 'has_details' => 'yes'],
    'balcony_clean' => ['name' => 'Balcony Clean', 'price' => 55.00, 'unit' => '/each', 'description' => null, 'has_quantity' => 'yes', 'has_details' => 'yes'],
    'garage' => ['name' => 'Garage', 'price' => 55.00, 'unit' => '/each', 'description' => null, 'has_quantity' => 'yes', 'has_details' => 'yes'],
];
$default_last_cleaning = [
    '1m' => ['name' => '1 Month ago or less', 'efficiency_default' => 550, 'efficiency_post_construction' => 350],
    '2m' => ['name' => '2 Months ago', 'efficiency_default' => 550, 'efficiency_post_construction' => 350],
    '3m' => ['name' => 'More than 3 months ago', 'efficiency_default' => 350, 'efficiency_post_construction' => 200],
    '6m' => ['name' => ' More than 6 months ago', 'efficiency_default' => 250, 'efficiency_post_construction' => 150],
];

$services_from_db = get_option('se_services', $default_services);
$addons = get_option('se_addons', $default_addons);
$last_cleaning_options = get_option('se_last_cleaning', $default_last_cleaning);

$services = ['One-Time' => $services_from_db];
?>
<div class="calculator-container">
    <form id="calcForm">
        <?php wp_nonce_field('calc_form_nonce_action', 'calc_form_nonce'); ?>
        <h1 class="form-title">Instant Estimate</h1>

        <div class="form-grid">
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="name" placeholder="Name" required>
            <input type="text" name="phone" placeholder="Phone" required>
            <input type="text" name="zip" placeholder="Zip Code" required>
        </div>

        <div class="custom-select-wrapper" id="service-selector">
            <div class="custom-select-trigger"><span>What service are you looking for today?</span><div class="arrow"></div></div>
            <div class="custom-select-options">
                <div class="options-panel">
                    <div class="options-panel-header"><h3>Select a Service</h3><button type="button" class="close-options-btn">&times;</button></div>
                    <div class="options-list">
                        <?php foreach ($services as $category => $service_items) : ?>
                            <?php foreach ($service_items as $id => $service) : ?>
                                <div class="custom-option" data-value='<?php echo json_encode(['id' => $id, 'category' => $category]); ?>'>
                                    <div class="option-content"><strong><?php echo esc_html($service['name']); ?></strong><p><?php echo esc_html($service['description']); ?></p></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <input type="hidden" name="service" id="service_value" required>

        <div class="custom-select-wrapper" id="last-clean-selector">
            <div class="custom-select-trigger"><span>Last clean performed was...</span><div class="arrow"></div></div>
            <div class="custom-select-options">
                 <div class="options-panel">
                    <div class="options-panel-header"><h3>Last Clean Performed</h3><button type="button" class="close-options-btn">&times;</button></div>
                    <div class="options-list">
                        <?php foreach ($last_cleaning_options as $id => $option) : ?>
                            <div class="custom-option" data-value="<?php echo esc_attr($id); ?>">
                                <div class="option-content"><strong><?php echo esc_html($option['name']); ?></strong></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <input type="hidden" name="last_cleaning" id="last_cleaning_value" required>

        <div class="custom-select-wrapper" id="addons-selector">
            <div class="custom-select-trigger"><span>Enhance Your Service with Add-Ons</span><div class="arrow"></div></div>
            <div class="custom-select-options">
                <div class="options-panel">
                    <div class="options-panel-header"><h3>Select Add-ons</h3><button type="button" class="close-options-btn close-addons-btn">Save Add-ons & Continue</button></div>
                    <div class="options-list">
                        <div class="addons-section">
                            <?php foreach ($addons as $id => $addon): ?>
                                <div class="addon-item">
                                    <label class="addon-main-label" for="addon-<?php echo esc_attr($id); ?>">
                                        <input type="checkbox" id="addon-<?php echo esc_attr($id); ?>" name="addons[<?php echo esc_attr($id); ?>][selected]">
                                        <div class="addon-info">
                                            <span class="addon-price">$<?php echo esc_html($addon['price']); ?></span>
                                            <span class="addon-name"><?php echo esc_html($addon['name']); ?></span>
                                            <?php if (!empty($addon['description'])): ?><span class="addon-description"><?php echo esc_html($addon['description']); ?></span><?php endif; ?>
                                        </div>
                                        <span class="addon-unit"><?php echo esc_html($addon['unit']); ?></span>
                                    </label>
                                    <div class="addon-details">
                                        <?php if (($addon['has_quantity'] ?? 'no') === 'yes'): ?>
                                            <div class="addon-detail-group">
                                                <label for="quantity-<?php echo esc_attr($id); ?>">How many?</label>
                                                <div class="stepper-controls">
                                                    <button type="button" class="stepper-btn minus" data-target="quantity-<?php echo esc_attr($id); ?>">−</button>
                                                    <input type="number" id="quantity-<?php echo esc_attr($id); ?>" name="addons[<?php echo esc_attr($id); ?>][quantity]" value="1" min="1">
                                                    <button type="button" class="stepper-btn plus" data-target="quantity-<?php echo esc_attr($id); ?>">+</button>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (($addon['has_details'] ?? 'no') === 'yes'): ?>
                                            <div class="addon-detail-group">
                                                <label for="details-<?php echo esc_attr($id); ?>">Please specify which</label>
                                                <textarea id="details-<?php echo esc_attr($id); ?>" name="addons[<?php echo esc_attr($id); ?>][details]" rows="3"></textarea>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="numeric-selectors-grid">
            <div class="numeric-input-wrapper" id="sqft-wrapper">
                <div class="numeric-trigger">
                    <span class="numeric-label">Home Size (Sqft)</span>
                    <input type="number" name="sqft" id="sqft_input" value="650" placeholder="E.g., 1200" min="1" required>
                </div>
            </div>
            <div class="custom-select-wrapper" id="beds-selector">
                <div class="numeric-trigger"><span class="numeric-label">Beds</span><span class="numeric-value">1</span></div>
                <div class="custom-select-options">
                    <div class="options-panel">
                        <div class="options-panel-header"><h3>Select Beds</h3><button type="button" class="close-options-btn">&times;</button></div>
                        <div class="options-list numeric-options-list">
                            <?php for ($i = 1; $i <= 10; $i++) : ?>
                                <div class="custom-option" data-value="<?php echo $i; ?>"><div class="option-content"><strong><?php echo $i; ?></strong></div></div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>
            <input type="hidden" name="beds" id="beds_value" value="1" required>
            <div class="custom-select-wrapper" id="baths-selector">
                <div class="numeric-trigger"><span class="numeric-label">Baths</span><span class="numeric-value">1</span></div>
                <div class="custom-select-options">
                    <div class="options-panel">
                        <div class="options-panel-header"><h3>Select Baths</h3><button type="button" class="close-options-btn">&times;</button></div>
                        <div class="options-list numeric-options-list">
                            <?php for ($i = 1; $i <= 10; $i++) : ?>
                                <div class="custom-option" data-value="<?php echo $i; ?>"><div class="option-content"><strong><?php echo $i; ?></strong></div></div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>
            <input type="hidden" name="baths" id="baths_value" value="1" required>
        </div>
        
        <div id="calc-result"></div>
        <div id="calc-result"></div>

 <div class="submission-area">
            <button type="submit" id="calculate-btn">Get instant price</button>
            <div id="price-display">$0</div>
        </div>

        <div id="calc-result"></div>

        <div id="paypal-payment-section" style="display:none; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
            <h3 style="text-align: center; margin-bottom: 15px;">Complete your Reservation</h3>
            <div id="paypal-button-container"></div>
        </div>

    </form>
</div>
    </form>
</div>