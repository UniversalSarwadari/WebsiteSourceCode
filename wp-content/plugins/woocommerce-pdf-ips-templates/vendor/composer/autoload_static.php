<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit81019867511214e10d393c59be5c6a8a
{
    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'WPO\\WC\\PDF_Invoices_Templates\\' => 30,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'WPO\\WC\\PDF_Invoices_Templates\\' => 
        array (
            0 => __DIR__ . '/../..' . '/includes',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'WPO\\WC\\PDF_Invoices_Templates\\Compatibility\\Third_Party_Plugins' => __DIR__ . '/../..' . '/includes/compatibility/class-third-party-compatibility.php',
        'WPO\\WC\\PDF_Invoices_Templates\\Dependencies' => __DIR__ . '/../..' . '/includes/class-wcpdf-templates-dependencies.php',
        'WPO\\WC\\PDF_Invoices_Templates\\Legacy\\Templates' => __DIR__ . '/../..' . '/includes/legacy/class-wcpdf-templates-legacy.php',
        'WPO\\WC\\PDF_Invoices_Templates\\Main' => __DIR__ . '/../..' . '/includes/class-wcpdf-templates-main.php',
        'WPO\\WC\\PDF_Invoices_Templates\\Settings' => __DIR__ . '/../..' . '/includes/class-wcpdf-templates-settings.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit81019867511214e10d393c59be5c6a8a::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit81019867511214e10d393c59be5c6a8a::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit81019867511214e10d393c59be5c6a8a::$classMap;

        }, null, ClassLoader::class);
    }
}