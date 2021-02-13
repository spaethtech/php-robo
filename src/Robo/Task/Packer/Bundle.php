<?php
declare(strict_types=1);

namespace MVQN\Robo\Task\Packer;

use Robo\Contract\TaskInterface;
use Robo\Result;
use Robo\Task\BaseTask;
use MVQN\SFTP\SftpClient;
use MVQN\SFTP\Exceptions\AuthenticationException;
use MVQN\SFTP\Exceptions\InitializationException;
use MVQN\SFTP\Exceptions\LocalStreamException;
use MVQN\SFTP\Exceptions\MissingExtensionException;
use MVQN\SFTP\Exceptions\RemoteConnectionException;
use MVQN\SFTP\Exceptions\RemoteStreamException;

/**
 * Exposes some SFTP functionality.
 *
 * ``` php
 * <?php
 * $this->taskSftp(<host>, <port>)
 *     ->login(<user>, <pass>)                  // Authenticates with the remote server.
 *     ->download(<remote>, <local>)            // Downloads the "remote" path to the "local" path.
 *     ->run();
 * ?>
 * ```
 *
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 */
class Bundle extends BaseTask
{


    protected $options = [

        "folder" => "",
        "ignore" => "",
        "output" => [
            "name" => "",
            "path" => "",
        ],
    ];

    #region CONSTRUCTOR / DESTRUCTOR

    /**
     * Base constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->setOptions($options);
    }

    #endregion

    #region SETTERS

    /**
     * Sets any desired options, overriding existing or default options.
     *
     * @param array $options The array of options.
     *
     * @return $this Returns this SFTP object to allow for method chaining.
     */
    public function setOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function setFolder(string $path): self
    {
        $this->options["folder"] = $path;
        return $this;
    }

    public function setOutput(string $name, string $path = ""): self
    {
        $this->options["output"]["name"] = $name;
        $this->options["output"]["path"] = $path;
        return $this;
    }

    public function setIgnore(string $path): self
    {
        $this->options["ignore"] = $path;
        return $this;
    }





    #endregion




    #region CACHING

    /**
     * @var string[]|null
     */
    private static $_ignoreCache = null;

    /**
     * Builds a lookup cache from an optional .zipignore file.
     *
     * @param string $ignore An optional .zipignore file.
     * @return bool Returns TRUE when the file was parsed successfully, otherwise FALSE.
     */
    private static function buildIgnoreCache(string $ignore = ""): bool
    {
        // Generates the absolute path, given an optional ignore file or using the default.
        $ignore = $ignore ?: realpath(__DIR__.DIRECTORY_SEPARATOR.".zipignore");

        // IF an ignore file does not exist, THEN set the cache to empty and return FALSE!
        if (!$ignore || !file_exists($ignore))
        {
            // Set the cache to empty, but valid.
            self::$_ignoreCache = [];
            // Return failure!
            return false;
        }

        // OTHERWISE, load all the lines from the ignore file.
        $lines = explode("\n", file_get_contents($ignore));
        // Set a clean cache collection.
        $cache = [];
        // Loop through every line from the ignore file...
        foreach ($lines as $line) {
            // Trim any extra whitespace from the line.
            $line = trim($line);
            // IF the line is empty, THEN skip!
            if ($line === "")
                continue;
            // IF the line is a comment, THEN skip!
            if(substr($line, 0, 1) === "#")
                continue;
            // IF the line contains a trailing comment, THEN strip off the comment!
            if(strpos($line, "#") !== false)
            {
                $parts = explode("#", $line);
                $line = trim($parts[0]);
            }
            // This is a valid entry, so add it to the collection.
            $cache[] = $line;
        }
        // Set the cache to the newly build collection, even if it is completely empty.
        self::$_ignoreCache = $cache;
        // Return success!
        return true;
    }

    #endregion


    /**
     * Checks an optional .zipignore file (or pre-built cache from the file) for inclusion of the specified string.
     *
     * @param string $path The relative path for which to search in the ignore file.
     * @param string $ignore The path to the optional ignore file, defaults to project root.
     *
     * @return bool Returns TRUE if the path is found in the file, otherwise FALSE.
     */
    private static function inIgnoreFile(string $path, string $ignore = ""): bool
    {
        if (!self::$_ignoreCache)
            self::buildIgnoreCache($ignore);
        // Identical match!
        if (array_search($path, self::$_ignoreCache, true) !== false)
            return true;
        // Partial match (at beginning only)!
        foreach (self::$_ignoreCache as $cacheItem)
        {
            if (strpos($path, $cacheItem) === 0)
                return true;
        }
        return false;
    }

    private $funcBefore;

    /**
     * @param callable $func
     * @return $this
     */
    public function before(callable $func): self
    {
        $this->funcBefore = $func;
        return $this;
    }

    private $funcAfter;

    /**
     * @param callable $func
     * @return $this
     */
    public function after(callable $func): self
    {
        $this->funcAfter = $func;
        return $this;
    }


    public function run()
    {
        if($this->funcBefore)
        {
            echo "Executing Before Hook...\n";
            call_user_func($this->funcBefore);
        }

        $folder = realpath($this->options["folder"] ?: getcwd());

        // IF the folder does not exist or is not a directory, THEN die()!
        if(!$folder || !is_dir($folder))
            die("The specified folder '$folder' does not exist, or is not a directory!");

        // Save the current working directory and move to the specified folder for the duration of this function!
        $old_dir = getcwd();
        chdir($folder);

        // Determine the absolute path, if any to the .zipignore file.
        $ignore = realpath($this->options["ignore"] ?: $folder.DIRECTORY_SEPARATOR.".zipignore");

        // Generate the archive name based on the project's folder name.
        $archive_name = $this->options["output"]["name"] ?: basename($folder);
        echo "$folder => $archive_name.zip\n";

        echo "Bundling...\n";


        // Instantiate a recursive directory iterator set to parse the files.
        $directory = new \RecursiveDirectoryIterator($folder);
        $file_info = new \RecursiveIteratorIterator($directory);
        // Create an empty collection of files to store the final set.
        $files = [];
        // Iterate through ALL of the files and folders starting at the root path...
        foreach ($file_info as $info)
        {
            $real_path = $info->getPathname();
            $file_name = $info->getFilename();
            // Skip /. and /..
            if($file_name === "." || $file_name === "..")
                continue;
            $path = str_replace($folder, "", $real_path); // Remove base path from the path string.
            $path = str_replace("\\", "/", $path); // Match .zipignore format
            $path = substr($path, 1, strlen($path) - 1); // Remove the leading "/"
            // IF there is no .zipignore file OR the current file is NOT listed in the .zipignore...
            if (!$ignore || !self::inIgnoreFile($path, $ignore))
            {
                // THEN add this file to the collection of files.
                $files[] = $path;
                echo "ADDED  : $path\n";
            }
            else
            {
                // OTHERWISE, ignore this file.
                echo "IGNORED: $path\n";
            }
        }
        // Generate the new archive's file name.
        $file_name = ($this->options["output"]["path"] ?: $folder)."/$archive_name.zip";

        // IF the file previously existed, THEN remove it to avoid inserting it into the new archive!
        if(file_exists($file_name))
            unlink($file_name);

        // Create a new archive.
        $zip = new \ZipArchive();

        // IF the archive could not be created, THEN fail here!
        if ($zip->open($file_name, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true)
            die("Unable to create the new archive: '$file_name'!");



        // Loop through each file in the list...
        foreach ($files as $file)
        {
            // Add the file to the archive using the same relative paths.
            $zip->addFile($file, $file);
        }
        // Report the total number of files archived.
        $total_files = $zip->numFiles;
        echo "FILES  : $total_files\n";

        // Report success or failure (including error messages).
        $status = $zip->status !== 0 ? $zip->getStatusString() : "SUCCESS!";
        echo "STATUS : $status\n";

        // Close the archive, we're all finished!
        $zip->close();


        // Return to the previous working directory.
        chdir($old_dir);

        if($this->funcAfter)
        {
            echo "Executing After Hook...\n";
            call_user_func($this->funcAfter);
        }
    }

}
