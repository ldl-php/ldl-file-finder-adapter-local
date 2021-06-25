<?php declare(strict_types=1);

namespace LDL\File\Finder\Adapter\Local\Facade;

use LDL\File\Collection\DirectoryCollection;
use LDL\File\Finder\Adapter\Collection\AdapterCollection;
use LDL\File\Finder\Adapter\Local\LocalFileFinderAdapter;
use LDL\File\Finder\Facade\FinderFacadeInterface;
use LDL\File\Finder\FileFinder;
use LDL\Validators\Chain\ValidatorChainInterface;

class LocalFileFinderFacade implements FinderFacadeInterface
{
    public static function find(
        iterable $directories,
        bool $recursive = true,
        ValidatorChainInterface $validators=null
    ) : iterable
    {
        $l = new LocalFileFinderAdapter(
            $validators
        );

        yield from (new FileFinder(
            new AdapterCollection([
                $l
            ])
        ))
        ->find(new DirectoryCollection($directories), $recursive);
    }
}