<?php
/**
 * This file is part of MySQLDumper released under the GNU/GPL 2 license
 * http://www.mysqldumper.net
 *
 * @package         MySQLDumper
 * @subpackage      Vcs
 * @version         SVN: $Rev$
 * @author          $Author$
 */
/**
 * Factory class for Version Control System support.
 *
 * @throws          Msd_Vcs_Exception
 * @package         MySQLDumper
 * @subpackage      Vcs
 */
class Msd_Vcs
{
    /**
     * @var Zend_Loader_PluginLoader
     */
    private static $_loader = null;

    /**
     * Ignore these filenames in the "available adapters" list.
     *
     * @var array
     */
    private static $_ignoreFileNames = array('Exception.php', 'Interface.php', 'Abstract.php');

    /**
     * Disabled Class contructor. This is a factory pattern.
     */
    private function __construct()
    {
    }

    /**
     * Initialize the plugin loader. It's used to load third-party modules.
     *
     * @static
     *
     * @return void
     */
    private static function _initLoader()
    {
        if (self::$_loader === null) {
            self::$_loader = new Zend_Loader_PluginLoader(
                array(
                    'Msd_Vcs_' => implode('/', array(APPLICATION_PATH, '..', 'library', 'Msd', 'Vcs')),
                    'Module_Vcs_' => implode('/', array(APPLICATION_PATH, '..', 'modules', 'library', 'Vcs')),
                )
            );
        }
    }

    /**
     * Retrieves the class name for an adapter.
     *
     * @static
     *
     * @param string $adapter
     *
     * @return false|string
     */
    private static function _getAdapterClassName($adapter)
    {
        $vcsClass = str_replace('_', ' ', strtolower($adapter));
        $vcsClass = str_replace(' ', '_', ucwords($vcsClass));
        self::_initLoader();
        return self::$_loader->load($vcsClass);
    }

    /**
     * Get VCS adapter.
     *
     * @static
     *
     * @throws Msd_Vcs_Exception
     * @param string $adapter        Name of vcs adapter class
     * @param array  $adapterOptions Params for vcs adapter class
     *
     * @return Msd_Vcs_Interface
     */
    public static function factory($adapter, $adapterOptions = array())
    {
        $className = self::_getAdapterClassName($adapter);
        $vcs = new $className($adapterOptions);
        if (!$vcs instanceof Msd_Vcs_Interface) {
            throw new Msd_Vcs_Exception("The specified VCS adapter doesn't implement the interface Msd_Vcs_Interface.");
        }

        return $vcs;
    }

    /**
     * Returns list of available vcs adapter classes.
     *
     * @static
     *
     * @return array
     */
    public static function getAvailableAdapter()
    {
        self::_initLoader();

        $paths = self::$_loader->getPaths();
        $classNames = array();
        foreach ($paths as $path) {
            if (file_exists($path[0])) {
                $classNames = array_merge($classNames, self::_getDirEntries($path[0]));
            }
        }
        return $classNames;
    }

    /**
     * Build the list of available adapters.
     *
     * @static
     *
     * @param string $path Path to read
     *
     * @return array
     */
    private static function _getDirEntries($path)
    {
        $dir = new DirectoryIterator($path);
        $classes = array();
        for (; $dir->valid(); $dir->next()) {
            $filename = $dir->getFilename();
            if ($dir->isDot() || $filename{0} == '.' || in_array($filename, self::$_ignoreFileNames)) {
                continue;
            }
            if ($dir->isDir()) {
                $subdirs = self::_getDirEntries($dir->getPathname());
                foreach ($subdirs as $entry) {
                    $classes[] = $dir->getFilename() . '_' . $entry;
                }
            } else {
                $classes[] = substr($dir->getFilename(), 0, -4);
            }
        }
        return $classes;
    }

    /**
     * Retrieves the available options for an adapter.
     *
     * @static
     *
     * @param string $adapter Name of vcs adapter class
     *
     * @return array
     */
    public static function getAdapterOptions($adapter)
    {
        /**
         * @var Msd_Vcs_Interface $adapter
         */
        $className = self::_getAdapterClassName($adapter);
        $adapter = new $className();
        return $adapter->getAdapterOptions();
    }

    /**
     * Returns the adapter option names for user specific credentials.
     *
     * @static
     *
     * @param string $adapter Name of vcs adapter class
     *
     * @return array
     */
    public static function getCredentialFields($adapter)
    {
        /**
         * @var Msd_Vcs_Interface $adapter
         */
        $className = self::_getAdapterClassName($adapter);
        $adapter = new $className();
        return $adapter->getCredentialFields();
    }
}
