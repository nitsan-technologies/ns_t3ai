<?php

namespace NITSAN\NsOpenai\Backend;

use NITSAN\NsOpenai\Domain\Repository\PageRepository;
use NITSAN\NsOpenai\Helper\NsExtensionConfiguration;
use TYPO3\CMS\Backend\Controller\PageLayoutController;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Page\PageRenderer;

class PageLayoutHeaderV12
{
    private array $requireJsModules = [
        '@TYPO3/CMS/NsOpenai/Module.js',
    ];

    protected ?PageRepository $pageRepository = null;

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
        if (!is_array($currentPage) || $languageId == -1) {
            return '';
        }
        $standlone = GeneralUtility::makeInstance(StandaloneView::class);
        foreach ($this->requireJsModules as $requireJsModule) {
            $this->pageRenderer->loadJavaScriptModule($requireJsModule);
        }
        $this->pageRenderer->addCssFile('EXT:ns_openai/Resources/Public/Css/Style.css');

        // $standlone->getRequest()->setControllerExtensionName('ns_openai');
        $standlone->setRequest($request);

        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($request,__FILE__.''.__LINE__);

        // \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($request->getControllerExtensionName(),__FILE__.''.__LINE__);
        // $standlone->getRequest()->setControllerExtensionName('ns_openai');


        $this->pageRenderer->addInlineLanguageLabelFile('EXT:ns_openai/Resources/Private/Language/locallang_be.xlf');
        $templateRootPath = GeneralUtility::getFileAbsFileName('EXT:ns_openai/Resources/Private/Backend/Templates/');
        $standlone->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:ns_openai/Resources/Private/Backend/Partials/')]);
        $typo3VersionArray = VersionNumberUtility::convertVersionStringToArray(
            VersionNumberUtility::getCurrentTypo3Version()
        );
        $templatePathAndFilename = $templateRootPath.'AiOpen.html';
        if (version_compare($typo3VersionArray['version_main'], 11, '<')) {
            $templatePathAndFilename = $templateRootPath.'/v10/AiOpen.html';
        }
        if (version_compare($typo3VersionArray['version_main'], 12, '=')) {
            $templatePathAndFilename = $templateRootPath.'/v12/AiOpen.html';
        }       
        $standlone->setTemplatePathAndFilename($templatePathAndFilename);
        $pageData = $this->pageRepository->getCurrentPageData($pageId, $typo3VersionArray['version_main']);
        $assign = [
            'baseUrl' => GeneralUtility::getIndpEnv('TYPO3_SITE_URL'),
            'pageId' => $pageId,
            'pageTitlePrompts' => $this->extensionConfiguration->getPageTitlePrompts(),
            'pageData' => $pageData,
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