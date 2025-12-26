import { NextResponse } from "next/server";

type SoustackPayload = {
  id: string;
  slug: string;
  title: string;
  excerpt: string;
  content: string;
  url: string;
  feature_image?: string;
  author?: string;
  published_at: string;
  updated_at: string;
};

const demoPosts: Record<string, SoustackPayload> = {
  "hello-world": {
    id: "1",
    slug: "hello-world",
    title: "Hello from Soustack",
    excerpt: "Sample payload served by the Soustack Next adapter.",
    content: "<p>This is a demo Soustack payload rendered by a Next.js route handler.</p>",
    url: "https://example.com/hello-world",
    feature_image: "https://placekitten.com/1200/630",
    author: "Soustack Maintainer",
    published_at: new Date("2024-06-01T12:00:00Z").toISOString(),
    updated_at: new Date("2024-06-01T12:00:00Z").toISOString()
  }
};

function normalizeSlug(rawSlug: string): string {
  return rawSlug.replace(/\\.soustack\\.json$/i, "");
}

export async function GET(
  _request: Request,
  { params }: { params: { slug: string } }
) {
  const slug = normalizeSlug(params.slug);
  const payload = demoPosts[slug];

  if (!payload) {
    return NextResponse.json(
      { error: "Soustack payload not found." },
      { status: 404 }
    );
  }

  return NextResponse.json(payload, {
    headers: {
      "Content-Type": "application/vnd.soustack+json"
    }
  });
}
