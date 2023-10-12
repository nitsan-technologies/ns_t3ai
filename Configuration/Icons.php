<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    // Icon identifier
    'openai_module' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:ns_openai/Resources/Public/Icons/Extension-Logo.svg',
    ],
];
