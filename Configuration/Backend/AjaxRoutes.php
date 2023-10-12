<?php

use NITSAN\NsOpenai\Controller\OpenAiController;
use NITSAN\NsOpenai\Override\LocalizationController;

return [
    'seo_title_generation' => [
        'path' => '/generate/page-title',
        'target' => OpenAiController::class . '::generatePageTitleAction'
    ],
    'save_request' => [
        'path' => '/generate/save',
        'target' => OpenAiController::class . '::saveAction'
    ],
];
