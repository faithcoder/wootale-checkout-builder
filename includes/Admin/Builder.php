<?php
/**
 * WooTale classic checkout builder admin screen.
 *
 * @package WooTale\CheckoutBuilder
 */

declare(strict_types=1);

namespace WooTale\CheckoutBuilder\Admin;

use WooTale\CheckoutBuilder\Checkout\Workflow;

defined( 'ABSPATH' ) || exit;

/**
 * Renders and saves the WooTale dashboard-style checkout builder.
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
		add_action( 'admin_post_wtcb_save_builder', array( $this, 'save' ) );
		add_action( 'wp_ajax_wtcb_save_builder', array( $this, 'ajax_save' ) );
	}

	/**
	 * Enqueue dashboard assets only on the WooTale builder screen.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( 'toplevel_page_wtcb-dashboard' !== $hook_suffix ) {
			return;
		}

		$workflow = $this->workflow->get();

		wp_enqueue_style(
			'wtcb-admin-builder',
			WTCB_PLUGIN_URL . 'assets/css/admin-builder.css',
			array(),
			WTCB_VERSION
		);

		wp_enqueue_script(
			'wtcb-admin-builder',
			WTCB_PLUGIN_URL . 'assets/js/admin-builder.js',
			array(),
			WTCB_VERSION,
			true
		);

		wp_add_inline_script(
			'wtcb-admin-builder',
			'window.wtcbInitialWorkflow = ' . $this->encode_json( $workflow ) . ';',
			'before'
		);
	}

	/**
	 * Register WooCommerce submenu.
	 */
	public function register_menu(): void {
		add_menu_page(
			__( 'WooTale Checkout', 'wootale-checkout-builder' ),
			__( 'WooTale Checkout', 'wootale-checkout-builder' ),
			'manage_woocommerce',
			'wtcb-dashboard',
			array( $this, 'render' ),
			'dashicons-feedback',
			56
		);

		add_submenu_page(
			'wtcb-dashboard',
			__( 'Checkout Builder', 'wootale-checkout-builder' ),
			__( 'Checkout Builder', 'wootale-checkout-builder' ),
			'manage_woocommerce',
			'wtcb-dashboard',
			array( $this, 'render' )
		);
	}

	/**
	 * Save submitted workflow.
	 */
	public function save(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to manage checkout settings.', 'wootale-checkout-builder' ) );
		}

		check_admin_referer( 'wtcb_save_builder' );

		$this->save_submitted_workflow();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'wtcb-dashboard',
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
					'message' => __( 'Sorry, you are not allowed to manage checkout settings.', 'wootale-checkout-builder' ),
				),
				403
			);
		}

		check_ajax_referer( 'wtcb_save_builder' );

		$this->save_submitted_workflow();

		wp_send_json_success(
			array(
				'message' => __( 'WooTale checkout builder saved.', 'wootale-checkout-builder' ),
			)
		);
	}

	/**
	 * Persist the posted workflow payload.
	 */
	private function save_submitted_workflow(): void {
		$raw      = isset( $_POST['wtcb_workflow'] ) ? wp_unslash( $_POST['wtcb_workflow'] ) : '';
		$decoded  = json_decode( (string) $raw, true );
		$workflow = is_array( $decoded ) ? $decoded : $this->workflow->defaults();

		$this->workflow->save( $workflow );
	}

	/**
	 * Render builder dashboard.
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'wootale-checkout-builder' ) );
		}

		$workflow     = $this->workflow->get();
		$checkout_url = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/' );

		echo '<div class="wrap wtcb-admin">';
		$this->render_header( $checkout_url );

		$updated = isset( $_GET['updated'] ) ? sanitize_text_field( wp_unslash( $_GET['updated'] ) ) : '';

		if ( '1' === $updated ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'WooTale checkout builder saved.', 'wootale-checkout-builder' ) . '</p></div>';
		}

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" id="wtcb-builder-form">';
		wp_nonce_field( 'wtcb_save_builder' );
		echo '<input type="hidden" name="action" value="wtcb_save_builder" />';
		echo '<textarea id="wtcb-workflow-input" name="wtcb_workflow" hidden>' . esc_textarea( $this->encode_json( $workflow ) ) . '</textarea>';
		$this->render_global_settings_panel( $workflow );
		echo '<div id="wtcb-builder-view">';
		echo '<div class="wtcb-builder-shell">';
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
		echo '<div class="wtcb-topbar">';
		echo '<div class="wtcb-topbar-main"><div class="wtcb-brand"><span class="wtcb-logo">WT</span><h1><strong>WooTale</strong> <span>Checkout Builder</span></h1></div>';
		echo '<div class="wtcb-actions"><a class="button" href="https://wootale.com/docs" target="_blank" rel="noreferrer">Docs</a><a class="button" href="' . esc_url( $checkout_url ) . '" target="_blank" rel="noreferrer">Preview Checkout</a><button class="button button-primary" type="submit" form="wtcb-builder-form">Save Changes</button></div></div>';
		echo '<nav class="wtcb-tabs"><a class="is-active" data-wtcb-tab="builder">Classic Builder</a><a>Block Builder</a><a>Fields</a><a data-wtcb-tab="settings">Settings</a><a>Rules</a><a>Analytics <span>Pro</span></a><a>Extensions <span>Pro</span></a></nav>';
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

		echo '<section class="wtcb-card wtcb-global-settings" id="wtcb-global-settings" hidden>';
		echo '<div class="wtcb-settings-title"><div><h2>' . esc_html__( 'General Settings', 'wootale-checkout-builder' ) . '</h2><p>' . esc_html__( 'Control the main checkout behavior and workflow.', 'wootale-checkout-builder' ) . '</p></div><div class="wtcb-global-settings__actions"><button type="button" class="button">Import</button><button type="button" class="button">Export</button></div></div>';
		echo '<div class="wtcb-settings-list">';
		echo '<label class="wtcb-check-row"><input type="checkbox" id="wtcb-multistep-enabled" ' . checked( $multi_step_enabled, true, false ) . ' /><span><strong>' . esc_html__( 'Enable Multistep form', 'wootale-checkout-builder' ) . '</strong><small>' . esc_html__( 'Show customers a guided step-by-step checkout.', 'wootale-checkout-builder' ) . '</small></span></label>';
		echo '<button type="button" class="button wtcb-configure-multistep" id="wtcb-open-multistep-settings" ' . ( $multi_step_enabled ? '' : 'hidden' ) . '>' . esc_html__( 'Configure multistep settings', 'wootale-checkout-builder' ) . '</button>';
		echo '</div></section>';
		echo '<div class="wtcb-modal" id="wtcb-multistep-modal" hidden role="dialog" aria-modal="true" aria-labelledby="wtcb-multistep-modal-title"><div class="wtcb-modal__panel wtcb-modal__panel--settings">';
		echo '<div class="wtcb-modal__head"><h2 id="wtcb-multistep-modal-title">' . esc_html__( 'Multistep Settings', 'wootale-checkout-builder' ) . '</h2><button type="button" class="wtcb-icon-button" data-close-multistep-settings>×</button></div>';
		echo '<div class="wtcb-modal__body wtcb-multistep-controls" id="wtcb-multistep-controls">';
		echo '<section class="wtcb-control-card wtcb-control-steps"><h3>Steps Number</h3><div class="wtcb-step-count"><button type="button" data-step-count-decrease>-</button><input type="number" id="wtcb-step-count" min="1" max="3" value="' . esc_attr( (string) count( $workflow['steps'] ) ) . '" /><button type="button" data-step-count-increase>+</button></div><p class="wtcb-small-note">Free version allows up to 3 steps.</p></section>';
		echo '<section class="wtcb-control-card wtcb-control-layout"><h3>Step Layout</h3><div class="wtcb-choice-cards" data-setting-segment="orientation"><button type="button" data-value="horizontal" class="' . esc_attr( 'horizontal' === $orientation ? 'is-active' : '' ) . '"><span class="wtcb-mini-icon">o-o-o</span><strong>Horizontal</strong></button><button type="button" data-value="vertical" class="' . esc_attr( 'vertical' === $orientation ? 'is-active' : '' ) . '"><span class="wtcb-mini-icon">o<br>|<br>o</span><strong>Vertical</strong></button></div></section>';
		echo '<section class="wtcb-control-card wtcb-control-navigation"><h3>Navigation Style</h3><div class="wtcb-choice-cards" data-setting-segment="navigation"><button type="button" data-value="line" class="' . esc_attr( 'line' === $navigation ? 'is-active' : '' ) . '"><strong>Line</strong></button><button type="button" data-value="buttons" class="' . esc_attr( 'buttons' === $navigation ? 'is-active' : '' ) . '"><strong>Button</strong></button></div></section>';
		echo '<section class="wtcb-control-card wtcb-line-setting wtcb-control-indicator"><h3>Step Indicator</h3><label>Type<select id="wtcb-indicator-select"><option value="number" ' . selected( $indicator, 'number', false ) . '>Number</option><option value="icon" ' . selected( $indicator, 'icon', false ) . '>Icon</option></select></label><p>Icon</p><div class="wtcb-icon-picker" data-setting-segment="stepIcon"><button type="button" data-value="1" class="' . esc_attr( '1' === $step_icon ? 'is-active' : '' ) . '">1</button><button type="button" data-value="user" class="' . esc_attr( 'user' === $step_icon ? 'is-active' : '' ) . '">♙</button><button type="button" data-value="flag" class="' . esc_attr( 'flag' === $step_icon ? 'is-active' : '' ) . '">⚑</button><button type="button" data-value="star" class="' . esc_attr( 'star' === $step_icon ? 'is-active' : '' ) . '">☆</button><button type="button" data-value="check" class="' . esc_attr( 'check' === $step_icon ? 'is-active' : '' ) . '">✓</button></div></section>';
		echo '<section class="wtcb-control-card wtcb-line-setting wtcb-control-connector"><h3>Connector</h3><label>Style<select id="wtcb-connector"><option value="solid" ' . selected( $connector, 'solid', false ) . '>Solid Line</option><option value="line" ' . selected( $connector, 'line', false ) . '>Thin Line</option><option value="arrow" ' . selected( $connector, 'arrow', false ) . '>Arrow</option><option value="none" ' . selected( $connector, 'none', false ) . '>None</option></select></label><label>Thickness <span><input type="range" id="wtcb-connector-thickness" min="1" max="8" value="' . esc_attr( (string) $connector_thickness ) . '" /> <output id="wtcb-connector-thickness-output">' . esc_html( (string) $connector_thickness ) . 'px</output></span></label><label>Gap <span><input type="range" id="wtcb-connector-gap" min="8" max="64" value="' . esc_attr( (string) $connector_gap ) . '" /> <output id="wtcb-connector-gap-output">' . esc_html( (string) $connector_gap ) . 'px</output></span></label></section>';
		echo '<section class="wtcb-control-card wtcb-control-colors"><h3>Colors</h3><label>Active Color <input type="color" id="wtcb-active-color" value="' . esc_attr( $active_color ) . '" /></label><label>Completed Color <input type="color" id="wtcb-completed-color" value="' . esc_attr( $completed_color ) . '" /></label><label>Inactive Color <input type="color" id="wtcb-inactive-color" value="' . esc_attr( $inactive_color ) . '" /></label></section>';
		echo '<section class="wtcb-control-card wtcb-control-buttons"><h3>Navigation Buttons</h3><div class="wtcb-button-config-grid"><div><h4>Previous Button</h4><label>Text <input type="text" id="wtcb-previous-text" value="' . esc_attr( $previous_text ) . '" /></label><label>Color <input type="color" id="wtcb-previous-button-color" value="' . esc_attr( $previous_button_color ) . '" /></label><label>Background <input type="color" id="wtcb-previous-button-bg" value="' . esc_attr( $previous_button_background ) . '" /></label></div><div><h4>Next Button</h4><label>Text <input type="text" id="wtcb-next-text" value="' . esc_attr( $next_text ) . '" /></label><label>Color <input type="color" id="wtcb-next-button-color" value="' . esc_attr( $next_button_color ) . '" /></label><label>Background <input type="color" id="wtcb-next-button-bg" value="' . esc_attr( $next_button_background ) . '" /></label></div><div><h4>Continue Button</h4><label>Text <input type="text" id="wtcb-continue-text" value="' . esc_attr( $continue_text ) . '" /></label><label>Color <input type="color" id="wtcb-continue-button-color" value="' . esc_attr( $continue_button_color ) . '" /></label><label>Background <input type="color" id="wtcb-continue-button-bg" value="' . esc_attr( $continue_button_background ) . '" /></label></div></div></section>';
		echo '<section><h3>Step Behavior</h3><label class="wtcb-switch-row"><span><strong>Allow clicking completed steps</strong><small>Users can go back to previous completed steps.</small></span><label class="wtcb-switch"><input type="checkbox" id="wtcb-allow-completed" ' . checked( $allow_completed, true, false ) . ' /><span></span></label></label><label class="wtcb-switch-row"><span><strong>Scroll to top on step change</strong><small>Automatically scroll to the checkout stepper.</small></span><label class="wtcb-switch"><input type="checkbox" id="wtcb-scroll-on-change" ' . checked( $scroll_on_change, true, false ) . ' /><span></span></label></label><label class="wtcb-switch-row"><span><strong>Validate before next step</strong><small>Prevent moving forward if current fields are invalid.</small></span><label class="wtcb-switch"><input type="checkbox" id="wtcb-validate-before-next" ' . checked( $validate_before_next, true, false ) . ' /><span></span></label></label><label class="wtcb-switch-row"><span><strong>Remember step on refresh</strong><small>Restore the last active step.</small></span><label class="wtcb-switch"><input type="checkbox" id="wtcb-remember-step" ' . checked( $remember_step, true, false ) . ' /><span></span></label></label></section>';
		echo '</div><div class="wtcb-modal__foot"><button type="button" class="button" data-close-multistep-settings>' . esc_html__( 'Close', 'wootale-checkout-builder' ) . '</button></div></div></div>';
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
		$woo = array(
			array( 'billing', 'billing_first_name', 'First name', true ),
			array( 'billing', 'billing_last_name', 'Last name', true ),
			array( 'billing', 'billing_email', 'Email address', true ),
			array( 'billing', 'billing_phone', 'Phone', false ),
			array( 'billing', 'billing_company', 'Company name', false ),
			array( 'billing', 'billing_country', 'Billing country / region', true ),
			array( 'billing', 'billing_address_1', 'Billing street address', true ),
			array( 'billing', 'billing_address_2', 'Billing apartment, suite, unit, etc.', false ),
			array( 'billing', 'billing_city', 'Billing town / city', true ),
			array( 'billing', 'billing_state', 'Billing state / county', true ),
			array( 'billing', 'billing_postcode', 'Billing postcode / ZIP', true ),
			array( 'shipping', 'shipping_first_name', 'Shipping first name', true ),
			array( 'shipping', 'shipping_last_name', 'Shipping last name', true ),
			array( 'shipping', 'shipping_company', 'Shipping company', false ),
			array( 'shipping', 'shipping_country', 'Shipping country / region', true ),
			array( 'shipping', 'shipping_address_1', 'Shipping street address', true ),
			array( 'shipping', 'shipping_address_2', 'Shipping apartment, suite, unit, etc.', false ),
			array( 'shipping', 'shipping_city', 'Shipping town / city', true ),
			array( 'shipping', 'shipping_state', 'Shipping state / county', true ),
			array( 'shipping', 'shipping_postcode', 'Shipping postcode / ZIP', true ),
			array( 'order', 'order_comments', 'Additional information / Order notes', false ),
		);
		$components = array(
			array( 'order_review', 'Your order' ),
			array( 'payment_methods', 'Payment Methods' ),
			array( 'terms', 'Terms & Conditions' ),
			array( 'place_order', 'Place Order Button' ),
		);

		echo '<aside class="wtcb-card wtcb-components"><h2>Add Components</h2><input class="wtcb-search" type="search" placeholder="Search components..." />';
		echo '<h3>Basic Fields</h3><div class="wtcb-component-grid">';
		foreach ( $basic as $item ) {
			printf( '<button type="button" draggable="true" class="wtcb-component" data-add-field="%s">%s</button>', esc_attr( $item[0] ), esc_html( $item[1] ) );
		}
		echo '</div><h3>Advanced Fields <span>Pro</span></h3><div class="wtcb-component-grid is-disabled"><button type="button">Date Picker</button><button type="button">File Upload</button><button type="button">Signature</button><button type="button">Multi Select</button></div>';
		echo '<h3>WooCommerce Fields</h3><div class="wtcb-component-grid">';
		foreach ( $woo as $item ) {
			printf(
				'<button type="button" draggable="true" class="wtcb-component" data-woo-field="%1$s" data-section="%2$s" data-required="%3$s">%4$s</button>',
				esc_attr( $item[1] ),
				esc_attr( $item[0] ),
				esc_attr( $item[3] ? '1' : '0' ),
				esc_html( $item[2] )
			);
		}
		echo '</div><h3>Checkout Components</h3><div class="wtcb-component-grid">';
		foreach ( $components as $item ) {
			printf( '<button type="button" draggable="true" class="wtcb-component" data-woo-component="%s">%s</button>', esc_attr( $item[0] ), esc_html( $item[1] ) );
		}
		echo '</div><p class="wtcb-hint">Drag components to the steps on the canvas.</p></aside>';
	}

	/**
	 * @param array<string,mixed> $workflow Workflow.
	 */
	private function render_steps_panel( array $workflow ): void {
		echo '<main class="wtcb-card wtcb-canvas"><div class="wtcb-canvas-head"><h2>Checkout Steps <span>' . esc_html( count( $workflow['steps'] ) ) . ' / 3 Free</span></h2></div>';
		echo '<div id="wtcb-steps">';

		foreach ( $workflow['steps'] as $index => $step ) {
			$this->render_step( $step, $index );
		}

		echo '</div><button type="button" class="wtcb-add-step" id="wtcb-add-step">+ Add Step<br><small>You can add up to 3 steps in the free version.</small></button></main>';
	}

	/**
	 * @param array<string,mixed> $step Step.
	 */
	private function render_step( array $step, int $index ): void {
		echo '<section class="wtcb-step" data-step-index="' . esc_attr( (string) $index ) . '" style="--step-color:' . esc_attr( $step['color'] ) . '">';
		echo '<div class="wtcb-step-head"><span class="wtcb-drag">::</span><span class="wtcb-badge">' . esc_html( (string) ( $index + 1 ) ) . '</span><div><input class="wtcb-step-title" value="' . esc_attr( $step['title'] ) . '" /><input class="wtcb-step-description" value="' . esc_attr( $step['description'] ) . '" /></div><button type="button" class="wtcb-collapse">⌃</button></div>';
		echo '<div class="wtcb-field-list" data-step-fields>';

		foreach ( $step['fields'] as $field ) {
			$this->render_field_card( $field );
		}

		echo '</div></section>';
	}

	/**
	 * @param array<string,mixed> $field Field.
	 */
	private function render_field_card( array $field ): void {
		$classes = 'wtcb-field-card';
		$width   = isset( $field['width'] ) ? (int) $field['width'] : 2;

		if ( empty( $field['enabled'] ) ) {
			$classes .= ' is-disabled';
		}

		$classes .= ' wtcb-width-' . ( in_array( $width, array( 1, 2 ), true ) ? $width : 2 );
		$is_native = 'native' === ( $field['type'] ?? '' );
		$is_custom = 'custom' === ( $field['type'] ?? '' );
		unset( $is_native );

		printf(
			'<div class="%1$s" draggable="true" data-field="%2$s"><span class="wtcb-field-handle" title="%3$s">▦</span><button type="button" class="wtcb-field-settings-button" data-open-field-settings title="%5$s">⚙</button><strong>%4$s</strong><span class="wtcb-field-actions"><button type="button" class="wtcb-icon-button" data-duplicate-field %6$s title="%7$s">⧉</button><button type="button" class="wtcb-icon-button wtcb-danger" data-remove-field %8$s title="%9$s">×</button></span></div>',
			esc_attr( $classes ),
			esc_attr( $this->encode_json( $field ) ),
			esc_attr__( 'Drag field', 'wootale-checkout-builder' ),
			esc_html( $field['label'] ),
			esc_attr__( 'Field settings', 'wootale-checkout-builder' ),
			$is_custom ? '' : 'disabled',
			esc_attr__( 'Duplicate field', 'wootale-checkout-builder' ),
			'',
			esc_attr__( 'Remove field', 'wootale-checkout-builder' )
		);
	}

	/**
	 * Render field settings modal.
	 */
	private function render_field_settings_modal(): void {
		echo '<div class="wtcb-modal" id="wtcb-field-modal" hidden role="dialog" aria-modal="true" aria-labelledby="wtcb-field-modal-title">';
		echo '<div class="wtcb-modal__panel">';
		echo '<div class="wtcb-modal__head"><h2 id="wtcb-field-modal-title">' . esc_html__( 'Edit Field', 'wootale-checkout-builder' ) . '</h2><button type="button" class="wtcb-icon-button" data-close-field-settings>×</button></div>';
		echo '<div class="wtcb-modal__body">';
		echo '<div class="wtcb-modal-grid"><label>' . esc_html__( 'Field Type', 'wootale-checkout-builder' ) . '<select id="wtcb-modal-field-type"><option value="text">Text</option><option value="email">Email</option><option value="tel">Phone</option><option value="number">Number</option><option value="textarea">Textarea</option><option value="select">Select</option><option value="radio">Radio</option><option value="checkbox">Checkbox</option><option value="component">Component</option></select></label>';
		echo '<label>' . esc_html__( 'Section', 'wootale-checkout-builder' ) . '<select id="wtcb-modal-section"><option value="billing">Billing</option><option value="shipping">Shipping</option><option value="order">Order</option><option value="component">Component</option></select></label></div>';
		echo '<div class="wtcb-modal-grid"><label>' . esc_html__( 'Field Label', 'wootale-checkout-builder' ) . '<input type="text" id="wtcb-modal-label" /></label>';
		echo '<label>' . esc_html__( 'Field Key / Name', 'wootale-checkout-builder' ) . '<input type="text" id="wtcb-modal-key" /></label></div>';
		echo '<label>' . esc_html__( 'Default Value', 'wootale-checkout-builder' ) . '<input type="text" id="wtcb-modal-default" placeholder="e.g. ABC" /></label>';
		echo '<label>' . esc_html__( 'Placeholder Text', 'wootale-checkout-builder' ) . '<input type="text" id="wtcb-modal-placeholder" placeholder="e.g. Enter your company name" /></label>';
		echo '<label>' . esc_html__( 'Options', 'wootale-checkout-builder' ) . '<textarea id="wtcb-modal-options" rows="4" placeholder="One option per line"></textarea></label>';
		echo '<label>' . esc_html__( 'Validation', 'wootale-checkout-builder' ) . '<select id="wtcb-modal-validation" multiple><option value="email">Email</option><option value="phone">Phone</option><option value="postcode">Postcode</option></select></label>';
		echo '<label>' . esc_html__( 'Field Width', 'wootale-checkout-builder' ) . '<select id="wtcb-modal-width"><option value="1">Half width - 1/2</option><option value="2">Full width - 2/2</option></select></label>';
		echo '<div class="wtcb-toggle-row"><span><strong>' . esc_html__( 'Required field', 'wootale-checkout-builder' ) . '</strong><small>' . esc_html__( 'Customer must fill this out to complete checkout', 'wootale-checkout-builder' ) . '</small></span><label class="wtcb-switch"><input type="checkbox" id="wtcb-modal-required" /><span></span></label></div>';
		echo '<div class="wtcb-toggle-row"><span><strong>' . esc_html__( 'Enable field', 'wootale-checkout-builder' ) . '</strong><small>' . esc_html__( 'Show this field on the checkout page', 'wootale-checkout-builder' ) . '</small></span><label class="wtcb-switch"><input type="checkbox" id="wtcb-modal-enabled" /><span></span></label></div>';
		echo '<div class="wtcb-toggle-row"><span><strong>' . esc_html__( 'Display in order details', 'wootale-checkout-builder' ) . '</strong><small>' . esc_html__( 'Show this field value in order details', 'wootale-checkout-builder' ) . '</small></span><label class="wtcb-switch"><input type="checkbox" id="wtcb-modal-display-order" /><span></span></label></div>';
		echo '<div class="wtcb-toggle-row"><span><strong>' . esc_html__( 'Display in emails', 'wootale-checkout-builder' ) . '</strong><small>' . esc_html__( 'Include this field value in order emails', 'wootale-checkout-builder' ) . '</small></span><label class="wtcb-switch"><input type="checkbox" id="wtcb-modal-display-emails" /><span></span></label></div>';
		echo '<div class="wtcb-toggle-row"><span><strong>' . esc_html__( 'Display in Thank you page', 'wootale-checkout-builder' ) . '</strong><small>' . esc_html__( 'Show this field after checkout is complete', 'wootale-checkout-builder' ) . '</small></span><label class="wtcb-switch"><input type="checkbox" id="wtcb-modal-display-thank-you" /><span></span></label></div>';
		echo '<p class="wtcb-native-lock-note" id="wtcb-native-lock-note" hidden>' . esc_html__( 'Native WooCommerce field keys and types are fixed, but you can remove them from a step and drag them back from the left panel.', 'wootale-checkout-builder' ) . '</p>';
		echo '</div>';
		echo '<div class="wtcb-modal__foot"><button type="button" class="button" data-close-field-settings>' . esc_html__( 'Cancel', 'wootale-checkout-builder' ) . '</button><button type="button" class="button button-primary" id="wtcb-apply-field-settings">' . esc_html__( 'Apply Settings', 'wootale-checkout-builder' ) . '</button></div>';
		echo '</div></div>';
	}

	/**
	 * @param array<string,mixed> $workflow Workflow.
	 */
	private function render_settings_panel( array $workflow ): void {
		$first_step = isset( $workflow['steps'][0] ) ? $workflow['steps'][0] : array();

		echo '<aside class="wtcb-card wtcb-settings"><h2>Step Settings</h2><select><option>' . esc_html( $first_step['title'] ?? 'Step 1' ) . '</option></select><div class="wtcb-setting-tabs"><a>General</a><a class="is-active">Style</a><a>Advanced</a></div>';
		echo '<h3>Step Layout</h3><div class="wtcb-segment"><button type="button" class="is-active">Content</button><button type="button">Style</button></div>';
		echo '<h3>Step Icon</h3><div class="wtcb-icons"><button type="button" class="is-active">1</button><button type="button">◎</button><button type="button">☆</button></div>';
		echo '<h3>Color Settings</h3><label>Primary Color <input type="color" value="' . esc_attr( $first_step['color'] ?? '#2563eb' ) . '" /></label>';
		echo '<h3>Step Visibility</h3><div class="wtcb-segment"><button type="button" class="is-active">Always Show</button><button type="button">Conditional Pro</button></div>';
		echo '<button type="button" class="button wtcb-delete-step">Delete Step</button></aside>';
	}

	/**
	 * @param array<string,mixed> $workflow Workflow.
	 */
	private function render_footer_stats( array $workflow ): void {
		$count = 0;

		foreach ( $workflow['steps'] as $step ) {
			$count += count( $step['fields'] );
		}

		echo '<div class="wtcb-card wtcb-stats"><div><strong>Checkout Flow</strong><span>Cart → Checkout → Thank You</span></div><div><strong>Steps Limit</strong><span>' . esc_html( count( $workflow['steps'] ) ) . ' / 3 used</span></div><div><strong>Fields Count</strong><span>' . esc_html( (string) $count ) . ' fields added</span></div><div><strong>Last Saved</strong><span>Just now</span></div></div>';
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
