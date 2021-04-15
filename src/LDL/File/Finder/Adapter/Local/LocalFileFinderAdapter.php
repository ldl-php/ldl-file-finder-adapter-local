<?php declare(strict_types=1);

namespace LDL\File\Finder\Adapter\Local;

use LDL\Framework\Base\Collection\CallableCollection;
use LDL\File\Finder\Adapter\AdapterInterface;
use LDL\File\Finder\FoundFile;
use LDL\Validators\HasValidatorResultInterface;
use LDL\Validators\Chain\ValidatorChain;
use LDL\Validators\Chain\ValidatorChainInterface;

class LocalFileFinderAdapter implements AdapterInterface
{
    /**
     * @var ValidatorChain
     */
    private $validators;

    /**
     * @var CallableCollection
     */
    private $onFile;

    /**
     * @var CallableCollection
     */
    private $onReject;

    /**
     * @var CallableCollection
     */
    private $onAccept;

    /**
     * Holds already traversed files (as keys) to avoid symlink recursion
     * @var array
     */
    private $files = [];

    private $count;

    public function __construct(ValidatorChainInterface $validatorChain = null)
    {
        $this->validators = $validatorChain ?? new ValidatorChain();
        $this->onFile = new CallableCollection();
        $this->onReject = new CallableCollection();
        $this->onAccept = new CallableCollection();
    }

    public function onFile() : CallableCollection
    {
        return $this->onFile;
    }

    public function onReject(): CallableCollection
    {
        return $this->onReject;
    }

    public function onAccept() : CallableCollection
    {
        return $this->onAccept;
    }

    public function find(iterable $directories): iterable
    {
        foreach($directories as $dir){
            if(!is_string($dir)){
                continue;
            }

            if(!is_dir($dir)){
                continue;
            }

            $files = scandir($dir);

            foreach($files as $file){
                if('.' === $file || '..' === $file){
                    continue;
                }

                $file = sprintf('%s%s%s', $dir, DIRECTORY_SEPARATOR, $file);

                if(array_key_exists($file, $this->files)){
                    continue;
                }

                $this->onFile()->call($file);

                $this->count++;

                $this->files[$file] = true;

                if(!is_readable($file)){
                    continue;
                }

                if(is_dir($file)){
                    yield from $this->find([$file]);
                }

                try{
                    $this->validators->validate($file);

                    $foundFile = new FoundFile(
                        $file,
                        new \SplFileInfo($file),
                        $this->validators->filterByInterface(HasValidatorResultInterface::class)
                    );

                    $this->onAccept()->call($file, $this->validators);

                    yield $foundFile;
                }catch(\Exception $e){

                    $this->onReject()->call($file, $this->validators);

                    continue;
                }

            }
        }
    }

    public function getTotalFileCount(): int
    {
        return $this->count;
    }

    public function getValidatorChain(): ValidatorChainInterface
    {
        return $this->validators;
    }

}
