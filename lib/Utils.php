<?php

/**
 * @author Pavel Břoušek <brousek@ics.muni.cz>
 */

namespace SimpleSAML\Module\proxystatistics;

class Utils
{
    public static function theOther($arr, $val)
    {
        return current(array_diff($arr, [$val]));
    }

    public static function metaData($id, $data)
    {
        return '<meta name="' . $id . '" id="' . $id . '" ' .
        'content="' . htmlspecialchars(json_encode($data, JSON_NUMERIC_CHECK)) . '">';
    }
}
