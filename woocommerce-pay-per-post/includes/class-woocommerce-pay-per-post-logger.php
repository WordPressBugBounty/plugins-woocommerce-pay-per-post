<?php

use Pramadillo\PayForPost\Monolog\Handler\StreamHandler;
use Pramadillo\PayForPost\Monolog\Logger;

class Woocommerce_Pay_Per_Post_Logger {

	/**
	 * Set once the log directory has been secured and the log file name used
	 * up to 3.3.0 removed. See secure_log_directory().
	 */
	const SECURED_OPTION = 'wc_pay_per_post_log_secured';

	/**
	 * The log file name used up to 3.3.0. Anyone could guess its address.
	 */
	const LEGACY_LOG_FILE_NAME = 'debug.log';

	public $log;
	protected $log_file_name;
	protected $log_file_uri;
	protected $log_file_url;
	protected $log_directory;
	protected $log_directory_url;
	private $debug;


	public function __construct() {

		$this->debug             = get_option( WC_PPP_SLUG . '_enable_debugging' );
		$uploads_dir             = wp_upload_dir();
		$this->log_directory     = $uploads_dir['basedir'] . '/woocommerce-pay-per-post-logs';
		$this->log_directory_url = $uploads_dir['baseurl'] . '/woocommerce-pay-per-post-logs';
		$this->log_file_name     = self::log_file_name();
		$this->log_file_uri      = $this->log_directory . '/' . $this->log_file_name;
		$this->log_file_url      = $this->log_directory_url . '/' . $this->log_file_name;

		if ( $this->debug ) {

			$this->log = new Logger( WC_PPP_SLUG );
			$this->create_log_file();

			try {
				$this->log->pushHandler( new StreamHandler( $this->log_file_uri, Logger::DEBUG ) );
			} catch ( Exception $e ) {
				die( $e->getMessage() );
			}

		}


	}

	/**
	 * The log lives in the uploads folder, which the web server serves. The
	 * .htaccess written by protect_log_directory() keeps it private on Apache,
	 * but nginx ignores it, so the file name carries a hash of this site's
	 * salts and cannot be guessed. WooCommerce names its own logs the same way.
	 */
	public static function log_file_name(): string {
		return 'debug-' . wp_hash( WC_PPP_SLUG . '_debug_log' ) . '.log';
	}

	public function log( $message, $context = [] ) {
		if ( $this->debug && isset( $_GET['wc-ppp-debug'] ) ) {
			$this->log->debug( $message, $context );
		}
	}


	/**
	 * Delete the log, and any log written under an earlier name: the name
	 * follows the site's salts, so changing the security keys (or a host or
	 * security plugin that rotates them) leaves the old file behind, and it
	 * can hold customers' email addresses.
	 */
	public function delete_log_file() {
		@unlink( $this->log_file_uri );
		@unlink( $this->log_directory . '/' . self::LEGACY_LOG_FILE_NAME );

		foreach ( (array) glob( $this->log_directory . '/debug-*.log' ) as $file ) {
			if ( is_string( $file ) && preg_match( '/^debug-[0-9a-f]+\.log$/', basename( $file ) ) ) {
				@unlink( $file );
			}
		}
	}

	/**
	 * Once per site: protect the log directory and delete the log written up
	 * to 3.3.0 under a guessable name. Runs on init, whether or not debugging
	 * is on, because that old file outlives the setting that created it.
	 */
	public static function secure_log_directory() {
		if ( get_option( self::SECURED_OPTION ) ) {
			return;
		}

		$logger = new self();
		if ( file_exists( $logger->log_directory ) ) {
			$logger->protect_log_directory();
			@unlink( $logger->log_directory . '/' . self::LEGACY_LOG_FILE_NAME );
		}

		update_option( self::SECURED_OPTION, 1, true );
	}

	private function create_log_file() {

		if ( ! file_exists( $this->log_directory ) ) {
			wp_mkdir_p( $this->log_directory );
			$this->protect_log_directory();
		}

		if ( ! file_exists( $this->log_file_uri ) ) {
			$this->log( 'Initial Log File Created' );
		}

	}

	/**
	 * Deny web access to the directory on Apache, and stop servers that list
	 * directories from listing it.
	 */
	protected function protect_log_directory() {
		$files = [
			'.htaccess'  => "<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n\tDeny from all\n</IfModule>\n",
			'index.php'  => "<?php\n// Silence is golden.\n",
			'index.html' => '',
		];

		foreach ( $files as $name => $contents ) {
			$path = $this->log_directory . '/' . $name;
			if ( ! file_exists( $path ) ) {
				@file_put_contents( $path, $contents );
			}
		}
	}

	public function get_log_uri(): string {
		return $this->log_file_uri;
	}

	public function get_log_url(): string {
		return $this->log_file_url;
	}

}
