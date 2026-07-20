<?php
/**
 * Checkoutly classic checkout builder admin screen.
 *
 * @package Checkoutly\\CheckoutBuilder
 */

declare(strict_types=1);

namespace Checkoutly\CheckoutBuilder\Admin;

use Checkoutly\CheckoutBuilder\Checkout\Workflow;

defined( 'ABSPATH' ) || exit;

/**
 * Renders and saves the Checkoutly multi-step checkout builder.
 */
final class Builder {
	/**
	 * Workflow repository.
	 *
	 * @var Workflow
	 */
	private $workflow;

	public function __construct( Workflow $workflow ) {
		$this->workflow = $workflow;
	}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_checkoutly_save_builder', array( $this, 'save' ) );
		add_action( 'wp_ajax_checkoutly_save_builder', array( $this, 'ajax_save' ) );
	}

	/**
	 * Enqueue dashboard assets only on the Checkoutly builder screen.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( 'toplevel_page_checkoutly-dashboard' !== $hook_suffix ) {
			return;
		}

		$workflow = $this->workflow->get();

		wp_enqueue_style(
			'checkoutly-admin-builder',
			CHECKOUTLY_PLUGIN_URL . 'assets/css/admin-builder.css',
			array(),
			CHECKOUTLY_VERSION
		);

		wp_enqueue_script(
			'checkoutly-admin-builder',
			CHECKOUTLY_PLUGIN_URL . 'assets/js/admin-builder.js',
			array(),
			CHECKOUTLY_VERSION,
			true
		);

		wp_add_inline_script(
			'checkoutly-admin-builder',
			'window.checkoutlyInitialWorkflow = ' . $this->encode_json( $workflow ) . ';',
			'before'
		);
	}

	/**
	 * Register WooCommerce submenu.
	 */
	public function register_menu(): void {
		add_menu_page(
			__( 'Checkoutly Checkout', 'checkoutly' ),
			__( 'Checkoutly Checkout', 'checkoutly' ),
			'manage_woocommerce',
			'checkoutly-dashboard',
			array( $this, 'render' ),
			'dashicons-feedback',
			56
		);

		add_submenu_page(
			'checkoutly-dashboard',
			__( 'Checkout Builder', 'checkoutly' ),
			__( 'Checkout Builder', 'checkoutly' ),
			'manage_woocommerce',
			'checkoutly-dashboard',
			array( $this, 'render' )
		);
	}

	/**
	 * Save submitted workflow.
	 */
	public function save(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to manage checkout settings.', 'checkoutly' ) );
		}

		check_admin_referer( 'checkoutly_save_builder' );

		$this->save_submitted_workflow();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'checkoutly-dashboard',
					'updated' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Save submitted workflow without reloading the dashboard.
	 */
	public function ajax_save(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Sorry, you are not allowed to manage checkout settings.', 'checkoutly' ),
				),
				403
			);
		}

		check_ajax_referer( 'checkoutly_save_builder' );

		$this->save_submitted_workflow();

		wp_send_json_success(
			array(
				'message' => __( 'Checkoutly checkout builder saved.', 'checkoutly' ),
			)
		);
	}

	/**
	 * Persist the posted workflow payload.
	 */
	private function save_submitted_workflow(): void {
		$raw      = isset( $_POST['checkoutly_workflow'] ) ? wp_check_invalid_utf8( wp_unslash( $_POST['checkoutly_workflow'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified by callers.
		$decoded  = json_decode( (string) $raw, true );
		$workflow = is_array( $decoded ) ? $decoded : $this->workflow->defaults();

		$this->workflow->save( $workflow );
	}

	/**
	 * Render builder dashboard.
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'checkoutly' ) );
		}

		$workflow     = $this->workflow->get();
		$checkout_url = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/' );

		echo '<div class="wrap checkoutly-admin">';
		$this->render_header( $checkout_url );

		$updated = isset( $_GET['updated'] ) ? sanitize_text_field( wp_unslash( $_GET['updated'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- redirect param after verified save.

		if ( '1' === $updated ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Checkoutly checkout builder saved.', 'checkoutly' ) . '</p></div>';
		}

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" id="checkoutly-builder-form">';
		wp_nonce_field( 'checkoutly_save_builder' );
		echo '<input type="hidden" name="action" value="checkoutly_save_builder" />';
		echo '<textarea id="checkoutly-workflow-input" name="checkoutly_workflow" hidden>' . esc_textarea( $this->encode_json( $workflow ) ) . '</textarea>';
		$this->render_global_settings_panel( $workflow );
		echo '<div id="checkoutly-builder-view">';
		echo '<div class="checkoutly-builder-shell">';
		$this->render_component_panel();
		$this->render_steps_panel( $workflow );
		$this->render_settings_panel( $workflow );
		echo '</div>';
		$this->render_footer_stats( $workflow );
		echo '</div>';
		$this->render_field_settings_modal();
		echo '</form>';
		echo '</div>';
	}

	private function render_header( string $checkout_url ): void {
		echo '<div class="checkoutly-topbar">';
		echo '<div class="checkoutly-topbar-main"><div class="checkoutly-brand"><span class="checkoutly-logo">WT</span><h1><strong>Checkoutly</strong> <span>Multi-Step Checkout Builder</span></h1></div>';
		echo '<div class="checkoutly-actions"><a class="button" href="https://checkoutly.com/docs" target="_blank" rel="noreferrer">Docs</a><a class="button" href="' . esc_url( $checkout_url ) . '" target="_blank" rel="noreferrer">Preview Checkout</a><button class="button button-primary" type="submit" form="checkoutly-builder-form">Save Changes</button></div></div>';
		echo '<nav class="checkoutly-tabs"><a class="is-active" data-checkoutly-tab="builder">Classic Builder</a><a class="checkoutly-tab-disabled" aria-disabled="true">Block Builder <span>' . esc_html__( 'Coming Soon', 'checkoutly' ) . '</span></a><a data-checkoutly-tab="settings">Settings</a><a class="checkoutly-tab-disabled" aria-disabled="true">Rules <span>' . esc_html__( 'Coming Soon', 'checkoutly' ) . '</span></a></nav>';
		echo '</div>';
	}

	/**
	 * @param array<string,mixed> $workflow Workflow.
	 */
	private function render_global_settings_panel( array $workflow ): void {
		$orientation = isset( $workflow['orientation'] ) ? (string) $workflow['orientation'] : 'horizontal';
		$indicator = isset( $workflow['indicator'] ) ? (string) $workflow['indicator'] : 'number';
		$step_icon = isset( $workflow['stepIcon'] ) ? (string) $workflow['stepIcon'] : '1';
		$connector = isset( $workflow['connector'] ) ? (string) $workflow['connector'] : 'solid';
		$navigation = isset( $workflow['navigation'] ) ? (string) $workflow['navigation'] : 'line';
		$navigation = 'tabs' === $navigation ? 'line' : $navigation;
		$active_color = isset( $workflow['activeColor'] ) ? (string) $workflow['activeColor'] : '#2563eb';
		$completed_color = isset( $workflow['completedColor'] ) ? (string) $workflow['completedColor'] : '#16a34a';
		$inactive_color = isset( $workflow['inactiveColor'] ) ? (string) $workflow['inactiveColor'] : '#6b7280';
		$multi_step_enabled = ! array_key_exists( 'multiStepEnabled', $workflow ) || ! empty( $workflow['multiStepEnabled'] );
		$connector_thickness = isset( $workflow['connectorThickness'] ) ? (int) $workflow['connectorThickness'] : 2;
		$connector_gap = isset( $workflow['connectorGap'] ) ? (int) $workflow['connectorGap'] : 24;
		$previous_text = isset( $workflow['previousText'] ) ? (string) $workflow['previousText'] : 'Previous';
		$next_text = isset( $workflow['nextText'] ) ? (string) $workflow['nextText'] : 'Next';
		$continue_text = isset( $workflow['continueText'] ) ? (string) $workflow['continueText'] : 'Continue';
		$previous_button_color = isset( $workflow['previousButtonColor'] ) ? (string) $workflow['previousButtonColor'] : '#1f2937';
		$previous_button_background = isset( $workflow['previousButtonBackground'] ) ? (string) $workflow['previousButtonBackground'] : '#f5f6f7';
		$next_button_color = isset( $workflow['nextButtonColor'] ) ? (string) $workflow['nextButtonColor'] : '#ffffff';
		$next_button_background = isset( $workflow['nextButtonBackground'] ) ? (string) $workflow['nextButtonBackground'] : '#2563eb';
		$continue_button_color = isset( $workflow['continueButtonColor'] ) ? (string) $workflow['continueButtonColor'] : '#ffffff';
		$continue_button_background = isset( $workflow['continueButtonBackground'] ) ? (string) $workflow['continueButtonBackground'] : '#16a34a';
		$allow_completed = ! array_key_exists( 'allowCompletedStepNavigation', $workflow ) || ! empty( $workflow['allowCompletedStepNavigation'] );
		$scroll_on_change = ! array_key_exists( 'scrollOnStepChange', $workflow ) || ! empty( $workflow['scrollOnStepChange'] );
		$validate_before_next = ! array_key_exists( 'validateBeforeNext', $workflow ) || ! empty( $workflow['validateBeforeNext'] );
		$remember_step = ! empty( $workflow['rememberStep'] );

		echo '<section class="checkoutly-card checkoutly-global-settings" id="checkoutly-global-settings" hidden>';
		echo '<div class="checkoutly-settings-title"><div><h2>' . esc_html__( 'General Settings', 'checkoutly' ) . '</h2><p>' . esc_html__( 'Control the main checkout behavior and workflow.', 'checkoutly' ) . '</p></div><div class="checkoutly-global-settings__actions"><button type="button" class="button">Import</button><button type="button" class="button">Export</button></div></div>';
		echo '<div class="checkoutly-settings-list">';
		echo '<label class="checkoutly-check-row"><input type="checkbox" id="checkoutly-multistep-enabled" ' . checked( $multi_step_enabled, true, false ) . ' /><span><strong>' . esc_html__( 'Enable Multistep form', 'checkoutly' ) . '</strong><small>' . esc_html__( 'Show customers a guided step-by-step checkout.', 'checkoutly' ) . '</small></span></label>';
		echo '<button type="button" class="button checkoutly-configure-multistep" id="checkoutly-open-multistep-settings" ' . ( $multi_step_enabled ? '' : 'hidden' ) . '>' . esc_html__( 'Configure multistep settings', 'checkoutly' ) . '</button>';
		echo '</div></section>';
		echo '<div class="checkoutly-modal" id="checkoutly-multistep-modal" hidden role="dialog" aria-modal="true" aria-labelledby="checkoutly-multistep-modal-title"><div class="checkoutly-modal__panel checkoutly-modal__panel--settings">';
		echo '<div class="checkoutly-modal__head"><h2 id="checkoutly-multistep-modal-title">' . esc_html__( 'Multistep Settings', 'checkoutly' ) . '</h2><button type="button" class="checkoutly-icon-button" data-close-multistep-settings>×</button></div>';
		echo '<div class="checkoutly-modal__body checkoutly-multistep-controls" id="checkoutly-multistep-controls">';
		echo '<section class="checkoutly-control-card checkoutly-control-steps"><h3>Steps Number</h3><div class="checkoutly-step-count"><button type="button" data-step-count-decrease>-</button><input type="number" id="checkoutly-step-count" min="1" max="3" value="' . esc_attr( (string) count( $workflow['steps'] ) ) . '" /><button type="button" data-step-count-increase>+</button></div><p class="checkoutly-small-note">Free version allows up to 3 steps.</p></section>';
		echo '<section class="checkoutly-control-card checkoutly-control-layout"><h3>Step Layout</h3><div class="checkoutly-choice-cards" data-setting-segment="orientation"><button type="button" data-value="horizontal" class="' . esc_attr( 'horizontal' === $orientation ? 'is-active' : '' ) . '"><span class="checkoutly-mini-icon">o-o-o</span><strong>Horizontal</strong></button><button type="button" data-value="vertical" class="' . esc_attr( 'vertical' === $orientation ? 'is-active' : '' ) . '"><span class="checkoutly-mini-icon">o<br>|<br>o</span><strong>Vertical</strong></button></div></section>';
		echo '<section class="checkoutly-control-card checkoutly-control-navigation"><h3>Navigation Style</h3><div class="checkoutly-choice-cards" data-setting-segment="navigation"><button type="button" data-value="line" class="' . esc_attr( 'line' === $navigation ? 'is-active' : '' ) . '"><strong>Line</strong></button><button type="button" data-value="buttons" class="' . esc_attr( 'buttons' === $navigation ? 'is-active' : '' ) . '"><strong>Button</strong></button></div></section>';
		echo '<section class="checkoutly-control-card checkoutly-line-setting checkoutly-control-indicator"><h3>Step Indicator</h3><label>Type<select id="checkoutly-indicator-select"><option value="number" ' . selected( $indicator, 'number', false ) . '>Number</option><option value="icon" ' . selected( $indicator, 'icon', false ) . '>Icon</option></select></label><p>Icon</p><div class="checkoutly-icon-picker" data-setting-segment="stepIcon"><button type="button" data-value="1" class="' . esc_attr( '1' === $step_icon ? 'is-active' : '' ) . '">1</button><button type="button" data-value="user" class="' . esc_attr( 'user' === $step_icon ? 'is-active' : '' ) . '">♙</button><button type="button" data-value="flag" class="' . esc_attr( 'flag' === $step_icon ? 'is-active' : '' ) . '">⚑</button><button type="button" data-value="star" class="' . esc_attr( 'star' === $step_icon ? 'is-active' : '' ) . '">☆</button><button type="button" data-value="check" class="' . esc_attr( 'check' === $step_icon ? 'is-active' : '' ) . '">✓</button></div></section>';
		echo '<section class="checkoutly-control-card checkoutly-line-setting checkoutly-control-connector"><h3>Connector</h3><label>Style<select id="checkoutly-connector"><option value="solid" ' . selected( $connector, 'solid', false ) . '>Solid Line</option><option value="line" ' . selected( $connector, 'line', false ) . '>Thin Line</option><option value="arrow" ' . selected( $connector, 'arrow', false ) . '>Arrow</option><option value="none" ' . selected( $connector, 'none', false ) . '>None</option></select></label><label>Thickness <span><input type="range" id="checkoutly-connector-thickness" min="1" max="8" value="' . esc_attr( (string) $connector_thickness ) . '" /> <output id="checkoutly-connector-thickness-output">' . esc_html( (string) $connector_thickness ) . 'px</output></span></label><label>Gap <span><input type="range" id="checkoutly-connector-gap" min="8" max="64" value="' . esc_attr( (string) $connector_gap ) . '" /> <output id="checkoutly-connector-gap-output">' . esc_html( (string) $connector_gap ) . 'px</output></span></label></section>';
		echo '<section class="checkoutly-control-card checkoutly-control-colors"><h3>Colors</h3><label>Active Color <input type="color" id="checkoutly-active-color" value="' . esc_attr( $active_color ) . '" /></label><label>Completed Color <input type="color" id="checkoutly-completed-color" value="' . esc_attr( $completed_color ) . '" /></label><label>Inactive Color <input type="color" id="checkoutly-inactive-color" value="' . esc_attr( $inactive_color ) . '" /></label></section>';
		echo '<section class="checkoutly-control-card checkoutly-control-buttons"><h3>Navigation Buttons</h3><div class="checkoutly-button-config-grid"><div><h4>Previous Button</h4><label>Text <input type="text" id="checkoutly-previous-text" value="' . esc_attr( $previous_text ) . '" /></label><label>Color <input type="color" id="checkoutly-previous-button-color" value="' . esc_attr( $previous_button_color ) . '" /></label><label>Background <input type="color" id="checkoutly-previous-button-bg" value="' . esc_attr( $previous_button_background ) . '" /></label></div><div><h4>Next Button</h4><label>Text <input type="text" id="checkoutly-next-text" value="' . esc_attr( $next_text ) . '" /></label><label>Color <input type="color" id="checkoutly-next-button-color" value="' . esc_attr( $next_button_color ) . '" /></label><label>Background <input type="color" id="checkoutly-next-button-bg" value="' . esc_attr( $next_button_background ) . '" /></label></div><div><h4>Continue Button</h4><label>Text <input type="text" id="checkoutly-continue-text" value="' . esc_attr( $continue_text ) . '" /></label><label>Color <input type="color" id="checkoutly-continue-button-color" value="' . esc_attr( $continue_button_color ) . '" /></label><label>Background <input type="color" id="checkoutly-continue-button-bg" value="' . esc_attr( $continue_button_background ) . '" /></label></div></div></section>';
		echo '<section><h3>Step Behavior</h3><label class="checkoutly-switch-row"><span><strong>Allow clicking completed steps</strong><small>Users can go back to previous completed steps.</small></span><label class="checkoutly-switch"><input type="checkbox" id="checkoutly-allow-completed" ' . checked( $allow_completed, true, false ) . ' /><span></span></label></label><label class="checkoutly-switch-row"><span><strong>Scroll to top on step change</strong><small>Automatically scroll to the checkout stepper.</small></span><label class="checkoutly-switch"><input type="checkbox" id="checkoutly-scroll-on-change" ' . checked( $scroll_on_change, true, false ) . ' /><span></span></label></label><label class="checkoutly-switch-row"><span><strong>Validate before next step</strong><small>Prevent moving forward if current fields are invalid.</small></span><label class="checkoutly-switch"><input type="checkbox" id="checkoutly-validate-before-next" ' . checked( $validate_before_next, true, false ) . ' /><span></span></label></label><label class="checkoutly-switch-row"><span><strong>Remember step on refresh</strong><small>Restore the last active step.</small></span><label class="checkoutly-switch"><input type="checkbox" id="checkoutly-remember-step" ' . checked( $remember_step, true, false ) . ' /><span></span></label></label></section>';
		echo '</div><div class="checkoutly-modal__foot"><button type="button" class="button" data-close-multistep-settings>' . esc_html__( 'Close', 'checkoutly' ) . '</button></div></div></div>';
	}

	private function render_component_panel(): void {
		$basic = array(
			array( 'text', 'Text' ),
			array( 'email', 'Email' ),
			array( 'tel', 'Phone' ),
			array( 'number', 'Number' ),
			array( 'textarea', 'Textarea' ),
			array( 'select', 'Select' ),
			array( 'radio', 'Radio' ),
			array( 'checkbox', 'Checkbox' ),
		);
		$customer = array(
			array( 'billing', 'billing_first_name', 'First Name', true ),
			array( 'billing', 'billing_last_name', 'Last Name', true ),
			array( 'billing', 'billing_email', 'Email', true ),
			array( 'billing', 'billing_phone', 'Phone Number', false ),
			array( 'billing', 'billing_company', 'Company', false ),
		);
		$billing = array(
			array( 'billing', 'billing_country', 'Billing country / region', true ),
			array( 'billing', 'billing_address_1', 'Billing street address', true ),
			array( 'billing', 'billing_address_2', 'Billing apartment, suite, unit, etc.', false ),
			array( 'billing', 'billing_city', 'Billing town / city', true ),
			array( 'billing', 'billing_state', 'Billing state / county', true ),
			array( 'billing', 'billing_postcode', 'Billing postcode / ZIP', true ),
		);
		$shipping = array(
			array( 'shipping', 'shipping_first_name', 'Shipping first name', true ),
			array( 'shipping', 'shipping_last_name', 'Shipping last name', true ),
			array( 'shipping', 'shipping_company', 'Shipping company', false ),
			array( 'shipping', 'shipping_country', 'Shipping country / region', true ),
			array( 'shipping', 'shipping_address_1', 'Shipping street address', true ),
			array( 'shipping', 'shipping_address_2', 'Shipping apartment, suite, unit, etc.', false ),
			array( 'shipping', 'shipping_city', 'Shipping town / city', true ),
			array( 'shipping', 'shipping_state', 'Shipping state / county', true ),
			array( 'shipping', 'shipping_postcode', 'Shipping postcode / ZIP', true ),
		);
		$order = array(
			array( 'order', 'order_comments', 'Additional information / Order notes', false ),
		);
		$components = array(
			array( 'order_payment', 'Order & Payment' ),
		);

		echo '<aside class="checkoutly-card checkoutly-components"><h2>Add Components</h2><input class="checkoutly-search" type="search" placeholder="Search components..." />';
		echo '<details class="checkoutly-component-accordion" open><summary>Basic Fields</summary><div class="checkoutly-component-grid">';
		foreach ( $basic as $item ) {
			printf( '<button type="button" draggable="true" class="checkoutly-component" data-add-field="%s">%s</button>', esc_attr( $item[0] ), esc_html( $item[1] ) );
		}
		echo '</div></details><h3>Advanced Fields <span>Coming Soon</span></h3><div class="checkoutly-component-grid is-disabled"><button type="button">Date Picker</button><button type="button">File Upload</button><button type="button">Signature</button><button type="button">Multi Select</button></div>';
		echo '<details class="checkoutly-component-accordion" open><summary>WooCommerce Fields</summary>';
		$this->render_woocommerce_field_group( __( 'Customer Information Fields', 'checkoutly' ), $customer );
		$this->render_woocommerce_field_group( __( 'Billing Fields', 'checkoutly' ), $billing );
		$this->render_woocommerce_field_group( __( 'Shipping Fields', 'checkoutly' ), $shipping );
		$this->render_woocommerce_field_group( __( 'Order Fields', 'checkoutly' ), $order );
		echo '<h4 class="checkoutly-component-group-title">' . esc_html__( 'Order & Payment', 'checkoutly' ) . '</h4><div class="checkoutly-component-grid">';
		foreach ( $components as $item ) {
			printf( '<button type="button" draggable="true" class="checkoutly-component" data-woo-component="%s">%s</button>', esc_attr( $item[0] ), esc_html( $item[1] ) );
		}
		echo '</div></details><p class="checkoutly-hint">Drag components to the steps on the canvas.</p></aside>';
	}

	/**
	 * Render one grouped set of WooCommerce field palette items.
	 *
	 * @param string              $title Group title.
	 * @param array<int,array{0:string,1:string,2:string,3:bool}> $fields Field data.
	 */
	private function render_woocommerce_field_group( string $title, array $fields ): void {
		echo '<h4 class="checkoutly-component-group-title">' . esc_html( $title ) . '</h4><div class="checkoutly-component-grid">';

		foreach ( $fields as $item ) {
			printf(
				'<button type="button" draggable="true" class="checkoutly-component" data-woo-field="%1$s" data-section="%2$s" data-required="%3$s">%4$s</button>',
				esc_attr( $item[1] ),
				esc_attr( $item[0] ),
				esc_attr( $item[3] ? '1' : '0' ),
				esc_html( $item[2] )
			);
		}

		echo '</div>';
	}

	/**
	 * @param array<string,mixed> $workflow Workflow.
	 */
	private function render_steps_panel( array $workflow ): void {
		echo '<main class="checkoutly-card checkoutly-canvas"><div class="checkoutly-canvas-head"><h2>Checkout Steps <span>' . esc_html( count( $workflow['steps'] ) ) . ' / 3 Free</span></h2><div class="checkoutly-bulk-actions" aria-label="' . esc_attr__( 'Bulk field actions', 'checkoutly' ) . '"><button type="button" class="button checkoutly-bulk-remove" data-bulk-remove disabled>' . esc_html__( 'Remove', 'checkoutly' ) . '</button><button type="button" class="button" data-bulk-show disabled>' . esc_html__( 'Show', 'checkoutly' ) . '</button><button type="button" class="button" data-bulk-hide disabled>' . esc_html__( 'Hide', 'checkoutly' ) . '</button></div></div>';
		echo '<div id="checkoutly-steps">';

		foreach ( $workflow['steps'] as $index => $step ) {
			$this->render_step( $step, $index );
		}

		echo '</div><button type="button" class="checkoutly-add-step" id="checkoutly-add-step">+ Add Step<br><small>You can add up to 3 steps.</small></button></main>';
	}

	/**
	 * @param array<string,mixed> $step Step.
	 */
	private function render_step( array $step, int $index ): void {
		$style = $this->step_style( $step );

		echo '<section class="checkoutly-step" draggable="true" data-step-index="' . esc_attr( (string) $index ) . '" data-step-style="' . esc_attr( $this->encode_json( $style ) ) . '" style="' . esc_attr( $this->step_style_attribute( $step, $style ) ) . '">';
		echo '<div class="checkoutly-step-head"><span class="checkoutly-drag" title="' . esc_attr__( 'Drag step', 'checkoutly' ) . '" aria-hidden="true"></span><span class="checkoutly-badge">' . esc_html( (string) ( $index + 1 ) ) . '</span><div><input class="checkoutly-step-title" value="' . esc_attr( $step['title'] ) . '" /><input class="checkoutly-step-description" value="' . esc_attr( $step['description'] ) . '" /></div><button type="button" class="checkoutly-collapse" aria-expanded="true" title="' . esc_attr__( 'Collapse step', 'checkoutly' ) . '"><span aria-hidden="true"></span></button></div>';
		echo '<div class="checkoutly-field-list" data-step-fields>';

		foreach ( $step['fields'] as $field ) {
			$this->render_field_card( $field );
		}

		echo '</div></section>';
	}

	/**
	 * @param array<string,mixed> $field Field.
	 */
	private function render_field_card( array $field ): void {
		$classes = 'checkoutly-field-card';
		$width   = isset( $field['width'] ) ? (int) $field['width'] : 2;

		if ( empty( $field['enabled'] ) ) {
			$classes .= ' is-disabled';
		}

		$classes .= ' checkoutly-width-' . ( in_array( $width, array( 1, 2 ), true ) ? $width : 2 );
		$is_native = 'native' === ( $field['type'] ?? '' );
		$is_custom = 'custom' === ( $field['type'] ?? '' );
		unset( $is_native );

		printf(
			'<div class="%1$s" draggable="true" data-field="%2$s"><span class="checkoutly-field-handle" title="%3$s" aria-hidden="true"></span><input type="checkbox" class="checkoutly-field-select" aria-label="%10$s" /><strong>%4$s</strong><span class="checkoutly-field-actions"><button type="button" class="checkoutly-field-settings-button" data-open-field-settings title="%5$s">⚙</button><button type="button" class="checkoutly-icon-button" data-duplicate-field %6$s title="%7$s">⧉</button><button type="button" class="checkoutly-icon-button checkoutly-danger" data-remove-field %8$s title="%9$s">×</button></span></div>',
			esc_attr( $classes ),
			esc_attr( $this->encode_json( $field ) ),
			esc_attr__( 'Drag field', 'checkoutly' ),
			esc_html( $field['label'] ),
			esc_attr__( 'Field settings', 'checkoutly' ),
			$is_custom ? '' : 'disabled',
			esc_attr__( 'Duplicate field', 'checkoutly' ),
			'',
			esc_attr__( 'Remove field', 'checkoutly' ),
			esc_attr(
				sprintf(
					/* translators: %s: Field label. */
					__( 'Select %s for bulk actions', 'checkoutly' ),
					(string) $field['label']
				)
			)
		);
	}

	/**
	 * Render field settings modal.
	 */
	private function render_field_settings_modal(): void {
		echo '<div class="checkoutly-modal" id="checkoutly-field-modal" hidden role="dialog" aria-modal="true" aria-labelledby="checkoutly-field-modal-title">';
		echo '<div class="checkoutly-modal__panel">';
		echo '<div class="checkoutly-modal__head"><h2 id="checkoutly-field-modal-title">' . esc_html__( 'Edit Field', 'checkoutly' ) . '</h2><button type="button" class="checkoutly-icon-button" data-close-field-settings>×</button></div>';
		echo '<div class="checkoutly-modal__body">';
		echo '<div class="checkoutly-modal-grid"><label>' . esc_html__( 'Field Type', 'checkoutly' ) . '<select id="checkoutly-modal-field-type"><option value="text">Text</option><option value="email">Email</option><option value="tel">Phone</option><option value="number">Number</option><option value="textarea">Textarea</option><option value="select">Select</option><option value="radio">Radio</option><option value="checkbox">Checkbox</option><option value="component">Component</option></select></label>';
		echo '<label>' . esc_html__( 'Step', 'checkoutly' ) . '<select id="checkoutly-modal-step"></select></label></div>';
		echo '<div class="checkoutly-modal-grid"><label>' . esc_html__( 'Field Label', 'checkoutly' ) . '<input type="text" id="checkoutly-modal-label" /></label>';
		echo '<label>' . esc_html__( 'Field Key / Name', 'checkoutly' ) . '<input type="text" id="checkoutly-modal-key" /></label></div>';
		echo '<label>' . esc_html__( 'Default Value', 'checkoutly' ) . '<input type="text" id="checkoutly-modal-default" placeholder="e.g. ABC" /></label>';
		echo '<label>' . esc_html__( 'Placeholder Text', 'checkoutly' ) . '<input type="text" id="checkoutly-modal-placeholder" placeholder="e.g. Enter your company name" /></label>';
		echo '<div class="checkoutly-option-editor"><strong>' . esc_html__( 'Options', 'checkoutly' ) . '</strong><div id="checkoutly-modal-options"></div><button type="button" class="button checkoutly-add-option" data-add-option>+ ' . esc_html__( 'Add Option', 'checkoutly' ) . '</button></div>';
		echo '<label>' . esc_html__( 'Validation', 'checkoutly' ) . '<select id="checkoutly-modal-validation"><option value="">None</option><option value="email">Email</option><option value="phone">Phone</option><option value="postcode">Postcode</option></select></label>';
		echo '<label>' . esc_html__( 'Field Width', 'checkoutly' ) . '<select id="checkoutly-modal-width"><option value="1">Half width - 1/2</option><option value="2">Full width - 2/2</option></select></label>';
		echo '<div class="checkoutly-toggle-row"><span><strong>' . esc_html__( 'Required field', 'checkoutly' ) . '</strong><small>' . esc_html__( 'Customer must fill this out to complete checkout', 'checkoutly' ) . '</small></span><label class="checkoutly-switch"><input type="checkbox" id="checkoutly-modal-required" /><span></span></label></div>';
		echo '<div class="checkoutly-toggle-row"><span><strong>' . esc_html__( 'Enable field', 'checkoutly' ) . '</strong><small>' . esc_html__( 'Show this field on the checkout page', 'checkoutly' ) . '</small></span><label class="checkoutly-switch"><input type="checkbox" id="checkoutly-modal-enabled" /><span></span></label></div>';
		echo '<div class="checkoutly-toggle-row"><span><strong>' . esc_html__( 'Display in order details', 'checkoutly' ) . '</strong><small>' . esc_html__( 'Show this field value in order details', 'checkoutly' ) . '</small></span><label class="checkoutly-switch"><input type="checkbox" id="checkoutly-modal-display-order" /><span></span></label></div>';
		echo '<div class="checkoutly-toggle-row"><span><strong>' . esc_html__( 'Display in emails', 'checkoutly' ) . '</strong><small>' . esc_html__( 'Include this field value in order emails', 'checkoutly' ) . '</small></span><label class="checkoutly-switch"><input type="checkbox" id="checkoutly-modal-display-emails" /><span></span></label></div>';
		echo '<div class="checkoutly-toggle-row"><span><strong>' . esc_html__( 'Display in Thank you page', 'checkoutly' ) . '</strong><small>' . esc_html__( 'Show this field after checkout is complete', 'checkoutly' ) . '</small></span><label class="checkoutly-switch"><input type="checkbox" id="checkoutly-modal-display-thank-you" /><span></span></label></div>';
		echo '<p class="checkoutly-native-lock-note" id="checkoutly-native-lock-note" hidden>' . esc_html__( 'Native WooCommerce field keys and types are fixed, but you can remove them from a step and drag them back from the left panel.', 'checkoutly' ) . '</p>';
		echo '</div>';
		echo '<div class="checkoutly-modal__foot"><button type="button" class="button" data-close-field-settings>' . esc_html__( 'Cancel', 'checkoutly' ) . '</button><button type="button" class="button button-primary" id="checkoutly-apply-field-settings">' . esc_html__( 'Apply Settings', 'checkoutly' ) . '</button></div>';
		echo '</div></div>';
	}

	/**
	 * @param array<string,mixed> $workflow Workflow.
	 */
	private function render_settings_panel( array $workflow ): void {
		$first_step = isset( $workflow['steps'][0] ) ? $workflow['steps'][0] : array();
		$style      = $this->step_style( $first_step );

		echo '<aside class="checkoutly-card checkoutly-settings"><h2>' . esc_html__( 'Step Settings', 'checkoutly' ) . '</h2><select id="checkoutly-step-settings-select">';
		foreach ( $workflow['steps'] as $index => $step ) {
			printf( '<option value="%1$s">%2$s</option>', esc_attr( (string) $index ), esc_html( $step['title'] ?? 				sprintf(
					/* translators: %d: Step number. */
					__( 'Step %d', 'checkoutly' ),
					$index + 1
				) ) );
		}
		echo '</select><div class="checkoutly-step-style-controls">';
		echo '<label>' . esc_html__( 'Step Title Color', 'checkoutly' ) . '<input type="color" id="checkoutly-step-title-color" value="' . esc_attr( $style['titleColor'] ) . '" /></label>';
		echo '<label>' . esc_html__( 'Step Background Color', 'checkoutly' ) . '<input type="color" id="checkoutly-step-background-color" value="' . esc_attr( $style['backgroundColor'] ) . '" /></label>';
		echo '<label>' . esc_html__( 'Step Border Style', 'checkoutly' ) . '<select id="checkoutly-step-border-style"><option value="solid">Solid</option><option value="dashed">Dashed</option><option value="dotted">Dotted</option><option value="none">None</option></select></label>';
		echo '<label>' . esc_html__( 'Step Border Width', 'checkoutly' ) . '<input type="number" id="checkoutly-step-border-width" min="0" max="12" value="' . esc_attr( (string) $style['borderWidth'] ) . '" /></label>';
		echo '<label>' . esc_html__( 'Step Border Radius', 'checkoutly' ) . '<input type="number" id="checkoutly-step-border-radius" min="0" max="40" value="' . esc_attr( (string) $style['borderRadius'] ) . '" /></label>';
		echo '<label>' . esc_html__( 'Step Border Color', 'checkoutly' ) . '<input type="color" id="checkoutly-step-border-color" value="' . esc_attr( $style['borderColor'] ) . '" /></label>';
		echo '<label>' . esc_html__( 'Step Padding', 'checkoutly' ) . '<input type="number" id="checkoutly-step-padding" min="0" max="80" value="' . esc_attr( (string) $style['padding'] ) . '" /></label>';
		echo '<label>' . esc_html__( 'Step Margin', 'checkoutly' ) . '<input type="number" id="checkoutly-step-margin" min="0" max="80" value="' . esc_attr( (string) $style['margin'] ) . '" /></label>';
		echo '</div><button type="button" class="button checkoutly-delete-step" id="checkoutly-remove-selected-step">' . esc_html__( 'Remove Step', 'checkoutly' ) . '</button></aside>';
	}

	/**
	 * @param array<string,mixed> $step Step.
	 * @return array<string,mixed>
	 */
	private function step_style( array $step ): array {
		$style = isset( $step['style'] ) && is_array( $step['style'] ) ? $step['style'] : array();
		$color = isset( $step['color'] ) ? (string) $step['color'] : '#2563eb';

		return array(
			'titleColor'      => isset( $style['titleColor'] ) ? (string) $style['titleColor'] : '#111827',
			'backgroundColor' => isset( $style['backgroundColor'] ) ? (string) $style['backgroundColor'] : '#ffffff',
			'borderStyle'     => isset( $style['borderStyle'] ) ? (string) $style['borderStyle'] : 'solid',
			'borderWidth'     => isset( $style['borderWidth'] ) ? (int) $style['borderWidth'] : 2,
			'borderRadius'    => isset( $style['borderRadius'] ) ? (int) $style['borderRadius'] : 10,
			'borderColor'     => isset( $style['borderColor'] ) ? (string) $style['borderColor'] : $color,
			'padding'         => isset( $style['padding'] ) ? (int) $style['padding'] : 14,
			'margin'          => isset( $style['margin'] ) ? (int) $style['margin'] : 14,
		);
	}

	/**
	 * @param array<string,mixed> $step Step.
	 * @param array<string,mixed> $style Step style.
	 */
	private function step_style_attribute( array $step, array $style ): string {
		$color = isset( $step['color'] ) ? (string) $step['color'] : '#2563eb';

		return sprintf(
			'--step-color:%1$s;--checkoutly-step-title-color:%2$s;--checkoutly-step-bg:%3$s;--checkoutly-step-border-style:%4$s;--checkoutly-step-border-width:%5$dpx;--checkoutly-step-radius:%6$dpx;--checkoutly-step-border-color:%7$s;--checkoutly-step-padding:%8$dpx;--checkoutly-step-margin:%9$dpx;',
			$color,
			(string) $style['titleColor'],
			(string) $style['backgroundColor'],
			(string) $style['borderStyle'],
			(int) $style['borderWidth'],
			(int) $style['borderRadius'],
			(string) $style['borderColor'],
			(int) $style['padding'],
			(int) $style['margin']
		);
	}

	/**
	 * @param array<string,mixed> $workflow Workflow.
	 */
	private function render_footer_stats( array $workflow ): void {
		$count = 0;

		foreach ( $workflow['steps'] as $step ) {
			$count += count( $step['fields'] );
		}

		echo '<div class="checkoutly-card checkoutly-stats"><div><strong>Checkout Flow</strong><span>Cart → Checkout → Thank You</span></div><div><strong>Steps Limit</strong><span>' . esc_html( count( $workflow['steps'] ) ) . ' / 3 used</span></div><div><strong>Fields Count</strong><span>' . esc_html( (string) $count ) . ' fields added</span></div><div><strong>Last Saved</strong><span>Just now</span></div></div>';
	}

	/**
	 * Encode data for safe reuse in HTML attributes, textareas, and inline scripts.
	 *
	 * @param array<string,mixed> $data Data to encode.
	 */
	private function encode_json( array $data ): string {
		$encoded = wp_json_encode( $data );

		return is_string( $encoded ) ? $encoded : '{}';
	}
}
