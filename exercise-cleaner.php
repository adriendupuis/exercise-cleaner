<?php
$targetStep = 2 <= count($argv) ? (int)$argv[1] : 1;

$folders = 'app src';

$startTagConstant = 'TRAINING EXERCISE START STEP';
$startTagRegex = "@$startTagConstant (?<step>[0-9]+)@";
$stopTagConstant = 'TRAINING EXERCISE STOP STEP';
$stopTagRegex = "@$stopTagConstant (?<step>[0-9]+)@";

$fileList = explode(PHP_EOL, trim(`grep '$startTagConstant' -Rl $folders;`));

foreach ($fileList as $file) {
    $lines = file($file);
    $keptLines = [];
    $isInside = false;
    $step = 0;
    foreach ($lines as $lineIndex => $line) {
        if ($isInside) {
            if (false !== strpos($line, "$stopTagConstant $step")) {
                $matches = [];
                preg_match($stopTagRegex, $line, $matches);
                if ($step === (int)$matches['step']) {
                    $isInside = false;
                    $step = 0;
                }
            }
        } else if (false !== strpos($line, $startTagConstant)) {
            $matches = [];
            preg_match($startTagRegex, $line, $matches);
            $step = (int)$matches['step'];
            if ($step >= $targetStep) {
                $isInside = true;
            } else {
                $keptLines[] = $line;
                $step = 0;
            }
        } else {
            $keptLines[] = $line;
        }
    }
    file_put_contents($file . '.cleaned', $keptLines);
}
