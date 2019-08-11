<?php namespace Phpcmf\Extend;

/* *
 *
 * Copyright [2018] [李睿]
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
 * 本文件是框架系统文件，二次开发时不建议修改本文件
 *
 * */


/**
 * Debug工具栏模板类
 */

class View extends \CodeIgniter\Debug\Toolbar\Collectors\Views
{


    /**
     * 把CI模板类改成PHPCMF模板类用于debug.
     */
    public function __construct()
    {
        $this->viewer = \Phpcmf\Service::V();
    }

}
