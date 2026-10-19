<?php
/** Collect classic admin notices without suppressing their callbacks. */
defined( 'ABSPATH' ) || exit;

final class Appartment_Notice_Center {
	const SLUG = 'appartment-notices';
	const VERSION = '1.2.0';

	public static function boot() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'network_admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'user_admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_bar_menu', array( __CLASS__, 'toolbar' ), 90 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_footer', array( __CLASS__, 'panel' ) );
		add_action( 'wp_ajax_appartment_collect_notices', array( __CLASS__, 'collect' ) );
		add_action( 'admin_post_appartment_clear_notices', array( __CLASS__, 'clear' ) );
	}

	private static function key() {
		return '_appartment_notices_' . get_current_blog_id() . ( is_network_admin() ? '_network' : ( is_user_admin() ? '_user' : '_site' ) );
	}

	private static function url() {
		return self_admin_url( 'admin.php?page=' . self::SLUG );
	}

	public static function menu() {
		add_menu_page( 'Centro notifiche', 'Notifiche', 'read', self::SLUG, array( __CLASS__, 'page' ), 'dashicons-bell', 3 );
	}

	public static function toolbar( $bar ) {
		if ( ! is_admin() || ! current_user_can( 'read' ) ) {
			return;
		}
		$bar->add_node( array( 'id' => 'appartment-notices', 'title' => 'Notifiche <span class="appartment-notice-count">0</span>', 'href' => self::url(), 'meta' => array( 'title' => 'Apri le notifiche della schermata' ) ) );
	}

	public static function assets() {
		if ( ! current_user_can( 'read' ) ) {
			return;
		}
		$file = dirname( __DIR__ ) . '/appartment-dashboard.php';
		wp_enqueue_style( 'appartment-notices', plugins_url( 'assets/notices.css', $file ), array(), self::VERSION );
		wp_enqueue_script( 'appartment-notices', plugins_url( 'assets/notices.js', $file ), array(), self::VERSION, true );
		// Preserve only routing information; never archive nonces or arbitrary query strings.
		$source = self_admin_url( basename( $GLOBALS['pagenow'] ?? 'index.php' ) );
		foreach ( array( 'page', 'post', 'post_type', 'taxonomy', 'tag_ID' ) as $name ) {
			if ( isset( $_GET[ $name ] ) && is_string( $_GET[ $name ] ) ) {
				$source = add_query_arg( $name, sanitize_text_field( wp_unslash( $_GET[ $name ] ) ), $source );
			}
		}
		wp_localize_script( 'appartment-notices', 'appartmentNotices', array(
			'ajax' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'appartment_notices' ),
			'archive' => self::url(), 'source' => $source, 'screen' => get_admin_page_title(),
			'context' => is_network_admin() ? 'network' : ( is_user_admin() ? 'user' : 'site' ),
			'storage' => 'appartment-notices-' . get_current_blog_id() . '-' . get_current_user_id(),
		) );
	}

	public static function panel() {
		if ( ! current_user_can( 'read' ) ) { return; }
		?>
		<div id="appartment-notice-overlay" hidden></div>
		<section id="appartment-notice-panel" role="dialog" aria-modal="true" aria-labelledby="appartment-notice-title" hidden>
			<header><h2 id="appartment-notice-title">Notifiche della schermata <span class="appartment-notice-count">0</span></h2><button type="button" class="button" id="appartment-notice-close" aria-label="Chiudi notifiche">Chiudi</button></header>
			<p>Qui puoi leggere gli avvisi e usare le loro azioni originali.</p>
			<div class="appartment-notice-tools"><button type="button" class="button" id="appartment-notice-toggle" aria-pressed="true">Mostra avvisi nelle pagine</button> <a href="<?php echo esc_url( self::url() ); ?>">Apri lo storico completo</a></div>
			<p id="appartment-notice-empty">Nessun avviso in questa schermata.</p>
			<div id="appartment-notice-live"></div>
			<p id="appartment-notice-save-status" role="status"></p>
		</section>
		<button type="button" class="button" id="appartment-notice-launcher" aria-haspopup="dialog" aria-controls="appartment-notice-panel">Notifiche <span class="appartment-notice-count">0</span></button>
		<?php
	}

	public static function normalize( $items, $existing, $now, $base = null ) {
		$existing = array_filter( (array) $existing, static function ( $row ) use ( $now ) {
			return is_array( $row ) && isset( $row['seen'] ) && $row['seen'] >= $now - 30 * DAY_IN_SECONDS;
		} );
		foreach ( array_slice( (array) $items, 0, 50 ) as $item ) {
			if ( ! is_array( $item ) || ! isset( $item['text'] ) || ! is_string( $item['text'] ) ) { continue; }
			$text = sanitize_textarea_field( substr( $item['text'], 0, 12000 ) );
			if ( '' === trim( $text ) ) { continue; }
			$source = isset( $item['source'] ) && is_string( $item['source'] ) ? esc_url_raw( $item['source'] ) : '';
			// Reject external destinations, including different origins of the same site.
			$admin = wp_parse_url( $base ?: self_admin_url() );
			$url = wp_parse_url( $source );
			if ( ! $url || isset( $url['user'] ) || isset( $url['pass'] ) || ( $url['scheme'] ?? '' ) !== ( $admin['scheme'] ?? '' ) || ( $url['host'] ?? '' ) !== ( $admin['host'] ?? '' ) || ( $url['port'] ?? null ) !== ( $admin['port'] ?? null ) || 0 !== strpos( $url['path'] ?? '', $admin['path'] ?? '/wp-admin/' ) ) { continue; }
			$source = strtok( $source, '?' );
			parse_str( $url['query'] ?? '', $query );
			foreach ( array( 'page', 'post', 'post_type', 'taxonomy', 'tag_ID' ) as $name ) {
				if ( isset( $query[ $name ] ) && is_string( $query[ $name ] ) ) { $source = add_query_arg( $name, sanitize_text_field( $query[ $name ] ), $source ); }
			}
			$id = hash( 'sha256', $source . '|' . preg_replace( '/\s+/', ' ', $text ) );
			$type = isset( $item['type'] ) && in_array( $item['type'], array( 'error', 'warning', 'success', 'info' ), true ) ? $item['type'] : 'info';
			$existing[ $id ] = array( 'text' => $text, 'type' => $type, 'source' => $source, 'screen' => sanitize_text_field( is_string( $item['screen'] ?? null ) ? $item['screen'] : '' ), 'first' => $existing[ $id ]['first'] ?? $now, 'seen' => $now );
		}
		uasort( $existing, static function ( $a, $b ) { return $b['seen'] <=> $a['seen']; } );
		return array_slice( $existing, 0, 100, true );
	}

	public static function collect() {
		check_ajax_referer( 'appartment_notices', 'nonce' );
		if ( ! current_user_can( 'read' ) ) { wp_send_json_error( null, 403 ); return; }
		// AJAX runs outside the network/user admin context; use an explicit validated context.
		$context = isset( $_POST['context'] ) && is_string( $_POST['context'] ) ? $_POST['context'] : 'site';
		if ( ! in_array( $context, array( 'site', 'network', 'user' ), true ) || ( 'network' === $context && ! current_user_can( 'manage_network' ) ) ) { wp_send_json_error( null, 403 ); return; }
		$key = '_appartment_notices_' . get_current_blog_id() . '_' . $context;
		$raw = isset( $_POST['items'] ) && is_string( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : '';
		if ( strlen( $raw ) > 200000 ) { wp_send_json_error( null, 413 ); return; }
		$items = json_decode( $raw, true );
		if ( ! is_array( $items ) ) { wp_send_json_error( null, 400 ); return; }
		$base = 'network' === $context ? network_admin_url() : ( 'user' === $context ? user_admin_url() : admin_url() );
		$rows = self::normalize( $items, get_user_meta( get_current_user_id(), $key, true ), time(), $base );
		update_user_meta( get_current_user_id(), $key, $rows );
		wp_send_json_success( array( 'count' => count( $rows ) ) );
	}

	public static function clear() {
		if ( ! current_user_can( 'read' ) ) { wp_die( 'Accesso non consentito.' ); }
		check_admin_referer( 'appartment_clear_notices' );
		$context = isset( $_POST['context'] ) && is_string( $_POST['context'] ) ? $_POST['context'] : 'site';
		if ( ! in_array( $context, array( 'site', 'network', 'user' ), true ) || ( 'network' === $context && ! current_user_can( 'manage_network' ) ) ) { wp_die( 'Accesso non consentito.' ); }
		delete_user_meta( get_current_user_id(), '_appartment_notices_' . get_current_blog_id() . '_' . $context );
		$base = 'network' === $context ? network_admin_url() : ( 'user' === $context ? user_admin_url() : admin_url() );
		wp_safe_redirect( $base . 'admin.php?page=' . self::SLUG . '&cleared=1' );
		exit;
	}

	public static function page() {
		if ( ! current_user_can( 'read' ) ) { return; }
		$rows = self::normalize( array(), get_user_meta( get_current_user_id(), self::key(), true ), time() );
		$labels = array( 'error' => 'Errore', 'warning' => 'Attenzione', 'success' => 'Conferma', 'info' => 'Informazione' );
		?>
		<div class="wrap appartment-notice-archive"><h1>Centro notifiche</h1>
		<p>Gli avvisi incontrati nelle pagine che visiti, solo per il tuo utente e questo sito. Ultimi 30 giorni, fino a 100 avvisi. Lo storico non indica se un problema è ancora presente: apri la pagina di origine per verificarlo e usare le azioni.</p>
		<div class="appartment-notice-tools"><label>Cerca <input type="search" id="appartment-notice-search" placeholder="Cerca negli avvisi"></label>
		<label>Tipo <select id="appartment-notice-filter"><option value="">Tutti</option><?php foreach ( $labels as $value => $label ) { echo '<option value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</option>'; } ?></select></label>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'appartment_clear_notices' ); ?><input type="hidden" name="action" value="appartment_clear_notices"><input type="hidden" name="context" value="<?php echo esc_attr( is_network_admin() ? 'network' : ( is_user_admin() ? 'user' : 'site' ) ); ?>"><button class="button" type="submit">Svuota il mio storico</button></form></div>
		<p id="appartment-archive-empty" <?php echo $rows ? 'hidden' : ''; ?>>Nessun avviso da mostrare. Gli avvisi vengono raccolti mentre visiti le pagine di amministrazione.</p>
		<div id="appartment-archive-list"><?php foreach ( $rows as $row ) { ?>
		<article class="appartment-archive-item appartment-type-<?php echo esc_attr( $row['type'] ); ?>" data-type="<?php echo esc_attr( $row['type'] ); ?>">
		<strong><?php echo esc_html( $labels[ $row['type'] ] ); ?></strong><p class="appartment-archive-text"><?php echo esc_html( $row['text'] ); ?></p>
		<footer><a href="<?php echo esc_url( $row['source'] ); ?>">Apri pagina di origine<?php echo $row['screen'] ? ': ' . esc_html( $row['screen'] ) : ''; ?></a><span>Ultimo rilevamento: <?php echo esc_html( wp_date( 'd/m/Y H:i', $row['seen'] ) ); ?></span></footer>
		</article><?php } ?></div></div>
		<?php
	}
}
