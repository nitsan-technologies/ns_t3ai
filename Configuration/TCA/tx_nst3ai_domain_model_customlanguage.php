<?php


$typo3Version = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Information\Typo3Version::class)->getMajorVersion();
return [
    'ctrl' => [
        'title' => 'LLL:EXT:ns_t3ai/Resources/Private/Language/locallang_be.xlf:NsT3Ai.tx_nst3ai_domain_model_customlanguage',
        'label' => 'iso_code',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'searchFields' => $typo3Version < 12 ? 'iso_code,speech' : '',
        'iconfile' => 'EXT:ns_t3ai/Resources/Public/Icons/Extension.svg'
    ],
    'types' => [
        '1' => ['showitem' => 'sys_language_uid,iso_code,speech, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access, hidden,'],
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => $typo3Version >= 12 ? [
                'type' => 'language',
                'default' => 0,
            ] : [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'special' => 'languages',
                'items' => [
                    ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages', -1, 'flags-multiple']
                ],
                'default' => 0,
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => 0,
                'items' => $typo3Version >= 12 ? [
                    ['label' => '', 'value' => 0],
                ] : [
                    ['', 0],
                ],
                'foreign_table' => 'tx_nst3ai_domain_model_customlanguage',
                'foreign_table_where' => 'AND {#tx_nst3ai_domain_model_customlanguage}.{#pid}=###CURRENT_PID### AND {#tx_nst3ai_domain_model_customlanguage}.{#sys_language_uid} IN (-1,0)',
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.visible',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => $typo3Version >= 12 ? [
                    ['label' => '', 'value' => '', 'invertStateDisplay' => true]
                ] : [
                    [0 => '', 1 => '', 'invertStateDisplay' => true]
                ],
            ],
        ],
        'starttime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => $typo3Version >= 12 ? [
                'type' => 'datetime',
                'default' => 0,
                'behaviour' => ['allowLanguageSynchronization' => true],
                'searchable' => false,
            ] : [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime,int',
                'default' => 0,
                'behaviour' => ['allowLanguageSynchronization' => true]
            ],
        ],
        'endtime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => $typo3Version >= 12 ? [
                'type' => 'datetime',
                'default' => 0,
                'range' => ['upper' => mktime(0, 0, 0, 1, 1, 2038)],
                'behaviour' => ['allowLanguageSynchronization' => true],
                'searchable' => false,
            ] : [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime,int',
                'default' => 0,
                'range' => ['upper' => mktime(0, 0, 0, 1, 1, 2038)],
                'behaviour' => ['allowLanguageSynchronization' => true]
            ],
        ],
        'iso_code' => [
            'exclude' => true,
            'label' => 'LLL:EXT:ns_t3ai/Resources/Private/Language/locallang_be.xlf:NsT3Ai.tx_aiseohelper_domain_model_customlanguage.iso_code',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => '',
                'searchable' => $typo3Version >= 12 ? true : null,
            ],
        ],
        'speech' => [
            'exclude' => true,
            'label' => 'LLL:EXT:ns_t3ai/Resources/Private/Language/locallang_be.xlf:NsT3Ai.tx_aiseohelper_domain_model_customlanguage.speech',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => '',
                'searchable' => $typo3Version >= 12 ? true : null,
            ],
        ],
    ],
];
