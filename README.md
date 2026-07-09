# WP Dialyra

WP Dialyra is a WordPress/WooCommerce plugin for connecting a store to Dialyra call automation. It provides an admin UI for managing order call flows, audio prompts, call history, queue tools, departments, agents, and Dialyra settings.

## Purpose

The plugin helps WooCommerce stores automatically or manually place customer calls around orders. It is designed for workflows such as:

- Confirming COD or pending orders by phone.
- Playing IVR menu prompts to customers.
- Capturing DTMF responses.
- Updating order status based on customer input.
- Transferring calls to departments or agents.
- Reviewing call history, retry queues, and test tools.

## Current UI Pages

The admin area is routed through:

`wp-admin/admin.php?page=wp-dialyra&p={page}`

Available pages:

- `dashboard` — overview of calls, queue, outcomes, agents, and departments.
- `flows` — flow library with default flow and product-specific assignment controls.
- `flow-builder` — menu-based IVR builder.
- `flow-preview` — customer journey preview for a selected flow.
- `audio` — audio upload/list UI.
- `call-history` — completed/failed call history table.
- `queue-calls` — waiting/retry call queue UI.
- `agents` — agent and SIP extension UI.
- `departments` — department list, schedule, and agent binding UI.
- `test-tools` — test call and webhook tools.
- `settings` — access token, business, trigger, retry, hours, webhook, and mapping settings.
- `login` — connection/login screen.

## Flow Builder

The flow builder uses store-owner friendly terminology and avoids exposing raw backend nodes or edges.

Main concepts:

- Flow name and description.
- Menu list with a default start menu.
- Customer instruction message using Audio or Message/TTS configuration.
- DTMF actions for keypad input.
- Business actions such as confirm order, cancel order, and transfer department.
- Next-step options such as repeat menu, go to menu, hangup, and end flow.
- Invalid input and timeout handling.
- Transfer timeout and transfer failed handling.
- Preview page for a readable customer journey.

## Product-Specific Flow Assignment

The flow library includes a product assignment action. The current UI uses a modal picker with:

- Assignment modes: specific products, specific categories, or all products.
- Product search by name/SKU.
- Selectable product rows.
- Selected product summary.

This is currently UI-only and ready to be wired to real WooCommerce product data.

## Project Structure

```text
wp-dialyra.php                         Plugin bootstrap
includes/                              Core plugin loader, i18n, activation
admin/class-wp-dialyra-admin.php       Admin hooks and asset loading
admin/pages/wp-dialyra-admin-display.php
admin/pages/views/                     Admin page templates
admin/pages/assets/css/                Admin UI styles
admin/pages/assets/js/                 Admin UI scripts
public/                                Public-facing hooks/assets
languages/                             Translation files
```

## Development Notes

- Admin pages are separated into individual files under `admin/pages/views/`.
- The page renderer chooses views using the `p` URL parameter.
- Shared admin CSS/JS lives under `admin/pages/assets/`.
- Documentation files under `docs/` are intentionally ignored by Git.
- No build step is currently required.

## Installation

1. Copy the plugin folder into `wp-content/plugins/`.
2. Activate **WP Dialyra** from the WordPress Plugins screen.
3. Open **WP Dialyra** from the WordPress admin menu.
4. Connect Dialyra and configure settings when backend/API integration is available.

## Development Validation

Useful checks:

```bash
php -l wp-dialyra.php
php -l admin/pages/wp-dialyra-admin-display.php
php -l admin/pages/views/flows.php
php -l admin/pages/views/flow-builder.php
```

For broader validation, lint changed PHP view files with `php -l`.

## Status

This repository currently contains the admin UI foundation. Most screens are static UI prototypes and should be connected to real Dialyra/WooCommerce APIs in the next implementation phase.

## License

GPL-2.0-or-later. See `LICENSE.txt`.
