<?php

/**
 * \brief The base class for all backends.
 *
 * This abstract class is the base for all other backend classes. It defines
 * all necessary functions. Some of these have to be overloaded, while others
 * are already implemented as a fallback, but should still be overloaded in a
 * child class if possible, to aviod performance penalties.
 */
abstract class BackendSkeleton
{

    /**
     * \brief The constructor should not be called directly, use PCA::factory
     * instead.
     *
     * @see PCA::factory()
     * @param $options an array of configuration options for the backend. Take
     * a look at the individual backend's documentation for more details.
     */
    public function __construct($options) { }

    /**
     * \brief Checks if a key exists.
     *
     * If not implemented by a backend, it uses get() as fallback and simply
     * checks if the value is null.
     *
     * @see get()
     * @param $key the key to search for.
     * @return Boolean value indicating whether the key exists or not.
     */
    public function exists($key)
    {
        return !is_null($this->get($key));
    }

    /**
     * \brief Inserts a value if the key does not already exist.
     *
     * If not implemented by a backend, it uses exists() to check if the key
     * already exists and only inserts the value if the key does not already
     * exist.
     *
     * @see exists()
     * @param $key the key that will be inserted.
     * @param $value the data that will be cached.
     * @param $ttl optional time to live in seconds. 0 means forever.
     * @return True if the value was successfully inserted, false when the key
     * already existed and was therefore not overridden or if there wase a
     * problem inserting the data (e.g. cache full, ...).
     */
    public function add($key, $value, $ttl = 0)
    {
        if($this->exists($key))
            return false;
        return $this->set($key, $value, $ttl);
    }

    /**
     * \brief Inserts a value.
     *
     * @param $key the key that will be inserted.
     * @param $value the data that will be cached.
     * @param $ttl optional time to live in seconds. 0 means forever.
     * @return True on success, false when there was a problem inserting the
     * data (e.g. cache full, ...).
     */
    abstract public function set($key, $value, $ttl = 0);

    /**
     * \brief Retrieves a value.
     *
     * @param $key the key of the value.
     * @return The value or null if there is no cached data for that key.
     */
    abstract public function get($key);

    /**
     * \brief Deletes a value.
     *
     * @param $key the key that will be removed.
     * @return True if the data was successfully removed, false when no data
     * with that key was stored.
     */
    abstract public function delete($key);

    /**
     * \brief Deletes all values.
     *
     * @return True on success, false if there was some kind of error.
     */
    abstract public function flush();

    /**
     * \brief Runs the garbage collection.
     *
     * Some backends require garbage collection to remove expired entries from
     * the cache, so this function should be called periodically.
     *
     * @return True on success, false if there was some kind of error.
     */
    public function gc()
    {
        return true;
    }

}
