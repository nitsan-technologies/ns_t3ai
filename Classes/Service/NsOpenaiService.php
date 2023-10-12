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
        ?FrontendInterface $cache = null,
        ?Client $client = null
    ) {
        $this->cache = $cache ?? GeneralUtility::makeInstance(CacheManager::class)->getCache('nsopenaitranslate');
        $this->client = $client ?? GeneralUtility::makeInstance(Client::class);

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->nsOpenaiSettingsRepository = $objectManager->get(SettingsRepository::class);

        $this->loadSupportedLanguages();
        $this->apiSupportedLanguages['target'] = $this->nsOpenaiSettingsRepository->getSupportedLanguages($this->apiSupportedLanguages['target']);
    }

    /**
     * Deepl Api Call for retrieving               .
     * @return array<int|string, mixed>
     */
    public function translateRequest(string $content, string $targetLanguage, string $sourceLanguage): array
    {
        // If the source language is set to Autodetect, no glossary can be detected.
        if ($sourceLanguage === 'auto') {
            $sourceLanguage = '';
        }
        return [];
        return json_decode($response->getBody()->getContents(), true);
    }

    private function loadSupportedLanguages(): void
    {
        $cacheIdentifier = 'ns-openai-supported-languages-target';
        if (($supportedTargetLanguages = $this->cache->get($cacheIdentifier)) === false) {
            $supportedTargetLanguages = $this->loadSupportedLanguagesFromAPI();

            $this->cache->set($cacheIdentifier, $supportedTargetLanguages, [], 86400);
        }

        foreach ($supportedTargetLanguages as $supportedLanguage) {
            $this->apiSupportedLanguages['target'][] = $supportedLanguage['language'];
            if ($supportedLanguage['supports_formality'] === true) {
                $this->formalitySupportedLanguages[] = $supportedLanguage['language'];
            }
        }

        $cacheIdentifier = 'ns-openai-supported-languages-source';

        if (($supportedSourceLanguages = $this->cache->get($cacheIdentifier)) === false) {
            $supportedSourceLanguages = $this->loadSupportedLanguagesFromAPI('source');

            $this->cache->set($cacheIdentifier, $supportedSourceLanguages, [], 86400);
        }

        foreach ($supportedSourceLanguages as $supportedLanguage) {
            $this->apiSupportedLanguages['source'][] = $supportedLanguage['language'];
        }
    }

    private function loadSupportedLanguagesFromAPI(string $type = 'target'): array
    {
        try {
//            $response = $this->client->getSupportedTargetLanguage($type);
        } catch (ClientException $e) {
            return [];
        }
        return [];
//        return json_decode($response->getBody()->getContents(), true);
    }
}
