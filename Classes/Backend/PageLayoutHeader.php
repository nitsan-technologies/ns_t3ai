<?php

namespace NITSAN\NsT3Ai\Backend;

use NITSAN\NsT3Ai\Domain\Repository\PageRepository;
use NITSAN\NsT3Ai\Helper\NsExtensionConfiguration;
use TYPO3\CMS\Backend\Controller\PageLayoutController;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

class PageLayoutHeader extends AbstractPageLayoutHeader
{
    private array $requireJsModules = [
        'TYPO3/CMS/NsT3ai/Module',
    ];

    protected ?PageRepository $pageRepository = null;

    private NsExtensionConfiguration $extensionConfiguration;

    public function __construct(NsExtensionConfiguration $extensionConfiguration, PageRepository $pageRepository)
    {
        parent::__construct();
        $this->extensionConfiguration = $extensionConfiguration;
        $this->pageRepository = $pageRepository;
    }

    /**
     * @param array|null $params
     * @param null  $parentObj
     * @return string
     */
    public function render(array $params = null, $parentObj = null): string
    {
        $languageId = $this->getLanguageId();
        $pageId = (int)GeneralUtility::_GET('id');
        $currentPage = $this->getCurrentPage($pageId, $languageId, $parentObj);
        if (!is_array($currentPage) || $languageId == -1 || $currentPage['hidden']) {
            return '';
        }
        $standlone = GeneralUtility::makeInstance(StandaloneView::class);
        foreach ($this->requireJsModules as $requireJsModule) {
            $this->pageRenderer->loadRequireJsModule($requireJsModule);
        }
        $this->pageRenderer->addCssFile('EXT:ns_t3ai/Resources/Public/Css/Style.css');
        $standlone->getRequest()->setControllerExtensionName('ns_t3ai');
        $templateRootPath = GeneralUtility::getFileAbsFileName('EXT:ns_t3ai/Resources/Private/Backend/Templates/');
        $standlone->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:ns_t3ai/Resources/Private/Backend/Partials/')]);
        $typo3VersionArray = VersionNumberUtility::convertVersionStringToArray(
            VersionNumberUtility::getCurrentTypo3Version()
        );
        $templatePathAndFilename = $templateRootPath.'T3Ai.html';
        if (version_compare($typo3VersionArray['version_main'], 11, '<')) {
            $templatePathAndFilename = $templateRootPath.'/v10/T3Ai.html';
        }
        $standlone->setTemplatePathAndFilename($templatePathAndFilename);
        $pageData = $this->pageRepository->getCurrentPageData($parentObj->id, $typo3VersionArray['version_main']);
        $assign = [
            'baseUrl' => GeneralUtility::getIndpEnv('TYPO3_SITE_URL'),
            'pageId' => $parentObj->id,
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

    protected function getCurrentPage(int $pageId, int $languageId, object $parentObj): ?array
    {
        $currentPage = null;
        if (($parentObj instanceof PageLayoutController || $parentObj instanceof ModuleTemplate) && $pageId > 0) {
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
