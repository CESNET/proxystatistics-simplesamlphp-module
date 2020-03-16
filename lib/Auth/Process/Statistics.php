<?php

/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 * @author Pavel Břoušek <brousek@ics.muni.cz>
 */

namespace SimpleSAML\Module\proxystatistics\Auth\Process;

use DateTime;
use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Logger;
use SimpleSAML\Module\proxystatistics\DatabaseCommand;

class Statistics extends ProcessingFilter
{
    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);
    }

    public function process(&$request)
    {
        $dateTime = new DateTime();
        $dbCmd = new DatabaseCommand();
        $dbCmd->insertLogin($request, $dateTime);
        $spEntityId = $request['SPMetadata']['entityid'];

        $eduPersonUniqueId = '';
        $sourceIdPEppn = '';
        $sourceIdPEntityId = '';

        if (isset($request['Attributes']['eduPersonUniqueId'][0])) {
            $eduPersonUniqueId = $request['Attributes']['eduPersonUniqueId'][0];
        }
        if (isset($request['Attributes']['sourceIdPEppn'][0])) {
            $sourceIdPEppn = $request['Attributes']['sourceIdPEppn'][0];
        }
        if (isset($request['Attributes']['sourceIdPEntityID'][0])) {
            $sourceIdPEntityId = $request['Attributes']['sourceIdPEntityID'][0];
        }

        if (isset($request['perun']['user'])) {
            $user = $request['perun']['user'];
            Logger::notice('UserId: ' . $user->getId() . ', identity: ' . $eduPersonUniqueId . ', service: '
                . $spEntityId . ', external identity: ' . $sourceIdPEppn . ' from ' . $sourceIdPEntityId);
        } else {
            Logger::notice('User identity: ' . $eduPersonUniqueId . ', service: ' . $spEntityId .
                ', external identity: ' . $sourceIdPEppn . ' from ' . $sourceIdPEntityId);
        }
    }
}
