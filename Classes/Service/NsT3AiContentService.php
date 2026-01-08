<?php

declare(strict_types=1);

namespace NITSAN\NsT3Ai\Service;

use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Core\Routing\UnableToLinkToPageException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class NsT3AiContentService
{
    /**
     * @var array
     */
    protected array $languages;
    /**
     * @var array
     */
    protected array $extConf;
    /**
     * @var PageRepository
     */
    protected PageRepository $pageRepository;
    /**
     * @var RequestFactory
     */
    protected RequestFactory $requestFactory;
    /**
     * @var SiteMatcher
     */
    protected SiteMatcher $siteMatcher;
    /**
     * @var UriBuilder
     */
    protected UriBuilder $uriBuilder;

    protected bool $nonLegacyModel;

    protected int $languageId;

    /**
     * @param PageRepository $pageRepository
     * @param SiteMatcher $siteMatcher
     * @param RequestFactory $requestFactory
     * @param UriBuilder $uriBuilder
     * @param array $languages
     * @param array $extConf
     */
    public function __construct(
        PageRepository $pageRepository,
        SiteMatcher $siteMatcher,
        RequestFactory $requestFactory,
        UriBuilder $uriBuilder,
        bool $nonLegacyModel,
        array $languages,
        array $extConf
    ) {
        $this->pageRepository = $pageRepository;
        $this->siteMatcher = $siteMatcher;
        $this->requestFactory = $requestFactory;
        $this->languages = $languages;
        $this->extConf = $extConf;
        $this->nonLegacyModel = $nonLegacyModel;
        $this->uriBuilder = $uriBuilder;
        $this->languageId = $this->getLanguageId();
    }

    /**
     * @throws Exception
     * @throws GuzzleException
     * @throws UnableToLinkToPageException
     */
    public function getContentFromAi(
        ServerRequestInterface $request,
        string $extConfPrompt,
        string $extConfReplaceText = ''
    ): string {
        $parsedBody = $request->getParsedBody();
        $locale = $this->getLocale((int)$parsedBody['pageId']);
        $previewUrl = $this->getPreviewUrl((int)$parsedBody['pageId'], $this->languageId);
        $strippedPageContent = $this->stripPageContent($this->fetchContentFromUrl($previewUrl));
        return $this->requestAi($strippedPageContent, $extConfPrompt, $extConfReplaceText, $locale, $parsedBody);
    }

    /**
     * @throws GuzzleException
     */
    public function requestAi(string $content, $extConfPromptPrefix, $extConfReplaceText = '', $languageIsoCode = '', $parsedBody = []): string
    {
        $jsonContent = [
            'model' => $this->extConf['model'],
        ];
        $this->addModelSpecificPrompt($jsonContent, $content, $extConfPromptPrefix, $languageIsoCode, $parsedBody);

        $response = $this->requestFactory->request(
            $this->nonLegacyModel ? 'https://api.openai.com/v1/chat/completions' : 'https://api.openai.com/v1/completions',
            'POST',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->extConf['apiKey']
                ],
                'json' => $jsonContent
            ]
        );

        $resJsonBody = $response->getBody()->getContents();
        $resBody = json_decode($resJsonBody, true);
        $generatedText = $this->extConf['model'] === 'gpt-3.5-turbo' || $this->extConf['model'] === 'gpt-4' ?
            $resBody['choices'][0]['message']['content'] : $resBody['choices'][0]['text'];
        return ltrim(str_replace($extConfReplaceText, '', $generatedText));
    }

    /**
     * @throws Exception
     * @throws GuzzleException
     * @throws UnableToLinkToPageException
     */
    public function getContentForSuggestions(ServerRequestInterface $request, string $type): string
    {
        $data = $request->getParsedBody();

        $view = $this->createView('GenerateSuggestions');
        $generatedContent = $this->getContentFromAi($request, 'openAiPromptPrefix' . $type, 'seo keywords:');
        $view->assignMultiple([
            'suggestions' => $this->buildBulletPointList($generatedContent),
            'data' => $data
        ]);
        return $view->render();
    }

    protected function stripPageContent(string $pageContent): string
    {
        if (preg_match('~<body[^>]*>(.*?)</body>~si', $pageContent, $body)) {
            $pageContent = $body[0];
        }
        $pageContent = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $pageContent);
        $pageContent = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $pageContent);
        $pageContent = preg_replace('#<footer(.*?)>(.*?)</footer>#is', '', $pageContent);
        $pageContent = preg_replace('#<nav(.*?)>(.*?)</nav>#is', '', $pageContent);
        return strip_tags($pageContent);
    }

    public function buildBulletPointList(string $content): array
    {
        $suggestions = GeneralUtility::trimExplode(PHP_EOL, $content, true);
        $strippedSuggestions = [];
        foreach ($suggestions as $suggestion) {
            if (!empty($suggestion)) {
                $strippedSuggestionsWithNumber = $suggestion;
                if (str_contains($suggestion, '-') && str_contains($suggestion, '•')) {
                    $strippedSuggestionsWithNumber = ltrim(str_replace(['-', '•'], '', $suggestion));
                }
                $line = preg_replace('/^\d+\.\s+/', '', $strippedSuggestionsWithNumber);
                $line = preg_replace('/^\d+\/\d+\/\d+\s+/', '', $line);
                $line = preg_replace('/^\d+\s+/', '', $line);
                $strippedSuggestions[] = preg_replace('/^"|"/', '', $line);
            }
        }
        return $strippedSuggestions;
    }

    /**
     * @throws Exception
     */
    protected function fetchContentFromUrl(string $previewUrl): string
    {

        if (empty($previewUrl) || strpos($previewUrl, 'http') !== 0) {
            throw new Exception('Invalid URL format: ' . $previewUrl);
        }
        try {
            $response = $this->requestFactory->request($previewUrl);
            $fetchedContent = $response->getBody()->getContents();
            if (empty($fetchedContent)) {
                throw new Exception(LocalizationUtility::translate('LLL:EXT:ns_t3ai/Resources/Private/Language/backend.xlf:AiSeoHelper.fetchContentFailed'));
            }
            return $fetchedContent;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function addModelSpecificPrompt(array &$jsonContent, string $content, string $extConfPromptPrefix, string $languageIsoCode, array $parsedBody)
    {
        if (array_key_exists('prompt', $parsedBody) && $parsedBody['prompt']) {
            $this->extConf[$extConfPromptPrefix] = $parsedBody['prompt'];
        }
        if (str_contains($this->extConf[$extConfPromptPrefix], '[Content]')) {
            $newContent = str_replace('[Content]', $content, $this->extConf[$extConfPromptPrefix]);
            $finalContent = $newContent . ' in ' . $this->languages[$languageIsoCode];
        } else {
            $finalContent = $this->extConf[$extConfPromptPrefix] . ' in ' . $this->languages[$languageIsoCode] . ":\n\n" . trim($content);
        }

        if ($this->extConf['model'] === 'gpt-3.5-turbo' || $this->extConf['model'] === 'gpt-4') {
            $jsonContent['messages'][] = [
                'role' => 'user',
                'content' => $finalContent
            ];
        } else {
            $jsonContent['prompt'] = $finalContent;
        }
    }

    /**
     * @param int $pageId
     * @param int $pageLanguage
     * @return string
     */
    /**
     * @param int $pageId
     * @param int $pageLanguage
     * @return string
     */
    public function getPreviewUrl(int $pageId, int $pageLanguage, bool $includeType = true): string
    {
        $typo3VersionArray = VersionNumberUtility::convertVersionStringToArray(
            VersionNumberUtility::getCurrentTypo3Version()
        );

        $arguments = ['_language' => $pageLanguage];
        if ($includeType) {
            $arguments['type'] = '1696828748';
        }

        $siteUrl = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
        $siteUrl = rtrim($siteUrl, '/') . '/';

        if ($typo3VersionArray['version_main'] <= 10) {
            // $this->uriBuilder->setRequest($this->getExtbaseRequest());
            $previewUri = $this->uriBuilder
                ->setTargetPageUid($pageId)
                ->setCreateAbsoluteUri(true)
                ->setArguments($arguments)
                ->buildFrontendUri();

            if (strpos($previewUri, 'http') !== 0) {
                $previewUri = $siteUrl . ltrim($previewUri, '/');
            }
            return $previewUri;
        }

        try {
            $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
            $site = $siteFinder->getSiteByPageId($pageId);
            $language = null;
            try {
                if ($pageLanguage === 0 || $pageLanguage === -1) {
                    $language = $site->getDefaultLanguage();
                } else {
                    $language = $site->getLanguageById($pageLanguage);
                }
            } catch (\InvalidArgumentException $e) {
                $language = $site->getDefaultLanguage();
            }
            $uri = (string)$site->getRouter()->generateUri(
                $pageId,
                $arguments,
                '',
                \TYPO3\CMS\Core\Routing\RouterInterface::ABSOLUTE_URL
            );
            if (empty($uri) || strpos($uri, 'http') !== 0) {
                $uri = $site->getBase() . 'index.php?id=' . $pageId . '&L=' . $pageLanguage . '&type=1696828748';

                if (strpos($uri, 'http') !== 0) {
                    $uri = $siteUrl . ltrim($uri, '/');
                }
            }
            return $uri;
        } catch (\Exception $e) {

            $this->uriBuilder->setRequest($this->getExtbaseRequest());
            $previewUri = $this->uriBuilder
                ->reset()
                ->setTargetPageUid($pageId)
                ->setCreateAbsoluteUri(true)
                ->setArguments($arguments)
                ->buildFrontendUri();

            if (strpos($previewUri, 'http') !== 0) {
                $previewUri = $siteUrl . ltrim($previewUri, '/');
            }
            return $previewUri;
        }
    }

    private function getExtbaseRequest()
    {
        $typo3VersionArray = VersionNumberUtility::convertVersionStringToArray(
            VersionNumberUtility::getCurrentTypo3Version()
        );

        if ($typo3VersionArray['version_main'] >= 11) {

            /** @var ServerRequestInterface $request */
            $request = $GLOBALS['TYPO3_REQUEST'];

            if (class_exists('TYPO3\\CMS\\Extbase\\Mvc\\ExtbaseRequestParameters')) {
                return new Request(
                    $request->withAttribute('extbase', new ExtbaseRequestParameters())
                );
            }
            return new Request($request);
        } else {
            return null;
        }
    }

    protected function getLanguageId(): int
    {
        $moduleData = (array)BackendUtility::getModuleData(['language' => 0], [], 'web_layout');
        return (int)($moduleData['language'] ?? 0);
    }

    public function getLocale(int $pageId): ?string
    {
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        try {
            $typo3VersionArray = VersionNumberUtility::convertVersionStringToArray(
                VersionNumberUtility::getCurrentTypo3Version()
            );
            $site = $siteFinder->getSiteByPageId($pageId);
            $language = null;

            if ($this->languageId === -1 || $this->languageId === 0) {
                $language = $site->getDefaultLanguage();
            } else {
                $language = $site->getLanguageById($this->languageId);
            }
            if ($typo3VersionArray['version_main'] >= 13) {
                $languageCode = $language->getLocale()->getLanguageCode();
            } elseif ($typo3VersionArray['version_main'] >= 10) {
                if (method_exists($language, 'getTwoLetterIsoCode')) {
                    $languageCode = $language->getTwoLetterIsoCode();
                } else {
                    $languageCode = $language->getLocale()->getLanguageCode();
                }
            } else {
                if (method_exists($language, 'getTwoLetterIsoCode')) {
                    $languageCode = $language->getTwoLetterIsoCode();
                } else {
                    $hreflang = $language->getHreflang();
                    $languageCode = substr($hreflang, 0, 2);
                }
            }

            return $languageCode;
        } catch (SiteNotFoundException | \InvalidArgumentException $e) {
            return '';
        }
    }

    /**
     * Create a Fluid view with the proper configuration
     *
     * @param string $templateName
     * @return object View instance
     */
    protected function createView(string $templateName)
    {
        $typo3VersionArray = VersionNumberUtility::convertVersionStringToArray(
            VersionNumberUtility::getCurrentTypo3Version()
        );

        // For TYPO3 v13+, use ViewFactory
        if ($typo3VersionArray['version_main'] >= 13) {
            $viewFactory = GeneralUtility::getContainer()->get(ViewFactoryInterface::class);

            // Get the template path
            $templatePath = GeneralUtility::getFileAbsFileName('EXT:ns_t3ai/Resources/Private/Templates/T3Ai/' . $templateName . '.html');


            $viewFactoryData = new ViewFactoryData(
                ['EXT:ns_t3ai/Resources/Private/Templates/'],
                ['EXT:ns_t3ai/Resources/Private/Partials/'],
                [],
                $templatePath,
                $this->getExtbaseRequest()
            );

            $view = $viewFactory->create($viewFactoryData);
            $view->getRenderingContext()->setControllerName('T3Ai');

            return $view;
        } else {
            // For TYPO3 v9-12, use StandaloneView
            $standaloneViewClass = 'TYPO3\\CMS\\Fluid\\View\\StandaloneView';

            if (!class_exists($standaloneViewClass)) {
                throw new \RuntimeException('StandaloneView not available in this TYPO3 version');
            }

            $view = GeneralUtility::makeInstance($standaloneViewClass);
            $view->setTemplateRootPaths(['EXT:ns_t3ai/Resources/Private/Templates/']);
            $view->setPartialRootPaths(['EXT:ns_t3ai/Resources/Private/Partials/']);
            $view->getRenderingContext()->setControllerName('T3Ai');
            $view->setTemplatePathAndFilename(
                GeneralUtility::getFileAbsFileName('EXT:ns_t3ai/Resources/Private/Templates/T3Ai/' . $templateName . '.html')
            );

            return $view;
        }
    }

    public function getTemplateData($templateName, $data)
    {
        $view = $this->createView($templateName);
        $view->assignMultiple([
            'data' => $data
        ]);

        return $view->render();
    }

    /**
     * @param array $jsonContent
     * @return array
     */
    public function requestAiForRteContent(array $jsonContent): array
    {
        $response = $this->requestFactory->request(
            'https://api.openai.com/v1/completions',
            'POST',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->extConf['apiKey']
                ],
                'json' => $jsonContent
            ]
        );
        $resJsonBody = $response->getBody()->getContents();
        return json_decode($resJsonBody, true);
    }
}
