# composer-plugin-for-wp

A Composer plugin that automates PHP namespace prefixing for WordPress plugins using PHP Scoper. Easy integration with existing WordPress plugins.


# `third-party.json`

```json
{
    "require": {
        // dependencies...
    },
    "require-dev": {
        "chrono-meter/wp-scoper-scripts": "dev-master"
    },
    "config": {
        "vendor-dir": "third-party.tmp",
        "allow-plugins": {
            "chrono-meter/wp-scoper-scripts": true
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
             * @default "./php-scoper.tmp"
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
            "config": ""
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
require_once __DIR__ . '/third-party/vendor/autoload.php';
```