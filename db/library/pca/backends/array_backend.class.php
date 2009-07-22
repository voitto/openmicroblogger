<?php

/**
 * \brief Backend class for testing using arrays for data storage.
 *
 * This backend class stores all data in a single array, which will be lost at
 * the end of the PHP script, so it is not recommended to use this backend in
 * production.
 */
class ArrayBackend extends BackendSkeleton
{

    /**
     * The array in which all data will be stored.
     */
    private $data;

    public function __construct($options)
    {
        $data = array();
    }

    public function exists($key)
    {
        if(!array_key_exists($key, $this->data))
            return false;
        return is_null($this->data[$key][0]) || ($this->data[$key][0] >= time());
    }

    public function set($key, $value, $ttl = 0)
    {
        $this->data[$key] = array($ttl = 0 ? null : (time() + $ttl), $value);
        return true;
    }

    public function get($key)
    {
        if($this->exists($key))
            return $this->data[$key][1];
    }

    public function delete($key)
    {
        if(!$this->exists($key))
            return false;
        unset($this->data[$key]);
        return true;
    }

    public function flush()
    {
        $this->data = array();
        return true;
    }

}
