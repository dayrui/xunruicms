<?php namespace Phpcmf\Library;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

/**
 * SMTP邮件发送类
 */

class Email {

    public $error;
    protected $config;

    /**
     * 样式配置文件
     */
    public function set($config) {
        $this->config = [
            'port' => $config['port'],
            'auth' => 1,
            'from' => $config['from'],
            'server' => $config['host'],
            'mailsend' => 2,
            'mailusername' => 1,
            'maildelimiter'	=> 1,
            'auth_username'	=> $config['user'],
            'auth_password'	=> $config['pass'],
        ];
        return $this;
    }

    // mail 发送
    public function mail($toemail, $subject, $message, $fname = '') {

        $subject = "=?UTF-8?B?".base64_encode($subject)."?=";
        $from_user = "=?UTF-8?B?".base64_encode($fname ? $fname : SITE_NAME)."?=";
        $headers = "From: ".$from_user." <".$this->config['from'].">\r\n".
            "MIME-Version: 1.0" . "\r\n" .
            "Content-type: text/html; charset=UTF-8" . "\r\n";

        return mail($toemail, $subject, $message, $headers);
    }

    // smtp发送
    public function send($toemail, $subject, $message, $fname = '') {

        $mail = $this->config;
        if (!$mail['server']) {
            return FALSE;
        }

        if ($mail['server'] == 'mail') {
            return $this->mail($toemail, $subject, $message, $fname);
        }

        $cfg = [];
        $cfg['charset'] = $charset = 'utf-8';
        $cfg['server'] = $mail['server'];
        $cfg['port'] = $mail['port'];
        $cfg['from'] = $mail['from'];
        $cfg['auth_username'] = $mail['auth_username'];
        $cfg['auth_password'] = $mail['auth_password'];
        unset($mail);

        $is_starttls = 0;
        if (strpos($cfg['server'], 'starttls://') === 0) {
            $is_starttls = 1;
            $cfg['server'] = str_replace('starttls://', '', $cfg['server']);
        }

        $mailusername = 1;
        $maildelimiter = "\r\n"; //换行符
        $cfg['port'] = $cfg['port'] ? $cfg['port'] : 25;

        $email_from = '=?'.$cfg['charset'].'?B?'.base64_encode($fname ? $fname : SITE_NAME)."?= <".$cfg['from'].">";
        $email_to = preg_match('/^(.+?) \<(.+?)\>$/',$toemail, $mats) ? ($mailusername ? '=?'.$cfg['charset'].'?B?'.base64_encode($mats[1])."?= <$mats[2]>" : $mats[2]) : $toemail;
        $email_subject = '=?'.$cfg['charset'].'?B?'.base64_encode(preg_replace("/[\r|\n]/", '', $subject)).'?=';
        $email_message = chunk_split(base64_encode(str_replace("\n", "\r\n", str_replace("\r", "\n", str_replace("\r\n", "\n", str_replace("\n\r", "\r", $message))))));

        $host = $_SERVER['HTTP_HOST'];
        $headers = "From: $email_from{$maildelimiter}X-Priority: 3{$maildelimiter}X-Mailer: $host {$maildelimiter}MIME-Version: 1.0{$maildelimiter}Content-type: text/html; charset=".$cfg['charset']."{$maildelimiter}Content-Transfer-Encoding: base64{$maildelimiter}";

        if(!$fp = fsockopen($cfg['server'], $cfg['port'], $errno, $errstr, 30)) {
            $this->runlog($cfg['server'].' - '.$cfg['auth_username'].' - '.$toemail, "fsockopen 无法连接到邮件服务器 [".$errno."-".$errstr."]");
            return FALSE;
        }

        stream_set_blocking($fp, true);
        $lastmessage = fgets($fp, 512);
        if(substr($lastmessage, 0, 3) != '220') {
            $this->runlog($cfg['server'].' - '.$cfg['auth_username'].' - '.$toemail, "连接失败", $lastmessage);
            return FALSE;
        }

        fputs($fp, "EHLO phpcmf\r\n");
        $lastmessage = fgets($fp, 512);
        if(substr($lastmessage, 0, 3) != 220 && substr($lastmessage, 0, 3) != 250) {
            $this->runlog($cfg['server'].' - '.$cfg['auth_username'].' - '.$toemail, "EHLO", $lastmessage);
            return FALSE;
        }

        // 是否starttls
        if ($is_starttls) {
            fputs($fp, "STARTTLS phpcmf\r\n");
            $lastmessage = fgets($fp, 512);
            if(substr($lastmessage, 0, 3) != 220 && substr($lastmessage, 0, 3) != 250) {
                $this->runlog($cfg['server'].' - '.$cfg['auth_username'].' - '.$toemail, "STARTTLS", $lastmessage);
                return FALSE;
            }
        }

        while(1) {
            if(substr($lastmessage, 3, 1) != '-' || empty($lastmessage)) {
                break;
            }
            $lastmessage = fgets($fp, 512);
        }

        //$crypto = stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);


        // 登录账号认证
        fputs($fp, "AUTH LOGIN\r\n");
        $lastmessage = fgets($fp, 512);
        if(substr($lastmessage, 0, 3) != 334) {
            $this->runlog($cfg['server'].' - '.$cfg['auth_username'].' - '.$toemail, "认证登录失败", $lastmessage);
            return FALSE;
        }
        fputs($fp, base64_encode($cfg['auth_username']) . "\r\n");
        $lastmessage = fgets($fp, 512);
        if(substr($lastmessage, 0, 3) != 334) {
            $this->runlog($cfg['server'].' - '.$cfg['auth_username'].' - '.$toemail, "账号验证失败", $lastmessage);
            return FALSE;
        }
        fputs($fp, base64_encode($cfg['auth_password']) . "\r\n");
        $lastmessage = fgets($fp, 512);
        if(substr($lastmessage, 0, 3) != 235) {
            $this->runlog($cfg['server'].' - '.$cfg['auth_username'].' - '.$toemail, "密码验证失败", $lastmessage);
            return FALSE;
        }

        $email_from = $cfg['from'];

        fputs($fp, "MAIL FROM: <".preg_replace("/.*\<(.+?)\>.*/", "\\1", $email_from).">\r\n");
        $lastmessage = fgets($fp, 512);
        if(substr($lastmessage, 0, 3) != 250) {
            fputs($fp, "MAIL FROM: <".preg_replace("/.*\<(.+?)\>.*/", "\\1", $email_from).">\r\n");
            $lastmessage = fgets($fp, 512);
            if(substr($lastmessage, 0, 3) != 250) {
                $this->runlog($cfg['server'].' - '.$cfg['auth_username'].' - '.$toemail, "MAIL FROM", $lastmessage);
                return FALSE;
            }
        }

        fputs($fp, "RCPT TO: <".preg_replace("/.*\<(.+?)\>.*/", "\\1", $toemail).">\r\n");
        $lastmessage = fgets($fp, 512);
        if(substr($lastmessage, 0, 3) != 250) {
            fputs($fp, "RCPT TO: <".preg_replace("/.*\<(.+?)\>.*/", "\\1", $toemail).">\r\n");
            $lastmessage = fgets($fp, 512);
            $this->runlog($cfg['server'].' - '.$cfg['auth_username'].' - '.$toemail, "RCPT TO", $lastmessage);
            return FALSE;
        }
        fputs($fp, "DATA\r\n");
        $lastmessage = fgets($fp, 512);
        if(substr($lastmessage, 0, 3) != 354) {
            $this->runlog($cfg['server'].' - '.$cfg['auth_username'].' - '.$toemail, "DATA", $lastmessage);
            return FALSE;
        }
        $headers .= 'Message-ID: <'.gmdate('YmdHs').'.'.substr(md5($email_message.microtime()), 0, 6).rand(100000, 999999).'@'.$_SERVER['HTTP_HOST'].">{$maildelimiter}";

        fputs($fp, "Date: ".gmdate('r')."\r\n");
        fputs($fp, "To: ".$email_to."\r\n");
        fputs($fp, "Subject: ".$email_subject."\r\n");
        fputs($fp, $headers."\r\n");
        fputs($fp, "\r\n\r\n");
        fputs($fp, "$email_message\r\n.\r\n");
        $lastmessage = fgets($fp, 512);
        if(substr($lastmessage, 0, 3) != 250) {
            $this->runlog($cfg['server'].' - '.$cfg['auth_username'].' - '.$toemail, "END", $lastmessage);
            return FALSE;
        }
        fputs($fp, "QUIT\r\n");
        return TRUE;
    }

    public function error() {
        return $this->error;
    }

    protected function runlog($server, $name, $msg = '') {

        if ($msg && $this->is_gb2312($msg) && function_exists('iconv')) {
            $new = iconv('GB2312', 'UTF-8', $msg);
            if ($new) {
                $msg = $new;
            }
        }

        $this->error = $name.'-'.$msg;
        
        @file_put_contents(WRITEPATH.'email_log.txt', date('Y-m-d H:i:s').' ['.$server.'] '.str_replace([PHP_EOL, chr(13), chr(10)], '', $msg).PHP_EOL, FILE_APPEND);
    }

    protected function is_gb2312($str) {
        return function_exists('mb_detect_encoding') && mb_detect_encoding($str,"UTF-8, ISO-8859-1, GBK")!="UTF-8";
	}

}