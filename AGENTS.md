# AGENTS.md — Checkoutly Checkout Builder

## Project Direction

Checkoutly Checkout Builder is now a dashboard-style workflow builder for the WooCommerce classic checkout shortcode.

The plugin is not based on Gutenberg Checkout Blocks. Do not add block metadata, editor block bundles, Store API checkout extensions, or WooCommerce Checkout Block nesting experiments unless the user explicitly asks to revive that architecture.

## Checkout Requirement

The checkout page should use:

```text
[woocommerce_checkout]
```

WooCommerce remains responsible for cart state, shipping, tax, payment, order creation, and checkout processing.

## Active Product Shape

- Admin dashboard under `Checkoutly Checkout`.
- Builder canvas with up to three free steps.
- Default first step includes native contact, billing, and shipping fields.
- Users can move fields between steps.
- Users can mark native fields required/optional.
- Users can disable native fields.
- Users can drag new basic custom fields into steps.
- Classic checkout hooks apply field status, required state, ordering, and custom fields.
- Frontend JavaScript reorganizes the classic shortcode checkout form into Checkoutly steps.

## Active Runtime Files

- `includes/Admin/Builder.php`
- `includes/Checkout/Workflow.php`
- `includes/Checkout/ClassicCheckout.php`
- `includes/Checkout/FieldRegistry.php`
- `includes/Checkout/Display.php`
- `includes/Plugin.php`
- `checkoutly.php`

## Verification

Use:

```sh
npm run lint:php
npm test
```
