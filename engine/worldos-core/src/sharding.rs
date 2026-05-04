use serde::{Deserialize, Serialize};
use crate::types::ZoneStateSerial;

/// Shard Identifier
pub type ShardId = u16;

/// Configuration for zone-to-shard mapping
#[derive(Debug, Clone, Default, Serialize, Deserialize)]
pub struct ShardMap {
    pub zone_to_shard: std::collections::HashMap<u32, ShardId>,
}

/// A read-only representation of a zone residing on a different shard
#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct GhostZone {
    pub id: u32,
    pub shard_id: ShardId,
    pub state_snapshot: ZoneStateSerial,
}

impl GhostZone {
    pub fn new(id: u32, shard_id: ShardId, state_snapshot: ZoneStateSerial) -> Self {
        Self { id, shard_id, state_snapshot }
    }
}
