<?php

namespace mortscode\feedback\helpers;

use Craft;

class CacheHelpers
{
    /**
     * Purges the cache for the given urls in the array
     *
     * @param array $entryUrls
     * @return void
     */
    public static function purgeEntriesByUrl(array $entryUrls): void {
        if (Craft::$app->plugins->isPluginEnabled('servd-asset-storage')) {
            $urls = [];
            foreach ($entryUrls as $entryUrl) {
                $urls[] = $entryUrl;
            }

            Craft::$app->queue->push(new \servd\AssetStorage\StaticCache\Jobs\PurgeUrlsJob([
                'description' => 'Purge static cache',
                'urls' => $urls,
            ]));
        }
    }
}