# WorldOS V6 Material Lifecycle & Ontology Resonance DSL
# doc §8.3: Δ = k · Output · pressure_* · Resonance

# --- [Phase 1: Activation & Obsolescence] ---

rule can_activate
    # context: { metrics: {}, material_inputs: {} }
$ok = true;
for ($key, $minVal) in $material_inputs
if ($metrics[$key] < $minVal)
$ok = false;
return $ok;

rule should_obsolete
    # context: { metrics: {}, material_outputs: {} }
$trigger = false;
for ($key, $minRequired) in $material_outputs
if ($metrics[$key] < ($minRequired * 0.2))
$trigger = true;
return $trigger;

# --- [Phase 2: Mutation] ---

rule evaluate_mutation
    # context: { metrics: {}, condition_string: "entropy > 0.5 && order < 0.3" }
    # DSL will eventually handle complex boolean expressions natively.
    # For now, we simulate the condition check.
    
$parts = split($condition_string, "&&");
$all_satisfied = true;
    
for $p in $parts
        # logic for simple comparisons
        # (Simplified for now, PHP will pass pre-parsed or we can use native DSL if grammar allows)
    
return $all_satisfied;

# --- [Phase 3: Pressure Resonance] ---

rule calculate_deltas
    # inputs: {
    #   k: 0.003,
    #   output: 1.0,
    #   resonance: 1.2,
    #   coefficients: { entropy: 0.5, innovation: 0.3 },
    #   scars: ["civil_war_scar"],
    #   edicts: [{target: "order", multiplier: 0.8}]
    # }
    
$deltas = {};
for ($vector, $coef) in $coefficients
$val = $k * $output * $coef * $resonance;
        
        # Apply Scar Effects
if ($vector == "order" && contains($scars, "civil_war_scar"))
$val = $val * 0.5;
if ($vector == "entropy" && contains($scars, "nuclear_fallout"))
$val = $val * 1.5;
        
        # Apply Edicts
for $e in $edicts
if ($e.target == $vector)
$val = $val * $e.multiplier;
        
$deltas[$vector] = $val;
    
return $deltas;