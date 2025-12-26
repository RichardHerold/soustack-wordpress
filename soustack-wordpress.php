<?php
/**
 * Plugin Name: Soustack Sidecar for WordPress
 * Description: Publishes Soustack recipe JSON endpoints and discovery links for WordPress posts.
 * Version: 0.1.0
 * Author: Soustack
 * License: MIT
 */

if (! defined('ABSPATH')) {
    exit;
}

define('SOUSTACK_WORDPRESS_OPTION', 'soustack_wordpress_options');

require_once plugin_dir_path(__FILE__) . 'includes/routes.php';
require_once plugin_dir_path(__FILE__) . 'includes/discovery.php';
require_once plugin_dir_path(__FILE__) . 'includes/generate.php';
require_once plugin_dir_path(__FILE__) . 'includes/validate.php';

/**
 * Provide default options merged with saved values.
 */
function soustack_wordpress_get_options(): array
{
    $defaults = [
        'enabled' => 1,
        'base_path' => '/soustack/',
        'strict_validation' => 0,
    ];

    $saved = get_option(SOUSTACK_WORDPRESS_OPTION, []);

    return wp_parse_args($saved, $defaults);
}

/**
 * Ensure defaults exist on activation and flush rules.
 */
function soustack_wordpress_activate(): void
{
    $options = soustack_wordpress_get_options();
    update_option(SOUSTACK_WORDPRESS_OPTION, $options);
    soustack_wordpress_register_rewrite_rules();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'soustack_wordpress_activate');

/**
 * Flush rewrite rules on deactivation to clean up.
 */
function soustack_wordpress_deactivate(): void
{
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'soustack_wordpress_deactivate');

/**
 * Admin settings page registration.
 */
function soustack_wordpress_register_settings_page(): void
{
    add_options_page(
        __('Soustack Sidecar', 'soustack'),
        __('Soustack', 'soustack'),
        'manage_options',
        'soustack-wordpress',
        'soustack_wordpress_render_settings_page'
    );
}
add_action('admin_menu', 'soustack_wordpress_register_settings_page');

/**
 * Register settings and fields.
 */
function soustack_wordpress_register_settings(): void
{
    register_setting('soustack_wordpress', SOUSTACK_WORDPRESS_OPTION);

    add_settings_section(
        'soustack_wordpress_main',
        __('Soustack endpoint settings', 'soustack'),
        '__return_false',
        'soustack_wordpress'
    );

    add_settings_field(
        'soustack_wordpress_enabled',
        __('Enable endpoint', 'soustack'),
        'soustack_wordpress_render_checkbox',
        'soustack_wordpress',
        'soustack_wordpress_main',
        [
            'label_for' => 'soustack_wordpress_enabled',
            'option_key' => 'enabled',
            'description' => __('Turn the Soustack endpoint on or off.', 'soustack'),
        ]
    );

    add_settings_field(
        'soustack_wordpress_base_path',
        __('Endpoint base path', 'soustack'),
        'soustack_wordpress_render_base_path',
        'soustack_wordpress',
        'soustack_wordpress_main'
    );

    add_settings_field(
        'soustack_wordpress_strict_validation',
        __('Strict validation', 'soustack'),
        'soustack_wordpress_render_checkbox',
        'soustack_wordpress',
        'soustack_wordpress_main',
        [
            'label_for' => 'soustack_wordpress_strict_validation',
            'option_key' => 'strict_validation',
            'description' => __('When enabled, lightweight validation requires ingredients and instructions.', 'soustack'),
        ]
    );
}
add_action('admin_init', 'soustack_wordpress_register_settings');

/**
 * Render checkbox fields used by settings.
 */
function soustack_wordpress_render_checkbox(array $args): void
{
    $options = soustack_wordpress_get_options();
    $option_key = $args['option_key'];
    $id = esc_attr($args['label_for']);
    $checked = ! empty($options[$option_key]) ? 'checked' : '';
    echo '<label for="' . $id . '">';
    echo '<input type="checkbox" id="' . $id . '" name="' . SOUSTACK_WORDPRESS_OPTION . '[' . esc_attr($option_key) . ']" value="1" ' . $checked . ' /> ';
    echo esc_html($args['description']);
    echo '</label>';
}

/**
 * Render base path input field.
 */
function soustack_wordpress_render_base_path(): void
{
    $options = soustack_wordpress_get_options();
    $value = isset($options['base_path']) ? $options['base_path'] : '/soustack/';
    echo '<input type="text" id="soustack_wordpress_base_path" name="' . SOUSTACK_WORDPRESS_OPTION . '[base_path]" value="' . esc_attr($value) . '" class="regular-text" />';
    echo '<p class="description">' . esc_html__('Base path for Soustack endpoint (include leading and trailing slashes).', 'soustack') . '</p>';
}

/**
 * Render settings page HTML.
 */
function soustack_wordpress_render_settings_page(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Soustack Sidecar', 'soustack'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('soustack_wordpress');
            do_settings_sections('soustack_wordpress');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

