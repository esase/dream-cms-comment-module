<?php
namespace Comment\Test;

define('APPLICATION_ROOT', '../../../');
require_once APPLICATION_ROOT . 'init_tests_autoloader.php';

use UnitTestBootstrap;

class CommentBootstrap extends UnitTestBootstrap\UnitTestBootstrap
{}

CommentBootstrap::init();