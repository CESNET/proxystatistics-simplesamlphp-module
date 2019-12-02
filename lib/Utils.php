<?php

/**
 * @author Pavel Břoušek <brousek@ics.muni.cz>
 */

namespace SimpleSAML\Module\proxystatistics;

use SimpleSAML\Module;

class Utils
{
    public static function getModuleUrlBaseMeta()
    {
        return '<meta name="module_url_base" id="module_url_base" content="'
            . htmlspecialchars(json_encode(Module::getModuleURL('proxystatistics/')))
            . '">';
    }
}
