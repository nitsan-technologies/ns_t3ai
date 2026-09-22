<?php

$typo3VersionArray = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionStringToArray(
    \TYPO3\CMS\Core\Utility\VersionNumberUtility::getCurrentTypo3Version()
);

if (version_compare($typo3VersionArray['version_main'], 11, '<=')) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawHeaderHook'][] =
        \NITSAN\NsT3Ai\Backend\PageLayoutHeader::class . '->render';
    $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['nst3ai'] =
        'EXT:ns_t3ai/Configuration/RTE/Plugin.yaml';
} else {
    $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['nst3ai'] =
        'EXT:ns_t3ai/Configuration/RTE/Pluginv12.yaml';
}
