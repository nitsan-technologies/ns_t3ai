<?php

use NITSAN\NsOpenai\Backend\PageLayoutHeader;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use NITSAN\NsOpenai\Utility\NsOpenAiBackendUtility;

$typo3VersionArray = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionStringToArray(
    \TYPO3\CMS\Core\Utility\VersionNumberUtility::getCurrentTypo3Version()
);

// Register page layout hooks to display additional information.
// Replaced with T3G\AgencyPack\Blog\Listener\ModifyPageLayoutContent in v12
if (version_compare($typo3VersionArray['version_main'], 11, '<=')) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawHeaderHook'][] = PageLayoutHeader::class . '->render';
    $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['default'] = 'EXT:ns_openai/Configuration/RTE/Plugin.yaml';
}
else{
    $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['default'] = 'EXT:ns_openai/Configuration/RTE/Pluginv12.yaml';
}

//// Make the extension configuration accessible
$extensionConfiguration = GeneralUtility::makeInstance(
    ExtensionConfiguration::class
);

// $renderer = GeneralUtility::makeInstance(
//     PageRenderer::class
// );

// $apiKeySet = \NITSAN\NsOpenai\Utility\NsOpenAiBackendUtility::isApiKeySet();
// \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($apiKeySet,__FILE__.''.__LINE__);
(static function () : void {
    ExtensionManagementUtility::addTypoScript(
        'ns_openai',
        'setup',
        "@import 'EXT:ns_openai/Configuration/TypoScript/openai.typoscript'"
    );
})();
if (version_compare($typo3VersionArray['version_main'], 12, '>=')) {
    // if($apiKeySet == true){
    //     // $config = $extensionConfiguration->get('ns_openai');
    //     // \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($config,__FILE__.''.__LINE__);
    //     // $apiKey = GeneralUtility::makeInstance(NsOpenAiBackendUtility::class);
    //     //  $renderer->addJsInlineCode('nsopenaikey', 'const NS_OPENAI_KEY = "' . $config['apiKey'] . '"', false, true);
    // }
} else {
    if (TYPO3_MODE === 'BE' && \NITSAN\NsOpenai\Utility\NsOpenAiBackendUtility::isApiKeySet()) {
        $config = $extensionConfiguration->get('ns_openai');
        $renderer = GeneralUtility::makeInstance(PageRenderer::class);
        $renderer->addJsInlineCode('nsopenaikey', 'const NS_OPENAI_KEY = "' . $config['apiKey'] . '"', false, true);
    }
}
