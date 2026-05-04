<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\World\Models\Universe;
use App\Modules\Institutions\Models\InstitutionalEntity;
use App\Modules\Narrative\Models\Chronicle;
use Illuminate\Support\Facades\Log;
use App\Modules\Simulation\Services\Meta\WorldTemplateManager;

class AscensionEngine
{
    public function __construct(
        protected WorldTemplateManager $worldTemplateManager,
        protected \App\Contracts\LlmNarrativeClientInterface $llmClient
    ) {}

    /**
     * Tiến hóa các thực thể định chế thành Supreme Entities (§50.2).
     */
    public function processAscension(Universe $universe, array $metrics): void
    {
        $sci = $metrics['sci'] ?? 0;
        if ($sci < 0.7) return;

        $entities = InstitutionalEntity::where('universe_id', $universe->id)
            ->whereNull('collapsed_at_tick')
            ->where('entity_type', '!=', 'supreme')
            ->get();

        foreach ($entities as $entity) {
            // Điều kiện thăng hoa: Năng lực tổ chức và Tính chính danh cực cao
            if ($entity->org_capacity > 0.85 && $entity->legitimacy > 0.8) {
                $this->ascendToSupreme($entity, $universe);
            }
        }
    }

    protected function ascendToSupreme(InstitutionalEntity $entity, Universe $universe): void
    {
        $entity->update([
            'entity_type' => 'supreme',
            'org_capacity' => 1.0,
            'institutional_memory' => $entity->institutional_memory + 1000.0 // Transcendental memory
        ]);

        Log::info("ASCENSION: Entity [{$entity->name}] in Universe [{$universe->id}] has become a SUPREME ENTITY.");

        // AI generate Axiom Shift for the Universe
        $prompt = "Một thực thể vĩ đại mang tên '{$entity->name}' vừa vượt qua quy luật bình thường trong mô phỏng WorldOS. Hãy đưa ra 1 mô tả ngắn gọn về MỘT QUY LUẬT VẬT LÝ hay CƠ CHẾ mà thực thể này vừa thay đổi vĩnh viễn (Axiom Shift). Format: 'Axiom Shift: [Nội dung quy luật đổi]'.";
        $axiomDescription = $this->llmClient->generate($prompt);

        if ($axiomDescription) {
            $this->worldTemplateManager->applyLocalAxiomShift($universe, [
                'entity_id' => $entity->id,
                'entity_name' => $entity->name,
                'description' => $axiomDescription
            ]);
        }

        Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => $universe->current_tick,
            'to_tick' => $universe->current_tick,
            'type' => 'ascension',
            'raw_payload' => [
                'action' => 'legacy_event',
                'description' => "SỰ THĂNG HOA: Thực thể '{$entity->name}' đã tích lũy đủ tính chính danh và năng lực để vượt qua giới hạn của một định chế thông thường, trở thành một Supreme Entity điều khiển dòng chảy của vũ trụ."
            ],
        ]);
    }
}


