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
		add_action( 'admin_post_wtcb_save_builder', array( $this, 'save' ) );
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

		$raw      = isset( $_POST['wtcb_workflow'] ) ? wp_unslash( $_POST['wtcb_workflow'] ) : '';
		$decoded  = json_decode( (string) $raw, true );
		$workflow = is_array( $decoded ) ? $decoded : $this->workflow->defaults();

		$this->workflow->save( $workflow );

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
	 * Render builder dashboard.
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'wootale-checkout-builder' ) );
		}

		$workflow     = $this->workflow->get();
		$checkout_url = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/' );

		echo '<div class="wrap wtcb-admin">';
		$this->render_styles();
		$this->render_header( $checkout_url );

		if ( isset( $_GET['updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'WooTale checkout builder saved.', 'wootale-checkout-builder' ) . '</p></div>';
		}

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" id="wtcb-builder-form">';
		wp_nonce_field( 'wtcb_save_builder' );
		echo '<input type="hidden" name="action" value="wtcb_save_builder" />';
		echo '<textarea id="wtcb-workflow-input" name="wtcb_workflow" hidden>' . esc_textarea( wp_json_encode( $workflow ) ) . '</textarea>';
		$this->render_global_settings_panel( $workflow );
		echo '<div class="wtcb-builder-shell">';
		$this->render_component_panel();
		$this->render_steps_panel( $workflow );
		$this->render_settings_panel( $workflow );
		echo '</div>';
		$this->render_footer_stats( $workflow );
		$this->render_field_settings_modal();
		echo '</form>';
		$this->render_scripts( $workflow );
		echo '</div>';
	}

	private function render_header( string $checkout_url ): void {
		echo '<div class="wtcb-topbar">';
		echo '<div class="wtcb-brand"><span class="wtcb-logo">WT</span><h1><strong>WooTale</strong> <span>Checkout Builder</span></h1></div>';
		echo '<nav class="wtcb-tabs"><a class="is-active" data-wtcb-tab="builder">Builder</a><a>Fields</a><a data-wtcb-tab="settings">Settings</a><a>Rules</a><a>Analytics <span>Pro</span></a><a>Extensions <span>Pro</span></a></nav>';
		echo '<div class="wtcb-actions"><a class="button" href="https://wootale.com/docs" target="_blank" rel="noreferrer">Docs</a><a class="button" href="' . esc_url( $checkout_url ) . '" target="_blank" rel="noreferrer">Preview Checkout</a><button class="button button-primary" type="submit" form="wtcb-builder-form">Save Changes</button></div>';
		echo '</div>';
	}

	/**
	 * @param array<string,mixed> $workflow Workflow.
	 */
	private function render_global_settings_panel( array $workflow ): void {
		$orientation = isset( $workflow['orientation'] ) ? (string) $workflow['orientation'] : 'horizontal';
		$indicator = isset( $workflow['indicator'] ) ? (string) $workflow['indicator'] : 'number';
		$connector = isset( $workflow['connector'] ) ? (string) $workflow['connector'] : 'solid';
		$navigation = isset( $workflow['navigation'] ) ? (string) $workflow['navigation'] : 'tabs';
		$active_color = isset( $workflow['activeColor'] ) ? (string) $workflow['activeColor'] : '#2563eb';
		$completed_color = isset( $workflow['completedColor'] ) ? (string) $workflow['completedColor'] : '#16a34a';
		$multi_step_enabled = ! array_key_exists( 'multiStepEnabled', $workflow ) || ! empty( $workflow['multiStepEnabled'] );

		echo '<section class="wtcb-card wtcb-global-settings" id="wtcb-global-settings" hidden>';
		echo '<div class="wtcb-global-settings__head"><div><h2>' . esc_html__( 'Checkout Settings', 'wootale-checkout-builder' ) . '</h2><p>' . esc_html__( 'Control the checkout workflow and step navigation.', 'wootale-checkout-builder' ) . '</p></div><label class="wtcb-switch-row"><span><strong>' . esc_html__( 'Enable Multi Step', 'wootale-checkout-builder' ) . '</strong><small>' . esc_html__( 'Show customers a guided step flow instead of one continuous checkout.', 'wootale-checkout-builder' ) . '</small></span><label class="wtcb-switch"><input type="checkbox" id="wtcb-multistep-enabled" ' . checked( $multi_step_enabled, true, false ) . ' /><span></span></label></label></div>';
		echo '<div class="wtcb-multistep-controls" id="wtcb-multistep-controls" ' . ( $multi_step_enabled ? '' : 'hidden' ) . '>';
		echo '<div><h3>Steps Number</h3><div class="wtcb-step-count"><button type="button" data-step-count-decrease>-</button><input type="number" id="wtcb-step-count" min="1" max="3" value="' . esc_attr( (string) count( $workflow['steps'] ) ) . '" /><button type="button" data-step-count-increase>+</button></div><p class="wtcb-small-note">Free workflows support up to 3 steps.</p></div>';
		echo '<div><h3>Step Layout</h3><div class="wtcb-segment" data-setting-segment="orientation"><button type="button" data-value="horizontal" class="' . esc_attr( 'horizontal' === $orientation ? 'is-active' : '' ) . '">Horizontal</button><button type="button" data-value="vertical" class="' . esc_attr( 'vertical' === $orientation ? 'is-active' : '' ) . '">Vertical</button></div></div>';
		echo '<div><h3>Indicator</h3><div class="wtcb-segment" data-setting-segment="indicator"><button type="button" data-value="number" class="' . esc_attr( 'number' === $indicator ? 'is-active' : '' ) . '">Number</button><button type="button" data-value="icon" class="' . esc_attr( 'icon' === $indicator ? 'is-active' : '' ) . '">Icon</button><button type="button" data-value="tab" class="' . esc_attr( 'tab' === $indicator ? 'is-active' : '' ) . '">Tab</button></div></div>';
		echo '<div><h3>Connector</h3><select id="wtcb-connector"><option value="solid" ' . selected( $connector, 'solid', false ) . '>Solid line</option><option value="line" ' . selected( $connector, 'line', false ) . '>Thin line</option><option value="arrow" ' . selected( $connector, 'arrow', false ) . '>Arrow</option><option value="none" ' . selected( $connector, 'none', false ) . '>None</option></select></div>';
		echo '<div><h3>Navigation</h3><div class="wtcb-segment" data-setting-segment="navigation"><button type="button" data-value="tabs" class="' . esc_attr( 'tabs' === $navigation ? 'is-active' : '' ) . '">Tabs</button><button type="button" data-value="buttons" class="' . esc_attr( 'buttons' === $navigation ? 'is-active' : '' ) . '">Buttons</button></div></div>';
		echo '<div><h3>Colors</h3><label>Active Color <input type="color" id="wtcb-active-color" value="' . esc_attr( $active_color ) . '" /></label><label>Completed Color <input type="color" id="wtcb-completed-color" value="' . esc_attr( $completed_color ) . '" /></label></div>';
		echo '</div></section>';
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
		echo '<main class="wtcb-card wtcb-canvas"><div class="wtcb-canvas-head"><h2>Checkout Steps <span>' . esc_html( count( $workflow['steps'] ) ) . ' / 3 Free</span></h2><div><button type="button" class="button">Import</button> <button type="button" class="button">Export</button></div></div>';
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
			esc_attr( wp_json_encode( $field ) ),
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

	private function render_styles(): void {
		echo '<style>
		.wtcb-admin{--blue:#2563eb;--line:#dbeafe;--ink:#111827;--muted:#6b7280;max-width:1660px}
		.wtcb-topbar{align-items:center;background:#fff;border:1px solid #e5e7eb;border-radius:10px;display:grid;gap:18px;grid-template-columns:auto 1fr auto;margin:16px 0;padding:16px}
		.wtcb-brand{align-items:center;display:flex;gap:12px}.wtcb-brand h1{font-size:22px;margin:0}.wtcb-brand span{font-weight:400}.wtcb-logo{align-items:center;border:2px solid var(--blue);border-radius:8px;color:var(--blue);display:inline-flex;font-size:11px;font-weight:700;height:30px;justify-content:center;width:30px}
		.wtcb-tabs{display:flex;gap:24px}.wtcb-tabs a{color:#4b5563;text-decoration:none}.wtcb-tabs .is-active{border-bottom:2px solid var(--blue);color:var(--blue);padding-bottom:10px}.wtcb-tabs span,.wtcb-components h3 span{background:#ede9fe;border-radius:6px;color:#7c3aed;font-size:11px;padding:2px 6px}.wtcb-actions{display:flex;gap:8px}
		.wtcb-global-settings{margin:0 0 20px}.wtcb-global-settings__head{align-items:center;display:flex;gap:20px;justify-content:space-between}.wtcb-global-settings__head h2{margin-bottom:4px}.wtcb-global-settings__head p{color:#6b7280;margin:0}.wtcb-multistep-controls{border-top:1px solid #e5e7eb;display:grid;gap:18px;grid-template-columns:repeat(3,minmax(0,1fr));margin-top:16px;padding-top:16px}.wtcb-multistep-controls[hidden]{display:none}.wtcb-switch-row{align-items:center;background:#f9fafb;border:1px solid #f3f4f6;border-radius:8px;display:flex;gap:20px;justify-content:space-between;min-width:340px;padding:12px}.wtcb-switch-row>span{display:grid;gap:4px}.wtcb-switch-row small{color:#6b7280;font-weight:400}
		.wtcb-builder-shell{display:grid;gap:20px;grid-template-columns:320px minmax(520px,1fr) 320px}.wtcb-card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 1px 2px rgba(15,23,42,.04);padding:16px}.wtcb-card h2{font-size:16px;margin:0 0 14px}.wtcb-card h3{font-size:13px;margin:18px 0 10px}.wtcb-search,.wtcb-settings select,.wtcb-step input,.wtcb-global-settings select{border:1px solid #e5e7eb;border-radius:6px;box-sizing:border-box;width:100%}
		.wtcb-component-grid{display:grid;gap:8px;grid-template-columns:1fr 1fr}.wtcb-component-grid button,.wtcb-component{background:#fff;border:1px solid #e5e7eb;border-radius:6px;cursor:pointer;padding:10px;text-align:left}.wtcb-component-grid.is-disabled button{color:#9ca3af}.wtcb-hint{background:#f9fafb;color:#6b7280;margin:16px -16px -16px;padding:12px 16px}
		.wtcb-canvas-head{align-items:center;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;margin:-16px -16px 16px;padding:16px}.wtcb-canvas-head span{background:#dbeafe;border-radius:6px;color:#2563eb;font-size:12px;padding:2px 8px}
		.wtcb-step{background:linear-gradient(90deg,rgba(37,99,235,.05),#fff);border:2px solid var(--step-color);border-radius:10px;margin-bottom:14px;padding:14px}.wtcb-step-head{align-items:center;display:grid;gap:12px;grid-template-columns:18px 30px 1fr 32px}.wtcb-drag{color:#9ca3af;cursor:grab}.wtcb-badge{align-items:center;background:var(--step-color);border-radius:50%;color:#fff;display:flex;height:30px;justify-content:center;width:30px}.wtcb-step-title{border:0!important;font-weight:700;padding:2px!important}.wtcb-step-description{border:0!important;color:#6b7280;padding:2px!important}
		.wtcb-field-list{align-items:start;display:grid;gap:10px;grid-auto-flow:row dense;grid-template-columns:repeat(2,minmax(0,1fr));min-height:54px;padding-top:12px}.wtcb-field-card{align-items:center;background:#fff;border:1px solid #e5e7eb;border-radius:6px;display:grid;gap:8px;grid-template-columns:18px 28px minmax(0,1fr) auto;min-height:42px;padding:10px}.wtcb-field-card.is-disabled{opacity:.5}.wtcb-field-card.is-dragging{opacity:.35}.wtcb-field-card strong{overflow-wrap:anywhere}.wtcb-field-handle{color:#9ca3af;cursor:grab}.wtcb-field-settings-button{background:transparent;border:0;color:#4b5563;cursor:pointer;font-size:20px;height:28px;line-height:1;padding:0;width:28px}.wtcb-field-settings-button:hover{color:#2563eb}.wtcb-field-actions{display:flex;gap:4px}.wtcb-icon-button{align-items:center;background:transparent;border:0;border-radius:5px;color:#4b5563;cursor:pointer;display:inline-flex;font-size:17px;height:28px;justify-content:center;line-height:1;padding:0;width:28px}.wtcb-icon-button:hover{background:#f3f4f6}.wtcb-icon-button:disabled{background:transparent;cursor:not-allowed;opacity:.3}.wtcb-icon-button.wtcb-danger{color:#dc2626}.wtcb-width-1{grid-column:span 1}.wtcb-width-2{grid-column:1/-1}
		.wtcb-add-step{background:#fff;border:1px dashed #cbd5e1;border-radius:10px;color:#2563eb;cursor:pointer;padding:24px;width:100%}.wtcb-add-step small{color:#6b7280}
		.wtcb-setting-tabs{border-bottom:1px solid #e5e7eb;display:flex;gap:22px;margin:16px -16px;padding:0 16px 10px}.wtcb-setting-tabs .is-active{border-bottom:2px solid var(--blue);color:var(--blue)}.wtcb-segment{display:grid;gap:8px;grid-template-columns:repeat(auto-fit,minmax(80px,1fr))}.wtcb-segment button,.wtcb-icons button,.wtcb-step-count button{background:#fff;border:1px solid #e5e7eb;border-radius:6px;padding:10px}.wtcb-segment .is-active,.wtcb-icons .is-active{border-color:#2563eb;color:#2563eb}.wtcb-icons{display:flex;gap:8px}.wtcb-settings label,.wtcb-global-settings label{display:flex;justify-content:space-between;margin:12px 0}.wtcb-settings select,.wtcb-global-settings select{min-height:38px}.wtcb-step-count{display:grid;gap:8px;grid-template-columns:42px 1fr 42px}.wtcb-step-count input{border:1px solid #e5e7eb;border-radius:6px;text-align:center}.wtcb-small-note{color:#6b7280;font-size:12px;margin:8px 0 0}.wtcb-delete-step{border-color:#fecaca!important;color:#dc2626!important;margin-top:16px;width:100%}
		.wtcb-stats{display:grid;gap:16px;grid-template-columns:repeat(4,1fr);margin-top:20px}.wtcb-stats div{border-right:1px solid #e5e7eb;padding:8px 16px}.wtcb-stats div:last-child{border-right:0}.wtcb-stats strong,.wtcb-stats span{display:block}.wtcb-stats span{color:#4b5563;margin-top:8px}
		.wtcb-modal{align-items:center;background:rgba(15,23,42,.45);bottom:0;display:flex;justify-content:center;left:0;position:fixed;right:0;top:0;z-index:100000}.wtcb-modal[hidden]{display:none}.wtcb-modal__panel{background:#fff;border-radius:10px;box-shadow:0 24px 80px rgba(15,23,42,.25);max-height:calc(100vh - 48px);max-width:900px;overflow:auto;width:min(900px,calc(100vw - 32px))}.wtcb-modal__head,.wtcb-modal__foot{align-items:center;background:#fff;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;padding:16px;position:sticky;top:0;z-index:1}.wtcb-modal__foot{border-bottom:0;border-top:1px solid #e5e7eb;bottom:0;gap:8px;justify-content:flex-end;top:auto}.wtcb-modal__head h2{font-size:18px;margin:0}.wtcb-modal__body{display:grid;gap:16px;padding:16px}.wtcb-modal-grid{display:grid;gap:16px;grid-template-columns:1fr 1fr}.wtcb-modal__body label{display:grid;gap:6px;font-weight:600}.wtcb-modal__body input[type=text],.wtcb-modal__body select,.wtcb-modal__body textarea{border:1px solid #d1d5db;border-radius:6px;box-sizing:border-box;min-height:40px;padding:8px;width:100%}.wtcb-modal__body textarea{resize:vertical}.wtcb-modal__body select[multiple]{min-height:86px}.wtcb-toggle-row{align-items:center;background:#f9fafb;border:1px solid #f3f4f6;border-radius:8px;display:flex;justify-content:space-between;padding:14px}.wtcb-toggle-row span{display:grid;gap:4px}.wtcb-toggle-row small{color:#6b7280;font-weight:400}.wtcb-switch input{clip:rect(0,0,0,0);height:1px;position:absolute;width:1px}.wtcb-switch span{background:#cbd5e1;border-radius:999px;display:block;height:24px;position:relative;width:44px}.wtcb-switch span:before{background:#fff;border-radius:50%;content:"";height:18px;left:3px;position:absolute;top:3px;transition:.15s;width:18px}.wtcb-switch input:checked+span{background:#2563eb}.wtcb-switch input:checked+span:before{left:23px}.wtcb-native-lock-note{background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;color:#1d4ed8;margin:0;padding:10px}
		@media(max-width:1200px){.wtcb-builder-shell{grid-template-columns:1fr}.wtcb-topbar{grid-template-columns:1fr}.wtcb-stats,.wtcb-multistep-controls{grid-template-columns:1fr 1fr}}@media(max-width:700px){.wtcb-field-list,.wtcb-modal-grid,.wtcb-multistep-controls{grid-template-columns:1fr}.wtcb-width-1,.wtcb-width-2{grid-column:1/-1}.wtcb-global-settings__head{align-items:stretch;display:grid}.wtcb-switch-row{min-width:0}}
		</style>';
	}

	/**
	 * @param array<string,mixed> $workflow Workflow.
	 */
	private function render_scripts( array $workflow ): void {
		echo '<script>window.wtcbInitialWorkflow = ' . wp_json_encode( $workflow ) . ';</script>';
		echo <<<'JS'
<script>
(function(){
	var dragged = null;
	var paletteField = null;
	var activeCard = null;
	var steps = document.getElementById('wtcb-steps');
	var input = document.getElementById('wtcb-workflow-input');
	var globalSettings = document.getElementById('wtcb-global-settings');
	var multiStepEnabled = document.getElementById('wtcb-multistep-enabled');
	var multiStepControls = document.getElementById('wtcb-multistep-controls');
	var canvasCount = document.querySelector('.wtcb-canvas-head h2 span');
	var stepCount = document.getElementById('wtcb-step-count');
	var connector = document.getElementById('wtcb-connector');
	var activeColor = document.getElementById('wtcb-active-color');
	var completedColor = document.getElementById('wtcb-completed-color');
	var modal = document.getElementById('wtcb-field-modal');
	var modalTitle = document.getElementById('wtcb-field-modal-title');
	var modalFieldType = document.getElementById('wtcb-modal-field-type');
	var modalSection = document.getElementById('wtcb-modal-section');
	var modalLabel = document.getElementById('wtcb-modal-label');
	var modalKey = document.getElementById('wtcb-modal-key');
	var modalDefault = document.getElementById('wtcb-modal-default');
	var modalPlaceholder = document.getElementById('wtcb-modal-placeholder');
	var modalOptions = document.getElementById('wtcb-modal-options');
	var modalValidation = document.getElementById('wtcb-modal-validation');
	var modalWidth = document.getElementById('wtcb-modal-width');
	var modalRequired = document.getElementById('wtcb-modal-required');
	var modalEnabled = document.getElementById('wtcb-modal-enabled');
	var modalDisplayOrder = document.getElementById('wtcb-modal-display-order');
	var modalDisplayEmails = document.getElementById('wtcb-modal-display-emails');
	var modalDisplayThankYou = document.getElementById('wtcb-modal-display-thank-you');
	var modalLockNote = document.getElementById('wtcb-native-lock-note');

	function parseField(card){ try { return JSON.parse(card.dataset.field || '{}'); } catch(e){ return {}; } }
	function fieldWidth(field){ return [1,2].indexOf(Number(field.width)) >= 0 ? Number(field.width) : 2; }
	function displayOptions(field){
		var display = field.display || {};
		return {
			orderDetails: display.orderDetails !== false,
			emails: display.emails !== false,
			thankYou: display.thankYou !== false
		};
	}
	function setField(card, field){
		var width = fieldWidth(field);
		field.display = displayOptions(field);
		card.dataset.field = JSON.stringify(field);
		card.classList.toggle('is-disabled', field.enabled === false);
		card.classList.remove('wtcb-width-1', 'wtcb-width-2', 'wtcb-width-3');
		card.classList.add('wtcb-width-' + width);
		card.querySelector('strong').textContent = field.label || field.key || 'Field';
	}
	function fieldCard(field){
		var card = document.createElement('div');
		field.width = fieldWidth(field);
		card.className = 'wtcb-field-card wtcb-width-' + field.width;
		card.draggable = true;
		card.innerHTML = '<span class="wtcb-field-handle" title="Drag field">▦</span><button type="button" class="wtcb-field-settings-button" data-open-field-settings title="Field settings">⚙</button><strong></strong><span class="wtcb-field-actions"><button type="button" class="wtcb-icon-button" data-duplicate-field title="Duplicate field">⧉</button><button type="button" class="wtcb-icon-button wtcb-danger" data-remove-field title="Remove field">×</button></span>';
		card.querySelector('[data-duplicate-field]').disabled = field.type !== 'custom';
		setField(card, field);
		return card;
	}
	function customField(type, label) {
		var key = 'wtcb_' + type + '_' + Date.now();
		return { id:key, key:key, section:'order', type:'custom', fieldType:type, label:label, required:false, enabled:true, width:2, default:'', placeholder:'', options:'', validation:[], display:{ orderDetails:true, emails:true, thankYou:true } };
	}
	function nativeField(section, key, label, required) {
		return { id:key, key:key, section:section, type:'native', fieldType:'text', label:label, required:required, enabled:true, width:2, default:'', placeholder:'', options:'', validation:[], display:{ orderDetails:true, emails:true, thankYou:true } };
	}
	function componentField(key, label) {
		return { id:'component_' + key, key:key, section:'component', type:'component', fieldType:'component', label:label, required:false, enabled:true, width:2, default:'', placeholder:'', options:'', validation:[], display:{ orderDetails:true, emails:true, thankYou:true } };
	}
	function settingValue(name, fallback) {
		var active = document.querySelector('[data-setting-segment="' + name + '"] .is-active');
		return active ? active.dataset.value : fallback;
	}
	function refreshMultiStepControls(){
		if (multiStepControls && multiStepEnabled) {
			multiStepControls.hidden = !multiStepEnabled.checked;
		}
	}
	function renumberSteps(){
		steps.querySelectorAll('.wtcb-step').forEach(function(step, index){
			step.dataset.stepIndex = String(index);
			var badge = step.querySelector('.wtcb-badge');
			if (badge) {
				badge.textContent = String(index + 1);
			}
		});
		if (stepCount) {
			stepCount.value = String(steps.querySelectorAll('.wtcb-step').length);
		}
		if (canvasCount) {
			canvasCount.textContent = steps.querySelectorAll('.wtcb-step').length + ' / 3 Free';
		}
	}
	function stepCard(index){
		var colors = ['#2563eb', '#16a34a', '#7c3aed'];
		var step = document.createElement('section');
		step.className = 'wtcb-step';
		step.dataset.stepIndex = String(index);
		step.style.setProperty('--step-color', colors[index] || '#2563eb');
		step.innerHTML = '<div class="wtcb-step-head"><span class="wtcb-drag">::</span><span class="wtcb-badge">' + (index + 1) + '</span><div><input class="wtcb-step-title" value="Step ' + (index + 1) + '" /><input class="wtcb-step-description" value="" /></div><button type="button" class="wtcb-collapse">⌃</button></div><div class="wtcb-field-list" data-step-fields></div>';
		return step;
	}
	function setStepCount(nextCount){
		var current = steps.querySelectorAll('.wtcb-step').length;
		nextCount = Math.max(1, Math.min(3, Number(nextCount) || current));

		while (current < nextCount) {
			steps.appendChild(stepCard(current));
			current++;
		}

		while (current > nextCount) {
			steps.lastElementChild.remove();
			current--;
		}

		renumberSteps();
		serialize();
	}
	function fieldDropTarget(list, y){
		var cards = Array.prototype.slice.call(list.querySelectorAll('.wtcb-field-card:not(.is-dragging)'));
		return cards.reduce(function(closest, child){
			var box = child.getBoundingClientRect();
			var offset = y - box.top - box.height / 2;
			if (offset < 0 && offset > closest.offset) {
				return { offset: offset, element: child };
			}
			return closest;
		}, { offset: Number.NEGATIVE_INFINITY, element: null }).element;
	}
	function serialize(){
		var workflow = {
			version: 1,
			multiStepEnabled: multiStepEnabled ? multiStepEnabled.checked : true,
			orientation: settingValue('orientation', 'horizontal'),
			indicator: settingValue('indicator', 'number'),
			connector: connector ? connector.value : 'solid',
			navigation: settingValue('navigation', 'tabs'),
			activeColor: activeColor ? activeColor.value : '#2563eb',
			completedColor: completedColor ? completedColor.value : '#16a34a',
			steps: []
		};
		steps.querySelectorAll('.wtcb-step').forEach(function(step, index){
			var fields = [];
			step.querySelectorAll('.wtcb-field-card').forEach(function(card){ fields.push(parseField(card)); });
			workflow.steps.push({
				id: 'wtcb_step_' + (index + 1),
				title: step.querySelector('.wtcb-step-title').value || ('Step ' + (index + 1)),
				description: step.querySelector('.wtcb-step-description').value || '',
				color: getComputedStyle(step).getPropertyValue('--step-color').trim() || '#2563eb',
				fields: fields
			});
		});
		input.value = JSON.stringify(workflow);
		renumberSteps();
	}

	document.addEventListener('dragstart', function(event){
		var card = event.target.closest('.wtcb-field-card');
		var component = event.target.closest('[data-add-field]');
		var wooField = event.target.closest('[data-woo-field]');
		var wooComponent = event.target.closest('[data-woo-component]');

		if (component) {
			paletteField = customField(component.dataset.addField, component.textContent.trim());
			event.dataTransfer.effectAllowed = 'copy';
			return;
		}

		if (wooField) {
			paletteField = nativeField(wooField.dataset.section, wooField.dataset.wooField, wooField.textContent.trim(), wooField.dataset.required === '1');
			event.dataTransfer.effectAllowed = 'copy';
			return;
		}

		if (wooComponent) {
			paletteField = componentField(wooComponent.dataset.wooComponent, wooComponent.textContent.trim());
			event.dataTransfer.effectAllowed = 'copy';
			return;
		}

		if (card) {
			dragged = card;
			card.classList.add('is-dragging');
			event.dataTransfer.effectAllowed = 'move';
		}
	});
	document.addEventListener('dragend', function(){
		if (dragged) {
			dragged.classList.remove('is-dragging');
		}
		dragged = null;
		paletteField = null;
	});
	document.addEventListener('dragover', function(event){
		var list = event.target.closest('[data-step-fields]');
		if (list) {
			event.preventDefault();
			var before = fieldDropTarget(list, event.clientY);
			if (dragged && before !== dragged) {
				list.insertBefore(dragged, before);
			}
		}
	});
	document.addEventListener('drop', function(event){
		var list = event.target.closest('[data-step-fields]');
		if (!list) {
			return;
		}

		if (paletteField) {
			event.preventDefault();
			var before = fieldDropTarget(list, event.clientY);
			list.insertBefore(fieldCard(paletteField), before);
			paletteField = null;
			serialize();
			return;
		}

		if (dragged) {
			event.preventDefault();
			dragged.classList.remove('is-dragging');
			dragged = null;
			serialize();
		}
	});
	document.addEventListener('click', function(event){
		var card = event.target.closest('.wtcb-field-card');
		var segmentButton = event.target.closest('[data-setting-segment] button[data-value]');
		var topTab = event.target.closest('[data-wtcb-tab]');

		if (topTab) {
			event.preventDefault();
			Array.prototype.forEach.call(document.querySelectorAll('[data-wtcb-tab]'), function(tab){
				tab.classList.toggle('is-active', tab === topTab);
			});
			if (globalSettings) {
				globalSettings.hidden = topTab.dataset.wtcbTab !== 'settings';
			}
		}
		if (segmentButton) {
			Array.prototype.forEach.call(segmentButton.parentNode.children, function(button){
				button.classList.toggle('is-active', button === segmentButton);
			});
			serialize();
		}
		if (event.target.matches('[data-step-count-decrease]')) {
			setStepCount(steps.querySelectorAll('.wtcb-step').length - 1);
		}
		if (event.target.matches('[data-step-count-increase], #wtcb-add-step')) {
			setStepCount(steps.querySelectorAll('.wtcb-step').length + 1);
		}
		if (event.target.matches('.wtcb-delete-step')) {
			setStepCount(steps.querySelectorAll('.wtcb-step').length - 1);
		}
		if (event.target.matches('[data-open-field-settings]') && card) {
			var field = parseField(card);
			var display = displayOptions(field);
			activeCard = card;
			modalTitle.textContent = 'Edit: ' + (field.label || field.key || 'Field');
			modalFieldType.value = field.fieldType || 'text';
			modalFieldType.disabled = field.type !== 'custom';
			modalSection.value = field.section || 'order';
			modalSection.disabled = field.type !== 'custom';
			modalLabel.value = field.label || '';
			modalKey.value = field.key || '';
			modalKey.disabled = field.type !== 'custom';
			modalDefault.value = field.default || '';
			modalPlaceholder.value = field.placeholder || '';
			modalOptions.value = field.options || '';
			Array.prototype.forEach.call(modalValidation.options, function(option){
				option.selected = (field.validation || []).indexOf(option.value) >= 0;
			});
			modalWidth.value = String(fieldWidth(field));
			modalRequired.checked = !!field.required;
			modalEnabled.checked = field.enabled !== false;
			modalDisplayOrder.checked = display.orderDetails;
			modalDisplayEmails.checked = display.emails;
			modalDisplayThankYou.checked = display.thankYou;
			modalLockNote.hidden = field.type === 'custom';
			modal.hidden = false;
		}
		if (event.target.matches('[data-close-field-settings]')) {
			modal.hidden = true;
			activeCard = null;
		}
		if (event.target.matches('#wtcb-apply-field-settings') && activeCard) {
			var next = parseField(activeCard);
			next.label = modalLabel.value.trim() || next.label || next.key;
			if (next.type === 'custom') {
				next.fieldType = modalFieldType.value || 'text';
				next.section = modalSection.value || 'order';
				next.key = modalKey.value.trim().toLowerCase().replace(/[^a-z0-9_]/g, '') || next.key;
			}
			next.default = modalDefault.value.trim();
			next.placeholder = modalPlaceholder.value.trim();
			next.options = modalOptions.value.trim();
			next.validation = Array.prototype.filter.call(modalValidation.options, function(option){ return option.selected; }).map(function(option){ return option.value; });
			next.required = modalRequired.checked;
			next.enabled = modalEnabled.checked;
			next.width = Number(modalWidth.value) || 2;
			next.display = {
				orderDetails: modalDisplayOrder.checked,
				emails: modalDisplayEmails.checked,
				thankYou: modalDisplayThankYou.checked
			};
			setField(activeCard, next);
			serialize();
			modal.hidden = true;
			activeCard = null;
		}
		if (event.target.matches('[data-duplicate-field]') && card && !event.target.disabled) {
			var field = parseField(card);
			if (field.type === 'custom') {
				var copy = JSON.parse(JSON.stringify(field));
				copy.key = 'wtcb_' + copy.fieldType + '_' + Date.now();
				copy.id = copy.key;
				copy.label = (copy.label || 'Field') + ' copy';
				card.parentNode.insertBefore(fieldCard(copy), card.nextSibling);
				serialize();
			}
		}
		if (event.target.matches('[data-remove-field]') && card && !event.target.disabled) {
			card.remove();
			serialize();
		}
		if (event.target.matches('[data-add-field]')) {
			var type = event.target.dataset.addField;
			var list = steps.querySelector('[data-step-fields]');
			list.appendChild(fieldCard(customField(type, event.target.textContent.trim())));
			serialize();
		}
		if (event.target.matches('[data-woo-field]')) {
			var wooList = steps.querySelector('[data-step-fields]');
			wooList.appendChild(fieldCard(nativeField(event.target.dataset.section, event.target.dataset.wooField, event.target.textContent.trim(), event.target.dataset.required === '1')));
			serialize();
		}
		if (event.target.matches('[data-woo-component]')) {
			var componentKey = event.target.dataset.wooComponent;
			var componentList = steps.querySelector('[data-step-fields]');
			componentList.appendChild(fieldCard(componentField(componentKey, event.target.textContent.trim())));
			serialize();
		}
	});
	if (stepCount) {
		stepCount.addEventListener('change', function(){ setStepCount(stepCount.value); });
	}
	if (multiStepEnabled) {
		multiStepEnabled.addEventListener('change', function(){
			refreshMultiStepControls();
			serialize();
		});
	}
	if (connector) {
		connector.addEventListener('change', serialize);
	}
	if (activeColor) {
		activeColor.addEventListener('input', serialize);
	}
	if (completedColor) {
		completedColor.addEventListener('input', serialize);
	}
	modal.addEventListener('click', function(event){
		if (event.target === modal) {
			modal.hidden = true;
			activeCard = null;
		}
	});
	document.addEventListener('input', serialize);
	refreshMultiStepControls();
	serialize();
})();
</script>
JS;
	}
}
