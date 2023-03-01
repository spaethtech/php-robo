<?php
declare(strict_types=1);

namespace Templating;

trait Tasks
{

    /**
     * @param string $pathToGit
     *
     * @return Template|\Robo\Collection\CollectionBuilder
     */
    protected function taskTemplate(string $path)
    {
        return $this->task(Template::class, $path);
    }


}
