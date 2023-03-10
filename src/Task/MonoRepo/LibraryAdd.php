<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace SpaethTech\Robo\Task\MonoRepo;

use Robo\Result;

/**
 * Class LibraryAdd
 *
 * @author Ryan Spaeth <rspaeth@spaethtech.com>
 * @copyright 2022 Spaeth Technologies Inc.
 */
class LibraryAdd extends LibraryBase
{
    /**
     * @return Result
     */
    public function run(): Result
    {
        if (file_exists($this->getPath()))
        {
            if ($this->force)
            {
                $this->removeSubmodule();
            }
            else
            {
                return Result::error($this,
                    "Found an existing package at <bg=red;options=bold>".$this->getPath()."</>, ".
                    "use <bg=red;options=bold>--force</> to force replacement");
            }
        }

        $this->addSubmodule();

        return Result::success($this);
    }

}
