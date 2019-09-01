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
 * 用于Services.php
 */

use CodeIgniter\HTTP\RequestInterface;

class Security extends \CodeIgniter\Security\Security
{
    /**
     * CSRF Verify
     *
     * @param RequestInterface $request
     *
     * @return $this|false
     * @throws \Exception
     */
    public function CSRFVerify(RequestInterface $request)
    {

        if (defined('SYS_CSRF') && !SYS_CSRF) {
            return $this;
        } elseif (defined('IS_API') && IS_API) {
            return $this;
        } elseif (isset($_GET['appid']) && isset($_GET['appsecret'])) {
            return $this;
        } elseif (APP_DIR == 'weixin') {
            return $this;
        }

        // If it's not a POST request we will set the CSRF cookie
        if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST')
        {
            return $this->CSRFSetCookie($request);
        }

        // Do the tokens exist in both the _POST and _COOKIE arrays?
        if (! isset($_POST[$this->CSRFTokenName], $_COOKIE[$this->CSRFCookieName]) || $_POST[$this->CSRFTokenName] !== $_COOKIE[$this->CSRFCookieName]
        ) // Do the tokens match?
        {
            CI_DEBUG && log_message('error', '跨站验证禁止此操作：'.FC_NOW_URL);
            dr_exit_msg(0, '跨站验证禁止此操作', 'CSRFVerify');;
        }

        // We kill this since we're done and we don't want to pollute the _POST array
        unset($_POST[$this->CSRFTokenName]);

        // Regenerate on every submission?
        if ($this->CSRFRegenerate)
        {
            // Nothing should last forever
            unset($_COOKIE[$this->CSRFCookieName]);
        }

        $this->CSRFSetHash();
        $this->CSRFSetCookie($request);

        log_message('info', 'CSRF token verified');

        return $this;
    }

}