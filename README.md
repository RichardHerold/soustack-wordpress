# Soustack sidecar publishing

This repository contains the WordPress plugin and an accompanying Next.js adapter scaffold for publishing Soustack-compatible sidecar JSON feeds and consuming them in a modern frontend.

- **WordPress plugin**: exposes `/soustack/<slug>.soustack.json`, emits discovery links, and validates payloads during publishing.
- **Next.js adapter**: provides an example Soustack route handler, an embed-friendly page, and a CI-ready conformance check.

## WordPress plugin

### Installation

1. Copy the `soustack-wordpress.php` file and the `includes/` directory into your WordPress installation’s `wp-content/plugins/` directory (or keep them as a mu-plugin).
2. Activate **Soustack Sidecar Publisher** in **Plugins → Installed Plugins**.
3. Visit **Settings → Soustack** to toggle strict validation mode if you want to block publishing on invalid payloads.

### What it does

- Registers a clean URL endpoint at `/soustack/{slug}.soustack.json` that returns the Soustack payload for the matching post/page.
- Outputs `<link rel="alternate" type="application/vnd.soustack+json" href="…">` for singular posts/pages to aid discovery.
- Validates Soustack payloads on publish; in **strict** mode publishing is blocked with an error if validation fails.

### Endpoint shape

The endpoint returns a JSON document with a stable shape suitable for sidecar publishing:

```json
{
  "id": "123",
  "slug": "hello-world",
  "title": "Hello World",
  "excerpt": "Short description…",
  "content": "<p>Rendered HTML…</p>",
  "url": "https://example.com/hello-world",
  "feature_image": "https://example.com/uploads/hello.jpg",
  "author": "Editor Name",
  "published_at": "2024-06-01T12:00:00+00:00",
  "updated_at": "2024-06-02T10:30:00+00:00"
}
```

### Validation

The plugin ships with a lightweight `Soustack_Core` validator that mirrors the expected `soustack-core` API:

- **Required**: `id`, `slug`, `title`, `url`, `content`.
- **Warnings**: missing excerpts, author names, or feature images are allowed but surfaced in the publish notice.
- **Strict mode**: enable in **Settings → Soustack** to block publish when validation errors are present.

Validation results are stored in the `_soustack_validation_errors` post meta for visibility.

## Next.js adapter

The `next-adapter/` folder is a starter/template for consuming Soustack JSON in a Next.js (App Router) project.

### Key files

- `next-adapter/app/api/soustack/[slug]/route.ts`: example route handler that serves Soustack JSON for sample content.
- `next-adapter/app/embed/page.tsx`: simple embed example consuming a Soustack payload.
- `next-adapter/package.json`: includes `@soustack/conformance` and a `test:soustack` script for CI validation.

### Running locally

```bash
cd next-adapter
npm install
npm run dev
# Visit http://localhost:3000/api/soustack/hello-world.soustack.json
```

### Conformance in CI

The provided npm script runs the conformance check against a running dev server:

```bash
npm run test:soustack
```

Wire this into your CI by starting the dev server (or a production build) and then invoking the script to validate your Soustack endpoints.

### Embeds

`app/embed/page.tsx` demonstrates embedding a Soustack payload using client-side fetch. Replace the endpoint URL and rendering logic to suit your frontend.

## Development

- Flush permalinks after activation if WordPress does not immediately recognize the `/soustack/{slug}.soustack.json` endpoint.
- The plugin uses WordPress hooks and does not require Composer; the Next.js scaffold is TypeScript-based and App Router–ready.

