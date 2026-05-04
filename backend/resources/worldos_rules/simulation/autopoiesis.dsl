# Autopoiesis Logic (Mã nguồn tự sinh) 🧬💻
# Phần này định nghĩa các quy tắc để Simulation tự sửa đổi chính mình.

# 1. Trạng thái Ổn định (Entropy Control)
on entropy > 0.9:
emit_event AUTOPOIESIS_STABILIZATION
eval_autopoiesis "simulation/physics.dsl" vector="STABILITY"

# 2. Tối ưu hóa Thông tin (Complexity Management)
on meta.information_density > 0.95:
emit_event AUTOPOIESIS_SINGULARITY_REACHED
eval_autopoiesis "simulation/integrity.dsl" vector="OPTIMIZATION"

# 3. Phản hồi Nhân quả (Causal Feedback)
on stability_index < 0.3:
drift "physics.causality_constant" by -0.02
set "meta.observation_load" to 0.0 # Bịt mắt người quan sát để giảm nợ nhân quả