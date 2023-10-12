<?php

namespace NITSAN\NsOpenai\Controller;

use NITSAN\NsOpenai\Service\NsOpenAiContentService;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use NITSAN\NsOpenai\Domain\Repository\PageRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Routing\UnableToLinkToPageException;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class OpenAiController
{
    /**
     * @var NsOpenAiContentService
     */
    protected NsOpenAiContentService $contentService;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    protected PageRepository $pageRepository;

    /**
     * @param NsOpenAiContentService $contentService
     * @param LoggerInterface $logger
     */
    public function __construct(NsOpenAiContentService $contentService, LoggerInterface $logger, PageRepository $pageRepository)
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
            $response = $this->logGuzzleError($e, $response);
        } catch (UnableToLinkToPageException $e) {
            $this->logger->error($e->getMessage());
            $response->withStatus(404);
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
        } catch (Exception $e) {
            $response = $this->logError($e, $response);
        }
        return $response;
    }

    private function logGuzzleError(GuzzleException $e, Response $response): Response
    {
        $this->logger->error($e->getMessage());
        if ($e->getCode() === 500 && strpos($e->getMessage(), 'auth_subrequest_error') !== false) {
            $response->withStatus($e->getCode());
            $response->getBody()->write(json_encode(['success' => false, 'error' => LocalizationUtility::translate('LLL:EXT:ns_openai/Resources/Private/Language/locallang_be.xlf:NsOpenai.apiNotReachable')]));
        } elseif ($e->getCode() === 401 && strpos($e->getMessage(), 'You need to provide your API key') !== false) {
            $response->withStatus($e->getCode());
            $response->getBody()->write(json_encode(['success' => false, 'error' => LocalizationUtility::translate('LLL:EXT:ns_openai/Resources/Private/Language/locallang_be.xlf:NsOpenai.missingApiKey')]));
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
            $response->getBody()->write(json_encode(['success' => false, 'error' => LocalizationUtility::translate('LLL:EXT:ns_openai/Resources/Private/Language/locallang_be.xlf:NsOpenai.pageNotAccessible')]));
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

}