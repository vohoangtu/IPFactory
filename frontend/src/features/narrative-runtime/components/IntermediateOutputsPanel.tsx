'use client';

import React, { useState } from 'react';

interface IntermediateOutputsPanelProps {
  historicalOutline?: unknown;
  storyboard?: unknown;
  finalProse?: string;
}

const IntermediateOutputsPanel: React.FC<IntermediateOutputsPanelProps> = ({
  historicalOutline,
  storyboard,
  finalProse,
}) => {
  const [expandedSection, setExpandedSection] = useState<string | null>(null);

  const toggleSection = (section: string) => {
    setExpandedSection(expandedSection === section ? null : section);
  };

  return (
    <div className="space-y-4">
      {/* Historical Outline */}
      {Boolean(historicalOutline) && (
        <div className="border border-white/10 bg-black/40 rounded-xl overflow-hidden">
          <button
            onClick={() => toggleSection('historical')}
            className="w-full px-4 py-3 flex items-center justify-between hover:bg-white/5 transition-colors"
          >
            <span className="text-xs font-bold uppercase tracking-widest text-violet-400">
              Historical Outline
            </span>
            <span className="text-gray-500">{expandedSection === 'historical' ? '−' : '+'}</span>
          </button>
          {expandedSection === 'historical' && (
            <div className="px-4 pb-4 border-t border-white/10">
              <pre className="text-[11px] text-gray-300 font-mono whitespace-pre-wrap overflow-auto max-h-64">
                {typeof historicalOutline === 'string' 
                  ? historicalOutline 
                  : (JSON.stringify(historicalOutline, null, 2) ?? '')}
              </pre>
            </div>
          )}
        </div>
      )}

      {/* Storyboard */}
      {Boolean(storyboard) && (
        <div className="border border-white/10 bg-black/40 rounded-xl overflow-hidden">
          <button
            onClick={() => toggleSection('storyboard')}
            className="w-full px-4 py-3 flex items-center justify-between hover:bg-white/5 transition-colors"
          >
            <span className="text-xs font-bold uppercase tracking-widest text-cyan-400">
              Storyboard
            </span>
            <span className="text-gray-500">{expandedSection === 'storyboard' ? '−' : '+'}</span>
          </button>
          {expandedSection === 'storyboard' && (
            <div className="px-4 pb-4 border-t border-white/10">
              <pre className="text-[11px] text-gray-300 font-mono whitespace-pre-wrap overflow-auto max-h-64">
                {typeof storyboard === 'string' 
                  ? storyboard 
                  : (JSON.stringify(storyboard, null, 2) ?? '')}
              </pre>
            </div>
          )}
        </div>
      )}

      {/* Final Prose */}
      {finalProse && (
        <div className="border border-emerald-500/30 bg-emerald-950/20 rounded-xl overflow-hidden">
          <button
            onClick={() => toggleSection('prose')}
            className="w-full px-4 py-3 flex items-center justify-between hover:bg-white/5 transition-colors"
          >
            <span className="text-xs font-bold uppercase tracking-widest text-emerald-400">
              Final Prose
            </span>
            <span className="text-gray-500">{expandedSection === 'prose' ? '−' : '+'}</span>
          </button>
          {expandedSection === 'prose' && (
            <div className="px-4 pb-4 border-t border-white/10">
              <p className="text-sm text-gray-300 font-serif leading-relaxed whitespace-pre-wrap line-clamp-none">
                {finalProse}
              </p>
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default IntermediateOutputsPanel;
