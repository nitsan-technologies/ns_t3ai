<?php

use NITSAN\NsOpenai\Backend\PageLayoutHeader;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\Controller\RecordListController;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawHeaderHook'][]
    = PageLayoutHeader::class . '->render';

$GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['default'] = 'EXT:ns_openai/Configuration/RTE/Plugin.yaml';

// Make the extension configuration accessible
$extensionConfiguration = GeneralUtility::makeInstance(
    ExtensionConfiguration::class
);

(static function (): void {
    ExtensionManagementUtility::addTypoScript(
        'ns_openai',
        'setup',
        "@import 'EXT:ns_openai/Configuration/TypoScript/openai.typoscript'"
    );

})();

if (TYPO3_MODE === 'BE' && \NITSAN\NsOpenai\Utility\NsOpenAiBackendUtility::isApiKeySet()) {
    $config = $extensionConfiguration->get('ns_openai');
    $renderer = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
    $renderer->addJsInlineCode('nsopenaikey', 'const NS_OPENAI_KEY = "' . $config['apiKey'] . '"', false, true);
}
