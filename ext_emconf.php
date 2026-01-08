<?php

$EM_CONF['ns_t3ai'] = [
    'title' => 'T3AI – TYPO3 AI Integration Extension',
    'description' => 'T3AI empowers your TYPO3 backend with advanced AI capabilities including content generation, SEO optimization, media creation, translation, and more using ChatGPT, Gemini, Claude, and Azure AI. Save time and boost productivity with powerful AI tools built directly into your TYPO3 environment.
    
    Explore the Live Demo: https://t3-extension-live.t3planet.com/typo3/?TYPO3_AUTOLOGIN_USER=editor-ns-t3ai
    Product Page, Documentation & Support: https://t3planet.com/t3ai-typo3-extension',
    'category' => 'be',
    'author' => 'Team T3Planet',
    'author_email' => 'info@t3planet.de',
    'author_company' => 'T3Planet',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'version' => '2.0.3',
    'constraints' => [
        'depends' => [
            'typo3' => '10.0.0-14.0.1',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
