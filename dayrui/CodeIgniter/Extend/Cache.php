<?php namespace Frame;

class Cache {

    private $cache;

    public function __construct() {
        $this->cache = cache();
    }

    public function save($key, $value, $time = 3600) {
        return $this->cache->save(SYS_KEY.$key, $value, $time);
    }

    public function get($key)
    {
        return $this->cache->get(SYS_KEY.$key);
    }

    public function delete($key)
    {
        $this->cache->delete(SYS_KEY.$key);
    }

    public function clean()
    {
        $this->cache->clean();
    }

    public function test($name) {

        $config = new \Config\Cache();
        $config->handler = $name;
        $adapter = new $config->validHandlers[$config->handler]($config);
        if (!$adapter->isSupported()) {
            return dr_return_data(0, dr_lang('PHP环境没有安装[%s]扩展', $config->handler));
        }

        $adapter->initialize();
        $rt = $adapter->save('test', 'phpcmf', 60);
        if (!$rt) {
            return dr_return_data(1, dr_lang('缓存方式[%s]存储失败', $config->handler));
        } elseif ($adapter->get('test') == 'phpcmf') {
            return dr_return_data(1, dr_lang('缓存方式[%s]已生效', $config->handler));
        } else {
            return dr_return_data(0, dr_lang('缓存方式[%s]未生效', $config->handler));
        }
    }
}