<?php

defined('TYPO3_MODE') || defined('TYPO3') || die();

$typo3VersionArray = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionStringToArray(
    \TYPO3\CMS\Core\Utility\VersionNumberUtility::getCurrentTypo3Version()
);
$extensionConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
    \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
);

if (version_compare($typo3VersionArray['version_main'], 12, '>=')) {
    $config = $extensionConfiguration->get('ns_openai');
    $renderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
    $renderer->addCssFile('EXT:ns_openai/Resources/Public/Css/Rte.css');
    $renderer->addInlineSetting(null,'NS_OPENAI_KEY',$config['apiKey']);
    $renderer->addInlineLanguageLabelFile('EXT:ns_openai/Resources/Private/Language/locallang_be.xlf');
}
