<?php namespace Phpcmf\Library;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

/**
 * 线程处理
 */

class Thread {

    /**
     * 线程非堵塞执行任务
     */
    function cron($param, $block = 0) {

        // 生成授权文件
        $param['auth'] = md5(dr_array2string($param));
        file_put_contents(WRITEPATH.'thread/'.$param['auth'].'.auth', SYS_TIME);

        $url = ROOT_URL.'index.php?s=api&c=run&m=cron&'.http_build_query($param);

        // 执行任务
        if (function_exists('fsockopen') || function_exists('pfsockopen')) {
            // 异步非堵塞执行
            return $this->_fsockopen($url, $block);
        } else {
            return file_get_contents($url);
        }
    }

    /**
     * fsockopen
     */
    private function _fsockopen($url, $block = 0) {

        $uri = parse_url($url);
        $timeout = 10;

        isset($uri['host']) ||$uri['host'] = '';
        isset($uri['path']) || $uri['path'] = '';
        isset($uri['query']) || $uri['query'] = '';
        isset($uri['port']) || $uri['port'] = '';

        $host = $uri['host'];
        $path = $uri['path'] ? $uri['path'] . ($uri['query'] ? '?' . $uri['query'] : '') : '/';
        $port = !empty($uri['port']) ? $uri['port'] : 80;

        $out = "GET $path HTTP/1.0\r\n";
        $out .= "Accept: */*\r\n";
        $out .= "Accept-Language: zh-cn\r\n";
        $out .= "User-Agent: IP-".\Phpcmf\Service::L('input')->ip_address()."\r\n";
        $out .= "Host: $host\r\n";
        $out .= "Connection: Close\r\n";
        $out .= "Cookie: \r\n\r\n";

        if (substr($url, 0, 8) == "https://") {
            $host = 'ssl://'.$host;
            $port = 443;
        }

        if (function_exists('fsockopen')) {
            $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        } elseif (function_exists('pfsockopen')) {
            $fp = @pfsockopen($host, $port, $errno, $errstr, $timeout);
        } else {
            dr_catcher_data($url, 10);
            return 1;
        }
		
        if (!$fp) {
            dr_catcher_data($url, 10);
            CI_DEBUG && log_message('error', 'fsockopen函数调用失败（'.$url.'）：'.FC_NOW_URL);
            return 0; //note $errstr : $errno \r\n
        } else {
            $limit = 500000;
            $return = '';
            //集阻塞/非阻塞模式流,$block==true则应用流模式
            stream_set_blocking($fp, $block);
            //设置流的超时时间
            stream_set_timeout($fp, $timeout);
            @fwrite($fp, $out);
            //从封装协议文件指针中取得报头／元数据
            $status = stream_get_meta_data($fp);
            //timed_out如果在上次调用 fread() 或者 fgets() 中等待数据时流超时了则为 TRUE,下面判断为流没有超时的情况
            if (!$status['timed_out']) {
                while (!feof($fp)) {
                    if (($header = @fgets($fp)) && ($header == "\r\n" || $header == "\n")) {
                        break;
                    }
                }
                $stop = false;
                //如果没有读到文件尾
                while (!feof($fp) && !$stop) {
                    //看连接时限是否=0或者大于8192  =》8192  else =》limit  所读字节数
                    $data = fread($fp, ($limit == 0 || $limit > 8192 ? 8192 : $limit));
                    $return .= $data;
                    if ($limit) {
                        $limit -= strlen($data);
                        $stop = $limit <= 0;
                    }
                }
            }
            @fclose($fp);
            return 1;
        }
    }

}