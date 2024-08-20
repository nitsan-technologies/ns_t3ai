<?php

declare(strict_types=1);

namespace NITSAN\NsT3Ai\Utility;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use NITSAN\NsT3Ai\Service\LanguageService;

class NsT3AiBackendUtility
{
    private static string $apiKey = '';

    private static bool $configurationLoaded = false;

    /**
     * @var array{uid: int, title: string}|array<empty>
     */
    protected static array $currentPage;

    /**
     * @return string
     */
    public static function getApiKey(): string
    {
        if (!self::$configurationLoaded) {
            self::loadConfiguration();
        }
        return self::$apiKey;
    }

    public static function loadConfiguration(): void
    {
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('ns_t3ai');
        self::$apiKey = $extensionConfiguration['apiKey'] ?? '';
        self::$configurationLoaded = true;
    }



    public static function isApiKeySet(): bool
    {
        if (!self::$configurationLoaded) {
            self::loadConfiguration();
        }

        return (bool)self::$apiKey;
    }
}
