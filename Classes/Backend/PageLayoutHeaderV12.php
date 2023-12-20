<?php

namespace NITSAN\NsOpenai\Backend;

use NITSAN\NsOpenai\Domain\Repository\PageRepository;
use NITSAN\NsOpenai\Helper\NsExtensionConfiguration;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Page\PageRenderer;

class PageLayoutHeaderV12
{
    private array $requireJsModules = [
        '@nitsan/nsopenai/ModuleV12.js',
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
        $standlone = GeneralUtility::makeInstance(StandaloneView::class);
        foreach ($this->requireJsModules as $requireJsModule) {
            $this->pageRenderer->loadJavaScriptModule($requireJsModule);
        }
        $this->pageRenderer->addCssFile('EXT:ns_openai/Resources/Public/Css/Style.css');

        $this->pageRenderer->addInlineLanguageLabelFile('EXT:ns_openai/Resources/Private/Language/locallang_be.xlf');
        $templateRootPath = GeneralUtility::getFileAbsFileName('EXT:ns_openai/Resources/Private/Backend/Templates/');
        $standlone->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:ns_openai/Resources/Private/Backend/Partials/')]);
        $typo3VersionArray = VersionNumberUtility::convertVersionStringToArray(
            VersionNumberUtility::getCurrentTypo3Version()
        );

        $templatePathAndFilename = $templateRootPath.'AiOpen.html';
        $standlone->setTemplatePathAndFilename($templatePathAndFilename);
        $pageData = $this->pageRepository->getCurrentPageData($pageId, $typo3VersionArray['version_main']);
        $assign = [
            'baseUrl' => GeneralUtility::getIndpEnv('TYPO3_SITE_URL'),
            'pageId' => $pageId,
            'pageTitlePrompts' => $this->extensionConfiguration->getPageTitlePrompts(),
            'pageData' => $pageData,
            'version' => $typo3VersionArray['version_main'],
        ];

        $standlone->assignMultiple($assign);
        return $standlone->render();
    }
    protected function getLanguageId(): int
    {
        $moduleData = (array)BackendUtility::getModuleData(['language'], [], 'web_layout');
        return (int)$moduleData['language'];
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