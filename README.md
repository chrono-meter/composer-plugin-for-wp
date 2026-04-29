# wp-scoper-scripts

A Composer script package that automates PHP namespace prefixing for WordPress plugins using PHP Scoper. Easy integration with existing WordPress plugins.


# `composer.json`

```json
{
    "autoload": {
        "psr-4": {
            "VendorName\\Package\\": "src/"
        }
    },
    "require-dev": {
        "chrono-meter/wp-scoper-scripts": "dev-master"
    },
    "scripts": {
        "post-install-cmd": [
            "@prefix-dependencies"
        ],
        "post-update-cmd": [
            "@prefix-dependencies"
        ],
        "prefix-dependencies": [
            "Composer\\Config::disableProcessTimeout",
            "ChronoMeter\\WpScoperScripts\\Script::run"
        ]
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
            "workdir": "",

            /**
             * Relative path for output (prefix-ed packages) directory.
             *
             * @default "./third-party"
             */
            "outdir": "",

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


# Then load

```php
require_once __DIR__ . '/third-party/vendor/autoload.php';
```