import { describe, expect, it, vi } from 'vitest';
import { useState } from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import ProviderModelsPanel from '../components/ai-runtime/ProviderModelsPanel';
import type { AiProviderModel } from '../types';

const fixtureModels: AiProviderModel[] = [
  {
    id: 1,
    provider: 'openai',
    model_name: 'gpt-4o-mini',
    display_name: 'GPT-4o Mini',
    is_active: true,
    metadata: { tier: 'mini' },
  },
  {
    id: 2,
    provider: 'gemini',
    model_name: 'gemini-1.5-pro',
    display_name: 'Gemini 1.5 Pro',
    is_active: false,
    metadata: { tier: 'pro' },
  },
];

// ProviderModelsPanel is fully controlled by its parent (editingModel/onStartAdd/onChange),
// mirroring how AiRuntimeOps drives it — a tiny stateful wrapper reproduces that contract
// so the "Add Model" -> form-open flow can be exercised without pulling in the real page.
function Wrapper() {
  const [editingModel, setEditingModel] = useState<Partial<AiProviderModel> | null>(null);

  return (
    <ProviderModelsPanel
      providerModels={fixtureModels}
      editingModel={editingModel}
      isExporting={false}
      onStartAdd={() => setEditingModel({ provider: 'openai', is_active: true })}
      onEdit={(model) => setEditingModel(model)}
      onCancelEdit={() => setEditingModel(null)}
      onSave={vi.fn()}
      onChange={setEditingModel}
      onExport={vi.fn()}
      onImport={vi.fn()}
      onDelete={vi.fn()}
    />
  );
}

describe('ProviderModelsPanel', () => {
  it('hiển thị danh sách provider model từ props', () => {
    render(<Wrapper />);

    expect(screen.getByText('GPT-4o Mini')).toBeDefined();
    expect(screen.getByText('Gemini 1.5 Pro')).toBeDefined();
    expect(screen.getByText('Model: gpt-4o-mini')).toBeDefined();
    expect(screen.getByText('Model: gemini-1.5-pro')).toBeDefined();
  });

  it('không hiển thị form khi chưa bấm Add Model', () => {
    render(<Wrapper />);
    expect(screen.queryByText('Add Provider Model')).toBeNull();
  });

  it('bấm nút Add Model mở form thêm provider model', () => {
    render(<Wrapper />);

    fireEvent.click(screen.getByText('Add Model'));

    expect(screen.getByText('Add Provider Model')).toBeDefined();
  });
});
