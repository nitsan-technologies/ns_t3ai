<?php

return [
    'dependencies' => ['backend'],
    'tags' => [
        'backend.form',
    ],
    'imports' => [
        '@nitsan/nst3ai/t3ai-plugin.js' => 'EXT:ns_t3ai/Resources/Public/JavaScript/Ckeditor/t3ai-plugin.js',
        '@nitsan/nst3ai/ModuleV12.js' => 'EXT:ns_t3ai/Resources/Public/JavaScript/ModuleV12.js',
        '@nitsan/nst3ai/pluginv12.js' => 'EXT:ns_t3ai/Resources/Public/JavaScript/Plugins/nst3ai/pluginv12.js',
    ],
];  