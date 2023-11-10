<?php

declare(strict_types=1);

namespace NITSAN\NsOpenai\Service;

use GuzzleHttp\Exception\ClientException;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Http\Request;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use NITSAN\NsOpenai\Client;
use NITSAN\NsOpenai\Domain\Repository\SettingsRepository;
use NITSAN\NsOpenai\Utility\NsOpenAiBackendUtility;

class NsOpenaiService
{
    /**
     * Default supported languages
     *
     *
     * @var string[]
     */
    public array $apiSupportedLanguages =  [
        'source' => [],
        'target' => [],
    ];

    /**
     * Formality supported languages
     * @var string[]
     */
    public array $formalitySupportedLanguages = [];

    protected SettingsRepository $deeplSettingsRepository;

    private FrontendInterface $cache;

    private Client $client;

    public function __construct(
        ?FrontendInterface $cache = null
    ) {
        $this->cache = $cache ?? GeneralUtility::makeInstance(CacheManager::class)->getCache('nsopenaitranslate');
    }
}
