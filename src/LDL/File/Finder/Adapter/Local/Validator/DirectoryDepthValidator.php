<?php declare(strict_types=1);

namespace LDL\File\Finder\Adapter\Local\Validator;

use LDL\Validators\Config\Exception\InvalidConfigException;
use LDL\Validators\Config\ValidatorConfigInterface;
use LDL\Validators\Traits\ValidatorValidateTrait;
use LDL\Validators\ValidatorInterface;

class DirectoryDepthValidator implements ValidatorInterface
{
    use ValidatorValidateTrait;

    /**
     * @var Config\DirectoryDepthValidatorConfig
     */
    private $config;

    public function __construct(int $depth = 2, bool $dumpable=true)
    {
        $this->config = new Config\DirectoryDepthValidatorConfig($depth, $dumpable);
    }

    public function assertTrue($path): void
    {
        $base = dirname($path);

        $dirs = substr_count($base, \DIRECTORY_SEPARATOR);
        $maxDepthExceeded = $dirs > $this->config->getDepth();

        if($maxDepthExceeded){
            throw new Exception\MaxDepthException("Maximum directory depth exceeded");
        }
    }

    /**
     * @param ValidatorConfigInterface $config
     * @return ValidatorInterface
     * @throws InvalidConfigException
     */
    public static function fromConfig(ValidatorConfigInterface $config): ValidatorInterface
    {
        if(false === $config instanceof Config\DirectoryDepthValidatorConfig){
            $msg = sprintf(
                'Config expected to be %s, config of class %s was given',
                __CLASS__,
                get_class($config)
            );
            throw new InvalidConfigException($msg);
        }

        /**
         * @var Config\DirectoryDepthValidatorConfig $config
         */
        return new self(
            $config->getDepth(),
            $config->isDumpable()
        );
    }

    /**
     * @return Config\DirectoryDepthValidatorConfig
     */
    public function getConfig(): Config\DirectoryDepthValidatorConfig
    {
        return $this->config;
    }
}