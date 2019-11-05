<?php namespace Phpcmf\ThirdParty;

/**
 * 百度API请求接口
 */

class BaiduApi {

    // 从url获取json数据
    static function _https_json_data($url, $param = [], $is_gbk = 0) {

        if (!$url) {
            return dr_return_data(0, 'url为空');
        }

        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
        curl_setopt ( $ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)' );
        curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, 1 );
        curl_setopt ( $ch, CURLOPT_AUTOREFERER, 1 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, is_array($param) ? http_build_query($param) : $param);
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
        $response = curl_exec ( $ch );
        if ($error=curl_error($ch)){
            return dr_return_data(0, $error);
        }
        curl_close($ch);
        if ($is_gbk && function_exists('mb_convert_encoding')) {
            $response = mb_convert_encoding($response, 'UTF8', 'GBK');
        }
        $data = json_decode($response, true);
        if (!$data) {
            return dr_return_data(0, $response);
        }

        return dr_return_data(1, 'ok', $data);
    }

    static function _access_token() {

        $cache_data = \Phpcmf\Service::L('cache')->init('file')->get('baidu_api_access_token');
        if ($cache_data['endtime'] && $cache_data['endtime'] > SYS_TIME && $cache_data['access_token']) {
            return dr_return_data(1, $cache_data['access_token']);
        }

        $url = 'https://aip.baidubce.com/oauth/2.0/token?grant_type=client_credentials&client_id='.SYS_BDNLP_AK.'&client_secret='.SYS_BDNLP_SK.'&';

        $res = self::_https_json_data($url, '');
        if (!$res['code']) {
            return dr_return_data(0, 'BaiduApiToken：'.$res['msg']);
        } elseif ($res['data']['error']) {
            return dr_return_data(0, 'BaiduApiToken：'.$res['data']['error'].'-'.$res['data']['error_description']);
        }

        $res['data']['endtime'] = SYS_TIME + 100000;
        $rt = \Phpcmf\Service::L('cache')->init('file')->save('baidu_api_access_token', $res['data'], 1000000);
        if (!$rt) {
            return dr_return_data(0, 'BaiduApi AccessToken：/cache/temp/目录不可写，文件写入失败');
        }

        return dr_return_data(1, $res['data']['access_token']);
    }

    static function get_data($url, $data, $is_gbk = 0) {

        $rt = self::_access_token();
        if (!$rt['code']) {
            return $rt;
        }

        $url = $url.'?access_token='.$rt['msg'];
        $res = self::_https_json_data($url, $data, $is_gbk);
        if (!$res['code']) {
            return dr_return_data(0, 'BaiduApiData：'.$res['msg']);
        } elseif ($res['data']['error_code']) {
            return dr_return_data(0, 'BaiduApiData：'.$res['data']['error_code'].'-'.$res['data']['error_msg']);
        }

        return dr_return_data(1, '', $res['data']);

    }

}