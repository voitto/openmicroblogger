<?php

/**
 * \brief Backend class using memcached.
 *
 * This backend class uses memcached, a simple caching daemon.
 *
 * List of options:
 *  - server: Server information array (see below) for a single server.
 *  - servers: An array of server information arrays. If the option 'server' is
 *  given, this option will be ignored.
 *  - compression: Boolean value to control compression of values. Requires the
 *  zlib extension (default: false).
 *
 * Server information array:
 *  - host: IP address or hostname of the server (required).
 *  - port: Port of the server (default: 11211).
 *  - weight: Probability of this server being selected relative to total
 *  weight of all servers (default: 1).
 *  - timeout: Timeout for the server connection in seconds (default: 1).
 *  - retry_intervall: Time in seconds before trying to reconnect to a server
 *  that previously had a timeout (default: 15).
 */
class MemcacheBackend extends BackendSkeleton
{

    private $memcache;
    private $flags = 0;

    public function __construct($options)
    {
        $this->memcache = new Memcache();
        if(!isset($options["servers"]))
            $options["servers"] = array($options["server"]);
        foreach($options["servers"] as $server) {
            $this->memcache->addServer(
                $server["host"],
                isset($server["port"]) ? $server["port"] : 11211 ,
                isset($server["persistent"]) ? $server["persistent"] : true,
                isset($server["weight"]) ? $server["weight"] : 1,
                isset($server["timeout"]) ? $server["timeout"] : 1,
                isset($server["retry_interval"]) ? $server["retry_interval"] : 15
            );
            if(isset($options["compression"]) && $options['compression'])
                $this->flags += MEMCACHE_COMPRESSED;
        }
    }

    public function add($key, $value, $ttl = 0)
    {
        if($ttl > 2592000) // When it is longer than 30 days, memcached thinks it is a unix timestamp
            $ttl += time();
        return $this->memcache->add($key, $value, $this->flags, $ttl);
    }

    public function set($key, $value, $ttl = 0)
    {
        if($ttl > 2592000)
            $ttl += time();
        return $this->memcache->set($key, $value, $this->flags, $ttl);
    }

    public function get($key)
    {
        $data = $this->memcache->get($key, $this->flags);
        return ($data === false) ? null : $data;
    }

    public function delete($key)
    {
        return $this->memcache->delete($key);
    }

    public function flush()
    {
        return $this->memcache->flush();
    }

}
