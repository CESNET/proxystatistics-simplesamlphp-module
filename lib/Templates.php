<?php declare(strict_types=1);

namespace SimpleSAML\Module\proxystatistics;

use SimpleSAML\Auth\Simple;
use SimpleSAML\Configuration;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\XHTML\Template;

class Templates
{
    private const DEBUG_PREFIX = 'proxystatistics:Templates - ';

    private const INSTANCE_NAME = 'instance_name';

    public static function showProviders($side, $tab)
    {
        assert(in_array($side, ['identity', 'service'], true));

        $t = new Template(Configuration::getInstance(), 'proxystatistics:providers-tpl.php');
        $t->data['side'] = $side;
        $t->data['tab'] = $tab;
        $t->show();
    }

    public static function pieChart($id)
    {
        ?>
        <div class="pie-chart-container row">
            <div class="canvas-container col-md-7">
                <canvas id="<?php echo $id; ?>" class="pieChart chart-<?php echo $id; ?>"></canvas>
            </div>
            <div class="legend-container col-md-5" id="legend-container"></div>
        </div>
        <?php
    }

    public static function timeRange($vars = [])
    {
        $t = new Template(Configuration::getInstance(), 'proxystatistics:timeRange-tpl.php');
        $t->data['lastDays'] = self::getSelectedTimeRange();
        foreach ($vars as $var => $value) {
            $t->data[$var] = $value;
        }

        $t->show();
    }

    public static function loginsDashboard()
    {
        $t = new Template(Configuration::getInstance(), 'proxystatistics:loginsDashboard-tpl.php');
        $t->show();
    }

    public static function showDetail($side)
    {
        $t = new Template(Configuration::getInstance(), 'proxystatistics:detail-tpl.php');

        $lastDays = self::getSelectedTimeRange();
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => 0,
            ],
        ]);
        $t->data['id'] = $id;

        $t->data['detailGraphClass'] = '';
        if (Config::getInstance()->getMode() === Utils::theOther(Config::SIDES, $side)) {
            $t->data['detailGraphClass'] = 'hidden';
        }

        self::headIncludes($t);

        $dbCmd = new DatabaseCommand();
        $t->data['head'] .= Utils::metaData(
            'loginCountPerDay',
            $dbCmd->getLoginCountPerDay($lastDays, [
                $side => $id,
            ])
        );
        $t->data['head'] .= Utils::metaData(
            'accessCounts',
            $dbCmd->getAccessCount(Utils::theOther(Config::SIDES, $side), $lastDays, [
                $side => $id,
            ])
        );

        $translations = [
            'count' => $t->t('{proxystatistics:stats:count}'),
            'other' => $t->t('{proxystatistics:stats:other}'),
            'of_logins' => $t->t('{proxystatistics:stats:of_logins}'),
            'of_users' => $t->t('{proxystatistics:stats:of_users}'),
        ];
        foreach (Config::SIDES as $s) {
            $translations['tables_' . $s] = $t->t('{proxystatistics:stats:side_' . $s . '}');
        }
        $t->data['head'] .= Utils::metaData('translations', $translations);

        $name = $dbCmd->getEntityNameByEntityIdentifier($side, $id);
        $t->data['header'] = $t->t('{proxystatistics:stats:' . $side . 'Detail_header_name}') . $name;

        $t->data['htmlinject']['htmlContentPost'][]
            = '<script type="text/javascript" src="' . self::getFullUrl('index.js') . '"></script>';

        $t->data['side'] = $side;
        $t->data['other_side'] = Utils::theOther(Config::SIDES, $side);

        $t->show();
    }

    public static function showIndex()
    {
        $config = Config::getInstance();

        $authSource = $config->getRequiredAuthSource();
        if ($authSource) {
            $as = new Simple($authSource);
            $as->requireAuth();
        }

        $t = new Template(Configuration::getInstance(), 'proxystatistics:index-tpl.php');
        $lastDays = self::getSelectedTimeRange();

        $t->data['tab'] = filter_input(
            INPUT_GET,
            'tab',
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'default' => 0,
                    'min_range' => 0,
                    'max_range' => 2,
                ],
            ]
        ); // indexed from 0

        $t->data['tabsAttributes'] = [
            'PROXY' => 'id="tab-1" href="' . self::getFullUrl('summary.php') . '?lastDays=' . $lastDays . '"',
            'IDP' => 'id="tab-2" href="' . self::getFullUrl('identityProviders.php') . '?lastDays=' . $lastDays . '"',
            'SP' => 'id="tab-3" href="' . self::getFullUrl('serviceProviders.php') . '?lastDays=' . $lastDays . '"',
        ];
        $mode = $config->getMode();
        if (Config::MODE_PROXY !== $mode) {
            $t->data['tabsAttributes'][$mode] = 'class="hidden" ' . $t->data['tabsAttributes'][$mode];
        }

        $t->data['header'] = $t->t('{proxystatistics:stats:statistics_header}');
        $instanceName = Configuration::getInstance()->getString(self::INSTANCE_NAME, null);
        if (null !== $instanceName) {
            $t->data['header'] = $instanceName . ' ' . $t->data['header'];
        } else {
            Logger::warning(
                self::DEBUG_PREFIX . 'missing configuration option in config.php - instance_name is not set.'
            );
        }

        self::headIncludes($t);

        $dbCmd = new DatabaseCommand();
        $t->data['head'] .= Utils::metaData('loginCountPerDay', $dbCmd->getLoginCountPerDay($lastDays));

        $translations = [
            'count' => $t->t('{proxystatistics:stats:count}'),
            'other' => $t->t('{proxystatistics:stats:other}'),
            'of_logins' => $t->t('{proxystatistics:stats:of_logins}'),
            'of_users' => $t->t('{proxystatistics:stats:of_users}'),
        ];
        foreach (Config::SIDES as $side) {
            $otherSide = Utils::theOther(Config::SIDES, $side);
            $t->data['head'] .= Utils::metaData(
                'loginCountPer' . $side,
                $dbCmd->getAccessCount($side, $lastDays, [
                    $otherSide => null,
                ])
            );
            $translations['tables_' . $side] = $t->t('{proxystatistics:stats:side_' . $side . '}');
        }

        $t->data['head'] .= Utils::metaData('translations', $translations);

        $t->show();
    }

    public static function showLegend($t, $side)
    {
        $mode = Config::getInstance()->getMode();
        echo $t->t(
            '{proxystatistics:stats:chart_legend}',
            [
                '!side_of' => $t->t('{proxystatistics:stats:chart_legend_side_of_' . $side . '}'),
                '!side_on' => $t->t('{proxystatistics:stats:chart_legend_side_on_' . $side . '}'),
            ]
        );
        if (Config::MODE_SP === $side && Config::MODE_SP !== $mode) {
            echo ' ';
            echo $t->t(
                '{proxystatistics:stats:first_access_only}',
                [
                    '!through_mode' => $t->t('{proxystatistics:stats:through_mode_' . $mode . '}'),
                ]
            );
        }
    }

    public static function showSummary()
    {
        $t = new Template(Configuration::getInstance(), 'proxystatistics:summary-tpl.php');
        $t->data['tab'] = 0;

        $mode = Config::getInstance()->getMode();
        $t->data['mode'] = $mode;
        $t->data['summaryGraphs'] = [];
        if (Config::MODE_PROXY === $mode || Config::MODE_MULTI_IDP === $mode) {
            foreach (Config::SIDES as $side) {
                $t->data['summaryGraphs'][$side] = [];
                $t->data['summaryGraphs'][$side]['Providers'] = 'col-md-6 graph';
                $t->data['summaryGraphs'][$side]['ProvidersLegend'] = 'col-md-12';
                $t->data['summaryGraphs'][$side]['ProvidersGraph'] = 'col-md-12';
            }
        } else {
            $side = $mode;
            $t->data['summaryGraphs'][$side] = [];
            $t->data['summaryGraphs'][$side]['Providers'] = 'hidden';
            $t->data['summaryGraphs'][$side]['ProvidersLegend'] = '';
            $t->data['summaryGraphs'][$side]['ProvidersGraph'] = '';
            $otherSide = Utils::theOther(Config::SIDES, $side);
            $t->data['summaryGraphs'][$otherSide] = [];
            $t->data['summaryGraphs'][$otherSide]['Providers'] = 'col-md-12 graph';
            $t->data['summaryGraphs'][$otherSide]['ProvidersLegend'] = 'col-md-6';
            $t->data['summaryGraphs'][$otherSide]['ProvidersGraph'] = 'col-md-6 col-md-offset-3';
        }

        $t->show();
    }

    private static function getSelectedTimeRange()
    {
        return filter_input(
            INPUT_GET,
            'lastDays',
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'default' => 0,
                    'min_range' => 0,
                ],
            ]
        );
    }

    private static function headIncludes($t)
    {
        $t->data['jquery'] = [
            'core' => true,
            'ui' => true,
            'css' => true,
        ];
        $t->data['head'] = '';
        $t->data['head'] .= '<link rel="stylesheet"  media="screen" type="text/css" href="' .
            self::getFullUrl('assets/css/bootstrap.min.css') . '" />';
        $t->data['head'] .= '<link rel="stylesheet"  media="screen" type="text/css" href="' .
            self::getFullUrl('assets/css/statisticsproxy.css') . '" />';
        $t->data['head'] .= '<script type="text/javascript" src="' .
            self::getFullUrl('assets/js/moment-with-locales.min.js') . '"></script>';
        $t->data['head'] .= '<script type="text/javascript" src="' .
            self::getFullUrl('assets/js/chart.min.js') . '"></script>';
        $t->data['head'] .= '<script type="text/javascript" src="' .
            self::getFullUrl('assets/js/hammer.min.js') . '"></script>';
        $t->data['head'] .= '<script type="text/javascript" src="' .
            self::getFullUrl('assets/js/chartjs-plugin-zoom.min.js') . '"></script>';
        $t->data['head'] .= '<script type="text/javascript" src="' .
            self::getFullUrl('assets/js/chartjs-adapter-moment.js') . '"></script>';
        $t->data['head'] .= '<script type="text/javascript" src="' .
            self::getFullUrl('assets/js/index.js') . '"></script>';

        $t->data['head'] .= Utils::metaData('module_url_base', self::getFullUrl());
    }

    private static function getFullUrl($path = ''): string
    {
        return Module::getModuleUrl('proxystatistics/') . $path;
    }
}
