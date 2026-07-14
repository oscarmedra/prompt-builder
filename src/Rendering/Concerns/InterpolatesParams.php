<?php

namespace NoahMedra\PromptBuilder\Rendering\Concerns;

/**
 * Shared {placeholder} / {nested.key} interpolation used by every
 * renderer. Unknown placeholders are left untouched (not silently
 * emptied) so typos are easy to spot in the output.
 */
trait InterpolatesParams
{
    protected function interpolate(string $text, array $params): string
    {
        if (empty($params)) {
            return $text;
        }

        return preg_replace_callback('/\{([a-zA-Z0-9_.]+)\}/', function ($matches) use ($params) {
            $value = $this->resolveParam($matches[1], $params);
            return $value !== null ? (string) $value : $matches[0];
        }, $text);
    }

    protected function resolveParam(string $path, array $params): mixed
    {
        $data = $params;
        foreach (explode('.', $path) as $key) {
            if (!is_array($data) || !array_key_exists($key, $data)) {
                return null;
            }
            $data = $data[$key];
        }

        return $data;
    }
}
