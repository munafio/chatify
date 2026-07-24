<?php

declare(strict_types=1);

namespace Chatify\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class GroupSystemMessageFormatter
{
    public static function participantAdded(Model $actor, iterable $targets): string
    {
        return trans('chatify::chatify.system_messages.participant_added', [
            'actor' => $actor->name,
            'targets' => self::joinNames(self::names($targets)),
        ]);
    }

    public static function participantRemoved(Model $actor, Model $target): string
    {
        return trans('chatify::chatify.system_messages.participant_removed', [
            'actor' => $actor->name,
            'target' => $target->name,
        ]);
    }

    public static function participantLeft(Model $user): string
    {
        return trans('chatify::chatify.system_messages.participant_left', [
            'user' => $user->name,
        ]);
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
            return trans('chatify::chatify.system_messages.members');
        }

        if (count($names) === 1) {
            return $names[0];
        }

        $last = array_pop($names);

        if (count($names) === 1) {
            return trans('chatify::chatify.system_messages.list_and', [
                'names' => $names[0],
                'last' => $last,
            ]);
        }

        return trans('chatify::chatify.system_messages.list_comma', [
            'names' => implode(', ', $names),
            'last' => $last,
        ]);
    }
}
