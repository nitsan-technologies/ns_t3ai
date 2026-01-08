<?php

$typo3VersionArray = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionStringToArray(
    \TYPO3\CMS\Core\Utility\VersionNumberUtility::getCurrentTypo3Version()
);

if (version_compare($typo3VersionArray['version_main'], 11, '<=')) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawHeaderHook'][] = \NITSAN\NsT3Ai\Backend\PageLayoutHeader::class . '->render';
    $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['default'] = $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['nst3ai'] =
    'EXT:ns_t3ai/Configuration/RTE/Plugin.yaml';

    } else {
        $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['default'] = $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['nst3ai'] =
        'EXT:ns_t3ai/Configuration/RTE/Pluginv12.yaml';
    }

//// Make the extension configuration accessible
$extensionConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
    \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
);

(static function (): void {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        'ns_t3ai',
        'setup',
        "@import 'EXT:ns_t3ai/Configuration/TypoScript/t3ai.typoscript'"
    );
})();

// COMMENT OUT or REMOVE this block that uses the missing class:
/*
if (version_compare($typo3VersionArray['version_main'], 11, '<=')) {
    if (TYPO3_MODE === 'BE' && \NITSAN\NsT3Ai\Utility\NsT3AiBackendUtility::isApiKeySet()) {
       $config = $extensionConfiguration->get('ns_t3ai');
       $renderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
       $renderer->addInlineSetting(null,'NS_T3AI_KEY',$config['apiKey']);
    }
}
*/

// Or if you want to keep the functionality but skip the class check:
if (version_compare($typo3VersionArray['version_main'], 11, '<=')) {
    if (TYPO3_MODE === 'BE') {
        try {
            $config = $extensionConfiguration->get('ns_t3ai');
            if (!empty($config['apiKey'] ?? '')) {
                $renderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
                $renderer->addInlineSetting(null, 'NS_T3AI_KEY', $config['apiKey']);
            }
        } catch (\Exception $e) {
            // Ignore if extension configuration not found
        }
    }
}
