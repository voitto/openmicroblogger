<?php

/**
 * \brief Backend class using the Alternative PHP Cache.
 *
 * This backend class uses the Alternative PHP Cache's user cache to store
 * data. It requires that APC is both installed and enabled via php.ini.
 */
class APCBackend extends BackendSkeleton
{

    public function add($key, $value, $expire = 0)
    {
        return apc_add($key, $value, $expire);
    }

    public function set($key, $value, $expire = 0)
    {
        return apc_store($key, $value, $expire);
    }

    public function get($key)
    {
        $data = apc_fetch($key);
        return ($data === false) ? null : $data;
    }

    public function delete($key)
    {
        return apc_delete($key);
    }

    public function flush()
    {
        return apc_clear_cache('user');
    }

}
