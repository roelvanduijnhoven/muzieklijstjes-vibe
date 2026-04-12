<?php

namespace App\Exception;

use RuntimeException;

class DeletionBlockedException extends RuntimeException
{
    /**
     * @param array<int, array{label: string, count: int}> $blockingUsages
     */
    public static function forEntity(string $entityLabel, array $blockingUsages): self
    {
        $parts = array_map(
            static fn (array $usage): string => sprintf('%d %s', $usage['count'], $usage['label']),
            $blockingUsages
        );

        return new self(sprintf(
            'Cannot delete this %s because it is still used by %s.',
            $entityLabel,
            self::joinUsageParts($parts)
        ));
    }

    /**
     * @param list<string> $parts
     */
    private static function joinUsageParts(array $parts): string
    {
        if (count($parts) === 1) {
            return $parts[0];
        }

        $lastPart = array_pop($parts);

        return sprintf('%s and %s', implode(', ', $parts), $lastPart);
    }
}
