import type { Metadata, Viewport } from "next";
import { Space_Grotesk, JetBrains_Mono } from "next/font/google";
import { AppProviders } from "./providers";
import "./globals.css";

const spaceGrotesk = Space_Grotesk({
  subsets: ["latin"],
  weight: ["300", "400", "500", "600", "700"],
  variable: "--font-space-grotesk",
  display: "swap",
});

const jetbrainsMono = JetBrains_Mono({
  subsets: ["latin"],
  weight: ["400", "500", "600"],
  variable: "--font-jetbrains-mono",
  display: "swap",
});

export const metadata: Metadata = {
  title: {
    default: "WorldOS V6 — Civilizational Dynamics Engine",
    template: "%s | WorldOS V6",
  },
  description:
    "WorldOS V6: An autonomous multi-universe simulation platform. Monitor civilization evolution, causal topology, actor dynamics, and narrative emergence in real-time.",
  keywords: ["simulation", "WorldOS", "multiverse", "civilization", "AI", "entropy"],
  openGraph: {
    title: "WorldOS V6 — Civilizational Dynamics Engine",
    description:
      "Real-time multi-universe simulation dashboard. Observe the unfolding of civilizations at the edge of entropy.",
    type: "website",
  },
};

export const viewport: Viewport = {
  themeColor: "#080810",
  colorScheme: "dark",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html
      lang="en"
      className={`dark ${spaceGrotesk.variable} ${jetbrainsMono.variable}`}
    >
      <body className="antialiased font-sans bg-[var(--bg-base)] text-slate-200 custom-scrollbar">
        <AppProviders>{children}</AppProviders>
      </body>
    </html>
  );
}
