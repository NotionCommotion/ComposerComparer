<?php

namespace NotionCommotion\ComposerComparer;
require_once('src/ComposerComparer.php');
$comparer = new ComposerComparer($argv[1]??null, $argv[2]??null, $argv[3]??null);
echo(PHP_EOL.json_encode($comparer->compare(), JSON_UNESCAPED_SLASHES).PHP_EOL.PHP_EOL);
echo(PHP_EOL.json_encode($comparer->merge(), JSON_UNESCAPED_SLASHES).PHP_EOL.PHP_EOL);
echo(PHP_EOL.implode(PHP_EOL, $comparer->getRequired()).PHP_EOL.PHP_EOL);
echo(PHP_EOL.implode(PHP_EOL, $comparer->getRequired(true)).PHP_EOL.PHP_EOL);
