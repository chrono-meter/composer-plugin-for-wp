<?php  // phpcs:disable WordPress, Squiz.Commenting, Universal.Files.SeparateFunctionsFromOO.Mixed, Generic.Files.OneObjectStructurePerFile.MultipleFound
/**
 * PHP-Scoper configuration file.
 *
 * @link https://gist.github.com/chrono-meter/1f0412c39beaaaee0d9146be5bc2a7c7
 * @link https://github.com/google/site-kit-wp/blob/develop/composer.json
 * @link https://github.com/google/site-kit-wp/blob/develop/php-scoper/composer.json
 * @link https://github.com/google/site-kit-wp/blob/develop/scoper.inc.php
 * @link https://github.com/google/site-kit-wp/blob/develop/includes/loader.php
 */

use Isolated\Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Path;


if ( ! getenv( 'SCOPER_WORKDIR' ) ) {
	throw new \RuntimeException( 'SCOPER_WORKDIR environment variable is not set.' );
}
if ( ! getenv( 'SCOPER_PREFIX' ) ) {
	throw new \RuntimeException( 'SCOPER_PREFIX environment variable is not set.' );
}
if ( ! getenv( 'COMPOSER_VENDOR_DIR' ) ) {
	throw new \RuntimeException( 'COMPOSER_VENDOR_DIR environment variable is not set.' );
}


/**
 * Get the list of WordPress excluded symbols.
 *
 * @param string $file_name The file name.
 * @link https://github.com/humbug/php-scoper/blob/main/docs/further-reading.md#wordpress-support
 */
function getWpExcludedSymbols( string $file_name ): array {  // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	$path = getenv( 'SCOPER_WORKDIR' ) . '/vendor/sniccowp/php-scoper-wordpress-excludes/generated/' . $file_name;

	return json_decode(
		file_get_contents( $path ),  // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		true,
	);
}


class SplFileInfoWithNoRealPath extends \SplFileInfo {
	public function getRealPath(): string {
		return $this->getPathname();
	}
}


/**
 * Faking realpath() is required to work around the issue that php-scoper does not support files with symlinks in their paths.
 *
 * @link https://github.com/humbug/php-scoper/blob/0.18.19/src/Configuration/ConfigurationFactory.php#L375-L377
 */
class FinderWithNoRealPath extends Finder {
	public function getIterator(): \Iterator {
		$result = array();

		foreach ( parent::getIterator() as $file ) {
			$result[] = new SplFileInfoWithNoRealPath( $file->getPathname() );
		}

		return new ArrayIterator( $result );
	}
}


function getComposerInstalledPackageDirs() {
	$data         = ( require Path::join( getenv( 'COMPOSER_VENDOR_DIR' ), 'composer/installed.php' ) );
	$root_package = $data['root'] ?? array();

	$result = array();

	foreach ($data['versions'] ?? array() as $package_name => $package_data) {
		if (
			'__root__' === $package_name
			||
			$root_package['name'] === $package_name
			||
			( $package_data['dev_requirement'] ?? false )
			||
			empty( $package_data['install_path'] )
		) {
			continue;
		}

		$result[] = Path::makeRelative( $package_data['install_path'], getcwd() );
	}

	return $result;
}


return array(
	'prefix'            => getenv( 'SCOPER_PREFIX' ),
	'finders'           => array(
		FinderWithNoRealPath::create()
			->files()
			->in( array( getenv( 'COMPOSER_VENDOR_DIR' ), ...getComposerInstalledPackageDirs() ) )
			->ignoreVCS( true ),
	),
	'exclude-classes'   => getWpExcludedSymbols( 'exclude-wordpress-classes.json' ),
	'exclude-constants' => getWpExcludedSymbols( 'exclude-wordpress-functions.json' ),
	'exclude-functions' => getWpExcludedSymbols( 'exclude-wordpress-constants.json' ),
);
