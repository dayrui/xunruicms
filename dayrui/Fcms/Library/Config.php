<?php namespace Phpcmf\Library;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/


// 配置文件生成
class Config {

    private $file;
    private $space = 32;
    private $header;

    /**
     * 配置文件
     *
     * @param	string	$file	文件绝对地址
     * @param	string	$name	文件备注名称
     * @return	object
     */
    public function file($file, $name = '', $space = 32) {
        $this->file = $file;
        $this->space = $space;
        $this->header = '<?php'.PHP_EOL.PHP_EOL.
            'if (!defined(\'BASEPATH\')) exit(\'No direct script access allowed\');'.PHP_EOL.PHP_EOL.
            '/**'.PHP_EOL.
            ' * '.$name.PHP_EOL.
            ' */'.PHP_EOL.PHP_EOL
        ;
        return $this;
    }

    /**
     * 生成require一维数组文件
     *
     * @param	array	$var	变量标识	array('变量名称' => '备注信息'), ...
     * @param	array	$data	对应值数组	array('变量名称' => '变量值'), ... 为空时直接生成$var
     * @return	int
     */
    public function to_require_one($var, $data = []) {

        $body = $this->header.'return ['.PHP_EOL.PHP_EOL;
        if ($data) {
            foreach ($var as $name => $note) {
                if (is_array($data[$name])) {
                    continue;
                }
                $name = $this->_safe_replace($name);
                $body.= '	\''.$name.'\''.$this->_space($name).'=> '.$this->_format_value($data[$name]).', //'.$note.PHP_EOL;
            }
        } elseif ($var) {
            foreach ($var as $name => $val) {
                if (is_array($val)) {
                    continue;
                }
                $name = $this->_safe_replace($name);
                $body.= '	\''.$name.'\''.$this->_space($name).'=> '.$this->_format_value($val).','.PHP_EOL;
            }
        }
        $body.= PHP_EOL.'];';
        !is_dir(dirname($this->file)) && dr_mkdirs(dirname($this->file));

        // 重置Zend OPcache
        function_exists('opcache_reset') && opcache_reset();
        
        return @file_put_contents($this->file, $body, LOCK_EX);
    }


    /**
     * 生成require N维数组文件
     *
     * @param	array	data
     * @return	int
     */
    public function to_require($data) {

        if (!is_array($data)) {
            $data = [];
        }

        $body = $this->header.'return ';
        $body .= str_replace(array('  ', ' 
    '), array('    ', ' '), var_export($data, TRUE));
        $body .= ';';
        !is_dir(dirname($this->file)) && dr_mkdirs(dirname($this->file));

        // 重置Zend OPcache
        function_exists('opcache_reset') && opcache_reset();

        return @file_put_contents($this->file, $body, LOCK_EX);
    }

    /**
     * 生成require N维数组文件
     *
     * @param	array	data
     * @return	int
     */
    public function to_require_array($value) {

        if (!$value) {
            return NULL;
        }

        $body = $this->header.'return ['.PHP_EOL.PHP_EOL;
        foreach ($value as $id => $data) {

            $body.= '       '.$id .' => ['.PHP_EOL.PHP_EOL;
            foreach ($data as $name => $val) {
                if (is_array($data[$name])) {
                    continue;
                }
                $name = $this->_safe_replace($name);
                $body.= '       	   \''.$name.'\''.$this->_space($name).'=> '.$this->_format_value($data[$name]).','.PHP_EOL;
            }
            $body.= PHP_EOL.'       ],'.PHP_EOL.PHP_EOL;
        }
        $body.= PHP_EOL.'];';
        
        !is_dir(dirname($this->file)) && dr_mkdirs(dirname($this->file));

        // 重置Zend OPcache
        function_exists('opcache_reset') && opcache_reset();

        return @file_put_contents($this->file, $body, LOCK_EX);
    }

    /**
     * 补空格
     */
    private function _space($name) {
        $len = dr_strlen($name) + 2;
        $cha = $this->space - $len;
        $str = '';
        for ($i = 0; $i < $cha; $i ++) $str .= ' ';
        return $str;
    }

    /**
     * 格式化值
     */
    private function _format_value($value) {
        return is_numeric($value) && strlen($value) <= 10 ? $value : '\''.str_replace(['\'', '\\'], '', (string)$value).'\'';
    }

    /**
     * 安全替换
     */
    private function _safe_replace($name) {
        return str_replace(
            ['..', '\\', '<', '>', "{", '}', ';', '[', ']', '\'', '"', '*', '?'],
            '',
            (string)$name
        );
    }
}