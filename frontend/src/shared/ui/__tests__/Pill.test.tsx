import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { Pill } from '../Pill';

describe('Pill', () => {
  it('renders label and tone class', () => {
    render(<Pill tone="active">LIVE</Pill>);
    const el = screen.getByText('LIVE');
    expect(el).toBeTruthy();
    expect(el.className).toContain('bg-');
  });
});
