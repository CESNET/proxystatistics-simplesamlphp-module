<?php

/**
 * Hook to run a cron job.
 *
 * @param array &$croninfo  Output
 * @return void
 */
function proxystatistics_hook_cron(&$croninfo)
{
    if ($croninfo['tag'] !== 'daily') {
        \SimpleSAML\Logger::debug('cron [proxystatistics]: Skipping cron in cron tag ['.$croninfo['tag'].'] ');
        return;
    }

    \SimpleSAML\Logger::info('cron [proxystatistics]: Running cron in cron tag ['.$croninfo['tag'].'] ');

    try {
        $dbCmd = new \SimpleSAML\Module\proxystatistics\DatabaseCommand();
        $dbCmd->deleteOldDetailedStatistics();
    } catch (\Exception $e) {
        $croninfo['summary'][] = 'Error during deleting old detailed statistics: '.$e->getMessage();
    }
}
