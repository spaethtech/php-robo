<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace SpaethTech\Robo\Task\MonoRepo;

use Robo\Common\ExecOneCommand;
use Robo\Task\BaseTask;

/**
 * Class PackageBase
 *
 * @author Ryan Spaeth <rspaeth@spaethtech.com>
 * @copyright 2022 Spaeth Technologies Inc.
 */
abstract class PackageBase extends BaseTask
{
    use ExecOneCommand;

    /** @var string The folder of all packages, relative to the project's root */
    protected string $dir = "lib";

    /** @var string The package's owner/organization */
    protected string $owner = "spaethtech";

    /** @var string The package's repository name */
    protected string $repo;

    /** @var string The base URL for the desired Git provider */
    protected string $url = "https://github.com";

    /** @var bool Required to delete (or replace) an existing package */
    protected bool $force = FALSE;

    /**
     * Default constructor
     *
     * @param string $repo The package's repository name
     */
    public function __construct(string $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Task setter for $dir
     *
     * @param string $dir
     * @return $this
     */
    public function dir(string $dir): self
    {
        $this->dir = $dir;
        return $this;
    }

    /**
     * Task setter for $force
     *
     * @param bool $force
     * @return $this
     */
    public function force(bool $force = TRUE): self
    {
        $this->force = $force;
        return $this;
    }

    /**
     * Task setter for $owner
     *
     * @param string $owner
     * @return $this
     */
    public function owner(string $owner): self
    {
        $this->owner = $owner;
        return $this;
    }

    /**
     * Task setter for $url
     *
     * @param string $url
     * @return $this
     */
    public function url(string $url): self
    {
        $this->url = $url;
        return $this;
    }



    /**
     * Helper to build the Path from provided parts
     *
     * @return string
     */
    protected function getPath(): string
    {
        return "$this->dir/$this->repo";
    }

    /**
     * Helper to build the Name from provided parts
     *
     * @return string
     */
    protected function getName(): string
    {
        return "$this->owner/$this->repo";
    }

    /**
     * Helper to build then URL from provided parts
     *
     * @return string
     */
    protected function getFullUrl(): string
    {
        $name = $this->getName();
        return "$this->url/$name";
    }



    protected function addSubmodule()
    {
        $path = $this->getPath();
        $name = $this->getName();
        $url  = $this->getFullUrl();

        if(!file_exists($path))
        {
            $this->executeCommand("git submodule add --name $name $url $path");
            $this->executeCommand("git add .gitmodules $path");
        }

    }

    protected function removeSubmodule()
    {
        $path = $this->getPath();
        $name = $this->getName();

        if(file_exists($path))
        {
            # Remove the submodule entry from .git/config
            $this->executeCommand("git submodule deinit -f $path");

            # Remove the submodule directory from the super-project's .git/modules directory
            $this->executeCommand("rm -rf .git/modules/$name");

            # Remove the entry in .gitmodules and remove the submodule directory
            $this->executeCommand("git rm -rf $path");
        }

    }


}
