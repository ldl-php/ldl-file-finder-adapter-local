<?php declare(strict_types=1);

namespace LDL\File\Finder\Adapter\Local\Facade;

use LDL\File\Collection\DirectoryCollection;
use LDL\File\Finder\Adapter\Collection\AdapterCollection;
use LDL\File\Finder\Adapter\Local\LocalFileFinderAdapter;
use LDL\File\Finder\Facade\FinderFacadeInterface;
use LDL\File\Finder\FileFinder;
use LDL\File\Finder\FinderResult;
use LDL\Validators\Chain\ValidatorChain;

class LocalFileFinderFacade implements FinderFacadeInterface
{
    public static function find(
        iterable $directories,
        iterable $validators=null,
        iterable $onAccept=null,
        iterable $onReject=null,
        iterable $onFile=null
    ) : iterable
    {
        $l = new LocalFileFinderAdapter(new ValidatorChain($validators));

        if(null !== $onAccept){
            $l->onAccept()->appendMany($onAccept);
        }

        if(null !== $onReject){
            $l->onReject()->appendMany($onReject);
        }

        if(null !== $onFile){
            $l->onFile()->appendMany($onFile);
        }

        yield from (new FileFinder(
            new AdapterCollection([
                $l
            ])
        ))
        ->find(new DirectoryCollection($directories));
    }

    public static function findResult(
        iterable $directories,
        iterable $validators=null,
        iterable $onAccept=null,
        iterable $onReject=null,
        iterable $onFile=null
    ): FinderResult
    {
        return new FinderResult(
            self::find(
                $directories,
                $validators,
                $onAccept,
                $onReject,
                $onFile
            )
        );
    }
}