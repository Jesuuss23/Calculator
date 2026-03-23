<?php

if (!defined('ABSPATH')) exit;

return [
    'One-Time' => [
        [
            'id'               => 'deep_cleaning',
            'name'             => 'Deep cleaning',
            'description'      => 'A complete and detailed cleaning that goes beyond the basics.',
            'precio_minimo' => 300,
            'personas'      => 2,
        ],
        [
            'id'               => 'move_out',
            'name'             => 'Move out',
            'description'      => 'Designed for those relocating, leaving your old property is spotless.',
            'precio_minimo' => 450,
            'personas'      => 2,
        ],
        [
            'id'               => 'move_in',
            'name'             => 'Move in',
            'description'      => 'We leave your new home move-in ready and sanitized.',
            'precio_minimo' => 375,
            'personas'      => 2,
        ],
        [
            'id'               => 'post_construction',
            'name'             => 'Post construction',
            'description'      => 'We remove dust, debris, and residues after renovations.',
            'precio_minimo' => 800,
            'personas'      => 2,
        ],
        [
            'id'               => 'basic_on_demand',
            'name'             => 'Basic on demand',
            'description'      => 'A standard cleaning for small apartments or quick maintenance.',
            'precio_minimo' => 140,
            'personas'      => 1,
        ],
    ],
];