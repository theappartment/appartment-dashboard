<?php
/** GitHub stable releases integration with the WordPress update API. */
defined( 'ABSPATH' ) || exit;

final class Appartment_GitHub_Updater {
	const REPOSITORY = 'theappartment/appartment-dashboard';
	const CACHE = 'appartment_dashboard_github_release';
	private $plugin_file;

	public function __construct( $plugin_file ) {
		$this->plugin_file = plugin_basename( $plugin_file );
		add_filter( 'update_plugins_github.com', array( $this, 'update' ), 10, 4 );
		add_action( 'delete_site_transient_update_plugins', array( $this, 'clear_cache' ) );
		// Before WordPress's priority-10 plugin check, invalidate both caches.
		add_action( 'load-update-core.php', array( $this, 'prepare_manual_check' ), 0 );
	}

	public function prepare_manual_check() {
		if ( empty( $_GET['force-check'] ) || ! current_user_can( 'update_plugins' ) ) {
			return;
		}
		$this->clear_cache();
		delete_site_transient( 'update_plugins' );
	}

	public function clear_cache() {
		delete_site_transient( self::CACHE );
	}

	public function update( $update, $plugin_data, $plugin_file, $locales ) {
		if ( $plugin_file !== $this->plugin_file ) {
			return $update;
		}
		$release = $this->release();
		if ( ! $release ) {
			return false;
		}
		return array(
			'id'           => 'https://github.com/' . self::REPOSITORY,
			'slug'         => 'appartment-dashboard',
			'version'      => $release['version'],
			'url'          => 'https://github.com/' . self::REPOSITORY . '/releases',
			'package'      => $release['package'],
		);
	}

	private function release() {
		$cached = get_site_transient( self::CACHE );
		if ( false !== $cached ) {
			return $cached;
		}
		$response = wp_remote_get(
			'https://api.github.com/repos/' . self::REPOSITORY . '/releases/latest',
			array(
				'timeout' => 10,
				'headers' => array( 'Accept' => 'application/vnd.github+json' ),
			)
		);
		$result = array();
		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( is_array( $data ) && empty( $data['draft'] ) && empty( $data['prerelease'] ) && isset( $data['tag_name'] ) && is_string( $data['tag_name'] ) && preg_match( '/^v?(\d+\.\d+\.\d+)$/D', $data['tag_name'], $matches ) ) {
				foreach ( (array) ( $data['assets'] ?? array() ) as $asset ) {
					if ( ! is_array( $asset ) || 'appartment-dashboard.zip' !== ( $asset['name'] ?? '' ) || 'uploaded' !== ( $asset['state'] ?? '' ) ) {
						continue;
					}
					$package = $asset['browser_download_url'] ?? '';
					// Only accept the expected release asset from our repository.
					$expected = 'https://github.com/' . self::REPOSITORY . '/releases/download/' . rawurlencode( $data['tag_name'] ) . '/appartment-dashboard.zip';
					if ( $expected === $package ) {
						$result = array( 'version' => $matches[1], 'package' => $package );
						break;
					}
				}
			}
		}
		// Cache failures briefly as well, to avoid hammering the unauthenticated API.
		set_site_transient( self::CACHE, $result, $result ? 6 * HOUR_IN_SECONDS : 15 * MINUTE_IN_SECONDS );
		return $result;
	}
}
