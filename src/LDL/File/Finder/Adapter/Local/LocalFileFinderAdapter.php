<?php declare(strict_types=1);

namespace LDL\File\Finder\Adapter\Local;

use LDL\Framework\Base\Collection\CallableCollection;
use LDL\File\Finder\Adapter\AdapterInterface;
use LDL\File\Finder\FoundFile;
use LDL\Validators\Chain\ValidatorChainInterface;
use LDL\Validators\HasValidatorResultInterface;

class LocalFileFinderAdapter implements AdapterInterface
{
    /**
     * @var ValidatorChainInterface
     */
    private $validators;

    /**
     * @var ValidatorChainInterface
     */
    private $validatorsResult;

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
        $this->validators = $validatorChain;
        $this->onFile = new CallableCollection();
        $this->onReject = new CallableCollection();
        $this->onAccept = new CallableCollection();
        $this->validatorsResult = $this->validators->filterByInterfaceRecursive(HasValidatorResultInterface::class);
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

                try{
                    if(null !== $this->validators) {
                        $this->validators->validate($file);
                    }

                    $foundFile = new FoundFile(
                        $file,
                        new \SplFileInfo($file),
                        $this->validatorsResult->getCollection()
                    );

                    $this->onAccept()->call($file, $this->validators);

                    yield $foundFile;

                    if(is_dir($file)){
                        yield from $this->find([$file]);
                    }

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

    public function getValidatorChain(): ?ValidatorChainInterface
    {
        return $this->validators;
    }

}
