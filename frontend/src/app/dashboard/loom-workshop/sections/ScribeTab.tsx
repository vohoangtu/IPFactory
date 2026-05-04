'use client';

import React, { useState } from 'react';
import { motion } from 'framer-motion';
import { ScrollText, Loader2, History, Clock, Sparkles } from 'lucide-react';
import { toast } from 'sonner';
import api from '@/lib/api';
import { useUniverse } from '@/contexts/UniverseContext';

interface ScribeResult {
  event_name: string;
  chronicle: string;
}

export default function ScribeTab() {
  const { activeUniverseId } = useUniverse();
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState<ScribeResult | null>(null);
  const [history, setHistory] = useState<ScribeResult[]>([]);
  
  const [formData, setFormData] = useState({
    event_type: 'battle',
    impact_score: 7.5,
    trigger_data: '{}',
    world_id: ''
  });

  const eventTypes = [
    'battle', 'discovery', 'diplomacy', 'disaster', 'miracle',
    'revolution', 'coronation', 'assassination', 'exploration', 'trade'
  ];

  const scribeHistory = async () => {
    const worldId = activeUniverseId || parseInt(formData.world_id);
    if (!worldId) {
      toast.error('Please select a universe or enter world ID');
      return;
    }

    setLoading(true);
    setResult(null);

    try {
      let triggerData = {};
      try {
        triggerData = JSON.parse(formData.trigger_data);
      } catch {
        // Invalid JSON, use empty object
      }

      const response = await api.post('/loom/scribe-history', {
        event_type: formData.event_type,
        impact_score: formData.impact_score,
        trigger_data: triggerData,
        world_id: worldId
      });

      const newResult = response.data.chronicle;
      setResult(newResult);
      setHistory(prev => [newResult, ...prev].slice(0, 10));
      toast.success('History scribed successfully');
    } catch (error) {
      console.error('Scribe history failed:', error);
      toast.error('Failed to scribe history');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
      {/* Input Form */}
      <div className="space-y-4">
        <div className="p-4 bg-slate-900 border border-slate-800 rounded-xl">
          <h3 className="text-xs uppercase tracking-widest text-amber-400 mb-4 flex items-center gap-2">
            <History className="w-3 h-3" />
            Event Details
          </h3>
          
          <div className="space-y-3">
            <div>
              <label className="text-[10px] uppercase text-slate-500 mb-1 block">Event Type</label>
              <select
                value={formData.event_type}
                onChange={(e) => setFormData({ ...formData, event_type: e.target.value })}
                className="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm focus:outline-none focus:border-amber-500"
              >
                {eventTypes.map(type => (
                  <option key={type} value={type}>{type.charAt(0).toUpperCase() + type.slice(1)}</option>
                ))}
              </select>
            </div>

            <div>
              <label className="text-[10px] uppercase text-slate-500 mb-1 block">Impact Score (0-10)</label>
              <div className="flex items-center gap-3">
                <input
                  type="range"
                  min="0"
                  max="10"
                  step="0.5"
                  value={formData.impact_score}
                  onChange={(e) => setFormData({ ...formData, impact_score: parseFloat(e.target.value) })}
                  className="flex-1 h-1 bg-slate-800 rounded-full appearance-none cursor-pointer accent-amber-500"
                />
                <span className="text-sm font-medium text-amber-400 w-12 text-right">
                  {formData.impact_score}
                </span>
              </div>
              <p className="text-[10px] text-slate-600 mt-1">
                {formData.impact_score < 5 ? 'Low impact - may be skipped for API cost savings' : 
                 formData.impact_score < 8 ? 'Medium impact - standard processing' : 
                 'High impact - detailed narrative generation'}
              </p>
            </div>

            {!activeUniverseId && (
              <div>
                <label className="text-[10px] uppercase text-slate-500 mb-1 block">World ID</label>
                <input
                  type="number"
                  value={formData.world_id}
                  onChange={(e) => setFormData({ ...formData, world_id: e.target.value })}
                  className="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm"
                  placeholder="Enter world ID..."
                />
              </div>
            )}

            <div>
              <label className="text-[10px] uppercase text-slate-500 mb-1 block">Trigger Data (JSON)</label>
              <textarea
                value={formData.trigger_data}
                onChange={(e) => setFormData({ ...formData, trigger_data: e.target.value })}
                className="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm font-mono text-xs focus:outline-none focus:border-amber-500 h-24 resize-none"
                placeholder='{"location": "Castle Black", "participants": ["Jon", "Ramsey"], "outcome": "victory"}'
              />
            </div>
          </div>
        </div>

        <button
          onClick={scribeHistory}
          disabled={loading}
          className="w-full flex items-center justify-center gap-2 px-6 py-3 bg-amber-500/20 text-amber-400 border border-amber-500/30 rounded-xl font-medium hover:bg-amber-500/30 transition-all disabled:opacity-50"
        >
          {loading ? (
            <>
              <Loader2 className="w-4 h-4 animate-spin" />
              Scribing...
            </>
          ) : (
            <>
              <ScrollText className="w-4 h-4" />
              Scribe History
            </>
          )}
        </button>
      </div>

      {/* Results */}
      <div className="space-y-4">
        {result && (
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            className="p-6 bg-gradient-to-br from-amber-950/30 to-slate-900/60 border border-amber-500/20 rounded-xl"
          >
            <div className="flex items-center gap-3 mb-4">
              <div className="w-10 h-10 rounded-xl bg-amber-500/20 flex items-center justify-center">
                <Sparkles className="w-5 h-5 text-amber-400" />
              </div>
              <div>
                <h3 className="font-semibold text-white">{result.event_name}</h3>
                <p className="text-xs text-slate-500">Just generated</p>
              </div>
            </div>

            <div className="p-4 bg-slate-800 rounded-lg">
              <p className="text-[10px] uppercase text-amber-400/70 mb-2 flex items-center gap-1">
                <ScrollText className="w-3 h-3" />
                Chronicle
              </p>
              <p className="text-slate-300 text-sm leading-relaxed font-serif whitespace-pre-wrap">
                {result.chronicle}
              </p>
            </div>
          </motion.div>
        )}

        {history.length > 0 && (
          <div className="p-4 bg-slate-900 border border-slate-800 rounded-xl">
            <h3 className="text-xs uppercase tracking-widest text-slate-500 mb-3 flex items-center gap-2">
              <Clock className="w-3 h-3" />
              Recent History ({history.length})
            </h3>
            <div className="space-y-2 max-h-64 overflow-y-auto">
              {history.map((item, idx) => (
                <div key={idx} className="p-3 bg-slate-800 rounded-lg cursor-pointer hover:bg-slate-700 transition-colors">
                  <p className="text-sm font-medium text-slate-300 truncate">{item.event_name}</p>
                  <p className="text-xs text-slate-500 line-clamp-2">{item.chronicle}</p>
                </div>
              ))}
            </div>
          </div>
        )}

        {!result && history.length === 0 && (
          <div className="h-full min-h-[300px] flex items-center justify-center border border-dashed border-slate-800 rounded-xl">
            <div className="text-center">
              <History className="w-12 h-12 text-slate-700 mx-auto mb-3" />
              <p className="text-slate-500 text-sm">No chronicles generated yet</p>
              <p className="text-slate-600 text-xs mt-1">Configure event and click Scribe History</p>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
