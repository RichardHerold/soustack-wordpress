<?php
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Register rewrite rules for Soustack endpoint.
 */
function soustack_wordpress_register_rewrite_rules(): void
{
    $options = soustack_wordpress_get_options();
    if (empty($options['enabled'])) {
        return;
    }

    $base = trim($options['base_path'] ?? '/soustack/', '/');
    $regex = $base . '/([^/]+?)\.soustack\.json$';
    add_rewrite_rule($regex, 'index.php?soustack=1&soustack_slug=$matches[1]', 'top');
}
add_action('init', 'soustack_wordpress_register_rewrite_rules');

/**
 * Register custom query vars.
 */
function soustack_wordpress_query_vars(array $vars): array
{
    $vars[] = 'soustack';
    $vars[] = 'soustack_slug';
    return $vars;
}
add_filter('query_vars', 'soustack_wordpress_query_vars');

/**
 * Handle template redirect for Soustack requests.
 */
function soustack_wordpress_template_redirect(): void
{
    $options = soustack_wordpress_get_options();
    if (empty($options['enabled'])) {
        return;
    }

    $soustack_flag = get_query_var('soustack');
    $slug = get_query_var('soustack_slug');

    if (! $soustack_flag) {
        return;
    }

    $post_id = get_query_var('p');
    if (! $post_id && $slug) {
        $post = get_page_by_path($slug, OBJECT, 'post');
        if ($post) {
            $post_id = $post->ID;
        }
    }

    if (! $post_id) {
        $post_id = get_the_ID();
    }

    if (! $post_id) {
        status_header(404);
        wp_send_json(['error' => 'Post not found'], 404);
    }

    $result = soustack_wordpress_generate_json((int) $post_id);
    if (! empty($result['headers'])) {
        foreach ($result['headers'] as $key => $value) {
            header($key . ': ' . $value);
        }
    }

    status_header(200);
    wp_send_json($result['body'], 200);
}
add_action('template_redirect', 'soustack_wordpress_template_redirect');
