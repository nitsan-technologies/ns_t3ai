<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    // Icon identifier
    't3ai_module' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:ns_t3ai/Resources/Public/Icons/Extension-Logo.svg',
    ],
    'actions-localize-nst3ai' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:ns_t3ai/Resources/Public/Icons/actions-localize-nst3ai.svg',
    ],
];
