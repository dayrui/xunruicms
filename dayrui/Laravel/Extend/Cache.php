<?php namespace Frame;

use Illuminate\Support\Facades\Cache as Fcache;

class Cache {

    public function save($key, $value, $time = 3600) {
        return Fcache::put(SYS_KEY.$key, $value, $time);
    }

    public function get($key)
    {
        return Fcache::get(SYS_KEY.$key);
    }

    public function delete($key)
    {
        Fcache::forget(SYS_KEY.$key);
    }

    public function clean()
    {
        Fcache::flush();
    }

    public function test($name) {

        $db = Fcache::store($name);
        $rt = $db->put('test', 'phpcmf', 60);
        if (!$rt) {
            return dr_return_data(1, dr_lang('缓存方式[%s]存储失败', $name));
        } elseif ($db->get('test') == 'phpcmf') {
            return dr_return_data(1, dr_lang('缓存方式[%s]已生效', $name));
        } else {
            return dr_return_data(0, dr_lang('缓存方式[%s]未生效', $name));
        }
    }
}