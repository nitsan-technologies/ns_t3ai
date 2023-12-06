<?php

return [
    'dependencies' => ['backend'],
    'tags' => [
        'backend.form',
    ],
    'imports' => [
        '@nitsan/nsopenai/openai-plugin.js' => 'EXT:ns_openai/Resources/Public/JavaScript/Ckeditor/openai-plugin.js',
        '@nitsan/nsopenai/ModuleV12.js' => 'EXT:ns_openai/Resources/Public/JavaScript/ModuleV12.js',
    ],
];  