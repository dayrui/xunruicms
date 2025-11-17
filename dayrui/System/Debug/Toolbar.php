<?php

declare(strict_types=1);

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace CodeIgniter\Debug;

use CodeIgniter\Debug\Toolbar\Collectors\BaseCollector;
use CodeIgniter\Debug\Toolbar\Collectors\Config;
use CodeIgniter\I18n\Time;
use Config\Toolbar as ToolbarConfig;

/**
 * Displays a toolbar with bits of stats to aid a developer in debugging.
 *
 * Inspiration: http://prophiler.fabfuel.de
 */
class Toolbar
{
    /**
     * Toolbar configuration settings.
     *
     * @var ToolbarConfig
     */
    protected $config;

    /**
     * Collectors to be used and displayed.
     *
     * @var list<BaseCollector>
     */
    protected $collectors = [];

    public function __construct(ToolbarConfig $config)
    {
        $this->config = $config;

        foreach ($config->collectors as $collector) {

            $this->collectors[] = new $collector();
        }
    }

    /**
     * Returns all the data required by Debug Bar
     *
     * @param float           $startTime App start time
     * @param IncomingRequest $request
     *
     * @return string JSON encoded data
     */
    public function run(float $startTime, float $totalTime): string
    {
        $data = [];
        // Data items used within the view.
        $data['url']             = dr_now_url();
        $data['method']          = 1;
        $data['isAJAX']          = 2;
        $data['startTime']       = $startTime;
        $data['totalTime']       = $totalTime * 1000;
        $data['totalMemory']     = number_format(memory_get_peak_usage() / 1024 / 1024, 3);
        $data['segmentDuration'] = $this->roundTo($data['totalTime'] / 7);
        $data['segmentCount']    = (int) ceil($data['totalTime'] / $data['segmentDuration']);
        $data['CI_VERSION']      = FRAME_VERSION;
        $data['collectors']      = [];

        foreach ($this->collectors as $collector) {
            $data['collectors'][] = $collector->getAsArray();
        }

        

        $data['vars']['response'] = [
            'headers'     => [],
        ];

       

        $data['config'] = Config::display();


        return json_encode($data);
    }

    /**
     * Called within the view to display the timeline itself.
     */
    protected function renderTimeline(array $collectors, float $startTime, int $segmentCount, int $segmentDuration, array &$styles): string
    {
        $rows       = $this->collectTimelineData($collectors);
        $styleCount = 0;

        // Use recursive render function
        return $this->renderTimelineRecursive($rows, $startTime, $segmentCount, $segmentDuration, $styles, $styleCount);
    }

    /**
     * Recursively renders timeline elements and their children.
     */
    protected function renderTimelineRecursive(array $rows, float $startTime, int $segmentCount, int $segmentDuration, array &$styles, int &$styleCount, int $level = 0, bool $isChild = false): string
    {
        $displayTime = $segmentCount * $segmentDuration;

        $output = '';

        foreach ($rows as $row) {
            $hasChildren = isset($row['children']) && ! empty($row['children']);
            $isQuery     = isset($row['query']) && ! empty($row['query']);

            // Open controller timeline by default
            $open = $row['name'] === 'Controller';

            if ($hasChildren || $isQuery) {
                $output .= '<tr class="timeline-parent' . ($open ? ' timeline-parent-open' : '') . '" id="timeline-' . $styleCount . '_parent" data-toggle="childrows" data-child="timeline-' . $styleCount . '">';
            } else {
                $output .= '<tr>';
            }

            $output .= '<td class="' . ($isChild ? 'debug-bar-width30' : '') . ' debug-bar-level-' . $level . '" >' . ($hasChildren || $isQuery ? '<nav></nav>' : '') . $row['name'] . '</td>';
            $output .= '<td class="' . ($isChild ? 'debug-bar-width10' : '') . '">' . $row['component'] . '</td>';
            $output .= '<td class="' . ($isChild ? 'debug-bar-width10 ' : '') . 'debug-bar-alignRight">' . number_format($row['duration'] * 1000, 2) . ' ms</td>';
            $output .= "<td class='debug-bar-noverflow' colspan='{$segmentCount}'>";

            $offset = ((((float) $row['start'] - $startTime) * 1000) / $displayTime) * 100;
            $length = (((float) $row['duration'] * 1000) / $displayTime) * 100;

            $styles['debug-bar-timeline-' . $styleCount] = "left: {$offset}%; width: {$length}%;";

            $output .= "<span class='timer debug-bar-timeline-{$styleCount}' title='" . number_format($length, 2) . "%'></span>";
            $output .= '</td>';
            $output .= '</tr>';

            $styleCount++;

            // Add children if any
            if ($hasChildren || $isQuery) {
                $output .= '<tr class="child-row ' . ($open ? '' : ' debug-bar-ndisplay') . '" id="timeline-' . ($styleCount - 1) . '_children" >';
                $output .= '<td colspan="' . ($segmentCount + 3) . '" class="child-container">';
                $output .= '<table class="timeline">';
                $output .= '<tbody>';

                if ($isQuery) {
                    // Output query string if query
                    $output .= '<tr>';
                    $output .= '<td class="query-container debug-bar-level-' . ($level + 1) . '" >' . $row['query'] . '</td>';
                    $output .= '</tr>';
                } else {
                    // Recursively render children
                    $output .= $this->renderTimelineRecursive($row['children'], $startTime, $segmentCount, $segmentDuration, $styles, $styleCount, $level + 1, true);
                }

                $output .= '</tbody>';
                $output .= '</table>';
                $output .= '</td>';
                $output .= '</tr>';
            }
        }

        return $output;
    }

    /**
     * Returns a sorted array of timeline data arrays from the collectors.
     *
     * @param array $collectors
     */
    protected function collectTimelineData($collectors): array
    {
        $data = [];

        // Collect it
        foreach ($collectors as $collector) {
            if (! $collector['hasTimelineData']) {
                continue;
            }

            $data = array_merge($data, $collector['timelineData']);
        }

        // Sort it
        $sortArray = [
            array_column($data, 'start'), SORT_NUMERIC, SORT_ASC,
            array_column($data, 'duration'), SORT_NUMERIC, SORT_DESC,
            &$data,
        ];

        array_multisort(...$sortArray);

        // Add end time to each element
        array_walk($data, static function (&$row): void {
            $row['end'] = $row['start'] + $row['duration'];
        });

        // Group it
        $data = $this->structureTimelineData($data);

        return $data;
    }

    /**
     * Arranges the already sorted timeline data into a parent => child structure.
     */
    protected function structureTimelineData(array $elements): array
    {
        // We define ourselves as the first element of the array
        $element = array_shift($elements);

        // If we have children behind us, collect and attach them to us
        while ($elements !== [] && $elements[array_key_first($elements)]['end'] <= $element['end']) {
            $element['children'][] = array_shift($elements);
        }

        // Make sure our children know whether they have children, too
        if (isset($element['children'])) {
            $element['children'] = $this->structureTimelineData($element['children']);
        }

        // If we have no younger siblings, we can return
        if ($elements === []) {
            return [$element];
        }

        // Make sure our younger siblings know their relatives, too
        return array_merge([$element], $this->structureTimelineData($elements));
    }

    /**
     * Returns an array of data from all of the modules
     * that should be displayed in the 'Vars' tab.
     */
    protected function collectVarData(): array
    {
        if (! ($this->config->collectVarData ?? true)) {
            return [];
        }

        $data = [];

        foreach ($this->collectors as $collector) {
            if (! $collector->hasVarData()) {
                continue;
            }

            $data = array_merge($data, $collector->getVarData());
        }

        return $data;
    }

    /**
     * Rounds a number to the nearest incremental value.
     */
    protected function roundTo(float $number, int $increments = 5): float
    {
        $increments = 1 / $increments;

        return ceil($number * $increments) / $increments;
    }

    /**
     * Prepare for debugging.
     *
     * @return void
     */
    public function prepare($app)
    {


        if (IS_POST) {
            return;
        } elseif (IS_API) {
            return;
        } elseif (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return;
        }

        //ajax请求
        
        /**
         * @var IncomingRequest|null $request
         */
        if (CI_DEBUG && ! is_cli()) {



            $stats   = $app->getPerformanceStats();
            $data    = $this->run( $stats['startTime'], $stats['totalTime']);

            helper('filesystem');

            // Updated to microtime() so we can get history
            $time = sprintf('%.6f', Time::now()->format('U.u'));

            if (! is_dir(WRITEPATH . 'debugbar')) {
                mkdir(WRITEPATH . 'debugbar', 0777);
            }

            write_file(WRITEPATH . 'debugbar/debugbar_' . $time . '.json', $data, 'w+');

 
            $kintScript         = file_get_contents($this->config->viewsPath.'script.js');
            
            $kintScript         = substr($kintScript, 0, strpos($kintScript, '</style>') + 8);
            $kintScript         = ($kintScript === '0') ? '' : $kintScript;


            $script = PHP_EOL
                . '<script  id="debugbar_loader" '
                . 'data-time="' . $time . '" '
                . 'src="' . WEB_DIR . SELF . '?debugbar"></script>'
                . '<script  id="debugbar_dynamic_script"></script>'
                . '<style id="debugbar_dynamic_style"></style>'
                . $kintScript
                . PHP_EOL;

                echo $script;
                return ;

            if (str_contains((string) $response->getBody(), '<head>')) {
                $response->setBody(
                    preg_replace(
                        '/<head>/',
                        '<head>' . $script,
                        $response->getBody(),
                        1
                    )
                );

                return;
            }

            $response->appendBody($script);
        }
    } 
    

    /**
     * Inject debug toolbar into the response.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public function respond()
    {
        

        $debugbar = isset($_GET['debugbar']) ? 1 : '';
        $debugbar_time = isset($_GET['debugbar_time']) && $_GET['debugbar_time'] ? $_GET['debugbar_time'] : '';

        // If the request contains '?debugbar then we're
        // simply returning the loading script
        if ($debugbar) {
            header('Content-Type: application/javascript');

            ob_start();
            include $this->config->viewsPath . 'toolbarloader.js';
            $output = ob_get_clean();
            $output = str_replace('{url}', rtrim(site_url(), '/'), $output);
            echo $output;

            exit;
        }

        // Otherwise, if it includes ?debugbar_time, then
        // we should return the entire debugbar.
        if ($debugbar_time) {
            helper('security');

        
            $filename = sanitize_filename('debugbar_' . $debugbar_time);
            $filename = WRITEPATH . 'debugbar/' . $filename . '.json';

            if (is_file($filename)) {
                // Show the toolbar if it exists
                echo $this->format(file_get_contents($filename), 'html');

                exit;
            }

            // Filename not found
            http_response_code(404);

            exit; // Exit here is needed to avoid loading the index page
        }
    }

    /**
     * Format output
     */
    protected function format(string $data, string $format = 'html'): string
    {
        $data = json_decode($data, true);

        

        $output = '';


        $data['styles'] = [];
        extract($data);
        ob_start();
        include $this->config->viewsPath . 'toolbar.tpl.php';
        $output = ob_get_clean();

        return $output;
    }

    public function render($name, $data)
    {
        extract($data);
        ob_start();
        include $this->config->viewsPath . $name;
        $output = ob_get_clean();
        return $output;
    }
}
