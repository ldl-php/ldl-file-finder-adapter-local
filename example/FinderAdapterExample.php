<?php declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use LDL\File\Finder\FoundFile;
use LDL\Validators\HasValidatorResultInterface;
use LDL\File\Finder\Adapter\Local\LocalFileFinderAdapter;
use LDL\File\Validator\FileTypeValidator;
use LDL\File\Validator\FileSizeValidator;
use LDL\File\Validator\HasRegexContentValidator;
use LDL\Validators\Chain\AndValidatorChain;
use LDL\File\Finder\Adapter\Local\Validator\DirectoryDepthValidator;
use LDL\Framework\Base\Constants;
use LDL\File\Constants\FileTypeConstants;
use LDL\Validators\Chain\OrValidatorChain;
use LDL\Validators\RegexValidator;

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
        //Regular files: not socket, fifos, block device files, etc
        new FileTypeValidator([FileTypeConstants::FILE_TYPE_REGULAR]),
        //Files which have a size lower or equal to 1000000 bytes (1MB)
        new FileSizeValidator(1000000, Constants::OPERATOR_LTE),
        new OrValidatorChain([
            //File content must match provided regex
            new HasRegexContentValidator($match, true),
            //OR, file name must match provided regex
            new RegexValidator($match)
        ])
    ]);

    dump(LDL\Validators\Chain\Dumper\ValidatorChainExprDumper::dump($fileChain));

    if($depth > 0){
        $fileChain->getChainItems()->unshift(new DirectoryDepthValidator($depth));
    }

    $fileChain->getChainItems()->lock();

    $r = (new LocalFileFinderAdapter($fileChain))->find($files);


    $count = 0;
    /**
     * @var FoundFile $f
     */
    foreach($r as $f){
        $count++;
        echo "File: $f\n";

        /**
         * @var HasValidatorResultInterface $v
         */
        foreach($f->getValidators() as $v){
            var_dump($v->getResult());
        }
    }

    $end = hrtime(true);

    echo "Found $count files matching regex $match\n";

    echo sprintf('Search took: %s milliseconds %s', ($end-$start)/1e+6,"\n\n");
}catch(\Exception $e) {

    echo "[ Finder failed! ]\n";
    echo $e->getMessage()."\n";
    var_dump($e->getTraceAsString());

}

