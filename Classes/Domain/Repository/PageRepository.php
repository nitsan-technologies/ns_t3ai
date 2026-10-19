<?php

namespace NITSAN\NsT3Ai\Domain\Repository;

use Doctrine\DBAL\Driver\Exception;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PageRepository
{
    /**
     * SEO fields the AI feature is allowed to write.
     *
     * @var list<string>
     */
    public const ALLOWED_SAVE_FIELDS = [
        'seo_title',
        'description',
        'keywords',
        'og_title',
        'og_description',
        'twitter_title',
        'twitter_description',
    ];

    /**
     * Persist a whitelisted pages field via DataHandler (enforces BE permissions).
     */
    public function saveField(int $pageId, string $fieldName, string $suggestion): bool
    {
        if ($pageId <= 0 || !in_array($fieldName, self::ALLOWED_SAVE_FIELDS, true)) {
            return false;
        }

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                'pages' => [
                    $pageId => [
                        $fieldName => $suggestion,
                    ],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();

        return empty($dataHandler->errorLog);
    }

    /**
     * @param array{uid: int} $targetLanguage
     */
    public function markPageAsTranslatedWithNsT3Ai(int $pageId, array $targetLanguage): void
    {
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('pages')
            ->update(
                'pages',
                [
                    'tx_nst3ai_content_not_checked' => 1,
                    'tx_nst3ai_translated_time' => time(),
                ],
                [
                    'l10n_parent' => $pageId,
                    'sys_language_uid' => $targetLanguage['uid'],
                ],
                [
                    Connection::PARAM_INT,
                    Connection::PARAM_INT,
                ]
            );
    }

    /**
     * @throws Exception
     */
    public function getCurrentPageData($pageId, $version)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');
        if ($version < 11) {
            return $connection->select(
                ['uid', 'title', 'seo_title', 'description', 'keywords', 'og_title', 'og_description', 'twitter_title', 'twitter_description'],
                'pages',
                ['uid' => (int)$pageId],
                [],
                [],
                1
            )->fetch();
        }

        return $connection->select(
            ['uid', 'title', 'seo_title', 'description', 'keywords', 'og_title', 'og_description', 'twitter_title', 'twitter_description'],
            'pages',
            ['uid' => (int)$pageId],
            [],
            [],
            1
        )->fetchAssociative();
    }
}
