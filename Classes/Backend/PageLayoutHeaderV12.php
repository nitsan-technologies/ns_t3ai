<?php

namespace NITSAN\NsT3Ai\Backend;

use NITSAN\NsT3Ai\Domain\Repository\PageRepository;
use NITSAN\NsT3Ai\Helper\NsExtensionConfiguration;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;

class PageLayoutHeaderV12
{
    private array $requireJsModules = [
        '@nitsan/nst3ai/ModuleV12.js',
    ];

    protected ?PageRepository $pageRepository = null;
    protected PageRenderer $pageRenderer;

    private NsExtensionConfiguration $extensionConfiguration;

    public function __construct(NsExtensionConfiguration $extensionConfiguration, PageRepository $pageRepository, PageRenderer $pageRenderer)
    {
        $this->extensionConfiguration = $extensionConfiguration;
        $this->pageRepository = $pageRepository;
        $this->pageRenderer = $pageRenderer;
    }

    public function render(ServerRequestInterface $request): string
    {
        $languageId = $this->getLanguageId();
        $pageId = (int)$request->getQueryParams()['id'];
        $currentPage = $this->getCurrentPage($pageId, $languageId);
        if (!is_array($currentPage) || $languageId == -1 || $currentPage['hidden'] == 1) {
            return '';
        }

        $typo3VersionArray = VersionNumberUtility::convertVersionStringToArray(
            VersionNumberUtility::getCurrentTypo3Version()
        );
        $typo3Version = $typo3VersionArray['version_main'];

        $view = null;
        if ($typo3Version >= 13) {

            $viewFactory = GeneralUtility::getContainer()->get(ViewFactoryInterface::class);

            $extbaseRequest = new Request(
                $request->withAttribute('extbase', new ExtbaseRequestParameters())
            );

            $templateRootPath = GeneralUtility::getFileAbsFileName('EXT:ns_t3ai/Resources/Private/Backend/Templates/');
            $partialRootPath = GeneralUtility::getFileAbsFileName('EXT:ns_t3ai/Resources/Private/Backend/Partials/');

            $viewFactoryData = new ViewFactoryData(
                [$templateRootPath],
                [$partialRootPath],
                [],
                null,
                $extbaseRequest
            );

            $view = $viewFactory->create($viewFactoryData);

            $templatePathAndFilename = $templateRootPath . 'T3Ai.html';


            try {
                if (method_exists($view->getRenderingContext()->getTemplatePaths(), 'setTemplatePathAndFilename')) {
                    $view->getRenderingContext()->getTemplatePaths()->setTemplatePathAndFilename($templatePathAndFilename);
                } else {
                    $templateContent = file_get_contents($templatePathAndFilename);
                    if ($templateContent !== false) {
                        $view->getRenderingContext()->getTemplatePaths()->setTemplateSource($templateContent);
                    } else {
                        throw new \RuntimeException('Could not read template file: ' . $templatePathAndFilename);
                    }
                }
            } catch (\Exception $e) {
                throw $e;
            }
        }


        if (!$view) {
            $standaloneViewClass = 'TYPO3\\CMS\\Fluid\\View\\StandaloneView';
            if (class_exists($standaloneViewClass)) {
                $view = GeneralUtility::makeInstance($standaloneViewClass);

                $templateRootPath = GeneralUtility::getFileAbsFileName('EXT:ns_t3ai/Resources/Private/Backend/Templates/');
                $view->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:ns_t3ai/Resources/Private/Backend/Partials/')]);
                $templatePathAndFilename = $templateRootPath . 'T3Ai.html';
                $view->setTemplatePathAndFilename($templatePathAndFilename);
            } else {
                return '';
            }
        }

        foreach ($this->requireJsModules as $requireJsModule) {
            if ($typo3Version >= 12) {
                $this->pageRenderer->loadJavaScriptModule($requireJsModule);
            } else {
                if (method_exists($this->pageRenderer, 'loadRequireJsModule')) {
                    $this->pageRenderer->loadRequireJsModule($requireJsModule);
                }
            }
        }

        $this->pageRenderer->addCssFile('EXT:ns_t3ai/Resources/Public/Css/Style.css');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:ns_t3ai/Resources/Private/Language/locallang_be.xlf');

        $pageData = $this->pageRepository->getCurrentPageData($pageId, $typo3Version);
        $assign = [
            'baseUrl' => GeneralUtility::getIndpEnv('TYPO3_SITE_URL'),
            'pageId' => $pageId,
            'pageTitlePrompts' => $this->extensionConfiguration->getPageTitlePrompts(),
            'pageData' => $pageData,
            'version' => $typo3Version,
        ];

        $view->assignMultiple($assign);
        return $view->render();
    }
    protected function getLanguageId(): int
    {
        $moduleData = (array)BackendUtility::getModuleData(['language' => 0], [], 'web_layout');
        return (int)($moduleData['language'] ?? 0);
    }
    protected function getCurrentPage(int $pageId, int $languageId): ?array
    {
        $currentPage = null;
        if ($pageId > 0) {
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
        }
        return $currentPage;
    }
}
