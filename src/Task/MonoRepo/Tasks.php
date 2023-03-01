<?php
/**
 * @noinspection PhpUnused
 * @noinspection PhpReturnDocTypeMismatchInspection
 */
declare(strict_types=1);

namespace SpaethTech\Robo\Task\MonoRepo;

use Robo\Collection\CollectionBuilder;

trait Tasks
{
    /**
     * @param string $repo The package/repository name
     *
     * @return PackageAdd|CollectionBuilder
     */
    protected function taskPackageAdd(string $repo)
    {
        return $this->task(PackageAdd::class, $repo);
    }

    /**
     * @param string $repo The package/repository name
     *
     * @return PackageRemove|CollectionBuilder
     */
    protected function taskPackageRemove(string $repo)
    {
        return $this->task(PackageRemove::class, $repo);
    }

}
