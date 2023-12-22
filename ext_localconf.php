<?php

$typo3VersionArray = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionStringToArray(
    \TYPO3\CMS\Core\Utility\VersionNumberUtility::getCurrentTypo3Version()
);

if (version_compare($typo3VersionArray['version_main'], 11, '<=')) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawHeaderHook'][] = \NITSAN\NsOpenai\Backend\PageLayoutHeader::class . '->render';
    $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['default'] = 'EXT:ns_openai/Configuration/RTE/Plugin.yaml';
}
else{
    $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['default'] = 'EXT:ns_openai/Configuration/RTE/Pluginv12.yaml';
}

//// Make the extension configuration accessible
$extensionConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
    \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
);

(static function () : void {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        'ns_openai',
        'setup',
        "@import 'EXT:ns_openai/Configuration/TypoScript/openai.typoscript'"
    );
})();
if (version_compare($typo3VersionArray['version_main'], 11, '<=')) { 
    if (TYPO3_MODE === 'BE' && \NITSAN\NsOpenai\Utility\NsOpenAiBackendUtility::isApiKeySet()) {
       $config = $extensionConfiguration->get('ns_openai');
       $renderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
       $renderer->addInlineSetting(null,'NS_OPENAI_KEY',$config['apiKey']);
    }
}