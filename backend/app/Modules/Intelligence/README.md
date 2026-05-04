# Intelligence Module

## 📋 Overview
Module này quản lý "Trí tuệ" của Multiverse, bao gồm các Thực thể thông minh (Actors), các Quyết định (Agent Decisions) và Hệ thống ghi nhớ AI (AI Memory).

## 🏗️ Architecture
Tuân thủ kiến trúc Modular Monolith & DDD:
- **Domain Layer**: 
    - `ActorEntity`: Logic nghiệp vụ thuần về sinh tồn và phát triển của một nhân vật.
    - `AgentDecisionEntity`: Bản ghi về một hành động được đưa ra bởi AI/Actor.
- **Application Layer**:
    - `Actions/`: Xử lý các Use Case như Spawn Actor, Record Decision.
- **Infrastructure Layer**:
    - `Repositories/`: Ánh xạ giữa Eloquent Model và Domain Entity.

## 📐 Structure
```
app/Modules/Intelligence/
├── Actions/
├── Contracts/
├── Dto/
├── Entities/
├── Providers/
├── Repositories/
└── README.md
```

## 🚀 Usage
```php
$actorRepo = app(ActorRepositoryInterface::class);
$actor = $actorRepo->findById($id);
$actor->evolve($context); // Domain logic
$actorRepo->save($actor);
```

## 📡 API Endpoints (Planned)
- `GET /api/worldos/actors`
- `GET /api/worldos/actors/{id}/decisions`

## 🔗 Integration
- Lắng nghe sự kiện `UniverseSimulationPulsed` từ Simulation module để kích hoạt tiến hóa của Actor.
- Cung cấp `ActorEntity` cho các Module khác (như Institutions) để tính toán sức ảnh hưởng xã hội.
