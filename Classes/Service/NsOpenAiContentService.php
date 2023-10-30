<?php

declare(strict_types=1);

namespace NITSAN\NsOpenai\Service;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Core\Routing\UnableToLinkToPageException;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class NsOpenAiContentService
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
        string $extConfReplaceText = ""
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
    public function requestAi(string $content, $extConfPromptPrefix, $extConfReplaceText = '', $languageIsoCode= '', $parsedBody = []): string
    {
        $jsonContent = [
            "model" => $this->extConf['model'],
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
        $standaloneView = GeneralUtility::makeInstance(StandaloneView::class);
        $standaloneView->setTemplateRootPaths(['EXT:ns_openai/Resources/Private/Templates/OpenAi/']);
        $standaloneView->getRenderingContext()->setControllerName('OpenAi');
        $standaloneView->setTemplate('GenerateSuggestions');
        $generatedContent = $this->getContentFromAi($request, 'openAiPromptPrefix' . $type, 'seo keywords:');
        $standaloneView->assignMultiple([
            'suggestions' => $this->buildBulletPointList($generatedContent),
            'data' => $data
        ]);
        return $standaloneView->render();
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
            if (!empty($suggestion)){
                $strippedSuggestionsWithNumber = $suggestion;
                if(str_contains($suggestion, '-') && str_contains($suggestion, '•')){
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
        $response = $this->requestFactory->request($previewUrl);
        $fetchedContent = $response->getBody()->getContents();

        if (empty($fetchedContent)) {
            throw new Exception(LocalizationUtility::translate('LLL:EXT:ns_openai/Resources/Private/Language/backend.xlf:AiSeoHelper.fetchContentFailed'));
        }
        return $fetchedContent;
    }

    protected function addModelSpecificPrompt(array &$jsonContent, string $content, string $extConfPromptPrefix, string $languageIsoCode, array $parsedBody)
    {
        if (array_key_exists('prompt', $parsedBody) && $parsedBody['prompt']) {
            $this->extConf[$extConfPromptPrefix] = $parsedBody['prompt'];
        }
        if (str_contains($this->extConf[$extConfPromptPrefix], '[Content]')) {
            $newContent = str_replace('[Content]', $content, $this->extConf[$extConfPromptPrefix]);
            $finalContent = $newContent. ' in ' . $this->languages[$languageIsoCode];
        } else {
            $finalContent = $this->extConf[$extConfPromptPrefix]. ' in ' . $this->languages[$languageIsoCode] .":\n\n" . trim($content);
        }

        if ($this->extConf['model'] === 'gpt-3.5-turbo' || $this->extConf['model'] === 'gpt-4') {
            $jsonContent["messages"][] = [
                'role' => 'user',
                'content' => $finalContent
                ];
        } else {
            $jsonContent["prompt"] = $finalContent;
        }

    }

    /**
     * @param int $pageId
     * @param int $pageLanguage
     * @return string
     */
    protected function getPreviewUrl(int $pageId, int $pageLanguage): string
    {
        $previewUri = $this->uriBuilder
            ->setTargetPageUid($pageId)
            ->setCreateAbsoluteUri(true)
            ->setArguments(['_language'=>$pageLanguage, 'type'=>'1696828748'])->buildFrontendUri();

        return filter_var($previewUri, FILTER_VALIDATE_URL) ? $previewUri : GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST'). $previewUri;

    }


    protected function getLanguageId(): int
    {
        $moduleData = (array)BackendUtility::getModuleData(['language'], [], 'web_layout');
        return (int)$moduleData['language'];
    }

    public function getLocale(int $pageId): ?string
    {
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        try {
            $site = $siteFinder->getSiteByPageId($pageId);
            if ($this->languageId === -1) {
                $this->languageId = $site->getDefaultLanguage()->getLanguageId();
                return $site->getDefaultLanguage()->getTwoLetterIsoCode();
            }
            return $site->getLanguageById($this->languageId)->getTwoLetterIsoCode();
        } catch (SiteNotFoundException|\InvalidArgumentException $e) {
            return null;
        }
    }
}