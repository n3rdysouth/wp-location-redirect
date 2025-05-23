<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit7e83f2b71270a527a7f8405e0c15b0b5
{
    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'WP_Location_Redirect\\' => 21,
        ),
        'M' => 
        array (
            'MaxMind\\WebService\\' => 19,
            'MaxMind\\Exception\\' => 18,
            'MaxMind\\Db\\' => 11,
        ),
        'G' => 
        array (
            'GeoIp2\\' => 7,
        ),
        'C' => 
        array (
            'Composer\\CaBundle\\' => 18,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'WP_Location_Redirect\\' => 
        array (
            0 => __DIR__ . '/../..' . '/includes',
        ),
        'MaxMind\\WebService\\' => 
        array (
            0 => __DIR__ . '/..' . '/maxmind/web-service-common/src/WebService',
        ),
        'MaxMind\\Exception\\' => 
        array (
            0 => __DIR__ . '/..' . '/maxmind/web-service-common/src/Exception',
        ),
        'MaxMind\\Db\\' => 
        array (
            0 => __DIR__ . '/..' . '/maxmind-db/reader/src/MaxMind/Db',
        ),
        'GeoIp2\\' => 
        array (
            0 => __DIR__ . '/..' . '/geoip2/geoip2/src',
        ),
        'Composer\\CaBundle\\' => 
        array (
            0 => __DIR__ . '/..' . '/composer/ca-bundle/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit7e83f2b71270a527a7f8405e0c15b0b5::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit7e83f2b71270a527a7f8405e0c15b0b5::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit7e83f2b71270a527a7f8405e0c15b0b5::$classMap;

        }, null, ClassLoader::class);
    }
}
