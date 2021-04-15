<?php declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use LDL\File\Finder\Adapter\Local\Facade\LocalFileFinderFacade;
use LDL\File\Finder\FoundFile;
use LDL\Validators\Chain\ValidatorChainInterface;
use LDL\File\Validator\FileTypeValidator;
use LDL\File\Validator\Config\FileTypeValidatorConfig;
use LDL\File\Validator\FileSizeValidator;
use LDL\File\Validator\Config\FileSizeValidatorConfig;
use LDL\File\Validator\HasRegexContentValidator;
use LDL\Validators\HasValidatorResultInterface;

try{

    echo "[ Find ]\n";

    if(!isset($_SERVER['argv'][1], $_SERVER['argv'][2])){
        die(sprintf('Usage: %s <dir1, dir2> <regex>%s', basename(__FILE__), "\n\n"));
    }

    $files = explode(',', $_SERVER['argv'][1]);
    $match = $_SERVER['argv'][2];

    $start = hrtime(true);
    $r = LocalFileFinderFacade::findResult(
        $files,
        [
            //new RegexValidator("/\.php/", true),
            new FileTypeValidator([FileTypeValidatorConfig::FILE_TYPE_REGULAR]),
            new FileSizeValidator(1000000, FileSizeValidatorConfig::OPERATOR_LTE, true),
            new HasRegexContentValidator($match, true)
        ],
        [
          static function (string $path){
            dump('Accept:', $path);
          }
        ],
        [
            static function (string $path, ValidatorChainInterface $validatorChain){
                dump('Reject:', $path);
                dump("Failed validators:");
                foreach($validatorChain->getFailed() as $validator){
                    echo get_class($validator)."\n";
                }
            }
        ],
        [
            static function ($path){
                dump('File:', $path);
            }
        ]
    );

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

