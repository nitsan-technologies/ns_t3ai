<?php

namespace NITSAN\NsT3Ai\Controller;

use NITSAN\NsT3Ai\Service\NsT3AiContentService;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use NITSAN\NsT3Ai\Domain\Repository\PageRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Routing\UnableToLinkToPageException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class T3AiController
{
    /**
     * @var NsT3AiContentService
     */
    protected NsT3AiContentService $contentService;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    protected PageRepository $pageRepository;

    /**
     * @param NsT3AiContentService $contentService
     * @param LoggerInterface $logger
     */
    public function __construct(NsT3AiContentService $contentService, LoggerInterface $logger, PageRepository $pageRepository)
    {
        $this->contentService = $contentService;
        $this->logger = $logger;
        $this->pageRepository = $pageRepository;
    }

    private function generateSuggestions(ServerRequestInterface $request, string $type): Response
    {
        $response = new Response();
        try {
            $response->getBody()->write(
                json_encode(
                    [
                        'success' => true,
                        'output' => $this->contentService->getContentForSuggestions($request, $type),
                    ]
                )
            );
            return $response;
        } catch (GuzzleException $e) {
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($e->getMessage(), __FILE__.' Line No. '.__LINE__);die;
            $response = $this->logGuzzleError($e, $response);
        } catch (UnableToLinkToPageException $e) {
            $this->logger->error($e->getMessage());
            $response->withStatus(404);
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
        } catch (Exception $e) {
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($e->getMessage(), __FILE__.' Line No. '.__LINE__);die;
            $response = $this->logError($e, $response);
        }
        return $response;
    }

    private function logGuzzleError(GuzzleException $e, Response $response): Response
    {
        $this->logger->error($e->getMessage());
        if ($e->getCode() === 500 && strpos($e->getMessage(), 'auth_subrequest_error') !== false) {
            $response->withStatus($e->getCode());
            $response->getBody()->write(json_encode(['success' => false, 'error' => LocalizationUtility::translate('LLL:EXT:ns_t3ai/Resources/Private/Language/locallang_be.xlf:NsT3Ai.apiNotReachable')]));
        } elseif ($e->getCode() === 401 && strpos($e->getMessage(), 'You need to provide your API key') !== false) {
            $response->withStatus($e->getCode());
            $response->getBody()->write(json_encode(['success' => false, 'error' => LocalizationUtility::translate('LLL:EXT:ns_t3ai/Resources/Private/Language/locallang_be.xlf:NsT3Ai.missingApiKey')]));
        } else {
            $response->withStatus(400);
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
        return $response;
    }

    private function logError(Exception $e, Response $response): Response
    {
        $this->logger->error($e->getMessage());
        $response->withStatus(400);
        if ($e->getCode() === 1476107295) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => LocalizationUtility::translate('LLL:EXT:ns_t3ai/Resources/Private/Language/locallang_be.xlf:NsT3Ai.pageNotAccessible')]));
        } else {
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
        return $response;
    }

    public function generatePageTitleAction(ServerRequestInterface $request): ResponseInterface
    {
        return $this->generateSuggestions($request, 'PageTitle');
    }

    public function saveAction(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody();
        $currentPage = $this->getCurrentPage($data['pageId'], $this->getLanguageId());
        $status = $this->pageRepository->saveField($currentPage['uid'], $data);
        $response = new Response();
        $response->getBody()->write(
            json_encode(
                [
                    'success' => $status,
                ]
            )
        );
        return $response;
    }

    protected function getLanguageId(): int
    {
        $moduleData = (array)BackendUtility::getModuleData(['language'], [], 'web_layout');
        return (int)$moduleData['language'];
    }

    protected function getCurrentPage($pageId, $languageId)
    {
        if ($languageId === 0) {
            $currentPage = BackendUtility::getRecord(
                'pages',
                $pageId
            );
        } elseif ($languageId > 0) {
            $overlayRecords = BackendUtility::getRecordLocalization(
                'pages',
                $pageId,
                $languageId
            );

            if (is_array($overlayRecords) && array_key_exists(0, $overlayRecords) && is_array($overlayRecords[0])) {
                $currentPage = $overlayRecords[0];
            }
        }
        return $currentPage;
    }

    public function generateRteContentAction(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        return $this->generateRteContent($body);
    }

    /**
     * @return ResponseInterface
     */
    public function renderRteTemplate(): ResponseInterface
    {
        $generatedContent = $this->contentService->getTemplateData('Rte', []);
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->setBodyContent($generatedContent);
        return $pageRenderer->renderResponse();
    }

    /**
     *
     * @param array $parsedBody
     * @return Response
     */
    private function generateRteContent(array $parsedBody): ResponseInterface
    {
        $response = new Response();
        $jsonContent = [
            'prompt' => $parsedBody['prompt'],
            'max_tokens' => (int)$parsedBody['max_tokens'],
            'model' => $parsedBody['model'],
            'temperature' => (float)$parsedBody['temperature'],
            'top_p' => (int)$parsedBody['top_p'],
            'n' => (int)$parsedBody['n'],
            'frequency_penalty' => (int)$parsedBody['frequency_penalty'],
            'presence_penalty' => (int)$parsedBody['presence_penalty']
        ];
        try {
            $generatedContent = $this->contentService->requestAiForRteContent($jsonContent);
            $completeText = '';
            $choices = $generatedContent['choices'];
            foreach($choices as $choicesItem) {
                $completeText .= "<p>" . htmlspecialchars($choicesItem['text'], ENT_QUOTES | ENT_HTML5, 'UTF-8') . "</p>";
            }
            $response->getBody()->write(
                json_encode(
                    [
                        'success' => true,
                        'generatedContent' => $completeText,
                    ]
                )
            );
        } catch(Exception $e) {
            $response->getBody()->write(
                json_encode(
                    [
                        'success' => false,
                    ]
                )
            );
        }
        return $response;
    }

}
