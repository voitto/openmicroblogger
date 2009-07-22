<?php

require_once(dirname(__FILE__) . '/backends/skeleton.php');

/**
 * \brief Implements a factory pattern to manage various backends.
 *
 * The PCA class implements two factory methods that both return a PCABackend
 * instance.
 */
final class PCA {

    /**
     * \brief The main method for creating a PCABackend instance of a specific
     * type.
     *
     * @param $type the name of the backend (e.g. 'file' for FileBackend).
     * @param $options an array of configuration options for the backend. Take
     * a look at an individual backend's documentation for more details.
     * @return An instance of the class PCABackend.
     */
    public static function factory($type, $options = array())
    {
        if(include_once(dirname(__FILE__) . '/backends/' . $type . '_backend.class.php'))
        {
            $classname = $type . 'Backend';
            return new $classname($options);
        }
        else
        {
            throw new Exception('Unknown backend "' . $type . '"');
        }
    }

    /**
     * \brief Returns the best-suited backend without the need for any
     * configuration.
     *
     * Tries to guess the best backend type that will be supported by this
     * version/configuration of PHP and automatically returns a PCABackend
     * instance. This function is useful for applications, where the user
     * shouldn't be overwhelmed with configuration options on install.
     *
     * The current strategy only knows 2 backends:
     *  - APCBackend: use when APC is installed and enabled
     *  - FileBackend: use as fallback
     *
     * @return An instance of the class PCABackend.
     */
    public static function get_best_backend()
    {
        if(ini_get('apc.enabled') && !(PHP_SAPI == 'cli' && !ini_get('apc.enable_cli')))
            return self::factory('apc');
        else
            return self::factory('file');
    }

}
