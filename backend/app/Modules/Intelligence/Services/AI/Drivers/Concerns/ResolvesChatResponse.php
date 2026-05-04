<?php

namespace App\Modules\Intelligence\Services\AI\Drivers\Concerns;

use Illuminate\Http\Client\Response;

trait ResolvesChatResponse
{
    protected function extractTextFromResponse(Response $response): ?string
    {
        $payload = $response->json();

        $candidates = [
            data_get($payload, 'choices.0.message.content'),
            data_get($payload, 'choices.0.text'),
            data_get($payload, 'message.content'),
            data_get($payload, 'output_text'),
            data_get($payload, 'output.0.content.0.text'),
        ];

        foreach ($candidates as $candidate) {
            $text = $this->normalizeChatContent($candidate);

            if ($text !== null) {
                return $text;
            }
        }

        return null;
    }

    protected function normalizeChatContent(mixed $content): ?string
    {
        if (is_string($content)) {
            $content = trim($content);
            return $content === '' ? null : $content;
        }

        if (!is_array($content)) {
            return null;
        }

        $segments = [];

        foreach ($content as $item) {
            if (is_string($item)) {
                $item = trim($item);
                if ($item !== '') {
                    $segments[] = $item;
                }

                continue;
            }

            if (!is_array($item)) {
                continue;
            }

            foreach (['text', 'content', 'value'] as $key) {
                $resolved = $this->normalizeChatContent($item[$key] ?? null);
                if ($resolved !== null) {
                    $segments[] = $resolved;
                    break;
                }
            }
        }

        if ($segments === []) {
            return null;
        }

        return implode("\n\n", $segments);
    }
}
