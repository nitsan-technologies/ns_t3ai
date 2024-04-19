<?php

defined('TYPO3_MODE') || defined('TYPO3') || die();

(static function (): void {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerPageTSConfigFile(
        'ns_t3ai',
        'Configuration/TSconfig/Page/rte_preset.tsconfig',
        'NS T3Ai :: Config RTE Preset'
    );
})();

