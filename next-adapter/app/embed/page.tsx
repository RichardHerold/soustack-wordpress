/* eslint-disable react/no-danger */
"use client";

import { useEffect, useState } from "react";

type SoustackPayload = {
  title: string;
  excerpt: string;
  content: string;
  feature_image?: string;
  url: string;
};

export default function EmbedPage() {
  const [payload, setPayload] = useState<SoustackPayload | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetch("/api/soustack/hello-world.soustack.json")
      .then(async (res) => {
        if (!res.ok) {
          throw new Error("Unable to load Soustack payload");
        }
        return res.json();
      })
      .then((data) => setPayload(data))
      .catch((err) => setError(err.message));
  }, []);

  return (
    <main style={{ padding: "24px" }}>
      <div className="card stack">
        <h1>Soustack embed</h1>
        <p>
          Fetch a Soustack payload at runtime and render it inside your frontend. Swap the fetch
          URL with your WordPress sidecar endpoint.
        </p>
        {error && <p style={{ color: "#ff7b72" }}>{error}</p>}
        {!error && !payload && <p>Loading Soustack payload…</p>}
        {payload && (
          <article className="stack">
            <header className="stack">
              <p style={{ margin: 0, opacity: 0.7 }}>{payload.url}</p>
              <h2 style={{ margin: 0 }}>{payload.title}</h2>
              {payload.feature_image && (
                <img
                  src={payload.feature_image}
                  alt={payload.title}
                  style={{ borderRadius: 12, width: "100%", maxWidth: 640 }}
                />
              )}
              <p style={{ margin: 0 }}>{payload.excerpt}</p>
            </header>
            <section dangerouslySetInnerHTML={{ __html: payload.content }} />
          </article>
        )}
      </div>
    </main>
  );
}
