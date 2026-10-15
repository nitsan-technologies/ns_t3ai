<?php
namespace NITSAN\NsT3Ai\Backend\Listener;
use TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent;
use NITSAN\NsT3Ai\Backend\PageLayoutHeaderV12;
use NITSAN\NsT3Ai\Backend\PremiumStrip;
use TYPO3\CMS\Core\Page\PageRenderer;

class ModifyPageLayoutContent {

    protected PageLayoutHeaderV12 $pageLayoutHeader;

    protected PremiumStrip $premiumStrip;

    protected PageRenderer $pageRenderer;

    public function __construct(PageLayoutHeaderV12 $pageLayoutHeader, PremiumStrip $premiumStrip, PageRenderer $pageRenderer)
    {
        $this->pageLayoutHeader = $pageLayoutHeader;
        $this->premiumStrip = $premiumStrip;
        $this->pageRenderer = $pageRenderer;
    }

    public function __invoke(ModifyPageLayoutContentEvent $event)
    {
        $request = $event->getRequest();
        $content = $this->pageLayoutHeader->render($request);
        if ($content !== '') {
            $premiumStrip = $this->premiumStrip->render();
            if ($premiumStrip !== '') {
                $this->pageRenderer->addCssFile(PremiumStrip::STYLESHEET);
                $content = $premiumStrip . $content;
            }
        }
        $event->addHeaderContent($content);
    }
}
