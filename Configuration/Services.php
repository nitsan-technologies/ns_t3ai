<?php

use NITSAN\NsT3Ai\Factory\SelectedModelFactory;
use NITSAN\NsT3Ai\Helper\NsExtensionConfiguration;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ReferenceConfigurator;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use NITSAN\NsT3Ai\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Log\LogManager;
use Psr\Log\LoggerInterface;
use NITSAN\NsT3Ai\Service\NsT3AiContentService;
use NITSAN\NsT3Ai\Factory\CustomLanguageFactory;
use NITSAN\NsT3Ai\Controller\T3AiController;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Core\Page\PageRenderer;

return static function (ContainerConfigurator $containerConfigurator, ContainerBuilder $containerBuilder): void {
    global $typo3VersionArray;
    $typo3VersionArray = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionStringToArray(
        \TYPO3\CMS\Core\Utility\VersionNumberUtility::getCurrentTypo3Version()
    );

    $services = $containerConfigurator->services();
    $services->defaults()
        ->private()
        ->autowire()
        ->autoconfigure();

    $services->load('NITSAN\\NsT3Ai\\', __DIR__ . '/../Classes/')
        ->exclude([
            __DIR__ . '/../Classes/Domain/Model',
        ]);

    $services->set('ExtConf.nsT3ai', 'array')
        ->factory([new ReferenceConfigurator(ExtensionConfiguration::class), 'get'])
        ->args([
            'ns_t3ai'
        ]);

    $services->set('CustomLanguageArray', 'array')
        ->factory([new ReferenceConfigurator(CustomLanguageFactory::class), 'getCustomLanguages']);

    $services->set('SelectedModel', 'bool')
        ->factory([new ReferenceConfigurator(SelectedModelFactory::class), 'checkSelectedModel'])
        ->arg('$extConf', new ReferenceConfigurator('ExtConf.nsT3ai'));

    $containerBuilder->register('Logger', LoggerInterface::class);
    $services->set('PsrLogInterface', 'Logger')
        ->factory([
            new ReferenceConfigurator(LogManager::class), 'getLogger'
        ]);

    $services->set(NsT3AiContentService::class)
        ->arg('$languages', new ReferenceConfigurator('CustomLanguageArray'))
        ->arg('$extConf', new ReferenceConfigurator('ExtConf.nsT3ai'))
        ->arg('$nonLegacyModel', new ReferenceConfigurator('SelectedModel'))
        ->arg('$uriBuilder', new ReferenceConfigurator(UriBuilder::class));

    $services->set(T3AiController::class)
        ->arg('$contentService', new ReferenceConfigurator(NsT3AiContentService::class))
        ->arg('$logger', new ReferenceConfigurator('PsrLogInterface'))
        ->arg('$pageRepository', new ReferenceConfigurator(PageRepository::class))
        ->public();

    $services->set(NsExtensionConfiguration::class)
        ->public();

    if(version_compare($typo3VersionArray['version_main'], 12, '>=')){
        $services->set(\NITSAN\NsT3Ai\Backend\PageLayoutHeaderV12::class)
            ->arg('$extensionConfiguration', new ReferenceConfigurator(NsExtensionConfiguration::class))
            ->arg('$pageRepository', new ReferenceConfigurator(PageRepository::class))
            ->arg('$pageRenderer', new ReferenceConfigurator(PageRenderer::class))
            ->public();
    }
    else{
        $services->set(\NITSAN\NsT3Ai\Backend\PageLayoutHeader::class)
            ->arg('$extensionConfiguration', new ReferenceConfigurator(NsExtensionConfiguration::class))
            ->arg('$pageRepository', new ReferenceConfigurator(PageRepository::class))
            ->public();            
    }
};
