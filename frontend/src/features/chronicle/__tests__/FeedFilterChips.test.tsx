import { describe, expect, it, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { FeedFilterChips } from '../components/FeedFilterChips';

describe('FeedFilterChips', () => {
  it('render 4 chip, toggle gọi onToggle với key, chip active có aria-pressed', () => {
    const onToggle = vi.fn();
    render(<FeedFilterChips active={['anomaly']} onToggle={onToggle} />);
    const anomaly = screen.getByRole('button', { name: 'Dị thường' });
    expect(anomaly.getAttribute('aria-pressed')).toBe('true');
    fireEvent.click(screen.getByRole('button', { name: 'Tường thuật' }));
    expect(onToggle).toHaveBeenCalledWith('narrative');
    expect(screen.getAllByRole('button')).toHaveLength(4);
  });
});
