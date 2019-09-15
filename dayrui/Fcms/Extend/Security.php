<?php namespace Phpcmf\Extend;

/**
 * http://www.xunruicms.com
 * 本文件是框架系统文件，二次开发时不可以修改本文件
 **/


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
            //CI_DEBUG && log_message('error', '跨站验证禁止此操作：'.FC_NOW_URL);
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