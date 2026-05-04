<?php

namespace App\Modules\Simulation\Core\Entities;

use App\Modules\World\Entities\Inventory;
use App\Modules\Psychology\ValueObjects\IdentityState;
use App\Modules\Psychology\ValueObjects\PsychologicalState;
use App\Modules\Psychology\ValueObjects\TraitVector;

/**
 * Agent là thực thể cốt lõi trên WorldOS.
 * Agent gộp các thành phần (Components) từ Psychology, Economics và Location (Geography).
 */
class Agent
{
    public function __construct(
        public readonly string $id,
        // -- Physical Limits & Survival --
        public float $health = 100.0,
        public float $energy = 100.0,
        public float $hunger = 0.0, 
        
        // -- Lifecycle --
        public int $birthTick = 0,
        public float $lifeExpectancy = 120.0, // Đơn vị: years hoặc cycles.

        // -- Geographic Component --
        public int $x = 0,
        public int $y = 0,

        // -- Economics Component --
        public Inventory $inventory = new Inventory('tbd', 50.0),

        // -- Psychology Component --
        public PsychologicalState $psychology = new PsychologicalState(),
        public TraitVector $traits = new TraitVector(),
        public IdentityState $identity = new IdentityState(0.5, 0.0, 1.0), 
        
        // Cụm trí nhớ ngắn hạn về kế hoạch (GOAP)
        public array $currentActionSequence = [] 
    ) {
        // Fix Inventory actorId
        if ($this->inventory->actorId === 'tbd') {
            $this->inventory = new Inventory($this->id, $this->inventory->maxWeightCapacity);
        }
    }

    /**
     * Mỗi tick trừ energy và làm đói đi
     */
    public function biologicalTick(): void
    {
        // Nhích cơn đói (100 ticks = 1.0)
        $this->hunger = min(1.0, $this->hunger + 0.01);

        // Hồi nhẹ energy nếu không làm gì, nhưng tối đa là 100
        $this->energy = min(100.0, $this->energy + 2.0);

        // Nếu đói khát quá, trừ máu
        if ($this->hunger >= 1.0) {
            $this->health -= 5.0; 
        }

        // Tác động ngược lên Tâm lý:
        // Càng đói / máu tụt càng sinh Fear, Stress.
        if ($this->hunger > 0.6 || $this->health < 50.0) {
            $stressDelta = ($this->hunger * 0.2) + ((100 - $this->health) * 0.01);
            $fearDelta   = ($this->hunger * 0.1);

            $this->psychology->applyDelta([
                'stress'  => $stressDelta,
                'fear'    => $fearDelta,
                'sadness' => 0.0,
                'anger'   => $this->hunger > 0.8 ? 0.05 : 0.0, // Over-hungry makes them irritable
                'joy'     => 0.0
            ]);
        }
    }
    
    /**
     * Pop the next action from GOAP sequence
     */
    public function getNextAction(): ?string
    {
        if (empty($this->currentActionSequence)) {
            return null;
        }

        // Action sequence là mảng ['forage', 'eat', ...]
        // Mỗi lần gọi sẽ làm 1 action (ở đâu đó trong Engine)
        return $this->currentActionSequence[0]; 
    }

    /**
     * Hoàn thành Action ở turn này -> Xóa khỏi queue
     */
    public function markActionCompleted(): void
    {
        if (!empty($this->currentActionSequence)) {
            array_shift($this->currentActionSequence);
        }
    }

    public function consumeEnergy(float $amount): bool
    {
        if ($this->energy < $amount) {
            return false;
        }
        $this->energy -= $amount;
        return true;
    }

    public function applyReproductionCost(): void
    {
        $this->consumeEnergy(30.0);
        $this->hunger = min(1.0, $this->hunger + 0.2);
        $this->psychology->applyDelta([
            'joy' => 0.3, 
            'stress' => 0.1
        ]);
    }

    public function die(): void
    {
        $this->health = 0.0;
        // Thực hiện các logic cleanup nếu cần
    }

    public function isAlive(): bool
    {
        return $this->health > 0;
    }
}
