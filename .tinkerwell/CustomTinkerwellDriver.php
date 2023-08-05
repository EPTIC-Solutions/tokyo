<?php

use Silly\Application;

class CustomTinkerwellDriver extends TinkerwellDriver
{
    private ?Application $app = null;

    public function canBootstrap($projectPath)
    {
        return file_exists($projectPath . '/bootstrap/app.php')
            && is_dir($projectPath . '/vendor');
    }

    public function bootstrap($projectPath)
    {
        require_once $projectPath . '/vendor/autoload.php';

        $app = require_once $projectPath . '/bootstrap/app.php';
        $this->app = $app;
    }
}
