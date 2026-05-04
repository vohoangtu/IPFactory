<?php

namespace App\Modules\Simulation\Core\Domain\Actions;

use App\Modules\Simulation\Core\Runtime\Causality\CausalLink;
use Illuminate\Support\Facades\Log;

/**
 * RecordCausalLinkAction — Ghi chép một mắt xích nhân quả vào hệ thống.
 * 
 * Đóng gói logic ghi nhận quan hệ nhân-quả giữa các sự kiện mô phỏng.
 * Tách biệt khỏi Engine để có thể tái sử dụng từ nhiều context khác nhau
 * (WorldKernel, RuleStage, hoặc một service bên ngoài).
 */
final class RecordCausalLinkAction
{
    /** @var CausalLink[] */
    private array $buffer = [];

    /**
     * Ghi nhận một CausalLink vào buffer nội bộ.
     */
    public function record(CausalLink $link, int $tick): void
    {
        $link->tick = $tick;
        $this->buffer[] = $link;

        Log::debug("RecordCausalLinkAction: Recorded causal link", [
            'cause' => $link->cause ?? 'unknown',
            'effect' => $link->effect ?? 'unknown',
            'tick' => $tick,
        ]);
    }

    /**
     * Flush buffer và trả về tất cả links đã ghi.
     * 
     * @return CausalLink[]
     */
    public function flush(): array
    {
        $links = $this->buffer;
        $this->buffer = [];
        return $links;
    }

    /**
     * Số lượng causal links hiện đang trong buffer.
     */
    public function count(): int
    {
        return count($this->buffer);
    }
}
