<?php declare(strict_types=1);

namespace LDL\File\Finder\Adapter\Local\Validator\Config;

use LDL\File\Finder\Adapter\Local\Validator\DirectoryDepthValidator;
use LDL\Framework\Base\Contracts\ArrayFactoryInterface;
use LDL\Framework\Base\Exception\ArrayFactoryException;
use LDL\Validators\Config\Traits\ValidatorConfigTrait;
use LDL\Validators\Config\ValidatorConfigInterface;

class DirectoryDepthValidatorConfig implements ValidatorConfigInterface
{
    use ValidatorConfigTrait;

    /**
     * @var int
     */
    private $depth;

    public function __construct(int $depth=2, bool $dumpable=true)
    {
        if($depth <= 0){
            $msg = sprintf('Depth for validator "%s" must be a integer greater than 0', DirectoryDepthValidator::class);
            throw new \InvalidArgumentException($msg);
        }

        $this->depth = $depth;
        $this->_tDumpable = $dumpable;
    }

    /**
     * @return int
     */
    public function getDepth(): int
    {
        return $this->depth;
    }

    /**
     * @return array
     */
    public function jsonSerialize() : array
    {
        return $this->toArray();
    }

    /**
     * @param array $data
     * @return ArrayFactoryInterface
     * @throws ArrayFactoryException
     */
    public static function fromArray(array $data = []): ArrayFactoryInterface
    {
        try{
            return new self(
                array_key_exists('depth', $data) ? (int)$data['depth'] : 2,
                array_key_exists('dumpable', $data) ? (bool)$data['dumpable'] : true
            );
        }catch(\Exception $e){
            throw new ArrayFactoryException($e->getMessage());
        }
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'depth' => $this->depth,
            'dumpable' => $this->_tDumpable
        ];
    }
}