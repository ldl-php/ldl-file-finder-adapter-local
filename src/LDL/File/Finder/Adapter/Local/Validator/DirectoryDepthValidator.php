<?php

declare(strict_types=1);

namespace LDL\File\Finder\Adapter\Local\Validator;

use LDL\Validators\Traits\ValidatorValidateTrait;
use LDL\Validators\ValidatorHasConfigInterface;
use LDL\Validators\ValidatorInterface;

class DirectoryDepthValidator implements ValidatorInterface, ValidatorHasConfigInterface
{
    use ValidatorValidateTrait;

    /**
     * @var int
     */
    private $depth;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var string
     */
    private $baseDirectory;

    public function __construct(
        int $depth = 2,
        string $description = null
    ) {
        if ($depth <= 0) {
            $msg = sprintf('Depth for validator "%s" must be a integer greater than 0', DirectoryDepthValidator::class);
            throw new \InvalidArgumentException($msg);
        }

        $this->depth = $depth;
        $this->description = $description;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function getDescription(): string
    {
        if (!$this->description) {
            return sprintf(
                'Validate that the directory depth is: %s',
                $this->depth,
            );
        }

        return $this->description;
    }

    public function assertTrue($path): void
    {
        $base = dirname($path);

        if (null === $this->baseDirectory) {
            $this->baseDirectory = $base;
        }

        if (0 !== strpos($base, $this->baseDirectory)) {
            $this->baseDirectory = $base;
        }

        $base = substr($base, strlen($this->baseDirectory));

        $dirs = substr_count($base, \DIRECTORY_SEPARATOR);

        $maxDepthExceeded = $dirs >= $this->depth;

        if ($maxDepthExceeded) {
            throw new Exception\MaxDepthException("Maximum directory depth exceeded");
        }
    }

    public function jsonSerialize(): array
    {
        return $this->getConfig();
    }

    /**
     * @throws Exception\DirectoryDepthValidatorException
     */
    public static function fromConfig(array $data = []): ValidatorInterface
    {
        try {
            return new self(
                array_key_exists('depth', $data) ? (int) $data['depth'] : 2,
                $data['description'] ?? null
            );
        } catch (\Exception $e) {
            throw new Exception\DirectoryDepthValidatorException($e->getMessage());
        }
    }

    public function getConfig(): array
    {
        return [
            'depth' => $this->depth,
            'description' => $this->getDescription(),
        ];
    }
}
