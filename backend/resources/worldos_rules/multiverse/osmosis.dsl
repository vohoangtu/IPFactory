# WorldOS V6 - Multiverse Osmosis Rules (V2)
# Handles reality bleeding between high-resonance universes.

rule Calculate_Reality_Bleed
priority 10
scope global
category multiverse
trigger resonance
chance 1.0
cooldown 1
when
resonance > 0.75
then
    # Innovation Bleed
when
source.innovation > target.innovation
then
calc innov_diff
formula "(source.innovation - target.innovation)"
calc innov_bleed
formula "(innov_diff * 0.05 * resonance)"
      
      # Use drift to smoothly accumulate the gain in the bleed vessel
drift bleed.innovation_gain target innov_bleed speed 1.0

    # Spirituality Bleed
when
source.spirituality > target.spirituality
then
calc spirit_diff
formula "(source.spirituality - target.spirituality)"
calc spirit_bleed
formula "(spirit_diff * 0.05 * resonance)"
      
drift bleed.spirituality_gain target spirit_bleed speed 1.0

    # Myth Bleed
when
source.myth > target.myth
then
calc myth_diff
formula "(source.myth - target.myth)"
calc myth_bleed
formula "(myth_diff * 0.05 * resonance)"
      
drift bleed.myth_gain target myth_bleed speed 1.0

    # Entropy Bleed (Harmful leakage from high-entropy source)
when
source.entropy > target.entropy
then
calc entropy_diff
formula "(source.entropy - target.entropy)"
calc entropy_bleed
formula "(entropy_diff * 0.02 * resonance)"
      
drift bleed.entropy_gain target entropy_bleed speed 1.0