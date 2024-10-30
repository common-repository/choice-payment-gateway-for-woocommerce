<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitb8ffcd9a30340b57d996dc741941fb5a
{
    public static $prefixLengthsPsr4 = array (
        'D' => 
        array (
            'Defuse\\Crypto\\' => 14,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Defuse\\Crypto\\' => 
        array (
            0 => __DIR__ . '/..' . '/defuse/php-encryption/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitb8ffcd9a30340b57d996dc741941fb5a::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitb8ffcd9a30340b57d996dc741941fb5a::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitb8ffcd9a30340b57d996dc741941fb5a::$classMap;

        }, null, ClassLoader::class);
    }
}