'use client';

const MODELS = [
  { value: 'gpt-4o', label: 'GPT-4o', provider: 'OpenAI' },
  { value: 'gpt-4o-mini', label: 'GPT-4o Mini', provider: 'OpenAI' },
  { value: 'claude-3-5-sonnet', label: 'Claude 3.5 Sonnet', provider: 'Anthropic' },
  { value: 'claude-3-haiku', label: 'Claude 3 Haiku', provider: 'Anthropic' },
  { value: 'gemini-1.5-pro', label: 'Gemini 1.5 Pro', provider: 'Google' },
  { value: 'gemini-flash', label: 'Gemini Flash', provider: 'Google' },
  { value: 'deepseek-chat', label: 'DeepSeek Chat', provider: 'DeepSeek' },
];

interface ModelSelectProps {
  value: string;
  onChange: (v: string) => void;
}

export default function ModelSelect({ value, onChange }: ModelSelectProps) {
  return (
    <select
      value={value}
      onChange={(e) => onChange(e.target.value)}
      className="w-full rounded border border-border-subtle bg-bg-base px-2 py-1.5 text-xs text-text-primary focus:border-brand-info focus:outline-none"
    >
      {MODELS.map((m) => (
        <option key={m.value} value={m.value}>
          {m.label} ({m.provider})
        </option>
      ))}
    </select>
  );
}
