<?php

defined('TYPO3_MODE') || defined('TYPO3') || die();

(static function (): void {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerPageTSConfigFile(
        'ns_openai',
        'Configuration/TSconfig/Page/rte_preset.tsconfig',
        'NS Openai :: Config RTE Preset'
    );
})();

