<?php

namespace NITSAN\NsT3Ai\Controller;

use NITSAN\NsT3Ai\Service\NsT3AiContentService;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use NITSAN\NsT3Ai\Domain\Repository\PageRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Routing\UnableToLinkToPageException;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class T3AiController
{
    private const MAX_TOKENS = 4000;
    private const MAX_N = 4;

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
            $parsedBody = $request->getParsedBody();
            if (!is_array($parsedBody)) {
                $parsedBody = [];
            }
            $pageId = (int)($parsedBody['pageId'] ?? 0);
            $this->assertBackendUserCanEditPage($pageId);

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
            $response = $this->logGenericError($e, $response);
        } catch (Exception $e) {
            $response = $this->logError($e, $response);
        } catch (\Throwable $e) {
            $response = $this->logGenericError($e, $response);
        }
        return $response;
    }

    /**
     * Application errors stay on HTTP 200 with success:false so existing BE AjaxRequest
     * .then() handlers can read the JSON (non-2xx would reject and show [object Object]).
     */
    private function jsonErrorResponse(Response $response, string $lllKey): Response
    {
        $message = LocalizationUtility::translate(
            'LLL:EXT:ns_t3ai/Resources/Private/Language/locallang_be.xlf:' . $lllKey
        ) ?: $lllKey;
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => $message,
        ]));
        return $response;
    }

    private function logGuzzleError(GuzzleException $e, Response $response): Response
    {
        $this->logger->error($e->getMessage());
        if ($e->getCode() === 500 && strpos($e->getMessage(), 'auth_subrequest_error') !== false) {
            return $this->jsonErrorResponse($response, 'NsT3Ai.apiNotReachable');
        }
        if ($e->getCode() === 401
            || strpos($e->getMessage(), 'You need to provide your API key') !== false
            || str_contains($e->getMessage(), 'Incorrect API key')
            || str_contains($e->getMessage(), 'invalid_api_key')
        ) {
            return $this->jsonErrorResponse($response, 'NsT3Ai.missingApiKey');
        }
        if (str_contains($e->getMessage(), 'exceeded your current quota')) {
            return $this->jsonErrorResponse($response, 'NsT3Ai.exceededQuota');
        }
        return $this->jsonErrorResponse($response, 'NsT3Ai.error');
    }

    private function logError(Exception $e, Response $response): Response
    {
        $this->logger->error($e->getMessage());
        if ($e->getCode() === 1476107295 || $e->getCode() === 1737050001 || $e->getCode() === 1737050002) {
            return $this->jsonErrorResponse($response, 'NsT3Ai.pageNotAccessible');
        }
        if ($e->getCode() === 1737050003) {
            return $this->jsonErrorResponse($response, 'NsT3Ai.invalidField');
        }
        if ($e->getCode() === 1737050004) {
            return $this->jsonErrorResponse($response, 'NsT3Ai.missingApiKey');
        }
        return $this->jsonErrorResponse($response, 'NsT3Ai.error');
    }

    private function logGenericError(\Throwable $e, Response $response): Response
    {
        $this->logger->error($e->getMessage());
        return $this->jsonErrorResponse($response, 'NsT3Ai.error');
    }

    public function generatePageTitleAction(ServerRequestInterface $request): ResponseInterface
    {
        return $this->generateSuggestions($request, 'PageTitle');
    }

    public function saveAction(ServerRequestInterface $request): ResponseInterface
    {
        $response = new Response();
        try {
            $data = $request->getParsedBody();
            if (!is_array($data)) {
                throw new Exception('Invalid request body', 1737050002);
            }

            $pageId = (int)($data['pageId'] ?? 0);
            $fieldName = (string)($data['fieldName'] ?? '');
            $suggestion = (string)($data['suggestion'] ?? '');

            if ($fieldName === '' || !in_array($fieldName, PageRepository::ALLOWED_SAVE_FIELDS, true)) {
                throw new Exception('Invalid field name', 1737050003);
            }

            $currentPage = $this->getCurrentPage($pageId, $this->getLanguageId());
            if (!is_array($currentPage) || empty($currentPage['uid'])) {
                throw new Exception('Page not found', 1737050002);
            }

            $recordUid = (int)$currentPage['uid'];
            $this->assertBackendUserCanEditPage($recordUid);

            $status = $this->pageRepository->saveField($recordUid, $fieldName, $suggestion);
            $response->getBody()->write(
                json_encode(
                    [
                        'success' => $status,
                    ]
                )
            );
            return $response;
        } catch (Exception $e) {
            return $this->logError($e, $response);
        } catch (\Throwable $e) {
            return $this->logGenericError($e, $response);
        }
    }

    protected function getLanguageId(): int
    {
        $moduleData = (array)BackendUtility::getModuleData(['language' => 0], [], 'web_layout');
        return (int)($moduleData['language'] ?? 0);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function getCurrentPage($pageId, $languageId): ?array
    {
        $pageId = (int)$pageId;
        if ($pageId <= 0) {
            return null;
        }

        $currentPage = null;
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
        return is_array($currentPage) ? $currentPage : null;
    }

    /**
     * @throws Exception
     */
    protected function assertBackendUserCanEditPage(int $pageId): void
    {
        if ($pageId <= 0) {
            throw new Exception('Invalid page id', 1737050002);
        }

        $backendUser = $GLOBALS['BE_USER'] ?? null;
        if (!$backendUser instanceof BackendUserAuthentication) {
            throw new Exception('Backend user required', 1737050001);
        }

        $page = BackendUtility::getRecord('pages', $pageId);
        if (!is_array($page)) {
            throw new Exception('Page not found', 1737050002);
        }

        if ($backendUser->isAdmin()) {
            return;
        }

        if (!$backendUser->isInWebMount($pageId)) {
            throw new Exception('Page not in web mount', 1737050001);
        }

        if (!$backendUser->doesUserHaveAccess($page, Permission::PAGE_EDIT)) {
            throw new Exception('Missing page edit permission', 1737050001);
        }
    }

    public function generateRteContentAction(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        if (!is_array($body)) {
            $body = [];
        }
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
     * @param array $parsedBody
     * @return ResponseInterface
     */
    private function generateRteContent(array $parsedBody): ResponseInterface
    {
        $response = new Response();
        try {
            $backendUser = $GLOBALS['BE_USER'] ?? null;
            if (!$backendUser instanceof BackendUserAuthentication) {
                throw new Exception('Backend user required', 1737050001);
            }

            // Optional pageId (modern plugins may omit it); enforce ACL when present.
            $pageId = (int)($parsedBody['pageId'] ?? 0);
            if ($pageId > 0) {
                $this->assertBackendUserCanEditPage($pageId);
            }

            $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('ns_t3ai');
            $configuredModel = (string)($extConf['model'] ?? 'gpt-4o');

            $jsonContent = [
                'prompt' => (string)($parsedBody['prompt'] ?? ''),
                'max_tokens' => min(max((int)($parsedBody['max_tokens'] ?? 256), 1), self::MAX_TOKENS),
                'model' => $configuredModel,
                'temperature' => min(max((float)($parsedBody['temperature'] ?? 0.5), 0.0), 0.9),
                'top_p' => 1,
                'n' => min(max((int)($parsedBody['n'] ?? 1), 1), self::MAX_N),
                'frequency_penalty' => 0,
                'presence_penalty' => 0,
            ];

            if ($jsonContent['prompt'] === '') {
                throw new Exception('Empty prompt', 1737050002);
            }

            $generatedContent = $this->contentService->requestAiForRteContent($jsonContent);
            $completeText = '';
            $choices = $generatedContent['choices'] ?? [];
            foreach ($choices as $choicesItem) {
                $generatedText = $choicesItem['text'] ?? ($choicesItem['message']['content'] ?? '');
                if ($generatedText === '') {
                    continue;
                }
                $completeText .= "<p>" . htmlspecialchars($generatedText, ENT_QUOTES | ENT_HTML5, 'UTF-8') . "</p>";
            }
            $response->getBody()->write(
                json_encode(
                    [
                        'success' => true,
                        'generatedContent' => $completeText,
                    ]
                )
            );
        } catch (GuzzleException $e) {
            $response = $this->logGuzzleError($e, $response);
        } catch (Exception $e) {
            $response = $this->logError($e, $response);
        } catch (\Throwable $e) {
            $response = $this->logGenericError($e, $response);
        }

        return $response;
    }
}
