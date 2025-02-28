<?php

require_once ECWID_PLUGIN_DIR . 'includes/class-ecwid-popup.php';

class Ecwid_Popup_Deactivate extends Ecwid_Popup {

	protected $_class = 'ecwid-popup-deactivate';

	const OPTION_DISABLE_POPUP = 'ecwid_disable_deactivate_popup';

    const HANDLE_SLUG = 'ecwid-popup-deactivate';
    const NONCE_SLUG = 'ecwid-popup-deactivate';

	public function __construct() {
		add_action( 'wp_ajax_ecwid_deactivate_feedback', array( $this, 'ajax_deactivate_feedback' ) );
	}

	public function enqueue_scripts() {
		parent::enqueue_scripts();
		wp_enqueue_script( self::HANDLE_SLUG, ECWID_PLUGIN_URL . '/js/popup-deactivate.js', array( 'jquery' ), get_option( 'ecwid_plugin_version' ) );
		wp_enqueue_style( self::HANDLE_SLUG, ECWID_PLUGIN_URL . '/css/popup-deactivate.css', array(), get_option( 'ecwid_plugin_version' ) );

        wp_localize_script(
			self::HANDLE_SLUG,
			'EcwidPopupDeactivate',
			array(
				'_ajax_nonce' => wp_create_nonce( self::NONCE_SLUG ),
			)
		);
	}

	public function ajax_deactivate_feedback() {
        if ( ! check_ajax_referer( self::NONCE_SLUG ) ) {
			die();
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			die();
		}

		$to = 'plugins-feedback@ecwid.com';

		$body_lines = array();
		if ( ! ecwid_is_demo_store() ) {
			$body_lines[] = 'Store ID: ' . get_ecwid_store_id();
		}

		$reasons = $this->_get_reasons();

		if ( isset( $_GET['reason'] ) ) {
			$reason = $reasons[ sanitize_text_field( wp_unslash( $_GET['reason'] ) ) ];
		} else {
			$reason = end( $reasons );
		}

		if ( isset( $reason['is_disable_message'] ) ) {
			update_option( self::OPTION_DISABLE_POPUP, true );
		}

		$body_lines[] = 'Store URL: ' . Ecwid_Store_Page::get_store_url();
		$body_lines[] = 'Plugin installed: ' . date_i18n( 'd M Y', get_option( 'ecwid_installation_date' ) );
		$body_lines[] = 'Plugin version: ' . get_option( 'ecwid_plugin_version' );
		$body_lines[] = 'Reason:' . $reason['text'] . "\n" . ( ! empty( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '[no message]' );

		$api = new Ecwid_Api_V3();

		$profile = $api->get_store_profile();
		if ( $profile && @$profile->account && @$profile->account->accountEmail ) {
			$reply_to = $profile->account->accountEmail;
		} else {
			global $current_user;
			$reply_to = $current_user->user_email;
		}

		$subject_template = __( '[%1$s] WordPress plugin deactivation feedback (store ID: %2$s)', 'ecwid-shopping-cart' );

		$prefix = $reason['code'];
		if ( ! empty( $_GET['message'] ) ) {
			$prefix .= ', commented';
		}

		$subject = sprintf( $subject_template, $prefix, get_ecwid_store_id() );

		$result = wp_mail(
			$to,
			$subject,
			implode( PHP_EOL, $body_lines ),
			'Reply-To:' . $reply_to
		);

		if ( $result ) {
			header( 'HTTP/1.1 200 OK' );
			die();
		} else {
			header( '500 Send mail failed' );
			die();
		}
	}

	public function is_disabled() {
		$disabled = get_option( self::OPTION_DISABLE_POPUP, false );

		if ( $disabled ) {
			return true;
		}

		if ( Ecwid_Config::is_wl() ) {
			return true;
		}

		if ( strpos( ecwid_get_current_user_locale(), 'en' ) !== 0 ) {
			return true;
		}

		return false;
	}

	protected function _get_footer_buttons() {
		return array(
			(object) array(
				'class' => 'button-primary float-left deactivate',
				'title' => __( 'Submit & Deactivate', 'ecwid-shopping-cart' ),
			),
			(object) array(
				'class' => 'button-link deactivate',
				'title' => __( 'Skip & Deactivate', 'ecwid-shopping-cart' ),
			),
		);
	}

	protected function _get_header() {
		return __( 'Before You Go', 'ecwid-shopping-cart' );
	}

	protected function _render_body() {
		if ( ecwid_is_paid_account() ) {
			$support_link = Ecwid_Config::get_contact_us_url();
		} else {
			$support_link = 'https://wordpress.org/support/plugin/ecwid-shopping-cart/#new-topic-0';
		}

		$reasons = $this->_get_reasons();
		require ECWID_POPUP_TEMPLATES_DIR . 'deactivate.php';
	}

	protected function _get_reasons() {
		$options = array(
			array(
				'text'         => __( 'I have a problem using this plugin', 'ecwid-shopping-cart' ),
				'has_message'  => true,
				'code'         => 'problem',
				'message_hint' => __( 'What was wrong?', 'ecwid-shopping-cart' ),
			),
			array(
				'text'         => sprintf(
					__( 'I couldn’t find a WordPress theme that goes well with %s', 'ecwid-shopping-cart' ),
					Ecwid_Config::get_brand()
				),
				'has_message'  => true,
				'code'         => 'theme',
				'message_hint' => sprintf(
					__( 'I use this WordPress theme: %s', 'ecwid-shopping-cart' ),
					wp_get_theme()->get( 'Name' )
				),
			),
			array(
				'text'         => __( 'The plugin doesn\'t support the feature I want', 'ecwid-shopping-cart' ),
				'has_message'  => true,
				'code'         => 'no feature',
				'message_hint' => __( 'What feature do you need?', 'ecwid-shopping-cart' ),
			),
			array(
				'text'         => __( 'I found a better plugin', 'ecwid-shopping-cart' ),
				'has_message'  => true,
				'code'         => 'found better',
				'message_hint' => __( 'Can you share the name of the plugin you chose?', 'ecwid-shopping-cart' ),
			),
			array(
				'text'               => __( 'It\'s a temporary deactivation. Please do not ask me again.', 'ecwid-shopping-cart' ),
				'has_message'        => false,
				'code'               => 'temporary',
				'is_disable_message' => true,
			),
			array(
				'text'         => __( 'Other', 'ecwid-shopping-cart' ),
				'has_message'  => true,
				'code'         => 'other',
				'message_hint' => __( 'Can you share your feedback? What was wrong?', 'ecwid-shopping-cart' ),
			),
		);

		return $options;
	}
}
