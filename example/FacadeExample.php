<?php declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use LDL\File\Finder\Adapter\Local\Facade\LocalFileFinderFacade;
use LDL\File\Finder\FoundFile;
use LDL\File\Validator\FileTypeValidator;
use LDL\File\Validator\Config\FileTypeValidatorConfig;
use LDL\File\Validator\FileSizeValidator;
use LDL\File\Validator\Config\FileSizeValidatorConfig;
use LDL\File\Validator\HasRegexContentValidator;
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
        new FileTypeValidator([FileTypeValidatorConfig::FILE_TYPE_REGULAR]),
        new FileSizeValidator(1000000, FileSizeValidatorConfig::OPERATOR_LTE),
        new HasRegexContentValidator($match, true)
    ]);

    $directoryChain = new AndValidatorChain([
        new FileTypeValidator([FileTypeValidatorConfig::FILE_TYPE_DIRECTORY]),
        new RegexValidator($match)
    ]);

    if($depth > 0){
        $fileChain->unshift(new DirectoryDepthValidator($depth, true));
        $directoryChain->unshift(new DirectoryDepthValidator($depth, true));
    }

    $r = LocalFileFinderFacade::findResult(
        $files,
        new OrValidatorChain([
            $fileChain,
            $directoryChain
        ]),
        [
          static function (string $path){
            dump('Accept:', $path);
          }
        ]
    );

    dump("DUMP FOUND FILES");
    dump($r->count());
    /**
     * @var FoundFile $f
     */
    foreach($r as $f){
        echo "File: $f\n";
        /**
         * @var HasValidatorResultInterface $v
         */
        foreach($f->getValidatorChain() as $v){
            var_dump($v->getResult());
        }
    }

    $end = hrtime(true);

    echo sprintf('Search took: %s milliseconds %s', ($end-$start)/1e+6,"\n\n");
}catch(\Exception $e) {

    echo "[ Finder failed! ]\n";
    var_dump($e);

}

