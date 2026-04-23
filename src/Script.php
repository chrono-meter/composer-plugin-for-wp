<?php  // phpcs:ignore
namespace ChronoMeter\WpScoperScripts;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Filesystem\Filesystem;

// phpcs:disable Squiz.Commenting, Squiz.PHP.CommentedOutCode, WordPress


class Script {
	public static function run( \Composer\Script\Event $event ): void {
		$filesystem = new Filesystem();
		$io         = $event->getIO();
		$args       = $event->getArguments();

		$project_dir = getcwd();

		// Parse "composer.json" in $project_dir.
		$composer_conf = json_decode( file_get_contents( Path::join( $project_dir, 'composer.json' ) ), true ) ?? array();
		$conf          = $composer_conf['extra']['scoper'] ?? array();

		$workdir = Path::join( $project_dir, $conf['workdir'] ?? 'php-scoper.tmp' );
		$outdir  = Path::join( $project_dir, $conf['outdir'] ?? 'third-party' );

		$io->write( 'Setup php-scoper in ' . $workdir );
		if ( $workdir ) {
			// Write ./php-scoper/composer.json.
			// NOTE: The version of "sniccowp/php-scoper-wordpress-excludes" is synchronized with WordPress and is not SemVer.
			$filesystem->mkdir( $workdir, 0777 );
			file_put_contents(
				Path::join( $workdir, 'composer.json' ),
				<<<'EOD'
{
	"require": {
		"humbug/php-scoper": "^0.18.11",
		"sniccowp/php-scoper-wordpress-excludes": "dev-master"
	},
	"minimum-stability": "dev",
	"prefer-stable": true
}
EOD
			);

			// Run: @composer --working-dir=php-scoper update --no-interaction
			static::execute_command( 'composer update --no-interaction', cwd: $workdir );
		}

		// Find "scoper.inc.php" then run: php php-scoper/vendor/humbug/php-scoper/bin/php-scoper add --output-dir=./third-party --force --quiet
		$command = array(
			PHP_BINARY,
			'-d memory_limit=-1',
			"$workdir/vendor/humbug/php-scoper/bin/php-scoper",
			'add',
			"--output-dir=$outdir",
			'--force',
			// '--quiet',
			'-vvv',
		);

		$io->write( 'Running php-scoper on ' . $project_dir );

		if ( !empty( $conf['config'] ) ) {
			$scoper_inc_path = Path::join( $project_dir, $conf['config'] );
			static::execute_command( array( ...$command, "--config=$scoper_inc_path" ), cwd: $project_dir );

		} elseif ( file_exists( Path::join( $project_dir, 'scoper.inc.php' ) ) ) {
			$scoper_inc_path = Path::join( $project_dir, 'scoper.inc.php' );
			static::execute_command( array( ...$command, "--config=$scoper_inc_path" ), cwd: $project_dir );

		} else {
			$scoper_inc_path = Path::join( __DIR__, 'scoper.inc.php' );
			$prefix          = $conf['prefix'] ?? array_key_first( $composer_conf['autoload']['psr-4'] ?? array() ) ?? '';
			$prefix          = rtrim( $prefix, '\\' );

			if ( empty( $prefix ) ) {
				throw new \RuntimeException( 'No prefix specified in composer.json or could not determine from autoload configuration.' );
			}

			static::execute_command(
				array( ...$command, "--config=$scoper_inc_path" ),
				cwd: $project_dir,
				env_vars: array(
				'SCOPER_PREFIX' => $prefix,
				'SCOPER_WORKDIR' => $workdir,
				)
			);
		}

		$io->write( 'Generate "autoload_classmap.php"...' );
		if ( is_dir( $outdir ) ) {
			file_put_contents( Path::join( $outdir, 'composer.json' ), '{ "autoload": { "classmap": [""] } }' );
			static::execute_command( "composer --working-dir=$outdir dump-autoload --classmap-authoritative --no-dev --no-interaction", cwd: $project_dir );
			unlink( Path::join( $outdir, 'composer.json' ) );
		}
	}

	protected static function execute_command( string|array $command, ?string $cwd = null, ?array $env_vars = null ): void {
		$process = proc_open(
			$command,
			array( STDIN, STDOUT, STDOUT ),
			$pipes,
			$cwd,
			$env_vars
		);

		if ( ! is_resource( $process ) ) {
			throw new \RuntimeException( 'Failed to execute command: ' . ( is_array( $command ) ? implode( ' ', $command ) : $command ) );
		}

		$return_var = proc_close( $process );

		if ( $return_var !== 0 ) {
			throw new \RuntimeException( "Command failed with exit code $return_var: " . ( is_array( $command ) ? implode( ' ', $command ) : $command ) );
		}
	}
}
