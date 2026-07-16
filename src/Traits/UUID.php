<?php

namespace Chatify\Traits;

use Illuminate\Support\Str;

trait UUID
{
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->{$model->getKeyName()} = (string) Str::uuid();
        });
    }

    public function getIncrementing ()
    {
        return false;
    }

    public function getKeyType ()
    {
        return 'string';
    }
}
