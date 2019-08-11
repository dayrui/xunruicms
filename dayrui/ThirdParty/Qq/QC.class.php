<?php
/* PHP SDK
 * @version 2.0.0
 * @author connect@qq.com
 * @copyright © 2013, Tencent Corporation. All rights reserved.
 */
require_once("Oauth.class.php");

/*
 * @brief QC类，api外部对象，调用接口全部依赖于此对象
 * */
class QC extends Oauth{
    private $kesArr, $APIMap;

    /**
     * _construct
     *
     * 构造方法
     * @access public 
     * @since 5
     * @param string $access_token  access_token value
     * @param string $openid        openid value
     * @return Object QC
     */
    public function __construct($access_token = "", $openid = ""){
        parent::__construct();

        $this->keysArr = array(
            "oauth_consumer_key" => '',
            "access_token" => $access_token,
            "openid" => $openid
        );

        //初始化APIMap
        /*
         * 加#表示非必须，无则不传入url(url中不会出现该参数)， "key" => "val" 表示key如果没有定义则使用默认值val
         * 规则 array( baseUrl, argListArr, method)
         */
        $this->APIMap = array(

            "get_user_info" => array(
                "https://graph.qq.com/user/get_user_info",
                array("format" => "json"),
                "GET"
            ),

        );
    }

    public function init($appid, $access_token, $openid) {

        $this->keysArr = array(
            "oauth_consumer_key" => $appid,
            "access_token" => $access_token,
            "openid" => $openid
        );

        return $this;
    }

    //调用相应api
    private function _applyAPI($arr, $argsList, $baseUrl, $method){
        $pre = "#";
        $keysArr = $this->keysArr;
        $optionArgList = array();//一些多项选填参数必选一的情形
        foreach($argsList as $key => $val){
            $tmpKey = $key;
            $tmpVal = $val;

            if(!is_string($key)){
                $tmpKey = $val;

                if(strpos($val,$pre) === 0){
                    $tmpVal = $pre;
                    $tmpKey = substr($tmpKey,1);
                    if(preg_match("/-(\d$)/", $tmpKey, $res)){
                        $tmpKey = str_replace($res[0], "", $tmpKey);
                        $optionArgList[$res[1]][] = $tmpKey;
                    }
                }else{
                    $tmpVal = null;
                }
            }

            //-----如果没有设置相应的参数
            if(!isset($arr[$tmpKey]) || $arr[$tmpKey] === ""){

                if($tmpVal == $pre){//则使用默认的值
                    continue;
                }else if($tmpVal){
                    $arr[$tmpKey] = $tmpVal;
                }else{
                    if($v = $_FILES[$tmpKey]){

                        $filename = dirname($v['tmp_name'])."/".$v['name'];
                        move_uploaded_file($v['tmp_name'], $filename);
                        $arr[$tmpKey] = "@$filename";

                    }else{
                        return "api调用参数错误: 未传入参数$tmpKey";
                    }
                }
            }

            $keysArr[$tmpKey] = $arr[$tmpKey];
        }
        //检查选填参数必填一的情形
        foreach($optionArgList as $val){
            $n = 0;
            foreach($val as $v){
                if(in_array($v, array_keys($keysArr))){
                    $n ++;
                }
            }

            if(! $n){
                $str = implode(",",$val);
                return "api调用参数错误: ".$str."必填一个";
            }
        }

        if($method == "POST"){
           $response = $this->urlUtils->post($baseUrl, $keysArr, 0);
        }else if($method == "GET"){
            $response = $this->urlUtils->get($baseUrl, $keysArr);
        }

        return $response;

    }

    /**
     * _call
     * 魔术方法，做api调用转发
     * @param string $name    调用的方法名称
     * @param array $arg      参数列表数组
     * @since 5.0
     * @return array          返加调用结果数组
     */
    public function __call($name, $arg){

        //如果APIMap不存在相应的api
        if(empty($this->APIMap[$name])){
            return "不存在的API: <span style='color:red;'>$name</span>";
        }

        //从APIMap获取api相应参数
        $baseUrl = $this->APIMap[$name][0];
        $argsList = $this->APIMap[$name][1];
        $method = isset($this->APIMap[$name][2]) ? $this->APIMap[$name][2] : "GET";

        if(empty($arg)){
            $arg[0] = null;
        }

        $responseArr = json_decode($this->_applyAPI($arg[0], $argsList, $baseUrl, $method), true);

        //检查返回ret判断api是否成功调用
        if($responseArr['ret'] == 0){
            return $responseArr;
        }else{
            return $response['msg'];
        }

    }

    //php 对象到数组转换
    private function objToArr($obj){
        if(!is_object($obj) && !is_array($obj)) {
            return $obj;
        }
        $arr = array();
        foreach($obj as $k => $v){
            $arr[$k] = $this->objToArr($v);
        }
        return $arr;
    }

   
    /**
     * get_access_token
     * 获得access_token
     * @param void
     * @since 5.0
     * @return string 返加access_token
     */
    public function get_access_token(){

    }

    //简单实现json到php数组转换功能
    private function simple_json_parser($json){
        $json = str_replace("{","",str_replace("}","", $json));
        $jsonValue = explode(",", $json);
        $arr = array();
        foreach($jsonValue as $v){
            $jValue = explode(":", $v);
            $arr[str_replace('"',"", $jValue[0])] = (str_replace('"', "", $jValue[1]));
        }
        return $arr;
    }
}
