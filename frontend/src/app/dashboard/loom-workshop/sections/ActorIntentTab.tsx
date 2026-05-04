'use client';

import React, { useState } from 'react';
import { motion } from 'framer-motion';
import { User, Brain, Target, Zap, MessageSquare, Loader2, Sparkles } from 'lucide-react';
import { toast } from 'sonner';
import api from '@/lib/api';
import { useUniverse } from '@/contexts/UniverseContext';

interface IntentResult {
  action: string;
  intensity: number;
  target: string | null;
  reasoning: string;
  confidence: number;
}

export default function ActorIntentTab() {
  const { activeUniverseId } = useUniverse();
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState<IntentResult | null>(null);
  
  const [formData, setFormData] = useState({
    actor_id: '',
    actor_name: '',
    archetype: '',
    traits: { aggression: 0.5, intelligence: 0.5, charisma: 0.5 },
    entropy: 0.5,
    stability_index: 0.5,
    myth_intensity: 0.5,
    tick: 1,
    recent_biography: ''
  });

  const actions = [
    'revolt', 'form_contract', 'migrate', 'trade', 
    'suppress_revolt', 'propagate_myth', 'explore', 'conquer'
  ];

  const archetypes = [
    'Warlord', 'Diplomat', 'Explorer', 'Merchant', 'Mystic', 
    'Scientist', 'Revolutionary', 'Guardian'
  ];

  const simulateIntent = async () => {
    if (!activeUniverseId) {
      toast.error('Please select a universe first');
      return;
    }

    if (!formData.actor_name || !formData.archetype) {
      toast.error('Please fill in actor name and archetype');
      return;
    }

    setLoading(true);
    setResult(null);

    try {
      const response = await api.post('/loom/actor-intent', {
        actor_id: parseInt(formData.actor_id) || Date.now(),
        actor_name: formData.actor_name,
        archetype: formData.archetype,
        traits: formData.traits,
        universe_context: {
          entropy: formData.entropy,
          stability_index: formData.stability_index,
          myth_intensity: formData.myth_intensity,
          tick: formData.tick
        },
        world_era: 'genesis',
        recent_biography: formData.recent_biography,
        available_actions: actions
      });

      setResult(response.data);
      toast.success('Intent simulated successfully');
    } catch (error) {
      console.error('ActorIntent simulation failed:', error);
      toast.error('Failed to simulate intent');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
      {/* Input Form */}
      <div className="space-y-4">
        <div className="p-4 bg-slate-900 border border-slate-800 rounded-xl">
          <h3 className="text-xs uppercase tracking-widest text-violet-400 mb-4 flex items-center gap-2">
            <User className="w-3 h-3" />
            Actor Profile
          </h3>
          
          <div className="space-y-3">
            <div>
              <label className="text-[10px] uppercase text-slate-500 mb-1 block">Actor Name</label>
              <input
                type="text"
                value={formData.actor_name}
                onChange={(e) => setFormData({ ...formData, actor_name: e.target.value })}
                className="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm focus:outline-none focus:border-violet-500"
                placeholder="e.g. Emperor Kael"
              />
            </div>

            <div>
              <label className="text-[10px] uppercase text-slate-500 mb-1 block">Archetype</label>
              <select
                value={formData.archetype}
                onChange={(e) => setFormData({ ...formData, archetype: e.target.value })}
                className="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm focus:outline-none focus:border-violet-500"
              >
                <option value="">Select archetype...</option>
                {archetypes.map(a => (
                  <option key={a} value={a}>{a}</option>
                ))}
              </select>
            </div>

            <div>
              <label className="text-[10px] uppercase text-slate-500 mb-1 block">Recent Biography</label>
              <textarea
                value={formData.recent_biography}
                onChange={(e) => setFormData({ ...formData, recent_biography: e.target.value })}
                className="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm focus:outline-none focus:border-violet-500 h-20 resize-none"
                placeholder="Recent events that shape this actor's mindset..."
              />
            </div>
          </div>
        </div>

        <div className="p-4 bg-slate-900 border border-slate-800 rounded-xl">
          <h3 className="text-xs uppercase tracking-widest text-cyan-400 mb-4 flex items-center gap-2">
            <Brain className="w-3 h-3" />
            Personality Traits (0-1)
          </h3>
          
          <div className="space-y-3">
            {Object.entries(formData.traits).map(([trait, value]) => (
              <div key={trait}>
                <div className="flex justify-between text-[10px] uppercase text-slate-500 mb-1">
                  <span>{trait}</span>
                  <span>{value.toFixed(2)}</span>
                </div>
                <input
                  type="range"
                  min="0"
                  max="1"
                  step="0.1"
                  value={value}
                  onChange={(e) => setFormData({
                    ...formData,
                    traits: { ...formData.traits, [trait]: parseFloat(e.target.value) }
                  })}
                  className="w-full h-1 bg-slate-800 rounded-full appearance-none cursor-pointer accent-violet-500"
                />
              </div>
            ))}
          </div>
        </div>

        <div className="p-4 bg-slate-900 border border-slate-800 rounded-xl">
          <h3 className="text-xs uppercase tracking-widest text-emerald-400 mb-4 flex items-center gap-2">
            <Target className="w-3 h-3" />
            Universe Context
          </h3>
          
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="text-[10px] uppercase text-slate-500 mb-1 block">Entropy</label>
              <input
                type="number"
                min="0"
                max="1"
                step="0.1"
                value={formData.entropy}
                onChange={(e) => setFormData({ ...formData, entropy: parseFloat(e.target.value) })}
                className="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm"
              />
            </div>
            <div>
              <label className="text-[10px] uppercase text-slate-500 mb-1 block">Stability</label>
              <input
                type="number"
                min="0"
                max="1"
                step="0.1"
                value={formData.stability_index}
                onChange={(e) => setFormData({ ...formData, stability_index: parseFloat(e.target.value) })}
                className="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm"
              />
            </div>
            <div>
              <label className="text-[10px] uppercase text-slate-500 mb-1 block">Myth Level</label>
              <input
                type="number"
                min="0"
                max="1"
                step="0.1"
                value={formData.myth_intensity}
                onChange={(e) => setFormData({ ...formData, myth_intensity: parseFloat(e.target.value) })}
                className="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm"
              />
            </div>
            <div>
              <label className="text-[10px] uppercase text-slate-500 mb-1 block">Tick</label>
              <input
                type="number"
                value={formData.tick}
                onChange={(e) => setFormData({ ...formData, tick: parseInt(e.target.value) })}
                className="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm"
              />
            </div>
          </div>
        </div>

        <button
          onClick={simulateIntent}
          disabled={loading || !activeUniverseId}
          className="w-full flex items-center justify-center gap-2 px-6 py-3 bg-violet-500/20 text-violet-400 border border-violet-500/30 rounded-xl font-medium hover:bg-violet-500/30 transition-all disabled:opacity-50"
        >
          {loading ? (
            <>
              <Loader2 className="w-4 h-4 animate-spin" />
              Simulating...
            </>
          ) : (
            <>
              <Sparkles className="w-4 h-4" />
              Simulate Intent
            </>
          )}
        </button>
      </div>

      {/* Result */}
      <div>
        {result ? (
          <motion.div
            initial={{ opacity: 0, scale: 0.95 }}
            animate={{ opacity: 1, scale: 1 }}
            className="p-6 bg-gradient-to-br from-violet-950/30 to-slate-900/60 border border-violet-500/20 rounded-xl"
          >
            <div className="flex items-center gap-3 mb-6">
              <div className="w-10 h-10 rounded-xl bg-violet-500/20 flex items-center justify-center">
                <Zap className="w-5 h-5 text-violet-400" />
              </div>
              <div>
                <h3 className="font-semibold text-white">Decision Result</h3>
                <p className="text-xs text-slate-500">Actor Intent Simulation</p>
              </div>
            </div>

            <div className="space-y-4">
              <div className="p-4 bg-slate-800 rounded-lg">
                <p className="text-[10px] uppercase tracking-widest text-slate-500 mb-2">Selected Action</p>
                <p className="text-xl font-bold text-violet-400 capitalize">{result.action}</p>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div className="p-3 bg-slate-800 rounded-lg">
                  <p className="text-[10px] uppercase text-slate-500 mb-1">Intensity</p>
                  <div className="flex items-center gap-2">
                    <div className="flex-1 h-2 bg-slate-800 rounded-full overflow-hidden">
                      <div 
                        className="h-full bg-amber-500 rounded-full"
                        style={{ width: `${result.intensity * 100}%` }}
                      />
                    </div>
                    <span className="text-sm font-medium">{(result.intensity * 100).toFixed(0)}%</span>
                  </div>
                </div>

                <div className="p-3 bg-slate-800 rounded-lg">
                  <p className="text-[10px] uppercase text-slate-500 mb-1">Confidence</p>
                  <div className="flex items-center gap-2">
                    <div className="flex-1 h-2 bg-slate-800 rounded-full overflow-hidden">
                      <div 
                        className="h-full bg-emerald-500 rounded-full"
                        style={{ width: `${result.confidence * 100}%` }}
                      />
                    </div>
                    <span className="text-sm font-medium">{(result.confidence * 100).toFixed(0)}%</span>
                  </div>
                </div>
              </div>

              {result.target && (
                <div className="p-3 bg-slate-800 rounded-lg">
                  <p className="text-[10px] uppercase text-slate-500 mb-1">Target</p>
                  <p className="text-sm text-white">{result.target}</p>
                </div>
              )}

              <div className="p-4 bg-slate-800 rounded-lg">
                <p className="text-[10px] uppercase text-slate-500 mb-2 flex items-center gap-1">
                  <MessageSquare className="w-3 h-3" />
                  Reasoning
                </p>
                <p className="text-sm text-slate-300 leading-relaxed">{result.reasoning}</p>
              </div>
            </div>
          </motion.div>
        ) : (
          <div className="h-full min-h-[400px] flex items-center justify-center border border-dashed border-slate-800 rounded-xl">
            <div className="text-center">
              <Brain className="w-12 h-12 text-slate-700 mx-auto mb-3" />
              <p className="text-slate-500 text-sm">Configure actor and universe context</p>
              <p className="text-slate-600 text-xs mt-1">Then click Simulate Intent</p>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
