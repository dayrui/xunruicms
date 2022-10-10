<?php namespace Phpcmf\Library;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/

/**
 * Ip 地址解析
 */

class Ip {

    private $file = WRITEPATH.'qqwry.dat';
    private $address;

    /**
     * ip查询
     */
    public function set($ip) {

        if (!$ip) {
            return '';
        }

        if (strpos($ip, '-') !== false) {
            list($ip) = explode('-', $ip); // 排除源端口号
        }

        $ip = dr_safe_replace($ip);
        $this->address = '';

        if (preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/", $ip)) {
            $iparray = explode('.', $ip);
            if ($iparray[0] == 10 || $iparray[0] == 127 || ($iparray[0] == 192 && $iparray[1] == 168) || ($iparray[0] == 172 && ($iparray[1] >= 16 && $iparray[1] <= 31))) {
                $this->address = '';
            } elseif ($iparray[0] > 255 || $iparray[1] > 255 || $iparray[2] > 255 || $iparray[3] > 255) {
                $this->address = '';
            } else {
                $this->address = $this->_full($ip);
            }
        }

        !$this->address && $this->address = '未知地区';

        return $this;
    }

    /**
     * IP地址解析详细地址
     */
    public function address($ip) {

        if (!$ip) {
            return '';
        } elseif ($ip == '127.0.0.1') {
            return '本地';
        }

        $rs = \Phpcmf\Hooks::trigger_callback('ip_address', $ip);
        if ($rs && isset($rs['code']) && $rs['code']) {
            return $rs['msg'];
        }

        $this->set($ip);

        return $this->address;
    }

    /**
     * IP地址解析城市
     */
    public function city($ip) {

        if (!$ip) {
            return '';
        } elseif ($ip == '127.0.0.1') {
            return '本地';
        }

        $rs = \Phpcmf\Hooks::trigger_callback('ip_city', $ip);
        if ($rs && isset($rs['code']) && $rs['code']) {
            return $rs['msg'];
        }

        $this->set($ip);
        if (preg_match('/省(.+)市/U', $this->address, $m)) {
            return $m[1];
        } elseif (preg_match('/(.+)市/U', $this->address, $m)) {
            return str_replace([
                '西藏',
                '内蒙古',
                '青海',
                '宁夏',
                '新疆'
            ], '', $m[1]);
        } else {
            list($name) = explode(' ', $this->address);
            return $name;
        }
    }

    /**
     * IP地址解析省
     */
    public function province($ip) {

        if (!$ip) {
            return '';
        } elseif ($ip == '127.0.0.1') {
            return '本地';
        }

        $rs = \Phpcmf\Hooks::trigger_callback('ip_province', $ip);
        if ($rs && isset($rs['code']) && $rs['code']) {
            return $rs['msg'];
        }

        $this->set($ip);
        if (preg_match('/(.+)省/U', $this->address, $m)) {
            return $m[1];
        } else {
            foreach ([
                         '西藏',
                         '内蒙古',
                         '青海',
                         '宁夏',
                         '新疆'
                     ] as $t) {
                if (strpos($this->address, $t) !== false) {
                    return $t;
                }
            }
            list($name) = explode(' ', $this->address);
            return $name;
        }
    }

    private function _full($ip) {

        if(!$fd = @fopen($this->file, 'rb')) {
            return 'IP库不存在';
        }

        $ip = explode('.', (string)$ip);
        $ipNum = $ip[0] * 16777216 + $ip[1] * 65536 + $ip[2] * 256 + $ip[3];

        if(!($DataBegin = fread($fd, 4)) || !($DataEnd = fread($fd, 4)) ) return;
        @$ipbegin = implode('', unpack('L', $DataBegin));
        if($ipbegin < 0) $ipbegin += pow(2, 32);
        @$ipend = implode('', unpack('L', $DataEnd));
        if($ipend < 0) $ipend += pow(2, 32);
        $ipAllNum = ($ipend - $ipbegin) / 7 + 1;

        $BeginNum = $ip2num = $ip1num = 0;
        $ipAddr1 = $ipAddr2 = '';
        $EndNum = $ipAllNum;

        while($ip1num > $ipNum || $ip2num < $ipNum) {
            $Middle= intval(($EndNum + $BeginNum) / 2);

            fseek($fd, $ipbegin + 7 * $Middle);
            $ipData1 = fread($fd, 4);
            if(strlen($ipData1) < 4) {
                fclose($fd);
                return '';
            }
            $ip1num = implode('', unpack('L', $ipData1));
            if($ip1num < 0) $ip1num += pow(2, 32);

            if($ip1num > $ipNum) {
                $EndNum = $Middle;
                continue;
            }

            $DataSeek = fread($fd, 3);
            if(strlen($DataSeek) < 3) {
                fclose($fd);
                return '';
            }
            $DataSeek = implode('', unpack('L', $DataSeek.chr(0)));
            fseek($fd, $DataSeek);
            $ipData2 = fread($fd, 4);
            if(strlen($ipData2) < 4) {
                fclose($fd);
                return '';
            }
            $ip2num = implode('', unpack('L', $ipData2));
            if($ip2num < 0) $ip2num += pow(2, 32);

            if($ip2num < $ipNum) {
                if($Middle == $BeginNum) {
                    fclose($fd);
                    return '';
                }
                $BeginNum = $Middle;
            }
        }

        $ipFlag = fread($fd, 1);
        if($ipFlag == chr(1)) {
            $ipSeek = fread($fd, 3);
            if(strlen($ipSeek) < 3) {
                fclose($fd);
                return '';
            }
            $ipSeek = implode('', unpack('L', $ipSeek.chr(0)));
            fseek($fd, $ipSeek);
            $ipFlag = fread($fd, 1);
        }

        if($ipFlag == chr(2)) {
            $AddrSeek = fread($fd, 3);
            if(strlen($AddrSeek) < 3) {
                fclose($fd);
                return '';
            }
            $ipFlag = fread($fd, 1);
            if($ipFlag == chr(2)) {
                $AddrSeek2 = fread($fd, 3);
                if(strlen($AddrSeek2) < 3) {
                    fclose($fd);
                    return '';
                }
                $AddrSeek2 = implode('', unpack('L', $AddrSeek2.chr(0)));
                fseek($fd, $AddrSeek2);
            } else {
                fseek($fd, -1, SEEK_CUR);
            }

            while(($char = fread($fd, 1)) != chr(0))
                $ipAddr2 .= $char;

            $AddrSeek = implode('', unpack('L', $AddrSeek.chr(0)));
            fseek($fd, $AddrSeek);

            while(($char = fread($fd, 1)) != chr(0))
                $ipAddr1 .= $char;
        } else {
            fseek($fd, -1, SEEK_CUR);
            while(($char = fread($fd, 1)) != chr(0))
                $ipAddr1 .= $char;

            $ipFlag = fread($fd, 1);
            if($ipFlag == chr(2)) {
                $AddrSeek2 = fread($fd, 3);
                if(strlen($AddrSeek2) < 3) {
                    fclose($fd);
                    return '';
                }
                $AddrSeek2 = implode('', unpack('L', $AddrSeek2.chr(0)));
                fseek($fd, $AddrSeek2);
            } else {
                fseek($fd, -1, SEEK_CUR);
            }
            while(($char = fread($fd, 1)) != chr(0))
                $ipAddr2 .= $char;
        }
        fclose($fd);

        if(preg_match('/http/i', $ipAddr2)) {
            $ipAddr2 = '';
        }
        $ipaddr = "$ipAddr1 $ipAddr2";
        $ipaddr = preg_replace('/CZ88\.NET/is', '', $ipaddr);
        $ipaddr = preg_replace('/^\s*/is', '', $ipaddr);
        $ipaddr = preg_replace('/\s*$/is', '', $ipaddr);
        if(preg_match('/http/i', $ipaddr) || $ipaddr == '') {
            $ipaddr = '';
        }

        $name = dr_code2utf8($ipaddr);
        $arr = explode(' ', $name);

        return $arr[0] ? $arr[0] : $name;
    }

}