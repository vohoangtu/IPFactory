<?php

namespace App\Modules\Narrative\Services;

class EventTriggerMapper
{
    public function getMetricValue(array $state, string $key): mixed
    {
        if (array_key_exists($key, $state)) {
            return $state[$key];
        }
        if (isset($state['metrics']) && is_array($state['metrics']) && array_key_exists($key, $state['metrics'])) {
            return $state['metrics'][$key];
        }
        if (isset($state['pressures']) && is_array($state['pressures']) && array_key_exists($key, $state['pressures'])) {
            return $state['pressures'][$key];
        }
        return null;
    }

    /**
     * @deprecated This method is no longer implemented. Event triggering has been moved to the RuleVM pipeline.
     */
    public function detectTriggeredEvents(array $state): array
    {
        return [];
    }
}
