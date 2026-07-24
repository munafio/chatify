<?php

declare(strict_types=1);

namespace Chatify\Support;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\UploadedFile;

trait ValidatesUploadedFiles
{
    protected function addUploadErrorMessages(Validator $validator, string $field): void
    {
        $validator->after(function (Validator $validator) use ($field) {
            if ($validator->errors()->has($field)) {
                return;
            }

            $file = $this->file($field);
            if (! $file instanceof UploadedFile || $file->isValid()) {
                return;
            }

            $message = match ($file->getError()) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => __('chatify::chatify.errors.upload_exceeds_server_limit'),
                UPLOAD_ERR_PARTIAL => __('chatify::chatify.errors.upload_partial'),
                UPLOAD_ERR_NO_FILE => __('chatify::chatify.errors.upload_no_file'),
                UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => __('chatify::chatify.errors.upload_temp_dir_error'),
                default => __('chatify::chatify.errors.upload_failed'),
            };

            $validator->errors()->add($field, $message);
        });
    }
}
