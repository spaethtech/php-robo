<?php
declare(strict_types=1);

namespace src\Task\Packer;


trait Tasks
{

    /**
     * @param array $configuration
     * @return Bundle
     */
    protected function taskPackerBundle(array $configuration = [])
    {
        // Always construct your tasks with the task builder.
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->task(Bundle::class, $configuration);
    }

}
