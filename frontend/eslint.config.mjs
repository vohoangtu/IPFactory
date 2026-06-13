import { defineConfig, globalIgnores } from "eslint/config";
import nextVitals from "eslint-config-next/core-web-vitals";
import nextTs from "eslint-config-next/typescript";

const eslintConfig = defineConfig([
  ...nextVitals,
  ...nextTs,
  // Test files: cho phép `any` cho mock/stub (không áp no-explicit-any cho test).
  {
    files: ["src/**/__tests__/**/*.{ts,tsx}", "src/**/*.test.{ts,tsx}"],
    rules: {
      "@typescript-eslint/no-explicit-any": "off",
    },
  },
  // Architecture guardrail: enforce app → features → shared layering; no cross-feature internals.
  {
    files: ["src/shared/**/*.{ts,tsx}", "src/features/**/*.{ts,tsx}", "src/app/(workspace)/**/*.{ts,tsx}"],
    rules: {
      "no-restricted-imports": ["error", {
        patterns: [
          { group: ["@/features/*/*", "!@/features/*/index", "!@/features/*"], message: "Import features only via their index.ts (public API)." },
          { group: ["@/shared/*/**/internal/*"], message: "Do not import shared internals." },
        ],
      }],
    },
  },
  // Override default ignores of eslint-config-next.
  globalIgnores([
    // Default ignores of eslint-config-next:
    ".next/**",
    "out/**",
    "build/**",
    "next-env.d.ts",
  ]),
]);

export default eslintConfig;
