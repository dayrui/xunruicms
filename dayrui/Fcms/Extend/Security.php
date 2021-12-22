<?php namespace Phpcmf\Extend;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
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
    public function verify(RequestInterface $request)
    {

        if (defined('IS_API') && IS_API) {
            return $this;
        } elseif (isset($_GET['appid']) && is_file(dr_get_app_dir('httpapi').'/install.lock')) {
            return $this;
        } elseif (APP_DIR == 'weixin') {
            return $this;
        } elseif (defined('IS_INSTALL') || defined('IS_NOT_CSRF')) {
            return $this;
        }

        // 过滤白名单内的控制器
        if (in_array(\Phpcmf\Service::L('router')->uri(), \Phpcmf\Service::Filters())) {
            return $this;
        }

        // Protects POST, PUT, DELETE, PATCH
        $method           = strtoupper($request->getMethod());
        $methodsToProtect = ['POST', 'PUT', 'DELETE', 'PATCH'];
        if (! in_array($method, $methodsToProtect, true)) {
            return $this;
        }

        // Does the token exist in POST, HEADER or optionally php:://input - json data.
        if ($request->hasHeader($this->headerName) && ! empty($request->header($this->headerName)->getValue())) {
            $tokenName = $request->header($this->headerName)->getValue();
        } else {
            $json = json_decode($request->getBody());
            if (! empty($request->getBody()) && ! empty($json) && json_last_error() === JSON_ERROR_NONE) {
                $tokenName = $json->{$this->tokenName} ?? null;
            } else {
                $tokenName = null;
            }
        }

        $token =  $request->getPost($this->tokenName) ?? $tokenName;

        // Do the tokens match?
        if (! isset($token, $this->hash) || ! hash_equals($this->hash, $token)) {
            dr_exit_msg(0, '跨站验证禁止此操作', 'CSRFVerify');
        }

        if (isset($_POST[$this->tokenName])) {
            // We kill this since we're done and we don't want to pollute the POST array.
            unset($_POST[$this->tokenName]);
            $request->setGlobal('post', $_POST);
        }

        $this->generateHash();

        return $this;
    }

    /**
     * Generates the CSRF Hash.
     */
    protected function generateHash(): string
    {
        if ($this->hash === null) {
            $name = 'csrf_hash_'.md5(isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] ? $_SERVER['HTTP_USER_AGENT'] : '');
            $hash = \Phpcmf\Service::L('cache')->get_auth_data($name, 1, 600);
            if ($hash) {
                return $this->hash = $hash;
            }
            $this->hash = bin2hex(random_bytes(16));
            \Phpcmf\Service::L('cache')->set_auth_data($name, $this->hash, 1);
        }

        return $this->hash;
    }

}