<?php

namespace App\Modules\Psychology\ValueObjects;

/**
 * Myth
 * 
 * Huyền thoại / Tín ngưỡng: Một vết sẹo tinh thần, hoặc một phép màu được ghi nhớ vĩnh viễn
 * bởi toàn bộ đám đông. Không thể tự nhiên phai mờ (No decay over time) như Memory thông thường.
 * Chỉ có thể bị thay thế bởi một Myth lớn hơn hoặc bị ảnh hưởng bởi quá trình "Tam sao thất bản".
 */
class Myth
{
    public function __construct(
        public readonly string $id,                // UUID
        public readonly string $eventSignature,    // Khái niệm (ví dụ: 'volcano_eruption_year_20')
        public readonly float $narrativePower,     // [0, 1]: Sức mạnh truyền thông/đức tin. 1.0 = đức tin tuyệt đối.
        public readonly float $distortionFactor,   // [0, 1]: Độ lêch lạc so với sự thật ban đầu (do truyền miệng).
        public readonly float $traumaImprint,      // [0, 1]: Dấu ấn nỗi sợ hãi mà Myth này gây ra cho Zone.
        public readonly float $moraleImprint,      // [0, 1]: Dấu ấn niềm tin/hy vọng (Tôn giáo mang lại hope).
        public readonly int $creationTick
    ) {
    }

    /**
     * Dị bản (Tam sao thất bản qua nhiều thế hệ).
     * Myth không mất đi (như decay của memory), nhưng nó dễ bị bóp méo (distortion).
     * Khi truyền lại cho thế hệ sau, power giảm nhẹ nhưng distortion tăng dần.
     */
    public function passToNextGeneration(int $currentTick): self
    {
        return new self(
            id: $this->id,
            eventSignature: $this->eventSignature,
            // Sức mạnh truyền miệng sẽ giảm dần nếu không có Ritual (nghi lễ) củng cố
            narrativePower: max(0.1, $this->narrativePower * 0.95),
            // Độ lệch lạc sẽ tự tăng lên qua thời gian
            distortionFactor: min(1.0, $this->distortionFactor + 0.1),
            traumaImprint: $this->traumaImprint, // Nỗi sợ di truyền không đổi
            moraleImprint: $this->moraleImprint, 
            creationTick: $this->creationTick
        );
    }

    public function toArray(): array
    {
        return [
            'id'                => $this->id,
            'event_signature'   => $this->eventSignature,
            'narrative_power'   => $this->narrativePower,
            'distortion_factor' => $this->distortionFactor,
            'trauma_imprint'    => $this->traumaImprint,
            'morale_imprint'    => $this->moraleImprint,
            'creation_tick'     => $this->creationTick,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['event_signature'],
            $data['narrative_power'],
            $data['distortion_factor'] ?? 0.0,
            $data['trauma_imprint'] ?? 0.0,
            $data['morale_imprint'] ?? 0.0,
            $data['creation_tick']
        );
    }
}
