<?php

namespace App\Modules\Narrative\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NarrativeEdge extends Model
{
    public const TYPE_ACTOR_INVOLVED_IN = 'ACTOR_INVOLVED_IN';
    public const TYPE_OCCURS_IN = 'OCCURS_IN';
    public const TYPE_CAUSES = 'CAUSES';
    public const TYPE_INTERPRETED_AS = 'INTERPRETED_AS';
    public const TYPE_REMEMBERED_AS = 'REMEMBERED_AS';

    protected $fillable = [
        'from_node_id',
        'to_node_id',
        'edge_type',
        'perspective',
        'weight',
        'metadata',
    ];

    protected $casts = [
        'weight' => 'float',
        'metadata' => 'array',
    ];

    public function fromNode(): BelongsTo
    {
        return $this->belongsTo(NarrativeNode::class, 'from_node_id');
    }

    public function toNode(): BelongsTo
    {
        return $this->belongsTo(NarrativeNode::class, 'to_node_id');
    }
}
