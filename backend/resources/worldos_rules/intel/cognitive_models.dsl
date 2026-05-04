# WorldOS Actor Cognition DSL (V2)
# Modeling Homeostatic Regulation and Causal Response

# --- 1. Homeostatic Regulation (Needs) ---

rule Homeostatic_Regulation_Base
priority 10
scope global
when
    true == true
then
    set hunger (1.0 - (energy / maxEnergy))
    set safety 0.8
    set reproduction 0.2
    set belonging_need 0.5

rule Homeostatic_Energy_Starvation
priority 11
when
    energy < (maxEnergy * 0.2)
then
    add hunger 1.2

rule Homeostatic_Collapse_Safety
priority 11
when
    is_collapse_active == true
then
    add safety -0.4

rule Homeostatic_Repro_Boost
priority 11
when
    energy > (maxEnergy * 0.7)
    generation < 10
then
    add reproduction 0.3

# --- 2. Personality Manifold ---

rule Personality_Manifold_Sync
priority 20
when
    true == true
then
    set trait_dominance (trait 0)
    set trait_ambition (trait 1)
    set trait_coercion (trait 2)
    set trait_empathy (trait 4)
    set trait_curiosity (trait 8)
    set trait_dogmatism (trait 9)
    set trait_risk (trait 10)
    set trait_fear (trait 11)
    set trait_hope (trait 13)
    set trait_pride (trait 15)
    set trait_solidarity (trait 5)
    set trait_pragmatism (trait 7)

# --- 3. Causal Resonance ---

rule Causal_Anxiety_Dynamics
priority 25
when
    causal_integrity < 0.4
then
    set causal_anxiety (1.0 - causal_integrity)
    add trait_fear (0.2 * causal_anxiety)
    add trait_risk (0.1 * causal_anxiety)

# --- 4. Motivation Synthesis ---

rule Motivation_Synthesis_Calc
priority 30
when
    true == true
then
    set mSurvival ((arch_survival * 0.7) + (trait_fear * 0.3))
    set mRepro ((arch_reproduction * 0.7) + (trait_hope * 0.3))
    set mWealth ((arch_wealth * 0.7) + (trait_pragmatism * 0.3))
    set mPower ((arch_power * 0.7) + (trait_dominance * 0.3))
    set mKnowledge ((arch_knowledge * 0.7) + (trait_curiosity * 0.3))
    set mMeaning ((arch_meaning * 0.7) + (trait_hope * 0.3))
    set mStatus ((arch_status * 0.7) + (trait_pride * 0.3))
    set mBelonging ((arch_belonging * 0.7) + (trait_solidarity * 0.3))

# --- 5. Action Utility Scoring ---

rule Action_Utility_Base
priority 40
when
    true == true
then
    set score_idle 0.1
    set score_eat (hunger * 1.5 + field_survival * 0.5)
    set score_flee (field_power * trait_fear * 2.0 + (1.0 - field_survival) * 1.5)
    set score_mate (reproduction * 1.2 + field_reproduction * mRepro + field_meaning * 0.3)
    set score_explore (trait_curiosity * 0.5 + field_knowledge * mKnowledge + field_status * 0.3)
    set score_battle (field_power * mPower * 1.5 + field_status * 0.5)
    set score_research (field_knowledge * mKnowledge * 2.5 + field_meaning * 0.5)
    set score_trade (field_wealth * mWealth * 2.0 + field_status * 0.4)
    set score_meditate (field_meaning * mMeaning * 2.2 + field_belonging * 0.3)

rule Cultural_Resonance_Adjustment
priority 41
when
    true == true
then
    set culture_weight 0.3
    set score_battle (score_battle * (1.0 + (meme_power - 0.5) * culture_weight))
    set score_research (score_research * (1.0 + (meme_knowledge - 0.5) * culture_weight))
    set score_meditate (score_meditate * (1.0 + (meme_meaning - 0.5) * culture_weight))
    set score_trade (score_trade * (1.0 + (meme_wealth - 0.5) * culture_weight))
    set score_mate (score_mate * (1.0 + (meme_reproduction - 0.5) * culture_weight))

rule Heroic_Acceleration_Scientist
priority 42
when
    is_heroic == true
    heroic_type == "SCIENTIST"
then
    set score_research (score_research * 2.0)

rule Heroic_Acceleration_General
priority 42
when
    is_heroic == true
    heroic_type == "GENERAL"
then
    set score_battle (score_battle * 2.0)

rule Heroic_Acceleration_Merchant
priority 42
when
    is_heroic == true
    heroic_type == "MERCHANT"
then
    set score_trade (score_trade * 2.0)

rule Heroic_Acceleration_Prophet
priority 42
when
    is_heroic == true
    heroic_type == "PROPHET"
then
    set score_meditate (score_meditate * 2.0)