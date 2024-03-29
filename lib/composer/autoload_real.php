<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInitdb72a6bac11cb0e6b971811c93d09a9b
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        spl_autoload_register(array('ComposerAutoloaderInitdb72a6bac11cb0e6b971811c93d09a9b', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInitdb72a6bac11cb0e6b971811c93d09a9b', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInitdb72a6bac11cb0e6b971811c93d09a9b::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
