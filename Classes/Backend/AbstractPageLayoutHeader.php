<?php
declare(strict_types=1);
namespace NITSAN\NsOpenai\Backend;

use TYPO3\CMS\Backend\Controller\PageLayoutController;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;


abstract class AbstractPageLayoutHeader
{
    /**
     * @var \TYPO3\CMS\Core\Page\PageRenderer
     */
    protected $pageRenderer;

    public function __construct()
    {
        $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:ns_openai/Resources/Private/Language/locallang_be.xlf');
    }

    abstract public function render(array $params = null, $parentObj = null): string;
}
