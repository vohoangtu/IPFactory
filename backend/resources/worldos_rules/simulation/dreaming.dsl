# WorldOS V6 - The Dreaming Rules (Subconscious)
# Logic for generating 'Whispers' from the collective subconscious

rule Generate_Nightmare_Whispers
priority 10
scope zone
when
trauma > 0.7
cultural.myth_belief > 0.6
then
    # intensity calculation
set intensity (trauma * cultural.myth_belief)
    
emit_event WHISPER_NIGHTMARE
metadata type "nightmare"
metadata content "Tiếng khóc của sự sụp đổ vọng lại từ tương lai."
metadata intensity intensity
    # The PHP bridge will extract these metadata fields

rule Generate_Prophetic_Whispers
priority 10
scope zone
when
entropy < 0.2
embodied_knowledge > 0.8
then
    # intensity calculation
set inv_entropy (1.0 - entropy)
set intensity (inv_entropy * embodied_knowledge)
    
emit_event WHISPER_PROPHETIC
metadata type "causal_trajectory"
metadata content "Ánh sáng của trí tuệ đang dệt nên một trật tự mới."
metadata intensity intensity