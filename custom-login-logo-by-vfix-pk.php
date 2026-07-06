<?php
/**
 * Plugin Name:       Custom Login Logo by VFIX.PK
 * Plugin URI:        https://github.com/mshahbazsiddiqui/custom-login-logo-by-vfix-pk
 * Description:       Replace the WordPress login screen logo (and Lost Password screen logo), point it to your homepage, and set its title/alt text to your site name. No core or theme files are modified.
 * Version:           1.0.2
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Muhammad Shahbaz
 * Author URI:        https://vfix.pk
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       custom-login-logo-by-vfix-pk
 * Domain Path:       /languages
 */

// Exit if accessed directly. Prevents any direct execution of this file outside WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Custom_Login_Logo_By_VFIXPK
 *
 * Encapsulates all functionality so nothing leaks into the global namespace,
 * no theme files are touched, and no core files are modified.
 */
final class Custom_Login_Logo_By_VFIXPK {

	/**
	 * The single option name used to store the logo attachment ID.
	 * We store ONLY an integer attachment ID -- never a raw URL, path, or
	 * arbitrary string -- so there is nothing here an attacker can inject
	 * SQL, script, or file-path payloads through.
	 */
	const OPTION_KEY = 'cll_logo_attachment_id';

	/**
	 * Nonce action/name used to protect the settings form.
	 */
	const NONCE_ACTION = 'cll_save_logo_action';
	const NONCE_NAME   = 'cll_save_logo_nonce';

	/**
	 * Capability required to view/change the setting.
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * Singleton instance.
	 *
	 * @var Custom_Login_Logo_By_VFIXPK|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Custom_Login_Logo_By_VFIXPK
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Wire up all WordPress hooks. No filesystem access, no DB queries,
	 * no external HTTP requests happen at load time.
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'register_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_action( 'admin_post_cll_save_logo', array( $this, 'handle_save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		add_action( 'login_enqueue_scripts', array( $this, 'print_login_logo_css' ) );
		add_filter( 'login_headerurl', array( $this, 'filter_login_headerurl' ) );
		add_filter( 'login_headertext', array( $this, 'filter_login_headertext' ) );
	}

	/* ---------------------------------------------------------------------
	 * Admin settings page
	 * ------------------------------------------------------------------ */

	/**
	 * Register the "Login Logo" submenu page under Settings.
	 * Gated by current_user_can() via the 'manage_options' capability
	 * argument, which WordPress enforces before rendering the page.
	 */
	public function register_settings_page() {
		add_options_page(
			__( 'Login Logo', 'custom-login-logo-by-vfix-pk' ),
			__( 'Login Logo', 'custom-login-logo-by-vfix-pk' ),
			self::CAPABILITY,
			'custom-login-logo-by-vfix-pk',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register the setting with the Settings API so WordPress' built-in
	 * options.php handler (not used directly here since we use
	 * admin-post.php for full nonce/capability control, see handle_save())
	 * knows about the sanitize callback as well, for defense in depth.
	 */
	public function register_setting() {
		register_setting(
			'cll_settings_group',
			self::OPTION_KEY,
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_attachment_id' ),
				'default'           => 0,
			)
		);
	}

	/**
	 * Sanitize the submitted attachment ID.
	 *
	 * Ensures the value is a non-negative integer AND actually points to
	 * an existing image attachment in the Media Library. This is the only
	 * "user input" this plugin accepts, and it is strictly validated.
	 *
	 * @param mixed $value Raw submitted value.
	 * @return int Sanitized attachment ID, or 0 if invalid.
	 */
	public function sanitize_attachment_id( $value ) {
		$attachment_id = absint( $value );

		if ( 0 === $attachment_id ) {
			return 0;
		}

		// Confirm it's a real, existing image attachment -- not an
		// arbitrary/guessed ID pointing to an unrelated post or file.
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return 0;
		}

		return $attachment_id;
	}

	/**
	 * Enqueue the WordPress Media Library scripts and our small admin JS
	 * only on our own settings page (never site-wide).
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( 'settings_page_custom-login-logo-by-vfix-pk' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_script(
			'cll-admin',
			plugins_url( 'assets/admin.js', __FILE__ ),
			array( 'jquery' ),
			'1.0.2',
			true
		);

		wp_localize_script(
			'cll-admin',
			'CLLAdmin',
			array(
				'chooseTitle'  => esc_html__( 'Select or Upload Login Logo', 'custom-login-logo-by-vfix-pk' ),
				'chooseButton' => esc_html__( 'Use this image', 'custom-login-logo-by-vfix-pk' ),
			)
		);

		wp_enqueue_style(
			'cll-admin',
			plugins_url( 'assets/admin.css', __FILE__ ),
			array(),
			'1.0.2'
		);
	}

	/**
	 * Render the settings page markup.
	 * All dynamic values are escaped on output.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'custom-login-logo-by-vfix-pk' ) );
		}

		$attachment_id = absint( get_option( self::OPTION_KEY, 0 ) );

		// If the stored ID no longer points to a real image (e.g. it was
		// deleted from the Media Library since being selected), treat it as
		// unset so the UI accurately reflects reality: no preview, the
		// "Remove Logo" button hidden, and a 0 submitted on next save.
		if ( $attachment_id > 0 && ! wp_attachment_is_image( $attachment_id ) ) {
			$attachment_id = 0;
		}

		$preview_url = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';

		?>
		<div class="wrap cll-settings-wrap">
			<h1><?php echo esc_html__( 'Login Logo Settings', 'custom-login-logo-by-vfix-pk' ); ?></h1>

			<?php
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only UI flag for a success notice; no data is read or processed from it, and the redirect that sets it only ever follows a nonce-verified save in handle_save().
			if ( isset( $_GET['cll_updated'] ) ) :
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html__( 'Login logo settings saved.', 'custom-login-logo-by-vfix-pk' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>
				<input type="hidden" name="action" value="cll_save_logo" />

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php echo esc_html__( 'Logo Image', 'custom-login-logo-by-vfix-pk' ); ?></th>
						<td>
							<div class="cll-preview-wrap">
								<img
									id="cll-logo-preview"
									src="<?php echo esc_url( $preview_url ); ?>"
									style="<?php echo $preview_url ? '' : 'display:none;'; ?>max-width:320px;height:auto;"
									alt="<?php echo esc_attr__( 'Current login logo preview', 'custom-login-logo-by-vfix-pk' ); ?>"
								/>
							</div>

							<input
								type="hidden"
								name="<?php echo esc_attr( self::OPTION_KEY ); ?>"
								id="cll_logo_attachment_id"
								value="<?php echo esc_attr( $attachment_id ); ?>"
							/>

							<p>
								<button type="button" class="button" id="cll-choose-logo">
									<?php echo esc_html__( 'Choose Image', 'custom-login-logo-by-vfix-pk' ); ?>
								</button>
								<button type="button" class="button" id="cll-remove-logo" style="<?php echo $attachment_id ? '' : 'display:none;'; ?>">
									<?php echo esc_html__( 'Remove Logo', 'custom-login-logo-by-vfix-pk' ); ?>
								</button>
							</p>
							<p class="description">
								<?php echo esc_html__( 'Recommended: a transparent PNG or SVG, roughly 320x80px. Leave empty to use the default WordPress logo.', 'custom-login-logo-by-vfix-pk' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<?php submit_button( esc_html__( 'Save Changes', 'custom-login-logo-by-vfix-pk' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle the settings form submission via admin-post.php.
	 *
	 * Enforces capability check + nonce verification before touching
	 * anything, then saves via update_option()/delete_option() only --
	 * no direct database queries.
	 */
	public function handle_save() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to perform this action.', 'custom-login-logo-by-vfix-pk' ) );
		}

		check_admin_referer( self::NONCE_ACTION, self::NONCE_NAME );

		$submitted_id  = isset( $_POST[ self::OPTION_KEY ] ) ? absint( wp_unslash( $_POST[ self::OPTION_KEY ] ) ) : 0;
		$attachment_id = $this->sanitize_attachment_id( $submitted_id );

		if ( $attachment_id > 0 ) {
			update_option( self::OPTION_KEY, $attachment_id );
		} else {
			delete_option( self::OPTION_KEY );
		}

		$redirect_url = add_query_arg(
			array( 'page' => 'custom-login-logo-by-vfix-pk', 'cll_updated' => '1' ),
			admin_url( 'options-general.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/* ---------------------------------------------------------------------
	 * Front-end (login screen) behavior
	 * ------------------------------------------------------------------ */

	/**
	 * Print inline CSS on wp-login.php (and the Lost Password screen, which
	 * uses the same template/hook) to swap the background image of the
	 * logo link. Falls back silently to the default WordPress logo if no
	 * custom logo is set or the stored attachment no longer exists.
	 */
	public function print_login_logo_css() {
		$attachment_id = absint( get_option( self::OPTION_KEY, 0 ) );

		if ( 0 === $attachment_id ) {
			return;
		}

		$logo_url = wp_get_attachment_image_url( $attachment_id, 'full' );

		if ( empty( $logo_url ) ) {
			return; // Attachment was deleted from the Media Library; fail safe to default.
		}
		?>
		<style type="text/css" id="cll-login-logo-style">
			body.login #login h1 a,
			body.login #login h1 a.custom-logo-link {
				background-image: url('<?php echo esc_url( $logo_url ); ?>');
				background-size: contain;
				background-position: center;
				background-repeat: no-repeat;
				width: 320px;
				max-width: 100%;
				height: 80px;
			}
		</style>
		<?php
	}

	/**
	 * Point the login logo link to the site homepage instead of
	 * wordpress.org.
	 *
	 * @return string
	 */
	public function filter_login_headerurl() {
		return esc_url( home_url( '/' ) );
	}

	/**
	 * Set the logo's title attribute (and screen-reader text) to the site
	 * name instead of "Powered by WordPress."
	 *
	 * @return string
	 */
	public function filter_login_headertext() {
		return esc_html( get_bloginfo( 'name' ) );
	}
}

Custom_Login_Logo_By_VFIXPK::instance();
