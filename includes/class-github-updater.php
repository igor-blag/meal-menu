<?php
namespace Meal_Menu;

defined( 'ABSPATH' ) || exit;

class GitHub_Updater {

	private $file;
	private $plugin;
	private $slug;
	private $repo;
	private $api_url;

	public function __construct( string $plugin_file, string $repo ) {
		$this->file    = $plugin_file;
		$this->plugin  = plugin_basename( $plugin_file );
		$this->slug    = dirname( $this->plugin );
		$this->repo    = $repo;
		$this->api_url = "https://api.github.com/repos/{$repo}/releases/latest";

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
		add_filter( 'upgrader_source_selection', array( $this, 'fix_source' ), 10, 4 );
	}

	public function check_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$release = $this->fetch();
		if ( ! $release || empty( $release['tag_name'] ) ) {
			return $transient;
		}

		$new = ltrim( $release['tag_name'], 'v' );
		if ( version_compare( $new, $this->get_version(), '<=' ) ) {
			return $transient;
		}

		$transient->response[ $this->plugin ] = (object) array(
			'slug'        => $this->slug,
			'plugin'      => $this->plugin,
			'new_version' => $new,
			'package'     => $release['zipball_url'],
			'url'         => "https://github.com/{$this->repo}",
		);

		return $transient;
	}

	public function plugin_info( $result, $action, $args ) {
		if ( $action !== 'plugin_information' || ( $args->slug ?? '' ) !== $this->slug ) {
			return $result;
		}

		$release = $this->fetch();
		if ( ! $release ) {
			return $result;
		}

		return (object) array(
			'name'          => $this->get_name(),
			'slug'          => $this->slug,
			'version'       => ltrim( $release['tag_name'], 'v' ),
			'author'        => '<a href="https://github.com/igor-blag">igor-blag</a>',
			'homepage'      => "https://github.com/{$this->repo}",
			'requires'      => '6.0',
			'tested'        => '7.0',
			'downloaded'    => 0,
			'last_updated'  => $release['published_at'] ?? '',
			'sections'      => array(
				'description' => $release['body'] ?? __( 'Плагин календаря питания', 'meal-menu' ),
			),
			'download_link' => $release['zipball_url'],
		);
	}

	public function fix_source( $source, $remote_source, $upgrader, $hook_extra ) {
		if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin ) {
			return $source;
		}

		$dirs = glob( trailingslashit( $source ) . '*', GLOB_ONLYDIR );
		if ( count( $dirs ) === 1 ) {
			return trailingslashit( $dirs[0] );
		}

		return $source;
	}

	private function fetch() {
		$cache = get_transient( 'meal_menu_gh_release' );
		if ( $cache !== false ) {
			return $cache;
		}

		$response = wp_remote_get( $this->api_url, array(
			'timeout'    => 10,
			'headers'    => array( 'Accept' => 'application/vnd.github.v3+json' ),
			'user-agent' => 'WordPress/Meal-Menu',
		) );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || ! isset( $data['tag_name'] ) ) {
			return null;
		}

		set_transient( 'meal_menu_gh_release', $data, HOUR_IN_SECONDS * 6 );

		return $data;
	}

	private function get_version(): string {
		$headers = get_file_data( $this->file, array( 'Version' => 'Version' ) );
		return $headers['Version'] ?? '0.0.0';
	}

	private function get_name(): string {
		$headers = get_file_data( $this->file, array( 'Name' => 'Plugin Name' ) );
		return $headers['Name'] ?? 'Meal Menu';
	}
}
