<?php
/**
 * Plugin Name: app[art]ment Dashboard
 * Description: Bacheca essenziale con contatti app[art]ment e riepiloghi nativi Site Kit e WooCommerce.
 * Version: 1.2.0
 * Update URI: https://github.com/theappartment/appartment-dashboard
 * Author: app[art]ment
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * Text Domain: appartment-dashboard
 */

defined( 'ABSPATH' ) || exit;

final class Appartment_Dashboard {
	const WIDGET = 'appartment_dashboard_contact';

	public static function boot() {
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'setup' ), PHP_INT_MAX );
		// Runs after dashboard registration, including late third-party callbacks.
		add_action( 'admin_head-index.php', array( __CLASS__, 'clean' ), PHP_INT_MAX );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_filter( 'hidden_meta_boxes', array( __CLASS__, 'visible_widgets' ), 10, 2 );
	}

	private static function is_dashboard() {
		$screen = get_current_screen();
		return $screen && 'dashboard' === $screen->id && ! is_network_admin();
	}

	public static function setup() {
		if ( ! self::is_dashboard() || ! current_user_can( 'read' ) ) {
			return;
		}
		remove_action( 'welcome_panel', 'wp_welcome_panel' );
		wp_add_dashboard_widget( self::WIDGET, 'powered by', array( __CLASS__, 'render' ) );
		self::clean();
	}

	private static function allowed() {
		$allowed = array( self::WIDGET );
		// Site Kit itself registers the widget only for authenticated/shared users.
		// Its dashboard permission also requires completed setup.
		if ( defined( 'GOOGLESITEKIT_VERSION' ) && current_user_can( 'googlesitekit_view_dashboard' ) && current_user_can( 'googlesitekit_view_wp_dashboard_widget' ) ) {
			$allowed[] = 'google_dashboard_widget';
		}
		if ( class_exists( 'WooCommerce' ) && ( current_user_can( 'view_woocommerce_reports' ) || current_user_can( 'manage_woocommerce' ) || current_user_can( 'publish_shop_orders' ) ) ) {
			$allowed[] = 'woocommerce_dashboard_status';
		}
		return $allowed;
	}

	public static function clean() {
		if ( ! self::is_dashboard() ) {
			return;
		}
		global $wp_meta_boxes;
		if ( empty( $wp_meta_boxes['dashboard'] ) ) {
			return;
		}
		$allowed = self::allowed();
		foreach ( $wp_meta_boxes['dashboard'] as $context => $priorities ) {
			foreach ( $priorities as $priority => $boxes ) {
				foreach ( $boxes as $id => $box ) {
					if ( ! in_array( $id, $allowed, true ) ) {
						unset( $wp_meta_boxes['dashboard'][ $context ][ $priority ][ $id ] );
					}
				}
			}
		}
	}

	public static function visible_widgets( $hidden, $screen ) {
		if ( $screen && 'dashboard' === $screen->id && ! is_network_admin() ) {
			return array_values( array_diff( $hidden, self::allowed() ) );
		}
		return $hidden;
	}

	public static function assets( $hook ) {
		if ( 'index.php' === $hook && self::is_dashboard() ) {
			wp_enqueue_style( 'appartment-dashboard', plugins_url( 'assets/dashboard.css', __FILE__ ), array(), '1.2.0' );
		}
	}

	public static function render() {
		?>
		<div class="appartment-contact">
			<img class="appartment-logo" src="<?php echo esc_url( plugins_url( 'assets/logoappartment.png', __FILE__ ) ); ?>" alt="app[art]ment" width="617" height="109">
			<div class="appartment-contact-details">
				<p>Contattaci:</p>
				<p><a href="mailto:info@theappartment.it">info@theappartment.it</a></p>
				<p><a class="appartment-phone" href="tel:+390422318502">0422 318502</a></p>
				<p>Via Giacomo Zanella 69, 31100 Treviso (IT)</p>
				<p>Lun-Ven 08:30 - 12:30 // 14:00 - 18:00</p>
			</div>
		</div>
		<?php
	}
}

Appartment_Dashboard::boot();

require_once __DIR__ . '/includes/class-github-updater.php';
new Appartment_GitHub_Updater( __FILE__ );

require_once __DIR__ . '/includes/class-notice-center.php';
Appartment_Notice_Center::boot();
