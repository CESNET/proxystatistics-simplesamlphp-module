<?php

namespace SimpleSAML\Module\proxystatistics\Auth\Process;

use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;

/**
 *
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */
class Statistics extends \SimpleSAML\Auth\ProcessingFilter
{
    private $config;
    private $reserved;

    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);

        if (!isset($config['config'])) {
            throw new Exception("missing mandatory configuration option 'config'");
        }
        $this->config = (array)$config['config'];
        $this->reserved = (array)$reserved;
    }

    public function process(&$request)
    {
        $dateTime = new \DateTime();
        DatabaseCommand::insertLogin($request, $dateTime);

        $eduPersonUniqueId = $request['Attributes']['eduPersonUniqueId'][0];
        $spEntityId = $request['SPMetadata']['entityid'];
        $sourceIdPEppn = $request['Attributes']['sourceIdPEppn'][0];
        $sourceIdPEntityId = $request['Attributes']['sourceIdPEntityID'][0];

        if (isset($request['perun']['user'])) {
            $user = $request['perun']['user'];
            Logger::notice('UserId: ' . $user->getId() . ', identity: ' .  $eduPersonUniqueId . ', service: '
                . $spEntityId . ', external identity: ' . $sourceIdPEppn . ' from ' . $sourceIdPEntityId);
        } else {
            Logger::notice('User identity: ' .  $eduPersonUniqueId . ', service: ' . $spEntityId .
                ', external identity: ' . $sourceIdPEppn . ' from ' . $sourceIdPEntityId);
        }

    }

}
