<?php

if (!defined('ABSPATH')) exit;

return [
    '1m' => [
        'name' => '1 Month ago or less',
        'efficiency' => ['default' => 550, 'post_construction' => 350],
    ],
    '2m' => [
        'name' => '2 Months ago',
        'efficiency' => ['default' => 550, 'post_construction' => 350],
    ],
    '3m' => [
        'name' => 'More than 3 months ago',
        'efficiency' => ['default' => 350, 'post_construction' => 200],
    ],
    '6m' => [
        'name' => ' More than 6 months ago',
        'efficiency' => ['default' => 250, 'post_construction' => 150],
    ],
];