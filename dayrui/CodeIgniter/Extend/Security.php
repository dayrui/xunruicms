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
use Config\App;

class Security extends \CodeIgniter\Security\Security {

    protected $tokenName = 'csrf_test_name';

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

        // 过滤白名单内的控制器
        if (in_array(\Phpcmf\Service::L('router')->uri(), \Phpcmf\Service::Filters())) {
            return $this;
        } elseif ((defined('IS_API_HTTP') && IS_API_HTTP) || (defined('IS_API') && IS_API)) {
            // api 请求下不做验证
            return $this;
        }

        // Protects POST, PUT, DELETE, PATCH
        $method = strtoupper($request->getMethod());
        $methodsToProtect = ['POST', 'PUT', 'DELETE', 'PATCH'];
        if (! in_array($method, $methodsToProtect, true)) {
            return $this;
        }

        // Does the token exist in POST, HEADER or optionally php:://input - json data.
        if ($request->hasHeader($this->headerName) && ! empty($request->header($this->headerName)->getValue())) {
            $tokenName = $request->header($this->headerName)->getValue();
        } else {
            $code = $request->getBody();
            $json = json_decode($code ? $code : '');
            if (! empty($request->getBody()) && ! empty($json) && json_last_error() === JSON_ERROR_NONE) {
                $tokenName = $json->{$this->tokenName} ?? null;
            } else {
                $tokenName = null;
            }
        }

        $token = $request->getPost($this->tokenName) ?? $tokenName;

        // Do the tokens match?
        if (! isset($token, $this->hash) || ! hash_equals($this->hash, $token)) {
            SYS_DEBUG && log_message('debug', 'CSRF验证拦截（'.$this->hash.' / '.$token.'）');
            dr_exit_msg(0, 'CSRF验证超时请重试', '', [
                'name' => $this->tokenName,
                'value' => $this->hash
            ]);
        }

        if (isset($_POST[$this->tokenName])) {
            // We kill this since we're done and we don't want to pollute the POST array.
            unset($_POST[$this->tokenName]);
            $request->setGlobal('post', $_POST);
        }

        return $this;
    }

    public function updateHash() {

        if (defined('SYS_CSRF_TIME') && SYS_CSRF_TIME) {
            // 每次生成，否则定期生成
            $this->hash = null;
            if ($this->csrfProtection === self::CSRF_PROTECTION_COOKIE) {
                unset($_COOKIE[$this->cookieName]);
                \Config\Services::response()->removeHeader('Content-Type');
                \Config\Services::response()->setcookie($this->cookieName, '', 0)->send();
            } else {
                // Session based CSRF protection
                Services::session()->remove($this->tokenName);
            }
        }

        $this->generateHash();
    }

}