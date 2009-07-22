<?php

/**
 * \brief Backend class using simple file-based storage.
 *
 * This backend class stores all data in files. It uses one file for each key
 * => value pair. The filename, or, if the subdirectories option is enabled,
 * the path and the filename, are the hexadecimal representation of the MD5
 * hash of the key.
 *
 * This backend requires garbage collection, please make sure to call gc()
 * periodically.
 *
 * List of options:
 *  - directory: The path where all cached data will be stored
 *  - subdirectories: Because many filesystems don't scale well with many files
 *  in one directory, the FileBackend supports using up to 31 nested levels of
 *  subdirectories. If you for example set this value to '3', the file
 *  '70d064794720f5072cb960e1f3b93f6f' will be stored in the directory '7/0/d/'
 *  with the new filename '064794720f5072cb960e1f3b93f6f'. The default is 2.
 *  Set to 0 to disable.
 */
class FileBackend extends BackendSkeleton
{

    /**
     * \brief The directory which holds the cached data.
     */
    private $directory;

    /**
     * \brief The level of subdirectories in the cache directory.
     */
    private $subdirectories = 2;

    public function __construct($options)
    {
        if(empty($options['directory']))
            $options['directory'] = $this->get_temp_dir();
        assert(is_dir($options['directory']) && is_writable($options['directory']));
        $this->directory = $options['directory'];
        if(array_key_exists('subdirectories', $options))
        {
            assert($options['subdirectories'] >= 0 && $options['subdirectories'] < 32);
            $this->subdirectories = $options['subdirectories'];
        }
    }

    /**
     * \brief Tries to find a suitable cache directory
     *
     * This function tries to find the temporary directory on a system. It uses
     * the function sys_get_temp_dir(), which was added in PHP 5.2.1, but falls
     * back to environment variables and a hackish trick employing the function
     * tempnam() if the PHP version is < 5.2.1.
     *
     * This function uses make_temp_dir() to create a subdirectory called 'pca'
     * in the temporary files directory of the server.
     *
     * @see make_temp_dir()
     * @return The path to the cache directory.
     */
    private function get_temp_dir()
    {
        if(function_exists('sys_get_temp_dir'))
            return $this->make_temp_dir(sys_get_temp_dir());
        foreach(array('TMP', 'TMPDIR', 'TEMP') as $value)
            if(!empty($_ENV[$value]) && is_writable($_ENV[$value]))
                return $this->make_temp_dir($_ENV[$value]);
        // Ok this is hackish, but it works
        $tmpfile = tempnam(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'not-there', '');
        unlink($tmpfile);
        return $this->make_temp_dir(dirname($tmpfile));
    }

    /**
     * \brief Creates a directory 'pca' and returns it's path.
     *
     * This function is used by get_temp_dir() to create a subdirectory called
     * 'pca' in the temporary files directory of the server (if it does not
     * already exist').
     *
     * @see get_temp_dir()
     * @param $directory the directory in which a 'pca' subdirectory will be created.
     * @return The path to the 'pca' subdirectory.
     */
    private function make_temp_dir($directory)
    {
        $directory .= DIRECTORY_SEPARATOR . 'pca';
        if(!is_dir($directory))
            mkdir($directory);
        return $directory;
    }

    /**
     * \brief Derives the filename for the cache file from a key
     *
     * This function derives the filename for the cache file from a key by
     * using a hash function (currently md5).
     *
     * @param $key the key.
     * @return The path to the cache file.
     */
    private function get_filename($key)
    {
        $file = md5($key);
        if($this->subdirectories)
            $file = wordwrap(substr($file, 0, $this->subdirectories), 1, DIRECTORY_SEPARATOR, true) . DIRECTORY_SEPARATOR . substr($file, $this->subdirectories);
        return $this->directory . DIRECTORY_SEPARATOR . $file;
    }

    public function set($key, $value, $ttl = 0)
    {
        $file = $this->get_filename($key);
        if($this->subdirectories && !is_dir(dirname($file)))
            mkdir(dirname($file), 0777, true);
        $data = serialize(array($ttl == 0 ? null : (time() + $ttl), $value));
        $data = md5($data, true) . $data;
        return is_int(file_put_contents($file, $data, LOCK_EX));
    }

    public function get($key)
    {
        $file = $this->get_filename($key);
        if(!file_exists($file))
            return null;
        $data = file_get_contents($file);
        $hash = substr($data, 0, 16);
        $data = substr($data, 16);
        if(md5($data, true) != $hash) {
            unlink($file);
            return null;
        }
        $data = unserialize($data);
        if(!is_null($data[0]) && $data[0] < time()) {
            unlink($file);
            return null;
        }
        return $data[1];
    }

    public function delete($key)
    {
        if($this->exists($key))
        {
            unlink($this->get_filename($key));
            return true;
        } else
            return false;
    }

    /**
     * \brief Walks through a directory and deletes expired/all cache files.
     *
     * This function is used both by the flush() operation and the garbage
     * collection. It recursively walks through a directory and deletes all
     * expired cache files or all files if the parameter $flush is set to true.
     *
     * @param $directory the directory that shall be cleaned.
     * @param $flush when true, all cache files (even non-expired ones) will be
     * deleted.
     *
     * @see flush()
     * @see gc()
     */
    private function walk_directory($directory, $flush = false)
    {
        $dir = new DirectoryIterator($directory);
        foreach($dir as $file)
        {
            if($file->isDot())
                next;
            elseif($file->isDir())
                $this->walk_directory($file->getPathname(), $flush);
            elseif($flush)
                unlink($file->getPathname());
            else {
                $data = file_get_contents($data);
                $hash = substr($data, 0, 16);
                $data = substr($data, 16);
                if(md5($data, true) != $hash)
                    unlink($file->getPathname());
                $data = unserialize($data);
                if(!is_null($data[0]) && $data[0] < time())
                    unlink($file->getPathname());
            }
        }
    }

    public function flush()
    {
        $this->walk_directory($this->directory, true);
        return true;
    }

    public function gc()
    {
        $this->walk_directory($this->directory);
        return true;
    }

}
