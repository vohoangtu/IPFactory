# Institutions Module

## 📋 Overview
Module này quản lý các "Định chế" (Institutions) trong Multiverse, bao gồm các Nền văn minh (Civilizations), các Giáo phái (Cults), các Hiệp hội (Orders) và các Thực thể Tối cao (Supreme Entities). Nó cũng xử lý các Hợp đồng xã hội (Social Contracts) và Quan hệ ngoại giao (Diplomacy).

## 🏗️ Architecture
Tuân thủ kiến trúc Modular Monolith & DDD:
- **Domain Layer**: 
    - `InstitutionalEntity`: Logic về sự phát triển ảnh hưởng, chi phí vận hành và tính chính danh.
    - `SocialContractEntity`: Các quy tắc ràng buộc giữa các thực thể.
    - `SupremeEntity`: Logic về quyền năng và nghiệp lực (Karma).
- **Application Layer**:
    - `Actions/`: SpawnInstitution, CollapseInstitution, ResolveDiplomacy.
    - `Services/`: DiplomaticResonanceEngine.
- **Infrastructure Layer**:
    - `Repositories/`: Ánh xạ giữa Eloquent Model và Domain Entity.

## 📐 Structure
```
app/Modules/Institutions/
├── Actions/
├── Contracts/
├── Dto/
├── Entities/
├── Events/
├── Listeners/
├── Providers/
├── Repositories/
├── Services/
└── README.md
```

## 🚀 Usage
```php
$instRepo = app(InstitutionalRepositoryInterface::class);
$institution = $instRepo->findById($id);
$institution->updateInfluence($universeContext);
$instRepo->save($institution);
```

## 📡 API Endpoints (Planned)
- `GET /api/worldos/institutions`
- `GET /api/worldos/supreme-entities`

## 🔗 Integration
- Lắng nghe `UniverseSimulationPulsed` để cập nhật trạng thái các định chế.
- Kết hợp với Module Intelligence để gán lãnh tụ cho các định chế khi có khủng hoảng.
