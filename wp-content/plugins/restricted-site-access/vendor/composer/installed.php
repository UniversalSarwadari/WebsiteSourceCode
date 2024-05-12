<?php return array(
    'root' => array(
        'name' => '10up/restricted-site-access',
        'pretty_version' => '7.5.0',
        'version' => '7.5.0.0',
        'reference' => '79dab7d9428d161135a80554fd7bee2f5498403b',
        'type' => 'wordpress-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => false,
    ),
    'versions' => array(
        '10up/restricted-site-access' => array(
            'pretty_version' => '7.5.0',
            'version' => '7.5.0.0',
            'reference' => '79dab7d9428d161135a80554fd7bee2f5498403b',
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        '10up/wp-compat-validation-tool' => array(
            'pretty_version' => 'dev-trunk',
            'version' => 'dev-trunk',
            'reference' => '19a8c7c1d39d3a4c896aeeac8d42edd20b8d2317',
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../../10up-lib/wp-compat-validation-tool',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'composer/installers' => array(
            'pretty_version' => 'dev-main',
            'version' => 'dev-main',
            'reference' => '2a9170263fcd9cc4fd0b50917293c21d6c1a5bfe',
            'type' => 'composer-plugin',
            'install_path' => __DIR__ . '/./installers',
            'aliases' => array(
                0 => '2.x-dev',
            ),
            'dev_requirement' => false,
        ),
        'mlocati/ip-lib' => array(
            'pretty_version' => 'dev-master',
            'version' => 'dev-master',
            'reference' => 'a2c0e36416a814ca164d873da77b19f6e7749aef',
            'type' => 'library',
            'install_path' => __DIR__ . '/../mlocati/ip-lib',
            'aliases' => array(
                0 => '9999999-dev',
            ),
            'dev_requirement' => false,
        ),
    ),
);
