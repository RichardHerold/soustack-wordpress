# Soustack Sidecar for WordPress

Publish Soustack recipe JSON from WordPress posts via a lightweight plugin.

## Features

- Serves Soustack JSON at `/soustack/<slug>.soustack.json` (with query fallback `?soustack=1&post=<id>` when permalinks are off).
- Adds discovery link tags on post pages for Soustack clients.
- Converts existing Schema.org Recipe JSON-LD to Soustack format when present.
- Provides a minimal fallback Soustack profile from post content when Schema.org is absent.
- Simple validation with a strict mode toggle.

## Installation

1. Copy the plugin folder into your WordPress `wp-content/plugins` directory.
2. Activate **Soustack Sidecar** from the WordPress Plugins page.
3. (Optional) Visit **Settings → Soustack** to adjust options:
   - Enable/disable the endpoint.
   - Change the base path (defaults to `/soustack/`).
   - Toggle strict validation (requires ingredients and instructions).

## Usage

- With pretty permalinks enabled, access `https://your-site.com/soustack/<post-slug>.soustack.json`.
- Without permalinks, use `https://your-site.com/?soustack=1&post=<post-id>`.
- On single post pages, a discovery tag is injected:
  ```html
  <link rel="alternate" type="application/vnd.soustack+json" href="..." />
  ```

## Validation behavior

- The plugin performs lightweight validation and sets `X-Soustack-Valid: true|false` on responses.
- If invalid, the JSON is still returned with `x-errors` describing issues and HTTP 200.

## Development

This MVP avoids external Node dependencies for shared hosting compatibility. A future version can swap the validator with `soustack-core` via a server-side integration.

