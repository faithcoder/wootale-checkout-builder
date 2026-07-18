# AGENTS.md — WooTale Checkout Builder

## 1. Project identity

**Plugin name:** WooTale Checkout Builder  
**Free plugin slug:** `wootale-checkout-builder`  
**Pro add-on slug:** `wootale-checkout-builder-pro`  
**PHP namespace:** `WooTale\CheckoutBuilder`  
**PHP/function prefix:** `wtcb_`  
**Block namespace:** `wootale`  
**Text domain:** `wootale-checkout-builder`  
**Primary platform:** WordPress + WooCommerce Checkout Blocks  
**Checkout support:** WooCommerce Block Checkout only for the initial release  
**Classic shortcode checkout:** Out of scope for the initial release

WooTale Checkout Builder is a native Gutenberg checkout workflow builder for WooCommerce. A merchant inserts one Checkout Builder block, creates checkout steps, and then arranges supported WooCommerce native checkout blocks and WooTale input-field blocks into those steps.

The product must preserve WooCommerce’s cart, Store API, payment, shipping, tax, order creation, and checkout-processing behavior. It must not rebuild payment processing or create a parallel checkout engine.

---

## 2. Product vision

Create the easiest way to build a guided, multi-step WooCommerce Block Checkout without writing code.

The editor experience should resemble a table/form builder:

1. Insert **WooTale Checkout Builder**.
2. Display an empty-state setup panel.
3. Choose the number of steps.
4. Click **Create Checkout**.
5. Rename and reorder steps.
6. Drag supported WooCommerce native checkout blocks and WooTale field blocks into steps.
7. Configure step layout, icons, colors, navigation, and responsive behavior.
8. Preview desktop and mobile checkout.
9. Choose whether customers visit the Cart first or go directly to Checkout.
10. Save and test the checkout.

The merchant-facing promise is:

> Build a WooCommerce checkout with steps, fields, native checkout components, and responsive styling directly inside the WordPress block editor.

---

## 3. Non-negotiable product requirements

### 3.1 Checkout Builder block

Register a dedicated parent block:

```text
wootale/checkout-builder
```

The empty state must include:

- Block title and short description.
- Step-count selector.
- Free limit notice.
- Pro limit notice when relevant.
- “Create Checkout” button.
- Optional starter-template selector.
- Link to documentation.
- Detection warning when the block is not inside a native `woocommerce/checkout` block.

Suggested empty-state copy:

```text
WooTale Checkout Builder

Create a guided checkout using steps and drag-and-drop blocks.

Number of steps: [ 1 ] [ 2 ] [ 3 ] [ 4 Pro ] [ 5 Pro ]

[ Create Checkout ]
```

### 3.2 Step limits

**Free version**

- Minimum: 1 step.
- Maximum: 3 steps.

**Pro version**

- Minimum: 1 step.
- Maximum: 5 steps.

The limit must be enforced for every insertion path:

- Empty-state generator.
- Add Step button.
- Block inserter.
- Duplicate block.
- Copy and paste.
- Patterns.
- Transforms.
- Synced or reusable content where applicable.
- Imported configurations.
- REST or programmatic insertion.

Do not enforce limits only by hiding interface controls.

If Pro is deactivated or a licence expires after a merchant has saved four or five steps:

- Continue rendering the existing checkout safely.
- Do not remove saved steps.
- Do not break checkout.
- Prevent adding or duplicating additional Pro-only steps.
- Allow deletion and basic recovery.
- Display an editor notice explaining the licence state.

### 3.3 Drag-and-drop behavior

The editor must allow drag-and-drop of:

1. WooTale Step blocks.
2. WooTale Section blocks.
3. WooTale custom input blocks.
4. Supported WooCommerce native checkout blocks.

The user experience must make native WooCommerce blocks appear assignable to steps. However, implementation must not corrupt the native Checkout block, Store API state, payment gateway state, or WooCommerce block validation.

See the mandatory feasibility gate in Section 5 before implementing physical nesting of native WooCommerce blocks.

### 3.4 Step presentation

Support both step-navigation orientations:

- Horizontal.
- Vertical.

Provide responsive settings:

- Desktop orientation.
- Tablet orientation.
- Mobile orientation.
- Optional automatic vertical layout below a breakpoint.
- Mobile accordion style.
- Scroll active step into view.
- Sticky step navigation where safe.

Support indicator styles:

- Numbers.
- Icons.
- Number plus title.
- Icon plus title.
- Progress bar.
- Tabs.
- Vertical timeline.
- Compact mobile indicator.

### 3.5 Checkout routing

Provide three customer-flow modes:

```text
Standard:
Product → Cart → Checkout

Skip Cart:
Product → Checkout

Buy Now:
Product → Add to Cart or Buy Now → Checkout
```

Free version must include:

- Standard Cart → Checkout.
- Global Skip Cart option.
- Optional separate Buy Now button.

Pro version may add:

- Per-product routing.
- Per-category routing.
- Per-product-type routing.
- Skip Cart only when the cart contains one item.
- Clear or preserve the existing cart.
- Custom Buy Now labels.
- Conditional routing by user role.
- Conditional routing by virtual/downloadable product status.

The routing feature must direct customers to the configured WooCommerce Checkout page containing the native Checkout block and WooTale builder. It must not create a second checkout engine.

---

## 4. Edition boundaries

## 4.1 Free version

The free WordPress.org plugin must be useful without Pro.

### Builder

- One checkout workflow.
- One to three steps.
- Add, delete, rename, duplicate, and reorder steps within the free limit.
- Step descriptions.
- Step IDs that remain stable after reordering.
- Step sections.
- Desktop and mobile preview.
- Undo/redo through native block-editor history.
- Import/export of a free-compatible configuration.
- Configuration validation before save.

### Supported native WooCommerce blocks

Initially target native blocks that belong to the Checkout Fields area:

- Contact Information.
- Shipping Address.
- Billing Address.
- Delivery / Shipping Method.
- Shipping Options.
- Pickup Options where available.
- Order Note.
- Additional Information.
- Express Checkout.
- Payment Options.
- Terms and Conditions.
- Checkout Actions / Place Order.

Important:

- Core blocks that are singletons must not be duplicated.
- Payment, Terms, and Place Order must remain in a valid final-step sequence.
- Address blocks must remain available before shipping-dependent methods.
- Order Summary and checkout totals belong to a separate WooCommerce block tree and are not arbitrary step children in version 1.
- Individual payment gateway blocks are managed inside WooCommerce’s Payment block and are not independently draggable.

### WooTale fields

- Text.
- Email.
- Telephone.
- Number.
- Textarea.
- Select.
- Radio.
- Checkbox.
- Heading.
- Paragraph.
- Notice.
- Divider.

### Basic field controls

- Label.
- Field key.
- Placeholder.
- Description.
- Default value.
- Required.
- Width: 25%, 33%, 50%, 66%, 75%, 100%.
- Autocomplete attribute.
- Input mode where appropriate.
- Basic validation.
- Show in order admin.
- Show in customer email.
- Show in administrator email.
- Show on thank-you page.
- Show in My Account order details.

### Basic step styling

- Horizontal or vertical.
- Number or icon indicator.
- Active color.
- Completed color.
- Inactive color.
- Text color.
- Background color.
- Connector color.
- Border width and color.
- Border radius.
- Step gap.
- Content spacing.
- Navigation button alignment.
- Previous/Continue button labels.
- Use theme button styling or basic custom button colors.
- Responsive orientation.

### Checkout behavior

- Validate the current step before Continue.
- Previous and Continue navigation.
- Optional click navigation to completed steps.
- Preserve entered values when navigating backward.
- Preserve active step during non-destructive WooCommerce recalculations.
- Standard, Skip Cart, and Buy Now modes.
- Checkout diagnostics panel.
- HPOS-compatible order storage.
- Guest and logged-in checkout.

## 4.2 Pro add-on

The Pro add-on must depend on the free plugin and extend it through documented PHP and JavaScript extension points. Do not duplicate the free plugin’s engine.

### Pro builder

- Up to five steps.
- Multiple workflows.
- Workflow assignment by product, category, product type, cart contents, user role, and customer status.
- Conditional steps.
- Conditional sections.
- Conditional fields.
- Reusable workflow templates.
- Industry templates.
- Workflow duplication.
- Version history and rollback.
- Advanced import/export.
- Migration tools for selected competitor plugins.

### Advanced fields

- Date.
- Time.
- Date range.
- File upload.
- Hidden field.
- Repeater.
- Signature.
- Terms acceptance.
- HTML/content block with safe sanitization.
- Product-specific fields.
- Quantity-aware fields.

Do not implement file upload until secure private storage, permissions, signed download access, MIME verification, size limits, retention controls, and cleanup are complete.

### Advanced rules

Conditions may use:

- Product.
- Product category.
- Product tag.
- Product type.
- Cart subtotal.
- Cart quantity.
- Coupon.
- Shipping country.
- Billing country.
- State.
- Postcode.
- Shipping method.
- Pickup method.
- Payment method.
- User role.
- Logged-in state.
- Customer order history.
- Previous WooTale field value.
- Virtual/downloadable cart.
- Subscription or booking product detection through integrations.

Support grouped AND/OR logic with a readable rule builder.

### Advanced styling

- Per-step colors.
- Per-step icons.
- Dashicons and a bundled, accessible icon set.
- Optional sanitized custom SVG upload only after security review.
- Custom connector styles.
- Step title and description typography.
- Responsive controls by device.
- Animations with reduced-motion support.
- Layout presets.
- Custom CSS field only for privileged users and only after capability/security review.
- Saved design presets.

### Pro commerce features

- Field-based fixed fees.
- Field-based percentage fees.
- Taxable fee settings.
- Multiple conditional fees.
- Saved customer field profiles.
- Webhooks.
- Automation integrations.
- Checkout analytics without collecting sensitive field values.
- Step drop-off reporting.
- Validation-error reporting.
- A/B testing only after the stable checkout engine exists.

### Pro routing

- Per-product and per-category Buy Now.
- Conditional cart clearing.
- Preserve-cart option.
- Redirect after login.
- Different workflow per cart composition.
- Direct checkout for virtual/downloadable products.

---

## 5. Mandatory feasibility gate: native WooCommerce blocks inside steps

This is the highest-risk part of the product.

WooCommerce native checkout blocks currently declare specific parents, and several have inserter/lock restrictions. The editor UX requires merchants to drag them into WooTale steps, but physical reparenting is not automatically a supported WooCommerce extension point.

### 5.1 Required Phase 0 spike

Before building the complete plugin, create an isolated prototype that tests:

1. A `wootale/checkout-builder` block inside `woocommerce/checkout-fields-block`.
2. A `wootale/checkout-step` block inside the builder.
3. Extending supported WooCommerce native block registration so a native block can be inserted or moved into `wootale/checkout-step`.
4. Saving and reopening the page without block-validation errors.
5. Rendering frontend checkout without JavaScript errors.
6. Store API updates after changing:
   - Email.
   - Country.
   - Address.
   - Postcode.
   - Shipping method.
   - Billing address.
7. Payment processing with:
   - WooPayments or Stripe.
   - PayPal Payments.
   - Cash on Delivery.
   - Direct Bank Transfer.
8. Moving backward and forward without losing selected payment state.
9. Mobile and desktop rendering.
10. WooCommerce update compatibility.

### 5.2 Route A: physical nesting

Use physical nesting only if the prototype demonstrates that:

- Native blocks render and operate correctly.
- Parent/context requirements remain satisfied.
- Block serialization remains valid.
- The checkout data stores and payment components remain stable.
- WooCommerce template recovery does not remove or relocate blocks.
- No unsupported core-file modifications are required.
- The approach can be guarded by automated tests.

Potential techniques may include editor registration filters and carefully extending parent/allowed-block settings, but do not assume this is safe merely because the editor permits insertion.

### 5.3 Route B: logical nesting fallback

If physical nesting is unreliable, implement logical step assignment while preserving the same merchant experience:

- The builder canvas shows draggable cards/previews for native WooCommerce blocks.
- Dragging assigns each native block to a WooTale step.
- Canonical native blocks remain in WooCommerce’s supported hierarchy.
- Store a stable map of native block name/client identity to step ID.
- The frontend step controller shows the assigned components for the active step.
- Do not clone native payment or address components.
- Do not create duplicate source-of-truth instances.
- Keep payment components mounted whenever required to preserve gateway state.

The product requirement is the drag-and-drop step-assignment experience. Physical DOM or block-tree nesting is secondary to checkout reliability.

### 5.4 Stop condition

If neither physical nor logical assignment can safely support native payment, address, shipping, and Place Order components:

- Stop implementation.
- Produce a technical blocker report.
- Do not ship a checkout that merely looks correct but breaks payment or order processing.

---

## 6. Block architecture

Recommended blocks:

```text
wootale/checkout-builder
wootale/checkout-step
wootale/checkout-section

wootale/field-text
wootale/field-email
wootale/field-phone
wootale/field-number
wootale/field-textarea
wootale/field-select
wootale/field-radio
wootale/field-checkbox

wootale/content-heading
wootale/content-paragraph
wootale/content-notice
wootale/content-divider
```

Pro blocks:

```text
wootale/field-date
wootale/field-time
wootale/field-date-range
wootale/field-file
wootale/field-repeater
wootale/field-signature
wootale/field-hidden
wootale/conditional-content
```

Recommended conceptual tree:

```text
woocommerce/checkout
├── woocommerce/checkout-fields-block
│   └── wootale/checkout-builder
│       ├── wootale/checkout-step
│       │   ├── supported WooCommerce native block or logical assignment
│       │   ├── wootale/checkout-section
│       │   │   └── WooTale fields/content
│       │   └── WooTale fields/content
│       ├── wootale/checkout-step
│       └── wootale/checkout-step
└── woocommerce/checkout-totals-block
```

Each block may contain only one `InnerBlocks` area. Use nested child blocks rather than multiple `InnerBlocks` instances inside one block.

### 6.1 Stable identifiers

Every builder, step, section, and field must have a stable UUID-style ID stored in attributes.

Never use `clientId` as persistent business identity because it can change after copy/paste, patterns, and parsing.

Suggested attributes:

```json
{
  "workflowId": "wtcb_workflow_xxx",
  "stepId": "wtcb_step_xxx",
  "sectionId": "wtcb_section_xxx",
  "fieldId": "wtcb_field_xxx"
}
```

Regenerate IDs when duplicating a field or importing a workflow to avoid collisions.

### 6.2 Builder attributes

Suggested attributes:

```json
{
  "workflowId": "",
  "version": 1,
  "orientationDesktop": "horizontal",
  "orientationTablet": "horizontal",
  "orientationMobile": "vertical",
  "indicatorStyle": "number",
  "showStepTitles": true,
  "allowCompletedStepNavigation": true,
  "validateOnContinue": true,
  "rememberActiveStep": false,
  "showOrderSummary": "always",
  "routingMode": "cart",
  "style": {}
}
```

### 6.3 Step attributes

```json
{
  "stepId": "",
  "title": "",
  "description": "",
  "icon": "",
  "continueLabel": "Continue",
  "previousLabel": "Previous",
  "isOptional": false,
  "conditions": [],
  "style": {}
}
```

### 6.4 Field schema

Use one internal schema for editor rendering, frontend rendering, validation, storage, and display.

```json
{
  "fieldId": "wtcb_field_delivery_date",
  "type": "text",
  "key": "delivery_reference",
  "label": "Delivery reference",
  "description": "",
  "placeholder": "",
  "required": false,
  "defaultValue": "",
  "width": "100",
  "autocomplete": "off",
  "validation": [],
  "conditions": [],
  "storage": {
    "order": true,
    "customer": false
  },
  "display": {
    "admin": true,
    "customerEmail": true,
    "adminEmail": true,
    "thankYou": true,
    "myAccount": true
  }
}
```

Field keys must be unique within a workflow.

---

## 7. Editor experience

## 7.1 Initial setup panel

Model the experience after a visual table/form-builder empty state.

Controls:

- Step count.
- Starter layout:
  - Contact → Delivery → Payment.
  - Contact → Details → Payment.
  - One-page/custom.
- Horizontal or vertical preview.
- Create button.

When steps are generated, insert step blocks through the block-editor data API in one undoable action where possible.

## 7.2 Canvas

Each Step block should show:

- Step number or icon.
- Editable title.
- Editable description.
- Drag handle.
- Duplicate action.
- Delete action.
- Add Section action.
- Inner block appender.
- Free/Pro limit feedback.
- Locked system-block notice where applicable.
- Validation warnings.

Use native block-editor selection, movers, list view, keyboard controls, and undo history. Do not implement a completely separate drag system unless native block APIs cannot meet the requirement.

## 7.3 Inspector controls

Organize settings into three tabs:

```text
Content | Style | Advanced
```

### Content

Builder:

- Step layout.
- Navigation.
- Validation behavior.
- Routing mode.
- Order summary visibility.
- Mobile behavior.

Step:

- Title.
- Description.
- Icon.
- Button labels.
- Optional/required status.
- Conditions in Pro.

Field:

- Label.
- Key.
- Placeholder.
- Required.
- Default.
- Validation.
- Display locations.

### Style

Builder:

- Orientation.
- Indicator style.
- Colors.
- Connector.
- Border.
- Radius.
- Spacing.
- Buttons.
- Responsive controls.

Step:

- Per-step styling in Pro.
- Icon styling.
- Active/completed/inactive styling.

Field:

- Prefer theme/global styles.
- Width and spacing.
- Avoid creating a full arbitrary form-design engine in version 1.

### Advanced

- HTML anchor.
- Additional CSS class.
- Debug information.
- Workflow ID.
- Copy configuration.
- Reset styles.
- Licence-related controls where applicable.

## 7.4 Toolbar

Builder toolbar:

- Preview mode.
- Horizontal/vertical switch.
- Add Step.
- Import/export.
- Diagnostics.
- Documentation.

Step toolbar:

- Move.
- Duplicate.
- Delete.
- Change icon.
- Mark optional.
- Open step settings.

---

## 8. Native WooCommerce block rules

Maintain a central compatibility registry.

Example:

```ts
type NativeBlockRule = {
    name: string;
    singleton: boolean;
    required: boolean;
    allowedSteps: 'any' | 'first' | 'final';
    mustComeBefore?: string[];
    mustComeAfter?: string[];
    canUnmount: boolean;
};
```

Initial rules:

### Contact Information

- Singleton.
- Recommended in first step.
- Must be before final submission.
- Email validation must complete before advancing.

### Shipping Address

- Singleton.
- Must precede shipping-rate-dependent selection.
- Changes may trigger cart recalculation.

### Billing Address

- Singleton.
- Must be available before payment submission.
- Do not remove required gateway/fraud fields without warnings.

### Shipping Method and Shipping Options

- Singleton where WooCommerce requires.
- Must follow enough address data to calculate rates.
- Continue must wait while rates update.

### Express Checkout

- Singleton.
- Do not place inside arbitrary conditional steps without payment testing.
- May be disabled for incompatible layouts.

### Payment Options

- Singleton.
- Final step only in version 1.
- Keep mounted when gateway state requires it.
- Do not clone payment components.

### Terms and Conditions

- Singleton.
- Final step.
- Before Place Order.

### Checkout Actions / Place Order

- Singleton.
- Required.
- Final step.
- Last actionable checkout component.

### Order Summary / Totals

- Remain in the native totals branch.
- Provide visibility settings:
  - Always visible.
  - Final step only.
  - Collapsible on mobile.
  - Sticky on desktop.
- Do not physically nest inside a WooTale step in version 1.

When a merchant attempts an invalid move:

- Prevent it.
- Return the block to its valid location.
- Show an actionable notice.
- Never silently save an invalid checkout.

---

## 9. Frontend multi-step controller

The frontend controller must coordinate with the native WooCommerce Checkout Block.

### Required behavior

- Render step indicator.
- Show one active step.
- Preserve all entered values.
- Validate current-step WooTale fields.
- Trigger or respect native WooCommerce validation.
- Wait for shipping/tax/cart recalculation before advancing.
- Move focus to the step heading after navigation.
- Announce step changes to screen readers.
- Scroll the active step into view.
- Return the customer to the step containing an error.
- Keep order summary synchronized.
- Submit through WooCommerce’s native Place Order flow.

### State

Temporary UI state:

- Active step.
- Completed steps.
- Expanded mobile section.
- Temporary preview state.

Transactional state must remain in WooCommerce’s data stores and Store API.

Do not create a second cart, customer, totals, or checkout state.

### Browser history

Version 1 may avoid modifying browser history. If history support is added:

- Use replace/push state carefully.
- Never lose checkout data.
- Back should navigate steps before leaving checkout only when thoroughly tested.
- Refresh should recover safely or start at the first invalid step.

### Error handling

- Show an error summary at the top of the active step.
- Link error items to fields.
- Focus the first invalid field.
- Do not hide WooCommerce server errors.
- When final validation fails in an earlier step, navigate to that step automatically.

---

## 10. Custom checkout field processing

For native-supported basic fields, evaluate WooCommerce’s Additional Checkout Fields API.

For arbitrary fields inside WooTale steps, use WooCommerce Store API extension data and server-side processing.

### Required pipeline

1. Register the field definition.
2. Render editor preview.
3. Render checkout input.
4. Update checkout extension data.
5. Define Store API schema.
6. Sanitize server-side.
7. Validate server-side.
8. Save through WooCommerce order CRUD.
9. Display in admin/email/thank-you/My Account when configured.
10. Include privacy/export/erasure behavior when personal data is stored.

### Storage

- Use WooCommerce order APIs.
- Remain HPOS-compatible.
- Prefix all order meta keys.
- Do not write directly to order post tables.
- Do not expose sensitive data through public Store API responses.
- Do not store card/payment details.

Suggested order meta:

```text
_wtcb_<field_key>
```

Keep a field-definition snapshot or readable label where needed so old orders remain understandable after a workflow changes.

---

## 11. Styling system

Prefer block supports, CSS custom properties, and theme/global styles.

Suggested CSS variables:

```css
--wtcb-active-color
--wtcb-completed-color
--wtcb-inactive-color
--wtcb-step-text-color
--wtcb-step-bg
--wtcb-connector-color
--wtcb-border-color
--wtcb-border-width
--wtcb-radius
--wtcb-step-gap
--wtcb-content-gap
--wtcb-icon-size
--wtcb-transition-duration
```

Requirements:

- Scope all selectors to WooTale blocks.
- Do not style unrelated WooCommerce blocks globally.
- Avoid `!important` unless documenting a narrow compatibility exception.
- Respect `prefers-reduced-motion`.
- Meet WCAG contrast expectations.
- Use logical CSS properties for RTL support.
- Test long translated step titles.
- Prevent horizontal overflow on mobile.
- Ensure vertical mode supports keyboard and screen-reader navigation.

---

## 12. Suggested repository structure

Free plugin:

```text
wootale-checkout-builder/
├── wootale-checkout-builder.php
├── readme.txt
├── composer.json
├── package.json
├── phpcs.xml.dist
├── phpstan.neon
├── webpack.config.js or @wordpress/scripts config
├── includes/
│   ├── Plugin.php
│   ├── Activator.php
│   ├── Compatibility/
│   │   ├── WooCommerce.php
│   │   ├── Blocks.php
│   │   └── HPOS.php
│   ├── Blocks/
│   │   ├── BlockRegistry.php
│   │   └── AssetLoader.php
│   ├── Checkout/
│   │   ├── Workflow.php
│   │   ├── StepRules.php
│   │   ├── NativeBlockRegistry.php
│   │   ├── FieldRegistry.php
│   │   ├── Validation.php
│   │   ├── Persistence.php
│   │   └── Display.php
│   ├── StoreApi/
│   │   ├── Schema.php
│   │   └── CheckoutExtension.php
│   ├── Routing/
│   │   ├── Settings.php
│   │   ├── SkipCart.php
│   │   └── BuyNow.php
│   ├── Admin/
│   │   ├── Settings.php
│   │   └── Diagnostics.php
│   └── Support/
│       ├── Capabilities.php
│       ├── Sanitizer.php
│       └── Logger.php
├── src/
│   ├── editor/
│   │   ├── index.ts
│   │   ├── store/
│   │   ├── components/
│   │   ├── native-blocks/
│   │   └── styles/
│   ├── frontend/
│   │   ├── index.tsx
│   │   ├── step-controller/
│   │   ├── fields/
│   │   └── styles/
│   └── shared/
│       ├── types.ts
│       ├── constants.ts
│       ├── schema.ts
│       └── rules.ts
├── blocks/
│   ├── checkout-builder/
│   ├── checkout-step/
│   ├── checkout-section/
│   ├── field-text/
│   ├── field-email/
│   ├── field-phone/
│   ├── field-number/
│   ├── field-textarea/
│   ├── field-select/
│   ├── field-radio/
│   ├── field-checkbox/
│   ├── content-heading/
│   ├── content-paragraph/
│   ├── content-notice/
│   └── content-divider/
├── assets/
│   ├── images/
│   └── icons/
├── languages/
└── tests/
    ├── php/
    ├── js/
    ├── e2e/
    └── fixtures/
```

Pro add-on:

```text
wootale-checkout-builder-pro/
├── wootale-checkout-builder-pro.php
├── composer.json
├── package.json
├── includes/
│   ├── Plugin.php
│   ├── Licensing/
│   ├── Conditions/
│   ├── Fees/
│   ├── Analytics/
│   ├── Templates/
│   ├── Migrations/
│   └── Fields/
├── src/
│   ├── editor/
│   ├── frontend/
│   └── shared/
├── blocks/
│   ├── field-date/
│   ├── field-time/
│   ├── field-file/
│   ├── field-repeater/
│   └── field-signature/
└── tests/
```

The Pro add-on must register capabilities through free-plugin hooks/interfaces.

Example extension points:

```php
apply_filters( 'wtcb_available_field_types', $types );
apply_filters( 'wtcb_max_steps', 3, $workflow );
apply_filters( 'wtcb_condition_operators', $operators );
do_action( 'wtcb_register_integrations', $registry );
```

Use a JavaScript filter/registry for editor extensions rather than importing Pro code into Free.

---

## 13. Technical stack

### PHP

- Namespaced object-oriented PHP.
- Composer PSR-4 autoloading.
- WordPress Coding Standards.
- WooCommerce CRUD APIs.
- WooCommerce HPOS compatibility declarations.
- Store API extension interfaces.
- Action Scheduler only for genuine background work.
- Strict capability checks.
- Nonces for privileged requests.
- Translation-ready strings.

### JavaScript

- React.
- TypeScript.
- `@wordpress/blocks`.
- `@wordpress/block-editor`.
- `@wordpress/components`.
- `@wordpress/data`.
- `@wordpress/api-fetch`.
- `@wordpress/i18n`.
- `@wordpress/element`.
- `@wordpress/icons`.
- `@wordpress/scripts`.
- WooCommerce Blocks registry and data packages where officially available.

Prefer native Gutenberg drag/drop and block list behavior. Do not introduce another drag-and-drop framework until a spike proves it is necessary.

### Testing and quality

- PHPUnit.
- PHPCS.
- PHPStan.
- Jest or Vitest where compatible with the WordPress build.
- React Testing Library.
- Playwright.
- `wp-env`.
- GitHub Actions.
- BrowserStack or equivalent manual matrix when available.

---

## 14. Compatibility targets

Determine exact current versions at project initialization and document them in `README.md`.

Baseline guidance:

- Require a WordPress version that supports the block metadata and APIs actually used.
- Require WooCommerce Checkout Blocks and the Store API features used by the plugin.
- Prefer WooCommerce versions supporting the Additional Checkout Fields API where used.
- Follow WooCommerce’s current PHP minimum.
- Test the latest stable WordPress and WooCommerce.
- Test at least two recent WooCommerce minor versions when feasible.
- Test supported PHP versions from WooCommerce’s current minimum through current stable PHP.

Never hardcode support claims without automated or documented manual tests.

### Required themes

- Storefront.
- Twenty Twenty-Five or current default block theme.
- Blocksy.
- Astra.

### Required payment methods

- WooPayments or Stripe.
- PayPal Payments.
- Cash on Delivery.
- Direct Bank Transfer.

### Important checkout scenarios

- Guest checkout.
- Logged-in checkout.
- Account creation.
- Physical products.
- Virtual products.
- Downloadable products.
- Variable products.
- Coupons.
- Taxes.
- Local Pickup.
- Multiple shipping methods.
- Shipping address different from billing.
- Empty cart.
- Out-of-stock item during checkout.
- Failed payment.
- Payment redirect.
- Browser refresh.
- Mobile checkout.

---

## 15. Security requirements

- Sanitize all block attributes before server-side use.
- Escape every rendered value according to context.
- Validate field keys against a strict pattern.
- Prevent duplicate or reserved field keys.
- Do not trust client-side validation.
- Use server-side Store API validation.
- Use WooCommerce order CRUD.
- Protect privileged REST routes with capabilities and nonces.
- Avoid unsafe HTML.
- Allow HTML only through strict `wp_kses` policies.
- Never expose secrets in Store API schemas.
- Never store payment credentials.
- Avoid collecting unnecessary personal data.
- Add WordPress personal-data exporter/eraser support when custom fields may contain personal data.
- Add retention settings before shipping file uploads or analytics.
- Add rate limiting or abuse protection where endpoints can be spammed.

---

## 16. Accessibility requirements

Editor:

- Native keyboard movers.
- Non-drag alternatives.
- Accessible labels for all controls.
- Announced limit and validation errors.
- Focus visible.
- No color-only states.

Frontend:

- Step list with semantic labels.
- `aria-current="step"` on the active item.
- Screen-reader announcement after step changes.
- Focus the active step heading.
- Error summary and links to invalid fields.
- Proper labels and descriptions.
- Required state exposed programmatically.
- Keyboard-accessible Previous/Continue controls.
- Reduced-motion support.
- Sufficient contrast.
- Logical reading order in horizontal and vertical layouts.

Accessibility is part of acceptance, not a later enhancement.

---

## 17. Performance requirements

- Load editor assets only in the block editor when relevant.
- Load frontend assets only when the builder block exists.
- Do not load Pro assets when Pro features are unused.
- Avoid repeated full-tree parsing during every keystroke.
- Memoize derived step/native-block maps.
- Keep block attributes reasonably small.
- Avoid large inline serialized datasets.
- Use WooCommerce data stores rather than polling.
- Debounce non-critical preview calculations.
- Do not block Place Order with analytics requests.
- Use indexed custom tables only if analytics or high-volume data genuinely requires them.
- Avoid custom tables for ordinary workflow configuration in version 1.

---

## 18. Development phases

## Phase 0 — feasibility and risk spike

Deliver:

- Minimal plugin scaffold.
- Builder and Step prototype.
- Native WooCommerce block nesting experiment.
- Logical-assignment fallback experiment.
- Test notes for block validation, checkout state, and payment behavior.
- Architecture decision record:
  - Route A physical nesting.
  - Route B logical nesting.
  - Blocker if neither is safe.

Do not begin the full interface before this decision.

## Phase 1 — plugin foundation

Deliver:

- Free plugin bootstrap.
- Dependency checks.
- Block registration.
- Asset loading.
- HPOS declaration.
- TypeScript build.
- Test setup.
- CI baseline.
- Diagnostics screen.

## Phase 2 — editor MVP

Deliver:

- Empty-state generator.
- One-to-three-step creation.
- Step editing.
- Step reordering.
- Sections.
- Custom field insertion.
- Step-limit enforcement.
- Inspector controls.
- List View compatibility.
- Basic preview.

## Phase 3 — frontend step flow

Deliver:

- Horizontal and vertical stepper.
- Previous/Continue.
- Active/completed state.
- Validation.
- Focus management.
- Responsive behavior.
- Order summary visibility.
- Native WooCommerce state synchronization.

## Phase 4 — field persistence

Deliver:

- Store API extension schema.
- Client extension-data updates.
- Server sanitization and validation.
- HPOS-compatible order storage.
- Admin/email/thank-you/My Account display.
- Privacy support.

## Phase 5 — routing

Deliver:

- Standard Cart flow.
- Global Skip Cart.
- Buy Now.
- Variable-product safeguards.
- Existing-cart behavior.
- Empty-cart fallback.
- Settings and documentation.

## Phase 6 — style controls

Deliver:

- Horizontal/vertical controls.
- Indicator choices.
- Icon/number mode.
- Colors.
- Connector.
- Spacing.
- Border/radius.
- Responsive controls.
- Accessible defaults.

## Phase 7 — Pro framework

Deliver:

- Pro add-on bootstrap.
- Step limit extension to five.
- Feature registry.
- Licence abstraction.
- Conditional rules.
- Advanced fields in isolated increments.
- Multiple workflows.
- Templates.
- Advanced routing.

## Phase 8 — hardening and release

Deliver:

- Payment matrix.
- Theme matrix.
- Accessibility audit.
- Security review.
- Performance profiling.
- Migration/recovery testing.
- Documentation.
- Screenshots.
- Demo workflows.
- WordPress.org-compliant Free package.
- Separate Pro package.

---

## 19. Acceptance criteria

### Editor acceptance

- A merchant can insert WooTale Checkout Builder in the native Checkout block.
- Empty state creates one to three steps in Free.
- Pro creates up to five.
- Steps can be renamed and reordered.
- Custom fields can be dragged among steps.
- Supported native WooCommerce blocks can be assigned to steps through the approved architecture.
- Invalid native-block placement is prevented with a useful message.
- Duplicate singleton blocks are prevented.
- Reloading the editor produces no block-validation warnings.
- Undo/redo works.
- List View reflects the workflow.
- Keyboard-only editing is possible.

### Frontend acceptance

- Only the active step is presented as active/visible according to the selected architecture.
- Previous/Continue preserves values.
- Required fields prevent advancement.
- Shipping/tax recalculations complete before advancement.
- Payment remains functional after navigating backward and forward.
- Server errors return users to the correct step.
- Place Order remains native WooCommerce behavior.
- Cart totals remain accurate.
- Refresh does not corrupt checkout.
- Horizontal and vertical modes work.
- Mobile layout has no horizontal overflow.
- Direct checkout and Buy Now handle edge cases safely.

### Free/Pro acceptance

- Free cannot create more than three steps.
- Pro can create five.
- Existing Pro workflows do not break after licence expiry.
- Free contains no dormant premium implementation that violates WordPress.org expectations.
- Pro uses extension points rather than replacing core classes.

### Quality acceptance

- PHPCS passes.
- PHPStan passes at the configured level.
- JavaScript/TypeScript lint passes.
- Unit tests pass.
- End-to-end checkout tests pass.
- No PHP warnings/notices.
- No browser-console errors.
- No Store API schema errors.
- No block-validation errors.
- HPOS compatibility is verified.
- Accessibility checks have no critical failures.

---

## 20. Non-goals for version 1

Do not implement these in the first stable Free release:

- Classic shortcode checkout.
- Full replacement of WooCommerce Checkout.
- Independent payment processing.
- Funnel builder.
- One-click post-purchase upsells.
- Order bumps.
- Cart abandonment.
- A/B testing.
- Full page-builder-level design system.
- Arbitrary custom JavaScript.
- Arbitrary movement of checkout totals into unsupported locations.
- Rebuilding payment gateway components.
- Dozens of third-party integrations.
- File uploads without complete security design.

Keep version 1 focused on a reliable block checkout workflow.

---

## 21. Coding rules for Codex

1. Inspect the repository before changing files.
2. Read this entire document before planning.
3. Start with Phase 0 and do not skip the native-block feasibility gate.
4. State assumptions before implementing uncertain WooCommerce behavior.
5. Prefer official WordPress and WooCommerce APIs.
6. Do not edit WordPress, WooCommerce, or gateway plugin core files.
7. Do not use undocumented DOM selectors as the only architecture for critical checkout behavior.
8. Do not hide checkout errors.
9. Do not duplicate payment, address, cart, totals, or order state.
10. Keep commits and changes focused by phase.
11. Add tests with each feature.
12. Preserve backward compatibility for saved block attributes.
13. Add a deprecation/migration path when changing block schemas.
14. Use semantic versioning.
15. Document public hooks and filters.
16. Keep Free and Pro responsibilities separate.
17. Never claim support for a payment gateway, theme, or WooCommerce version without testing.
18. Report blockers honestly rather than implementing unsafe hacks.
19. Keep the public checkout operational when Pro is disabled.
20. Before finishing any task, run the relevant lint, unit, and end-to-end checks and summarize results.

---

## 22. Suggested first Codex task

Use this prompt after placing `AGENTS.md` in the plugin repository root:

```text
Read AGENTS.md completely.

Begin Phase 0 only.

Create a minimal WooTale Checkout Builder plugin scaffold and a technical feasibility prototype that determines whether supported WooCommerce native Checkout Blocks can be physically nested inside a custom wootale/checkout-step block without breaking block validation, Store API state, shipping updates, or payment processing.

Also prototype the logical step-assignment fallback described in AGENTS.md.

Do not build the full product yet.

Deliver:
1. The minimal plugin scaffold.
2. Builder and Step blocks.
3. The two native-block assignment experiments.
4. Automated tests that are practical at this stage.
5. An ADR document recommending Route A, Route B, or stopping because neither is safe.
6. Setup and test commands.
7. A concise list of unresolved risks.
```

---

## 23. Official technical references

Use current official documentation as the source of truth and re-check it during implementation:

- WordPress nested blocks and InnerBlocks:  
  https://developer.wordpress.org/block-editor/how-to-guides/block-tutorial/nested-blocks-inner-blocks/

- WordPress block metadata, parent, ancestor, and allowedBlocks:  
  https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/

- WooCommerce Cart and Checkout inner-block filter:  
  https://developer.woocommerce.com/docs/block-development/extensible-blocks/cart-and-checkout-blocks/filters-in-cart-and-checkout/additional-cart-checkout-inner-block-types/

- WooCommerce Checkout block reference and native parent relationships:  
  https://developer.woocommerce.com/docs/block-development/reference/block-references/

- WooCommerce Additional Checkout Fields API:  
  https://developer.woocommerce.com/docs/block-development/extensible-blocks/cart-and-checkout-blocks/additional-checkout-fields/

- Adding custom checkout fields through Store API extension data:  
  https://developer.woocommerce.com/docs/apis/store-api/extending-store-api/extend-store-api-add-custom-fields/

- Extending the WooCommerce Store API:  
  https://developer.woocommerce.com/docs/apis/store-api/extending-store-api/

- Available Store API extensible endpoints:  
  https://developer.woocommerce.com/docs/apis/store-api/extending-store-api/available-endpoints-to-extend

---

## 24. Final product principle

The checkout must remain dependable before it becomes visually flexible.

When a design request conflicts with WooCommerce payment, shipping, tax, Store API, accessibility, or order-processing integrity, preserve checkout integrity and provide the closest safe editor experience.
