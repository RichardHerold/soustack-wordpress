import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "Soustack Next Adapter",
  description: "Example Next.js app exposing Soustack JSON and embed usage."
};

export default function RootLayout({
  children
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en">
      <body>{children}</body>
    </html>
  );
}
