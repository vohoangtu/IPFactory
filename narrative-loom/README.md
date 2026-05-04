# NARRATIVE LOOM: THE CREATIVE PIPELINE (LANGGRAPH)

Tài liệu này giải thích chi tiết kiến trúc, luồng hoạt động, và các khuyến nghị triển khai của **NarrativeLoom** - Hệ thống Multi-Agent biên dịch dữ liệu mô phỏng toán học từ WorldOS thành văn chương nghệ thuật.

Hệ thống được xây dựng bằng Python, sử dụng **FastAPI** làm cổng giao tiếp và **LangGraph** để điều phối (orchestrate) luồng làm việc của nhiều AI Agents (LLMs) khác nhau một cách tuần tự và có kiểm soát.

---

## 1. TỔNG QUAN KIẾN TRÚC (ARCHITECTURE OVERVIEW)

NarrativeLoom hoạt động như một microservice độc lập (container `narrative_loom`), nhận request từ WorldOS Backend (Laravel).

Thay vì dùng 1 prompt dài và duy nhất cho 1 AI (thường gây ảo giác - hallucination và quên context), NarrativeLoom chia nhỏ quá trình viết lách thành một **dây chuyền sản xuất** (Pipeline) gồm 4 phân xưởng (Nodes), do 4 AI Agents đóng vai trò chuyên biệt.

*Cấu trúc lõi nằm trong:* `narrative-loom/graph.py`

### Bộ Nhớ Tập Thể (State)
Mọi dữ liệu đi qua dây chuyền không bị lọt ra ngoài, mà được lưu trong một Dictionary gọi là `NarrativeState` (`state.py`). Cuốn sổ này liên tục được các Agent viết thêm vào (Ví dụ: Historian viết xong mục `historical_outline`, truyền sổ cho Psychologist...).

---

## 2. DÂY CHUYỀN SẢN XUẤT (THE 4 AI AGENTS)

### Node A: The Historian (Sử Gia)
- **File**: `agents/historian.py`
- **Đầu vào (Input)**: Dữ liệu Raw JSON từ WorldOS (Chronicles) gồm Tick (Thời điểm), Event Type, Target, Payload.
- **Nhiệm vụ**: Đóng vai trò màng lọc. Nó không được phép viết văn bay bổng. Nó chỉ tóm tắt sự kiện thành **Dàn ý lịch sử (Historical Outline)** mạch lạc (Gồm: Nguyên nhân, Kết quả, Sự kiện chính).
- **💡 Khuyến nghị Model**: `gpt-4o` (OpenAI) hoặc `gemini-1.5-pro` (Google). Vì tác vụ này đòi hỏi khả năng bóc tách JSON và phân tích chuỗi nhân quả (Causality) rất mạnh mẽ, không cần văn hay.

### Node B: The Psychologist (Bác Sĩ Tâm Lý)
- **File**: `agents/psychologist.py`
- **Đầu vào (Input)**: `historical_outline` từ Sử Gia + `Character Profiles` (Hồ sơ tâm lý nhân vật từ WorldOS API).
- **Nhiệm vụ**: Đào sâu vào nội tâm nhân vật. Trả lời câu hỏi *"Tại sao chúng hành động như vậy?"* dựa trên chỉ số Traits (VD: Lòng tham, Sự tàn bạo, Độ dị giáo - Heresy).
- **💡 Khuyến nghị Model**: `claude-3-opus-20240229` hoặc `claude-3-5-sonnet` (Anthropic). Claude nổi tiếng với khả năng thấu cảm và phân tích logic tâm lý sâu sắc hơn các mô hình khác.

### Node C: The Director (Đạo Diễn Dàn Cảnh)
- **File**: `agents/director.py`
- **Đầu vào (Input)**: Dàn ý lịch sử + Phân tích tâm lý + Trạng thái vũ trụ (World State Snapshot - mức độ hỗn loạn/trật tự).
- **Nhiệm vụ**: Đóng vai trò quy hoạch không gian (Spatial reasoning). Nó chọn góc máy quay ở đâu (Close-up, Wide-shot), thời tiết thế nào, ánh sáng ra sao để cộng hưởng với cảm xúc tâm lý. Đầu ra là **Kịch bản (Storyboard)**.
- **💡 Khuyến nghị Model**: `gpt-4o` (OpenAI). Khả năng hiểu không gian 3D, bài trí cảnh quan và tưởng tượng vật lý của GPT-4 hiện tại là tốt nhất cho việc dàn cảnh (Scene Setting).

### Node D: The Wordsmith (Nhà Giả Kim Ngôn Từ)
- **File**: `agents/wordsmith.py`
- **Đầu vào (Input)**: Storyboard hoàn chỉnh.
- **Nhiệm vụ**: Chắp bút dứt điểm. Dùng quy tắc vàng trong văn học: **"Show, Don't Tell"** (Tả, đừng Kể). Nó biến các con số khô khan, các góc quay lý trí thành một kiệt tác văn học đậm chất nghệ thuật (Literary Prose).
- **💡 Khuyến nghị Model**: `claude-3-opus-20240229` (Anthropic). Trong mảng sáng tác văn chương, hành văn lãng mạn, mờ ảo và dạt dào cảm xúc tiếng Việt (hoặc tiếng Anh), Claude hiện không có đối thủ.

---

## 3. KHẢ NĂNG "ĐA VŨ TRỤ AI" (MULTI-PROVIDER UNIVERSE)

Hệ thống cung cấp một Factory (`utils/llm_factory.py`) sử dụng LangChain để trừu tượng hóa kết nối với AI. Điều này có nghĩa là WorldOS **không bao giờ bị khóa cứng (Vendor Lock-in)** vào OpenAI.

**Các Provider đã được tích hợp sẵn:**
1. `openai` (GPT-4o, GPT-4-turbo)
2. `anthropic` (Claude 3.5 Sonnet, Opus)
3. `google` (Gemini 1.5 Pro)
4. `local` (Chỉ định `LOCAL_LLM_URL` tới các Local AI chạy trên HuggingFace/LM Studio/Ollama - Giúp hoàn toàn miễn phí nếu có server GPU mạnh).

**Cơ chế Mix-Match (Pha trộn Model):**
Người dùng có toàn quyền cấu hình Agent nào xài Model nào.
*> Ví dụ cấu hình tối ưu chi phí & chất lượng:*
- The Historian: `gpt-4o-mini` (Rẻ, đọc JSON nhanh).
- The Psychologist: `gemini-1.5-flash` (Token dồi dào, đọc data nhanh).
- The Director: `gpt-4o` (Dàn cảnh xuất sắc).
- The Wordsmith: `claude-3-5-sonnet` (Văn hay chữ tốt).

---

## 4. KẾ HOẠCH TÍCH HỢP BƯỚC TIẾP THEO (NEXT STEPS)

Đồ thị đã xây xong ở Backend Python. Để dòng chảy (Data River) xuyên suốt:

1. **Laravel Integration**: Sửa đổi `WeaveNarrativesCommand` (trong thư mục `app/Console/Commands/` của Backend PHP). Thay vì gọi Service AI PHP lạc hậu, Command này sẽ bắn POST Request chứa `world_id` sang Endpoint `http://narrative_loom:8001/weave-chronicles`.
2. **Push Result**: Sau khi LangGraph vắt ra vế `final_prose`, NarrativeLoom sẽ chủ động cập nhật lại cột `content` của bảng `chronicles` ở phía Laravel, hoặc trả về Response để Laravel tự lưu.
3. **Frontend UI**: Thêm mục Config ở Frontend cho phép Admin thiết lập API Keys của Anthropic, Google... và dropdown gán Model ngắm bắn cho từng Agent 1, 2, 3, 4.
