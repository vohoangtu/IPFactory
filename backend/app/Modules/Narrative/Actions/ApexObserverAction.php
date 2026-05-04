<?php

namespace App\Modules\Narrative\Actions;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Runtime\State\StateManager;
use Illuminate\Support\Facades\Log;

/**
 * Phase 72: Apex Observer Action 👁️⚡
 * 
 * Cho phép Đấng Sáng Thế can thiệp trực tiếp vào dòng chảy thực tại cấp cao.
 */
class ApexObserverAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        private readonly StateManager $stateManager
    ) {}

    /**
     * Thực thi lệnh Apex.
     */
    public function execute(Universe $universe, string $command, array $payload = []): array
    {
        // Apex commands thường thực thi ngay khi simulation CHƯA chạy tick tiếp theo
        // hoặc trong một tiến trình can thiệp riêng. Ta cần load state.
        $state = $this->stateManager->get();
        if (!$state) {
            $state = $this->stateManager->load($universe);
        }

        $result = match ($command) {
            'LOCK_TRAJECTORY' => $this->lockTrajectory($state),
            'COLLAPSE_WAVEFUNCTION' => $this->collapseWavefunction($state),
            'DILATE_TIME' => $this->dilateTime($state, $payload['factor'] ?? 1.0),
            'INJECT_REALITY_GLITCH' => $this->injectGlitch($state, $payload['magnitude'] ?? 0.1),
            default => ['ok' => false, 'error' => "Unknown Apex command: $command"]
        };

        if ($result['ok'] ?? false) {
            // Lưu state ngay lập tức sau can thiệp tối thượng
            $this->stateManager->save($universe);
            
            Log::info("ApexObserver: Command $command executed successfully on universe {$universe->id}");
        }

        return $result;
    }

    /**
     * Khóa dòng thời gian: Ngăn chặn rule mutation và các biến động lớn.
     */
    private function lockTrajectory(WorldState $state): array
    {
        $state->set('meta.trajectory_locked', true);
        $state->set('meta.rule_mutation_rate', 0.0);
        $state->set('stability_index', 1.0);
        
        return ['ok' => true, 'message' => 'Trajectory has been locked at the apex level.'];
    }

    /**
     * Ép buộc sụp đổ hàm sóng: Giảm entropy cục bộ về 0.
     */
    private function collapseWavefunction(WorldState $state): array
    {
        $state->updateField('entropy', -0.5, 'Apex Wavefunction Collapse');
        $state->set('meta.observation_load', 0.0);
        $state->set('meta.last_collapse_tick', $state->get('tick', 0));
        
        return ['ok' => true, 'message' => 'Wavefunction collapsed into a deterministic state.'];
    }

    /**
     * Điều chỉnh độ giãn nở thời gian thủ công.
     */
    private function dilateTime(WorldState $state, float $factor): array
    {
        $state->set('meta.manual_time_dilation', $factor);
        
        return ['ok' => true, 'message' => "Time dilation factor set to $factor."];
    }

    /**
     * Tiêm lỗi thực tại: Tạo biến động nhân quả ngẫu nhiên.
     */
    private function injectGlitch(WorldState $state, float $magnitude): array
    {
        $state->updateField('entropy', $magnitude, 'Apex Reality Glitch');
        $state->set('meta.causal_divergence', (float)$state->get('meta.causal_divergence', 0) + $magnitude);
        
        return ['ok' => true, 'message' => "Reality glitch with magnitude $magnitude injected."];
    }
}


