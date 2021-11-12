<?php namespace Phpcmf\Model;
/**
 * {{www.xunruicms.com}}
 * {{迅睿内容管理框架系统}}
 * 本文件是框架系统文件，二次开发时不可以修改本文件，可以通过继承类方法来重写此文件
 **/


// 系统模型 - 后台
class System extends \Phpcmf\Model {

    public $config = [

        'SYS_DEBUG'	=> '调试器开关',
        'SYS_ADMIN_CODE' => '后台登录验证码开关',
        'SYS_ADMIN_LOG' => '后台操作日志开关',
        'SYS_AUTO_FORM' => '自动存储表单数据',
        'SYS_ADMIN_PAGESIZE' => '后台数据分页显示数量',
        'SYS_CRON_AUTH' => '自动任务权限IP地址',
        'SYS_SMS_IMG_CODE' => '发送短信验证码双重图形验证开关',
        'SYS_GO_404' => '404页面跳转开关',
        'SYS_301' => '内容地址唯一模式',

        'SYS_URL_PREG' => '地址匹配规则',

        'SYS_KEY' => '安全密匙',
        'SYS_CSRF'	=> '开启跨站验证',
        'SYS_HTTPS'	=> 'https模式',
        'SYS_ADMIN_MODE'	=> '禁用后台登录进行模式选择',
        'SYS_ADMIN_LOGINS'	=> '登录失败N次后，系统将锁定登录',
        'SYS_ADMIN_LOGIN_TIME'	=> '登录失败锁定后在x分钟内禁止登录',
        'SYS_ADMIN_OAUTH'    => '后台启用快捷登录',

        'SYS_ATTACHMENT_DB'	    => '附件归属开启模式',
        'SYS_ATTACHMENT_GUEST'	=> '游客是否附件上传',
        'SYS_ATTACHMENT_SAFE'	=> '附件上传安全模式',
        'SYS_ATTACHMENT_PATH'	=> '附件上传路径',
        'SYS_ATTACHMENT_SAVE_TYPE'	=> '附件存储方式',
        'SYS_ATTACHMENT_SAVE_DIR'	=> '附件存储目录',
        'SYS_ATTACHMENT_SAVE_ID'	=> '附件存储全局策略',
        'SYS_ATTACHMENT_URL'	=> '附件访问地址',
        'SYS_AVATAR_PATH'	=> '头像上传路径',
        'SYS_AVATAR_URL'	=> '头像访问地址',
        'SYS_BDMAP_API'	=> '百度地图API',
        'SYS_API_CODE'	=> 'API请求时验证码开关',
        'SYS_THEME_ROOT'	=> '风格目录引用作用域',

        'SYS_NOT_UPDATE'	=> '禁止自动检测版本',

    ];
    
    
    /**
	 * 保存配置文件
	 */
    public function save_config($system, $data) {

        foreach ($this->config as $name => $s) {
            if (isset($data[$name])) {
                $value = $data[$name];
                if ($name == 'SYS_ADMIN_PAGESIZE') {
                    $value = max(1, $value);
                }
                $system[$name] = $value;
            }
        }

        \Phpcmf\Service::L('config')->file(WRITEPATH.'config/system.php', '系统配置文件', 32)->to_require_one(
            $this->config,
            $system
        );
    }

    // 读取配置信息
    public function get_setting($name) {
        $data = $this->table('admin_setting')->where('name', $name)->getRow();
        return $data ? dr_string2array($data['value']) : [];
    }

    // 存储配置信息
    public function save_setting($name, $value) {
        $this->table('admin_setting')->replace([
            'name' => $name,
            'value' => dr_array2string($value),
        ]);
    }

    // 更新缓存
    public function cache() {

        $rt = [];
        $data = $this->table('admin_setting')->getAll();
        if ($data) {
            foreach ($data as $t) {
                $rt[$t['name']] = dr_string2array($t['value']);
            }
        }

        \Phpcmf\Service::L('cache')->set_file('admin_setting', $rt);
    }

}