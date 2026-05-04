<?php

namespace App\Modules\Simulation\Core\Runtime\RuleVM;

use Illuminate\Support\Facades\File;

class RuleGraphService
{
    public function buildGraph(string $rulesDirectory): array
    {
        $files = File::allFiles($rulesDirectory);
        $graph = [
            'nodes' => [],
            'edges' => [],
        ];

        $fields = [];
        foreach ($files as $file) {
            if ($file->getExtension() !== 'dsl') continue;
            
            $content = $file->getContents();
            $fileRules = $this->parseDsl($content, $file->getRelativePathname());
            
            foreach ($fileRules as $rule) {
                $ruleId = "rule:" . $rule['name'];
                $graph['nodes'][] = [
                    'id' => $ruleId,
                    'label' => $rule['name'],
                    'type' => 'rule',
                    'file' => $rule['file'],
                    'category' => $rule['category'] ?? 'general'
                ];

                foreach ($rule['triggers'] as $trigger) {
                    $fieldId = "field:" . $trigger;
                    $fields[$trigger] = true;
                    $graph['edges'][] = ['id' => "e:{$fieldId}->{$ruleId}", 'source' => $fieldId, 'target' => $ruleId, 'type' => 'trigger'];
                }

                foreach ($rule['modifications'] as $mod) {
                    $fieldId = "field:" . $mod;
                    $fields[$mod] = true;
                    $graph['edges'][] = ['id' => "e:{$ruleId}->{$fieldId}", 'source' => $ruleId, 'target' => $fieldId, 'type' => 'modify'];
                }
            }
        }

        foreach (array_keys($fields) as $field) {
            $graph['nodes'][] = ['id' => "field:" . $field, 'label' => $field, 'type' => 'field'];
        }

        return $graph;
    }

    protected function parseDsl(string $content, string $filename): array
    {
        $rules = [];
        $chunks = preg_split('/(?=rule\s+)/', $content);

        foreach ($chunks as $chunk) {
            if (!preg_match('/rule\s+([a-zA-Z0-9_]+)/', $chunk, $matches)) continue;
            
            $rule = [
                'name' => $matches[1],
                'file' => $filename,
                'triggers' => [],
                'modifications' => [],
                'reads' => [],
                'category' => null
            ];

            if (preg_match('/category\s+([a-zA-Z0-9_]+)/', $chunk, $m)) $rule['category'] = $m[1];
            if (preg_match_all('/trigger\s+([a-zA-Z0-9_\.]+)/', $chunk, $m)) $rule['triggers'] = array_unique($m[1]);
            if (preg_match_all('/(?:drift|set)\s+([a-zA-Z0-9_\.]+)/', $chunk, $m)) $rule['modifications'] = array_unique($m[1]);

            $rules[] = $rule;
        }

        return $rules;
    }
}
