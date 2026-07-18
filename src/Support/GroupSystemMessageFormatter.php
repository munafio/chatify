<?php

declare(strict_types=1);

namespace Chatify\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class GroupSystemMessageFormatter
{
    public static function participantAdded(Model $actor, iterable $targets): string
    {
        $names = self::joinNames(self::names($targets));

        return "{$actor->name} added {$names}";
    }

    public static function participantRemoved(Model $actor, Model $target): string
    {
        return "{$actor->name} removed {$target->name}";
    }

    public static function participantLeft(Model $user): string
    {
        return "{$user->name} left";
    }

    private static function names(iterable $users): array
    {
        return Collection::make($users)
            ->map(fn (Model $user): string => (string) $user->name)
            ->values()
            ->all();
    }

    private static function joinNames(array $names): string
    {
        if ($names === []) {
            return 'members';
        }

        if (count($names) === 1) {
            return $names[0];
        }

        $last = array_pop($names);

        return implode(', ', $names).' and '.$last;
    }
}
