<?php
declare(strict_types=1);

namespace rspaeth\Robo\Task\Sftp;

use PHPUnit\Framework\TestCase;

final class GetTests extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        chdir(dirname(__DIR__, 4));
    }

    public function testRun()
    {
        $remote = "/readme.txt";
        $local  = ".".DIRECTORY_SEPARATOR."readme.txt";

        echo exec("robo sftp:get $remote $local");

        $this->assertFileExists($local);

        if(file_exists($local))
            unlink($local);
    }



}
