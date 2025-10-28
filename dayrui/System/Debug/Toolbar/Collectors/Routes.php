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

namespace CodeIgniter\Debug\Toolbar\Collectors;

use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

/**
 * Routes collector
 */
class Routes extends BaseCollector
{
    /**
     * Whether this collector has data that can
     * be displayed in the Timeline.
     *
     * @var bool
     */
    protected $hasTimeline = false;

    /**
     * Whether this collector needs to display
     * content in a tab or not.
     *
     * @var bool
     */
    protected $hasTabContent = true;

    /**
     * The 'title' of this Collector.
     * Used to name things in the toolbar HTML.
     *
     * @var string
     */
    protected $title = 'Routes';

    /**
     * Returns the data of this collector to be formatted in the toolbar
     *
     * @return array{
     *      matchedRoute: list<array{
     *          directory: string,
     *          controller: string,
     *          method: string,
     *          paramCount: int,
     *          truePCount: int,
     *          params: list<array{
     *              name: string,
     *              value: mixed
     *          }>
     *      }>,
     *      routes: list<array{
     *          method: string,
     *          route: string,
     *          handler: string
     *      }>
     * }
     *
     * @throws ReflectionException
     */
    public function display(): array
    {
        
        $file = APPPATH;
        if ($file == FRAMEPATH) {
            $file = CMSPATH.'Control';
        } else {
            $file.= 'Controllers';
        }

        if (IS_ADMIN) {
            $file.= '/Admin';
        } elseif (IS_MEMBER) {
            $file.= '/Member';
        } elseif (IS_API) {
            $file.= '/Api';
        }

        return [
            'matchedRoute' => [
                   'uri' => \Phpcmf\Service::L('Router')->uri(),
                   'url' => dr_now_url(),
                   'app' => APP_DIR ? APP_DIR : '/',
                   'controller' => \Phpcmf\Service::L('Router')->class,
                   'method' => \Phpcmf\Service::L('Router')->method,
                   'file' => $file.'/'.ucfirst(\Phpcmf\Service::L('Router')->class).'.php',
            ],
            'get'       => $_GET,
        ];
    }

    /**
     * Returns a count of all the routes in the system.
     */
    public function getBadgeValue(): int
    {

        return 1;
    }

    /**
     * Display the icon.
     *
     * Icon from https://icons8.com - 1em package
     */
    public function icon(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAFDSURBVEhL7ZRNSsNQFIUjVXSiOFEcuQIHDpzpxC0IGYeE/BEInbWlCHEDLsSiuANdhKDjgm6ggtSJ+l25ldrmmTwIgtgDh/t37r1J+16cX0dRFMtpmu5pWAkrvYjjOB7AETzStBFW+inxu3KUJMmhludQpoflS1zXban4LYqiO224h6VLTHr8Z+z8EpIHFF9gG78nDVmW7UgTHKjsCyY98QP+pcq+g8Ku2s8G8X3f3/I8b038WZTp+bO38zxfFd+I6YY6sNUvFlSDk9CRhiAI1jX1I9Cfw7GG1UB8LAuwbU0ZwQnbRDeEN5qqBxZMLtE1ti9LtbREnMIuOXnyIf5rGIb7Wq8HmlZgwYBH7ORTcKH5E4mpjeGt9fBZcHE2GCQ3Vt7oTNPNg+FXLHnSsHkw/FR+Gg2bB8Ptzrst/v6C/wrH+QB+duli6MYJdQAAAABJRU5ErkJggg==';
    }
}
