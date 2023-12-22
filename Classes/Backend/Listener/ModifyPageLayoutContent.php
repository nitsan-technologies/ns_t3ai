<?php
namespace NITSAN\NsOpenai\Backend\Listener;
use TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent;
use NITSAN\NsOpenai\Backend\PageLayoutHeaderV12;

class ModifyPageLayoutContent {

    protected PageLayoutHeaderV12 $pageLayoutHeader;

    public function __construct(PageLayoutHeaderV12 $pageLayoutHeader)
    {
        $this->pageLayoutHeader = $pageLayoutHeader;
    }

    public function __invoke(ModifyPageLayoutContentEvent $event)
    {
        $request = $event->getRequest();
        $content = $this->pageLayoutHeader->render($request);
        $event->addHeaderContent($content);
    }
}
