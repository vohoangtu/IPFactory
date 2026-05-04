"use client";

import { motion } from "framer-motion";

interface WeatherEngineProps {
  entropy: number;
}

export default function WeatherEngine({ entropy }: WeatherEngineProps) {
  // If entropy is high, map distorts and gets glitchy overlay
  const isHighStrain = entropy > 8.0;

  return (
    <div className="absolute inset-0 pointer-events-none overflow-hidden z-0">
      {/* 2D Cyber Grid Base */}
      <div 
        className="absolute inset-0 bg-[linear-gradient(rgba(20,184,166,0.1)_1px,transparent_1px),linear-gradient(90deg,rgba(20,184,166,0.1)_1px,transparent_1px)] bg-[size:40px_40px] opacity-30"
        style={{
          boxShadow: "inset 0 0 100px rgba(0,0,0,0.9)"
        }}
      />
      
      {/* Sweeping Radar Line */}
      <motion.div
        animate={{ rotate: 360 }}
        transition={{ duration: 10, repeat: Infinity, ease: "linear" }}
        className="absolute top-1/2 left-1/2 w-[150%] h-1 origin-left bg-gradient-to-r from-transparent via-teal-500/20 to-teal-400"
        style={{ transform: "translate(-50%, -50%)" }}
      />
      <motion.div
        animate={{ rotate: 360 }}
        transition={{ duration: 10, repeat: Infinity, ease: "linear" }}
        className="absolute top-1/2 left-1/2 w-[150%] h-[150%] opacity-10 origin-top-left bg-[conic-gradient(from_0deg,transparent_0deg,rgba(20,184,166,0.4)_10deg,transparent_40deg)]"
        style={{ transform: "translate(-50%, -50%)" }}
      />

      {/* High Strain / Entropy Effect */}
      {isHighStrain && (
        <motion.div 
          initial={{ opacity: 0 }}
          animate={{ opacity: [0.1, 0.3, 0.1, 0.5, 0.0] }}
          transition={{ duration: 2, repeat: Infinity, repeatType: "mirror" }}
          className="absolute inset-0 bg-red-900/10 mix-blend-color-burn filter contrast-150"
        >
          <div className="absolute inset-0 bg-[linear-gradient(transparent_50%,rgba(0,0,0,0.5)_50%)] bg-[length:100%_4px]" />
          <div className="absolute top-10 left-10 p-2 border border-red-500/50 text-red-500 text-xs font-mono font-bold blink">
            WARNING: CRITICAL ENTROPY (STRAIN &gt; 8.0). REALITY BLEED DETECTED.
          </div>
        </motion.div>
      )}

      {/* Scanlines Effect Overlay (Global) */}
      <div className="absolute inset-0 bg-[url('https://grainy-gradients.vercel.app/noise.svg')] opacity-10 mix-blend-overlay"></div>
    </div>
  );
}
