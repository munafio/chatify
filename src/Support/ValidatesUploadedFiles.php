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
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The file exceeds the server upload size limit. Increase upload_max_filesize and post_max_size in php.ini.',
                UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded. Please try again.',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'Server cannot write upload temp files. Set upload_tmp_dir in php.ini to a writable directory (e.g. storage/framework/temp).',
                default => 'The file failed to upload.',
            };

            $validator->errors()->add($field, $message);
        });
    }
}
