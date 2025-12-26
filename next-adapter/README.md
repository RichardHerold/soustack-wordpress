# Soustack Next adapter

This Next.js starter ships a ready-to-use Soustack JSON route handler, an embed demo, and a CI-friendly conformance script.

## Usage

```bash
npm install
npm run dev
```

- Soustack payload: http://localhost:3000/api/soustack/hello-world.soustack.json
- Embed demo: http://localhost:3000/embed

## Conformance

Run the provided script after your server is up:

```bash
npm run test:soustack
```

The script runs linting and then executes `@soustack/conformance` against the demo endpoint. Replace the URL in `package.json` with your own route once you hook up real data.

## Extending

- Update `app/api/soustack/[slug]/route.ts` to fetch real Soustack payloads (for example, from your WordPress site).
- The embed example in `app/embed/page.tsx` fetches client-side—replace the URL to hydrate your UI from the live sidecar endpoint.
