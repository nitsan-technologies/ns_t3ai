<?php

use NITSAN\NsT3Ai\Controller\T3AiController;
use NITSAN\NsT3Ai\Override\LocalizationController;

return [
    'seo_title_generation' => [
        'path' => '/generate/page-title',
        'target' => T3AiController::class . '::generatePageTitleAction'
    ],
    'save_request' => [
        'path' => '/generate/save',
        'target' => T3AiController::class . '::saveAction'
    ],
    'rte_content_generation' => [
        'path' => '/backend/rte-content-generation',
        'target' => T3AiController::class . '::generateRteContentAction'
    ],
    'rte_template' => [
        'path' => '/render/rte-template',
        'target' => T3AiController::class . '::renderRteTemplate'
    ],
];
