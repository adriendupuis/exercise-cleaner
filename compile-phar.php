#!/usr/bin/env php
<?php

$pharFileName='exercice-cleaner.phar';

$phar = new Phar($pharFileName);
$phar->buildFromDirectory('src');
$phar->setStub($phar->createDefaultStub('Command.php'));
