<?php
/**
 * @noinspection PhpUnused
 * @noinspection PhpReturnDocTypeMismatchInspection
 */
declare(strict_types=1);

namespace SpaethTech\Robo\Task\MonoRepo;

use Robo\Collection\CollectionBuilder;

/**
 * Trait Tasks
 *
 * @author Ryan Spaeth <rspaeth@spaethtech.com>
 * @copyright 2022 Spaeth Technologies Inc.
 */
trait Tasks
{
    /**
     * @param string $name The library name
     *
     * @return LibraryAdd|CollectionBuilder
     */
    protected function taskLibraryAdd(string $name)
    {
        return $this->task(LibraryAdd::class, $name);
    }

    /**
     * @param string $name The library name
     *
     * @return LibraryDel|CollectionBuilder
     */
    protected function taskLibraryDel(string $name)
    {
        return $this->task(LibraryDel::class, $name);
    }

    /**
     * @param string $name The library name
     *
     * @return LibraryNew|CollectionBuilder
     */
    protected function taskLibraryNew(string $name)
    {
        return $this->task(LibraryNew::class, $name);
    }

}
