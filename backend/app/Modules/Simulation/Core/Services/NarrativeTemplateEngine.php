<?php

namespace App\Modules\Simulation\Core\Services;

/**
 * Narrative Template Engine: Biến event log JSON khô khan thành văn xuôi có cảm xúc.
 * Sử dụng mẫu câu (Template) kết hợp context để sinh ra câu chuyện.
 */
class NarrativeTemplateEngine
{
    /** @var array<string, array<string>> Templates theo event type */
    private array $templates = [
        'attack' => [
            '{attacker}, bị cơn đói và sự tuyệt vọng dồn ép, đã tấn công {victim} giữa vùng {biome}.',
            'Máu đổ trên {biome}! {attacker} bất ngờ lao vào {victim} trong một cơn giận dữ mù quáng.',
            'Tiếng kêu cứu vang lên khi {attacker} ra tay cướp bóc {victim} không thương tiếc.',
        ],
        'cooperate' => [
            '{giver} mỉm cười chia sẻ lương thực cho {receiver} đang kiệt sức bên bờ suối.',
            'Một hành động nhân ái giữa thời loạn: {giver} trao đồ ăn cho {receiver}.',
            'Trong bóng tối của đói khát, {giver} đã chìa bàn tay giúp đỡ {receiver}.',
        ],
        'trade_barter' => [
            '{trader} và {partner} đứng bên nhau, lặng lẽ trao đổi hàng hóa bằng ánh mắt đồng thuận.',
            'Thương vụ tại {biome}: {trader} đổi hàng thừa lấy thứ mình cần từ {partner}.',
        ],
        'build_shelter' => [
            '{builder} dùng gỗ và đá dựng nên một mái nhà đơn sơ giữa {biome}. Một ngọn lửa nhỏ bắt đầu cháy.',
            'Từ đôi bàn tay trần, {builder} đã xây dựng nên nơi trú ẩn đầu tiên cho mình.',
        ],
        'death' => [
            '{agent} gục xuống, hơi thở cuối cùng tan vào gió. Cái đói đã chiến thắng.',
            'Một linh hồn nữa rời bỏ mảnh đất này. {agent} đã không thể vượt qua.',
            'Sự im lặng bao trùm khi {agent} nằm bất động. Thế giới mất đi một sinh mệnh.',
        ],
        'birth' => [
            'Một tiếng khóc vang lên giữa {biome}. {child} chào đời, mang trong mình dòng máu của {parent1} và {parent2}.',
            'Sự sống tìm được đường giữa hỗn loạn — {child} ra đời!',
        ],
        'forage' => [
            '{agent} cúi người nhặt nhạnh những quả dại trong khu rừng rậm rạp.',
        ],
        'eat' => [
            '{agent} ngồi xuống, nhai chậm rãi miếng thức ăn quý giá. Nỗi sợ lùi xa.',
        ],
        'season_change' => [
            'Gió đổi hướng. Mùa {season} bắt đầu kéo về, thay đổi diện mạo của thế giới.',
            'Bầu trời chuyển màu. Mùa {season} đã đến — một kỷ nguyên mới mở ra.',
        ],
    ];

    /**
     * Sinh văn xuôi từ event type và context variables.
     */
    public function narrate(string $eventType, array $context = []): string
    {
        if (!isset($this->templates[$eventType])) {
            return "Một sự kiện không xác định đã xảy ra: $eventType.";
        }

        $templateList = $this->templates[$eventType];
        $template = $templateList[array_rand($templateList)];

        // Thay thế các biến {key} bằng giá trị thực
        foreach ($context as $key => $value) {
            $template = str_replace('{' . $key . '}', (string) $value, $template);
        }

        return $template;
    }

    /**
     * Phát hiện Story Arc: Chuỗi sự kiện liên quan đến cùng 1 Agent.
     * 
     * @param array $eventLog Danh sách sự kiện [{type, agent, tick}, ...]
     * @return array<string, array> Các arc theo agent_id
     */
    public function detectStoryArcs(array $eventLog): array
    {
        $arcs = [];

        foreach ($eventLog as $event) {
            $agentId = $event['agent'] ?? null;
            if (!$agentId) continue;

            if (!isset($arcs[$agentId])) {
                $arcs[$agentId] = ['events' => [], 'arc_type' => 'unknown'];
            }

            $arcs[$agentId]['events'][] = $event;
        }

        // Gán arc type dựa trên pattern
        foreach ($arcs as $agentId => &$arc) {
            $types = array_column($arc['events'], 'type');

            if (in_array('death', $types)) {
                $arc['arc_type'] = 'tragedy';
            } elseif (in_array('attack', $types) && in_array('build_shelter', $types)) {
                $arc['arc_type'] = 'redemption'; // Bị đánh rồi xây nhà mới
            } elseif (count(array_filter($types, fn($t) => $t === 'cooperate')) >= 2) {
                $arc['arc_type'] = 'friendship';
            } elseif (in_array('birth', $types)) {
                $arc['arc_type'] = 'legacy';
            }
        }

        return $arcs;
    }
}
