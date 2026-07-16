<?php

declare(strict_types=1);

namespace Chatify\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class ChatifyModel extends Model
{
    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if ($model->getIncrementing() === false && empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function getTable(): string
    {
        $key = $this->tableConfigKey();

        if ($key !== null) {
            return (string) config('chatify.tables.'.$key, parent::getTable());
        }

        return parent::getTable();
    }

    protected function tableConfigKey(): ?string
    {
        return null;
    }
}
