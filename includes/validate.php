<?php
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Validate Soustack recipe with simple rules.
 * Placeholder for soustack-core validation; keeps hosting minimal.
 */
function soustack_wordpress_validate(array $recipe): array
{
    $options = soustack_wordpress_get_options();
    $errors = [];

    if (empty($recipe['title'])) {
        $errors[] = 'Title is required.';
    }

    if (! empty($options['strict_validation'])) {
        if (empty($recipe['ingredients'])) {
            $errors[] = 'Ingredients are required in strict mode.';
        }
        if (empty($recipe['instructions'])) {
            $errors[] = 'Instructions are required in strict mode.';
        }
    }

    $valid = empty($errors);

    return [
        'valid' => $valid,
        'errors' => $errors,
        'recipe' => $recipe,
    ];
}

