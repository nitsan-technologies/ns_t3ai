<?php

namespace NITSAN\NsOpenai\Domain\Repository;

use Doctrine\DBAL\Driver\Exception;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PageRepository
{
    public function saveField($pageId, $data): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');
        try {
            $row = $connection->select(
                ['*'],
                'pages',
                ['uid' => (int)$pageId],
                [],
                [],
                1
            )->fetchAssociative();
            if($data['fieldName']=='seo_title'){
                $row['seo_title'] = '';
            }
            if ($row !== false && isset($row[$data['fieldName']])) {
                if($data['fieldName'] == 'seo_title'){
                    $connection->update('pages', [
                        $data['fieldName'] => (string)$data['suggestion'],
                        'seo_title' => (string)$data['suggestion']
                    ], ['uid' => (int)$pageId]);
                }
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param array{uid: int} $targetLanguage
     */
    public function markPageAsTranslatedWithNsOpenai(int $pageId, array $targetLanguage): void
    {
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('pages')
            ->update(
                'pages',
                [
                    'tx_nsopenai_content_not_checked' => 1,
                    'tx_nsopenai_translated_time' => time(),
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
    public function getCurrentPageData($pageId): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');
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