<?php
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Inject discovery link for Soustack JSON.
 */
function soustack_wordpress_add_discovery_link(): void
{
    if (! is_singular('post')) {
        return;
    }

    $options = soustack_wordpress_get_options();
    if (empty($options['enabled'])) {
        return;
    }

    $post_id = get_the_ID();
    if (! $post_id) {
        return;
    }

    $url = soustack_wordpress_get_endpoint_url($post_id);
    if (! $url) {
        return;
    }

    echo '<link rel="alternate" type="application/vnd.soustack+json" href="' . esc_url($url) . '" />' . "\n";
}
add_action('wp_head', 'soustack_wordpress_add_discovery_link');

/**
 * Build Soustack endpoint URL for a post.
 */
function soustack_wordpress_get_endpoint_url(int $post_id): ?string
{
    $options = soustack_wordpress_get_options();
    $post = get_post($post_id);
    if (! $post) {
        return null;
    }

    if (get_option('permalink_structure')) {
        $base = trailingslashit(home_url($options['base_path'] ?? '/soustack/'));
        return $base . urlencode($post->post_name) . '.soustack.json';
    }

    return add_query_arg([
        'soustack' => '1',
        'post' => $post->ID,
    ], home_url('/'));
}

