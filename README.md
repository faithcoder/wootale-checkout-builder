# WooTale Checkout Builder

WooTale Checkout Builder is a dashboard-style checkout workflow builder for the WooCommerce classic checkout shortcode.

The active plugin architecture is no longer based on Gutenberg Checkout Blocks. Merchants configure checkout steps, native WooCommerce fields, and custom fields from the WooTale Checkout dashboard. WooCommerce still renders and processes the classic checkout form through its standard shortcode flow.

## Current Scope

- Admin dashboard at `WooTale Checkout > Checkout Builder`.
- Default three-step workflow.
- Contact, billing, and shipping native WooCommerce fields start in the first step.
- Native fields can be moved between steps, marked required/optional, or disabled.
- Basic custom fields can be added to the workflow.
- Classic checkout fields are modified through `woocommerce_checkout_fields`.
- Custom fields are saved to WooCommerce order meta through order CRUD APIs.
- Frontend step layout is applied to the classic shortcode checkout page.

## Commands

```sh
npm run lint:php
npm run test:php
npm test
```

## Checkout Page Requirement

The checkout page should use the WooCommerce classic shortcode:

```text
[woocommerce_checkout]
```
