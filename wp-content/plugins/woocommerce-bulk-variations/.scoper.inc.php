<?php

declare( strict_types=1 );

use Isolated\Symfony\Component\Finder\Finder;

return [
    // The prefix configuration. If a non null value will be used, a random prefix will be generated.
    'prefix'                  => 'Barn2\\Plugin\\WC_Bulk_Variations\\Dependencies',
    'expose-global-constants' => false,
    'expose-global-classes'   => false,
    'expose-global-functions' => false,

    /**
     * By default when running php-scoper add-prefix, it will prefix all relevant code found in the current working
     * directory. You can however define which files should be scoped by defining a collection of Finders in the
     * following configuration key.
     *
     * For more see: https://github.com/humbug/php-scoper#finders-and-paths.
     */
    'finders'                    => [
        Finder::create()->
        files()->
        ignoreVCS( true )->
        notName( '/LICENSE|.*\\.md|.*\\.dist|Makefile|composer\\.(json|lock)/' )->
        exclude(
            [
                'doc',
                'test',
                'build',
                'test_old',
                'tests',
                'Tests',
                'vendor-bin',
            ]
        )->
        in(
            [
                'vendor/barn2/setup-wizard/',
            ]
        )->
        append(
            [
                'vendor/barn2/setup-wizard/build/setup-wizard.asset.php',
                'vendor/barn2/setup-wizard/build/setup-wizard.css',
                'vendor/barn2/setup-wizard/build/setup-wizard.js',
            ]
        )->
        name( [ '*.php' ] ),
    ],

    'exclude-classes' => [
        "WP_REST_Response",
    ],

];