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



class Category extends \Phpcmf\Admin\Category
{

    public function index() {
        $this->_Admin_List();
    }

    public function all_add() {
        $this->_Admin_All_Add();
    }

    public function add() {
        $this->_Admin_Add();
    }

    public function edit() {
        $this->_Admin_Edit();
    }

    public function url_edit() {
        $this->_Admin_Url_Edit();
    }

    public function move_edit() {
        $this->_Admin_Move_Edit();
    }
    
    public function show_edit() {
        $this->_Admin_Show_Edit();
    }
    
    public function displayorder_edit() {
        $this->_Admin_Order();
    }
    
    public function html_edit() {
        $this->_Admin_Html_Edit();
    }

    public function htmlall_edit() {
        $this->_Admin_Html_All_Edit();
    }

    public function phpall_edit() {
        $this->_Admin_Php_All_Edit();
    }

    public function del() {
        $this->_Admin_Del();
    }


}
