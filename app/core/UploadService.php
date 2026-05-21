<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class UploadService
{
    private string $destination;
    private array $allowedMimes;
    private int $maxSize;

    public function __construct(string $destination, array $allowedMimes, int $maxSize)
    {
        $this->destination = rtrim($destination, '/');
        $this->allowedMimes = $allowedMimes;
        $this->maxSize = $maxSize;

        if (!is_dir($this->destination)) {
            mkdir($this->destination, 0775, true);
        }
    }

    public function store(array $file, string $prefix = 'file'): string
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new RuntimeException('Invalid upload payload.');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload failed with code: ' . $file['error']);
        }

        if (($file['size'] ?? 0) > $this->maxSize) {
            throw new RuntimeException('File exceeds the maximum allowed size.');
        }

        $tmpName = $file['tmp_name'] ?? '';
        if (!is_uploaded_file($tmpName)) {
            throw new RuntimeException('The uploaded file is not valid.');
        }

        $mime = $this->detectMime($tmpName);
        if (!in_array($mime, $this->allowedMimes, true)) {
            throw new RuntimeException('Unsupported file type.');
        }

        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'bin',
        };

        $filename = sprintf(
            '%s_%s.%s',
            $prefix,
            bin2hex(random_bytes(8)),
                            $extension
        );

        $target = $this->destination . '/' . $filename;

        if (!move_uploaded_file($tmpName, $target)) {
            throw new RuntimeException('Could not move the uploaded file.');
        }

        return $filename;
    }

    private function detectMime(string $path): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return $finfo->file($path) ?: 'application/octet-stream';
    }
}
