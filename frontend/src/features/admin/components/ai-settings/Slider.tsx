'use client';

interface SliderProps {
  label: string;
  value: number;
  min: number;
  max: number;
  step: number;
  unit?: string;
  onChange: (v: number) => void;
}

export default function Slider({ label, value, min, max, step, unit, onChange }: SliderProps) {
  return (
    <div className="space-y-1.5">
      <div className="flex items-center justify-between">
        <span className="text-xs text-text-muted">{label}</span>
        <span className="rounded bg-bg-elevated px-1.5 py-0.5 text-xs font-mono text-text-secondary">
          {value}
          {unit}
        </span>
      </div>
      <input
        type="range"
        min={min}
        max={max}
        step={step}
        value={value}
        onChange={(e) => onChange(parseFloat(e.target.value))}
        className="h-1 w-full cursor-pointer accent-brand-info"
      />
      <div className="flex justify-between text-[10px] text-text-disabled">
        <span>
          {min}
          {unit}
        </span>
        <span>
          {max}
          {unit}
        </span>
      </div>
    </div>
  );
}
