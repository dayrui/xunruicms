<?php namespace Phpcmf\Controllers\Admin;

/* *
 *
 * Copyright [2019] [李睿]
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * www.xunruicms.com
 *
 * 本文件是框架系统文件，二次开发时不建议修改本文件
 *
 * */



class Module_content extends \Phpcmf\Common
{

    public function index() {

        $module = \Phpcmf\Service::L('cache')->get('module-'.SITE_ID.'-content');

        // 设置url
        if ($module) {
            foreach ($module as $dir => $t) {
                if ($t['hlist'] == 1) {
                    unset($module[$dir]);
                    continue;
                }
                $module[$dir]['url'] =\Phpcmf\Service::L('Router')->url($dir.'/content/index');
            }
        }

        $tmp['system'] = [
            'name' => '内容维护',
            'icon' => 'fa fa-database',
            'title' => '内容维护',
            'url' =>\Phpcmf\Service::L('Router')->url('content/index'),
            'dirname' => 'system',
        ];

        $module = dr_array22array($module, $tmp);

        $one = reset($module);

        // 只存在一个项目
        dr_count($module) == 1 && dr_redirect($one['url']);

        $dirname = $one['dirname'];

        \Phpcmf\Service::V()->assign([
            'url' => $one['url'],
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '内容维护' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-wrench'],
                ]
            ),
            'module' => $module,
            'dirname' => $dirname,
        ]);
        \Phpcmf\Service::V()->display('iframe_content.html');exit;
    }

}
