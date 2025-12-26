<?php
if (! defined('ABSPATH')) {
    exit;
}

require_once ABSPATH . 'wp-admin/includes/file.php';

/**
 * Generate Soustack JSON for a post.
 */
function soustack_wordpress_generate_json(int $post_id): array
{
    $post = get_post($post_id);
    if (! $post) {
        return [
            'valid' => false,
            'headers' => ['X-Soustack-Valid' => 'false'],
            'body' => ['error' => 'Post not found'],
        ];
    }

    $schema = soustack_wordpress_extract_schema($post);
    $soustack = $schema ? soustack_wordpress_convert_schema_to_soustack($schema) : soustack_wordpress_fallback_soustack($post);

    $validated = soustack_wordpress_validate($soustack);

    $headers = ['Content-Type' => 'application/vnd.soustack+json'];
    $headers['X-Soustack-Valid'] = $validated['valid'] ? 'true' : 'false';

    $body = $validated['valid'] ? $validated['recipe'] : [
        'recipe' => $validated['recipe'],
        'x-errors' => $validated['errors'],
    ];

    return [
        'valid' => $validated['valid'],
        'headers' => $headers,
        'body' => $body,
    ];
}

/**
 * Attempt to extract Schema.org JSON-LD from post content.
 */
function soustack_wordpress_extract_schema(WP_Post $post): ?array
{
    if (! has_block('core/html', $post) && ! has_filter('the_content', 'do_blocks')) {
        // Fallback: still search raw content.
    }

    $content = $post->post_content;
    if (function_exists('apply_filters')) {
        $content = apply_filters('the_content', $content);
    }

    if (preg_match_all('#<script type="application/ld\+json">(.*?)</script>#is', $content, $matches)) {
        foreach ($matches[1] as $json) {
            $decoded = json_decode($json, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if (isset($decoded['@type']) && strtolower($decoded['@type']) === 'recipe') {
                    return $decoded;
                }
                if (is_array($decoded)) {
                    // Some themes wrap graph in @graph.
                    if (isset($decoded['@graph']) && is_array($decoded['@graph'])) {
                        foreach ($decoded['@graph'] as $item) {
                            if (isset($item['@type']) && strtolower($item['@type']) === 'recipe') {
                                return $item;
                            }
                        }
                    }
                }
            }
        }
    }

    return null;
}

/**
 * Convert Schema.org recipe to Soustack recipe.
 */
function soustack_wordpress_convert_schema_to_soustack(array $schema): array
{
    $recipe = [
        'title' => $schema['name'] ?? '',
        'description' => $schema['description'] ?? '',
        'yield' => $schema['recipeYield'] ?? '',
        'total_time' => $schema['totalTime'] ?? '',
        'ingredients' => [],
        'instructions' => [],
        'url' => $schema['url'] ?? get_permalink(),
    ];

    if (! empty($schema['recipeIngredient']) && is_array($schema['recipeIngredient'])) {
        $recipe['ingredients'] = array_values(array_filter(array_map('wp_strip_all_tags', $schema['recipeIngredient'])));
    }

    if (! empty($schema['recipeInstructions'])) {
        if (is_array($schema['recipeInstructions'])) {
            foreach ($schema['recipeInstructions'] as $step) {
                if (is_string($step)) {
                    $recipe['instructions'][] = wp_strip_all_tags($step);
                } elseif (is_array($step) && isset($step['text'])) {
                    $recipe['instructions'][] = wp_strip_all_tags($step['text']);
                }
            }
        } elseif (is_string($schema['recipeInstructions'])) {
            $lines = preg_split('/\r?\n/', $schema['recipeInstructions']);
            foreach ($lines as $line) {
                $line = trim(wp_strip_all_tags($line));
                if ($line !== '') {
                    $recipe['instructions'][] = $line;
                }
            }
        }
    }

    return $recipe;
}

/**
 * Lightweight fallback when no Schema.org recipe exists.
 */
function soustack_wordpress_fallback_soustack(WP_Post $post): array
{
    $content = wp_strip_all_tags($post->post_content);
    $lines = array_values(array_filter(array_map('trim', explode("\n", $content))));

    return [
        'title' => get_the_title($post),
        'description' => '',
        'yield' => '',
        'total_time' => '',
        'ingredients' => [],
        'instructions' => $lines,
        'url' => get_permalink($post),
    ];
}

