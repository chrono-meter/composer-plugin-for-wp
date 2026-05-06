# composer-plugin-for-wp

A Composer plugin that automates PHP namespace prefixing for WordPress plugins using PHP Scoper. Easy integration with existing WordPress plugins.


# `third-party.json`

```json
{
    "require": {
        // dependencies...
    },
    "require-dev": {
        "chrono-meter/composer-plugin-for-wp": "*"
    },
    "config": {
        "vendor-dir": "third-party.tmp",
        "allow-plugins": {
            "chrono-meter/composer-plugin-for-wp": true
        }
    },
    "extra": {
        "scoper": {
            /**
             * Namespace prefix.
             *
             * @default the first key of `$.autoload['psr-4']`
             */
            "prefix": "ThePrefix",

            /**
             * Relative path for Scoper installation directory.
             *
             * @default "/tmp/phpscoper-PROJECTPATHHASH.tmp"
             */
            "work-dir": "",

            /**
             * Relative path for output (prefix-ed packages) directory.
             *
             * @default "./third-party"
             */
            "out-dir": "",

            /**
             * Relative path for custom "scoper.inc.php" file.
             *
             * @default this package's embedded "scoper.inc.php"
             */
            "config": "",

            /**
             * Additional configurations.
             *
             * @link https://github.com/humbug/php-scoper/blob/main/docs/configuration.md
             */
            "exclude-files": [],
            "exclude-namespaces": [],
            "exclude-constants": [],
            "exclude-classes": [],
            // https://github.com/humbug/php-scoper/issues/1131
            "exclude-functions": [],
            "expose-global-constants": true,
            "expose-global-classes": true,
            "expose-global-functions": true,
            "expose-namespaces": [],
            "expose-constants": [],
            "expose-classes": [],
            "expose-functions": [],
        }
    }
}
```

Run:

```sh
COMPOSER=third-party.json composer update
```

Then load:

```php
( function () {
	/**
	 * Autoload third-party classes.
	 */
	// Method 1. Use composer's autoloader.
	// require_once __DIR__ . '/third-party/vendor/autoload.php';

	// Method 2. Use own autoloader.
	spl_autoload_register(
		function ( string $klass ) {
			static $class_map = ( @include __DIR__ . '/third-party/vendor/composer/autoload_classmap.php' ) ?: array();  // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, Universal.Operators.DisallowShortTernary.Found
			if ( isset( $class_map[ $klass ] ) ) {
				require_once $class_map[ $klass ];

				return true;
			}
		},
		prepend: true
	);

	/**
	 * Load third-party files.
	 */
	foreach ( ( @include __DIR__ . '/third-party/composer/autoload_files.php' ) ?: array() as $file ) {  // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, Universal.Operators.DisallowShortTernary.Found
		require_once $file;
	}
} )();
```