<?php

defined('TYPO3_MODE') || defined('TYPO3') || die();

$typo3VersionArray = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionStringToArray(
    \TYPO3\CMS\Core\Utility\VersionNumberUtility::getCurrentTypo3Version()
);
$extensionConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
    \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
);

if (version_compare($typo3VersionArray['version_main'], 12, '>=')) {
    $config = $extensionConfiguration->get('ns_t3ai');
    $renderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
    $renderer->addInlineLanguageLabelFile('EXT:ns_t3ai/Resources/Private/Language/locallang_be.xlf');
    $renderer->addCssFile('EXT:ns_t3ai/Resources/Public/Css/Rte.css');
}
