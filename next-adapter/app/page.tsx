import Link from "next/link";

export default function Home() {
  return (
    <main style={{ padding: "24px" }}>
      <div className="card stack">
        <h1>Soustack Next adapter</h1>
        <p>
          This starter shows how to serve Soustack JSON via a route handler and how to embed it
          from a Next.js frontend.
        </p>
        <ul>
          <li>
            Example payload:{" "}
            <Link href="/api/soustack/hello-world.soustack.json">
              /api/soustack/hello-world.soustack.json
            </Link>
          </li>
          <li>
            Embed demo: <Link href="/embed">/embed</Link>
          </li>
        </ul>
      </div>
    </main>
  );
}
