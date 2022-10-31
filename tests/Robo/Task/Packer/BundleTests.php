<?php
declare(strict_types=1);

namespace SpaethTech\Robo\Task\Packer;

use PHPUnit\Framework\TestCase;

final class BundleTests extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        chdir(dirname(__DIR__, 4));
    }

    public function testRun()
    {
        $folder  = __DIR__;
        $output = "test";

        echo exec("robo packer:bundle $folder $output .zipignore");



    }



}
