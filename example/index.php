<?php declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use LDL\File\Finder\Adapter\Local\Facade\LocalFileFinderFacade;
use LDL\File\Finder\FoundFile;
use LDL\File\Validator\FileTypeValidator;
use LDL\File\Validator\FileSizeValidator;
use LDL\File\Validator\HasRegexContentValidator;
use LDL\Framework\Helper\ComparisonOperatorHelper;
use LDL\Validators\HasValidatorResultInterface;
use LDL\Validators\Chain\OrValidatorChain;
use LDL\Validators\Chain\AndValidatorChain;
use LDL\Validators\RegexValidator;
use LDL\File\Finder\Adapter\Local\Validator\DirectoryDepthValidator;

try{

    echo "[ Find ]\n";

    if(!isset($_SERVER['argv'][1], $_SERVER['argv'][2])){
        die(sprintf('Usage: %s <dir1, dir2> <regex>%s', basename(__FILE__), "\n\n"));
    }

    $depth = isset($_SERVER['argv'][3]) ? (int) $_SERVER['argv'][3] : 0;
    $files = explode(',', $_SERVER['argv'][1]);
    $match = $_SERVER['argv'][2];

    $start = hrtime(true);

    $fileChain = new AndValidatorChain([
        new FileTypeValidator([FileTypeValidator::FILE_TYPE_REGULAR]),
        new FileSizeValidator(1000000, ComparisonOperatorHelper::OPERATOR_LTE),
        new HasRegexContentValidator($match, true)
    ]);

    $directoryChain = new AndValidatorChain([
        new FileTypeValidator([FileTypeValidator::FILE_TYPE_DIRECTORY]),
        new RegexValidator($match)
    ]);

    if($depth > 0){
        $fileChain->getChainItems()->unshift(new DirectoryDepthValidator($depth));
        $directoryChain->getChainItems()->unshift(new DirectoryDepthValidator($depth));
    }

    $fileChain->getChainItems()->lock();
    $directoryChain->getChainItems()->lock();

    $r = LocalFileFinderFacade::find(
        $files,
        true,
        new OrValidatorChain([
            $fileChain,
            $directoryChain
        ])
    );

    /*$files = IterableHelper::toArray($r);

    dump("DUMP FOUND FILES");
    dump(count($files));*/
    /**
     * @var FoundFile $f
     */
    foreach($r as $f){
        echo "File: $f\n";
        /**
         * @var HasValidatorResultInterface $v
         */
        foreach($f->getValidators() as $v){
            var_dump($v->getResult());
        }
    }

    $end = hrtime(true);

    echo sprintf('Search took: %s milliseconds %s', ($end-$start)/1e+6,"\n\n");
}catch(\Exception $e) {

    echo "[ Finder failed! ]\n";
    var_dump($e);

}

