<?php

namespace App\Modules\Intelligence\Domain\Policy;

enum ModelType: string
{
    case LINEAR = 'linear';
    case SIGMOID = 'sigmoid';
    case POLYNOMIAL = 'polynomial';
    case CONTEXT_AWARE = 'context_aware';
}
