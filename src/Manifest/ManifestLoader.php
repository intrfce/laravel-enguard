<?php

namespace SocialMind\EnGuard\Manifest;

use JsonException;

/**
 * Locates and decodes env.json into a Manifest. Format is strict JSON with no
 * runtime schema dependency (ADR-0005): structural checks live in ManifestValidator.
 */
final class ManifestLoader
{
    public function exists(string $path): bool
    {
        return is_file($path);
    }

    public function load(string $path): Manifest
    {
        if (! is_file($path)) {
            throw ManifestException::missing($path);
        }

        $raw = (string) file_get_contents($path);

        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw ManifestException::invalidJson($path, $e->getMessage());
        }

        if (! is_array($data)) {
            throw ManifestException::invalidJson($path, 'expected a JSON object at the top level.');
        }

        return Manifest::fromArray($data);
    }
}
