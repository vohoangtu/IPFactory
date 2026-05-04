# WorldOS V10 - Meaning Systems & Ideological Drift
# Quy tắc củng cố hoặc sụp đổ ý nghĩa dựa trên hằng số thực tại.

rule Meaning_Coherence_Decay
priority 10
scope global
category culture
trigger tick
when
entropy > 0.7
then
    # Hỗn loạn cao làm xói mòn niềm tin và trật tự ý tưởng
drift meta.meaning_systems.coherence target 0.4 speed 0.02
emit_event IDEOLOGICAL_DISSOLUTION
metadata severity "WARNING"
metadata description "Sự hỗn loạn của thực tại đang làm xói mòn tính nhất quán của các hệ thống tư tưởng."

rule Religion_Stability_Anchor
priority 15
scope global
category religion
trigger tick
when
stability_index > 0.8
meaning_systems.type == "RELIGION"
then
    # Sự ổn định cao củng cố tầm ảnh hưởng của tôn giáo
drift meta.meaning_systems.influence target 0.9 speed 0.01
emit_event COLLECTIVE_FAITH_STRENGTHENED
metadata description "Trong kỷ nguyên thanh bình, đức tin trở thành ngọn hải đăng neo giữ tâm hồn quần thể."

rule Ideology_Radicalization
priority 15
scope global
category ideology
trigger social_unrest
when
social_unrest > 0.9
meaning_systems.type == "IDEOLOGY"
then
    # Bất ổn xã hội cực đoan thúc đẩy các hệ tư tưởng cực đoan
drift meta.meaning_systems.coherence target 1.0 speed 0.1
drift meta.meaning_systems.influence target 0.8 speed 0.05
emit_event IDEOLOGICAL_RADICALIZATION
metadata description "Sự phẫn nộ tập thể đã biến các ý niệm ôn hòa thành những giáo điều cực đoan."