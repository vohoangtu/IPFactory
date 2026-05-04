# Vector 18 chiều của Actor và tương tác với WorldOS

Mỗi actor có một **trait vector** 18 chiều (giá trị 0–1). Vector này quyết định mô tả nhân vật, chuyển archetype, survival, và cách actor bị “kéo” bởi trường xã hội (entropy, stability, civilization state) trong WorldOS. Thể chất là **vector riêng (huyết mạch)** trong `metrics['physic']`, xem §4.

---

## 1. Danh sách 18 chiều (TRAIT_DIMENSIONS)

| Index | Tên (EN)     | Ý nghĩa ngắn |
|-------|---------------|--------------|
| 0     | Dominance     | Khát khao thống trị, áp đặt ý chí |
| 1     | Ambition      | Tham vọng, vươn lên |
| 2     | Coercion      | Sẵn sàng dùng quyền lực, ép buộc |
| 3     | Loyalty       | Trung thành với nhóm/nguyên tắc |
| 4     | Empathy       | Đồng cảm, trắc ẩn |
| 5     | Solidarity    | Đoàn kết, hướng về cộng đồng |
| 6     | Conformity    | Tuân theo đám đông, chuẩn mực |
| 7     | Pragmatism    | Thực dụng, tỉnh táo, tính toán |
| 8     | Curiosity    | Tò mò, tìm hiểu bí ẩn |
| 9     | Dogmatism     | Giáo điều, tin vào chân lý cố định |
| 10    | RiskTolerance | Chấp nhận mạo hiểm (đồng thời dùng làm **resilience** trong survival) |
| 11    | Fear          | Nỗi sợ, lo lắng |
| 12    | Vengeance     | Hận thù, mong trả thù |
| 13    | Hope          | Hy vọng, lạc quan |
| 14    | Grief         | Đau thương, mất mát |
| 15    | Pride         | Kiêu hãnh, tự tôn |
| 16    | Shame         | Hổ thẹn, tội lỗi |
| 17    | Longevity     | Tuổi thọ tương đối (0–1): max_age và xác suất sống sót mỗi tick |

---

## 2. Tương tác với WorldOS (theo module)

### 2.1 Survival (ProcessActorSurvivalAction + ActorTransitionSystem)

- **Tuổi thọ (theo tick → năm):**  
  `age_years = (current_tick - spawned_at_tick) / ticks_per_year`  
  `effective_max_age = default_max_age_years * (0.5 + 0.5 * Longevity)`  
  Nếu `age_years >= effective_max_age` → actor chết (nhân quả theo thời gian).
- **Xác suất sống mỗi tick (RNG):**  
  Dùng **Resilience** (trait 10), **Longevity** (trait 17), **vector huyết mạch** `metrics['physic']` (aggregate), **entropy** (từ snapshot):  
  `logit = resilience*0.35 + longevity*0.15 + aggregatePhysic(metrics.physic)*0.15 + (1-entropy)*0.35` → logistic → xác suất sống; cộng thêm baseline mortality ~1.5%/tick.

→ **Traits 10 và 17** tương tác trực tiếp với “sống/chết” và thời gian WorldOS (tick, entropy).

### 2.2 Narrative – mô tả & Fate Tags (TraitMapper)

- **mapToDescription(traits):** Đổi vector thành câu mô tả (ví dụ: 0,1,2 cao → “khát khao thống trị, tham vọng lớn lao, thích dùng quyền lực”; 4,5,6 cao → trắc ẩn, cộng đồng, conform; 7–16 cao → các mô tả tương ứng).
- **getFateTags(traits):**  
  - Dominance + Ambition rất cao → “The Conqueror”  
  - Empathy + Hope rất cao → “The Messiah”  
  - Curiosity rất cao → “The Void-Seeker”  
  - Vengeance rất cao → “The Avenger”  
  - Dogmatism rất cao → “The Inquisitor”  
  - Pragmatism + Curiosity cao, Dogmatism thấp → “Awareness_of_the_Clock”, “Simulation_Skepticism”.
- **generateMonologueSeed(traits, archetype):** Tạo “internal monologue” từ trait nổi trội (0,1,2 → quyền lực; 3,4,5 → cộng đồng; 7 → thực dụng; 8 → bí ẩn; 9 → giáo điều; 11 → sợ; 12 → trả thù; 13 → hy vọng; …).

→ Các chiều **0–16** tương tác với **narrative layer**: mô tả nhân vật, fate tags, monologue trong WorldOS.

### 2.3 Chuyển Archetype (TraitMapper::detectArchetypeShift)

- **Ambition** cao + archetype Commoner → Opportunist.  
- **Empathy** cao + Commoner → Sage.  
- **Coercion** cao + Opportunist → Warlord.  
- **Dogmatism** cao + Sage → High_Priest.  
- **Curiosity** rất cao + Sage → Scholar.  
- **Pragmatism** rất cao + Opportunist → Merchant_Lord.  
- **Dogmatism** rất cao → Zealot.

→ **Traits 1, 2, 4, 7, 8, 9** tương tác với **archetype** (và qua đó với các hành vi/impact theo archetype trong WorldOS).

### 2.4 Cognitive Dynamics – “trường xã hội” kéo traits (CognitiveDynamicsEngine)

- **Social field** có 4 thành phần: aggression, rational, spiritual, conformity.
- **Map trait → field:**  
  - **aggressionField:** Dominance, Vengeance, Coercion (0, 2, 12)  
  - **rationalField:** Curiosity, Pragmatism, Ambition (1, 7, 8)  
  - **spiritualField:** Hope, Dogmatism, Fear, Grief (9, 11, 13, 14)  
  - **conformityField:** Conformity, Solidarity, Loyalty, Empathy, Pride, Shame (3, 4, 5, 6, 15, 16)
- Mỗi tick, từng trait bị **kéo** về giá trị trường tương ứng (social pull), có damping và noise → traits **tiến hóa** theo trạng thái vũ trụ (entropy, stability, civilization state qua snapshot).
- **Radical state:** Chênh lệch lớn giữa Dominance và Curiosity → có thể vào trạng thái “radical_warrior” hoặc “radical_scholar” (lưu trong metrics).

→ **Toàn bộ 17 chiều gốc** (0–16) đều có thể bị trường WorldOS kéo; **Longevity (17)** có thể thêm vào map sau nếu cần. Thể chất không nằm trong trait mà trong vector huyết mạch (metrics.physic).

### 2.5 Micro Mode / Crisis – “ai thắng” (RunMicroModeAction)

- Khi stability thấp, sinh 3–5 agent, mỗi agent có **traits 18D** (17 gốc + Longevity). Thể chất (nếu cần) lấy từ vector huyết mạch riêng.
- **Utility = attractorScore(civilizationState) + T17 context weight · traits + noise.**  
  `contextWeight` phụ thuộc snapshot (entropy, stability, …); từng chiều 0–16 đóng góp vào “điểm” agent trong bối cảnh civilization hiện tại.
- Agent có utility cao nhất “thắng” → apply impact lên universe, branch event, spawn actor từ winner (traits được đưa vào WorldOS qua event).

→ **Traits 0–16** tương tác với **civilization state** và **quyết định nhánh lịch sử** (micro crisis, branch event).

### 2.6 Autonomy / Loom Intent (AgentAutonomyService, IntentActionMapper)

- **Dominance (0), Ambition (1)** được dùng trong logic chọn actor/ưu tiên (ví dụ actor nổi bật cho Loom).
- Hành động từ Loom (revolt, migrate, propagate_myth, form_contract) được map sang **evolveTraits** trong ActorTransitionSystem: combat→strength, research→intelligence, trade→charisma (hiện map bằng tên trait, chưa gắn rõ index 0–16; có thể chuẩn hóa sau).

→ **0, 1** (và có thể thêm) tương tác với **autonomy** và **intent layer** trong WorldOS.

### 2.7 Great Filter / Institutions (GreatFilterEngine, AscendHeroAction)

- **Pragmatism (7)** được dùng như “trust” trong Great Filter (tổng trust += traits[7]).
- **Ambition (1), Curiosity (8)** rất cao → điều kiện ascend hero.
- **Empathy (4), Pragmatism (7)** → spirituality / hardtech trong payload.

→ **1, 4, 7, 8** tương tác với **institutions**, **great filter**, **ascension**.

### 2.8 Cognitive aggregate (ActorCognitiveService)

- Tính **destiny_gradient**, **causal_curiosity**, **anomaly_sensitivity** từ **trung bình traits** của actors (ví dụ curiosity index 8, …) và state_vector / metrics → ghi vào `state_vector['cognitive_aggregate']`, kích hoạt meaning_crisis, causal_trajectory, scientific_revolution.

→ **Trait trung bình (đặc biệt 8 – Curiosity)** tương tác với **cognitive layer** và **sự tự tổ chức văn minh** (tôn giáo, khoa học, magic) trong WorldOS.

---

## 3. Tóm tắt nhanh: Chiều nào làm gì

| Nhóm        | Chiều        | Vai trò chính trong WorldOS |
|------------|--------------|-----------------------------|
| Quyền lực  | 0, 1, 2      | Narrative (conqueror, monologue), archetype (Warlord, Opportunist), cognitive dynamics (aggression), micro mode utility, autonomy |
| Liên kết   | 3, 4, 5, 6   | Narrative (messiah, cộng đồng), cognitive dynamics (conformity), archetype (Sage) |
| Lý tính    | 7, 8, 9      | Narrative (pragmatist, void-seeker, inquisitor), archetype (Scholar, Merchant_Lord, High_Priest, Zealot), great filter (trust), cognitive (curiosity), fate tags |
| Rủi ro/sống| 10           | **Survival** (resilience), micro mode utility |
| Cảm xúc    | 11, 12, 13, 14, 15, 16 | Narrative (fear, vengeance, hope, grief, pride, shame), cognitive dynamics (spiritual/conformity), fate tags (avenger), archetype |
| Tuổi thọ   | 17           | **Survival** (max_age theo năm + xác suất sống mỗi tick) |

---

## 4. Vector Huyết mạch (Physic)

**Thể chất** của actor không phải một chiều trait mà là **một vector riêng** (huyết mạch), lưu trong `metrics['physic']`, định nghĩa bởi `ActorEntity::PHYSIC_DIMENSIONS`:

| Index | Tên (EN)   | Ý nghĩa ngắn |
|-------|-------------|---------------|
| 0     | Vitality    | Sinh lực, khả năng hồi phục |
| 1     | Stamina    | Sức bền, chịu đựng |
| 2     | Strength   | Sức mạnh thể chất |
| 3     | Endurance  | Độ bền trước stress/tổn thương |
| 4     | Resilience | Thể chất chống entropy (tách khỏi trait RiskTolerance) |

- **Lưu trữ:** `metrics['physic']` = array float [0..1] theo thứ tự index trên. Mặc định khi spawn: `ActorEntity::defaultPhysicVector()` (toàn 0.5).
- **Survival:** `ActorTransitionSystem::processSurvival()` dùng aggregate (trung bình các chiều) thay cho một trait Physic đơn; nếu không có `physic` thì coi như 0.5.
- **Mở rộng sau:** Có thể drift/evolve vector huyết mạch theo tick (tổn thương, tuổi tác, sự kiện), narrative mô tả thể chất từ từng chiều, hoặc map vào cognitive field nếu cần.

---

## 5. Gợi ý khi nghiên cứu / mở rộng

- **Thêm chiều mới:** Thêm tên vào `ActorEntity::TRAIT_DIMENSIONS`, mở rộng default traits (SpawnActorAction, TransmigrationEngine), rồi gắn vào: survival, TraitMapper, CognitiveDynamicsEngine (mapTraitToField), RunMicroModeAction (context weight), và bất kỳ chỗ nào đọc traits theo index/tên.
- **Đổi ý nghĩa:** Sửa chỗ dùng trait đó (ví dụ đổi resilience từ 10 sang một index khác thì sửa ActorTransitionSystem và mọi chỗ đọc resilience).
- **Tick = thời gian:** `ticks_per_year` và `default_max_age_years` trong config quyết định “100 tick = bao nhiêu năm” và “tuổi thọ tối đa” cho nhân quả survival; Longevity (17) điều chỉnh max_age và xác suất sống mỗi tick. Thể chất lấy từ vector huyết mạch `metrics['physic']`, không từ trait.

File tham chiếu code: `ActorEntity.php`, `ActorTransitionSystem.php`, `ProcessActorSurvivalAction.php`, `TraitMapper.php`, `CognitiveDynamicsEngine.php`, `RunMicroModeAction.php`, `AgentAutonomyService.php`, `ActorCognitiveService.php`, `GreatFilterEngine.php`, `AscendHeroAction.php`.
