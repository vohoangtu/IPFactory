<?php

namespace App\Contracts\Simulation;

use App\Modules\World\Models\Universe;

interface SeederInterface
{
    /**
     * Gieo mầm các vật liệu và khởi tạo trạng thái ban đầu cho Universe.
     */
    public function seed(Universe $universe): void;

    /**
     * Kiểm tra xem Seeder có hỗ trợ Origin này không.
     */
    public function supports(string $origin): bool;
}

