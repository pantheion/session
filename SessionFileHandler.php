<?php

namespace Pantheion\Session;

use Pantheion\Facade\Arr;
use Pantheion\Filesystem\Directory;
use Pantheion\Filesystem\File;
use Carbon\Carbon;

/**
 * Class to handle the session files
 */
class SessionFileHandler
{
    /**
     * Lifetime of a session in minutes
     */
    const LIFETIME = 60;

    /**
     * Path to the sessions folder
     */
    const SESSIONS_FOLDER = 'storage/sessions';

    /**
     * Directory of the session files
     *
     * @var Directory
     */
    protected $directory;

    /**
     * SessionFileHandler constructor function
     */
    public function __construct()
    {
        $this->directory = Directory::get(SessionFileHandler::SESSIONS_FOLDER);
    }

    /**
     * Returns the proper last file for the
     * Session class
     *
     * @return File
     */
    public function lastSessionFile()
    {
        if(Arr::empty($this->directory->files())) {
            return null;
        }

        $files = array_filter($this->directory->files(), function($file) {
            return filemtime($file->fullpath) > Carbon::now()->subMinutes(SessionFileHandler::LIFETIME)->getTimestamp();
        });

        return !Arr::empty($files) ? Arr::last($files) : null;
    }

    /**
     * Returns the contents of the session file
     *
     * @param File $file
     * @return array
     */
    public function read(File $file)
    {
        return unserialize($file->contents());
    }

    /**
     * Writes the data into a file
     * with the name equal to the
     * id of the session
     *
     * @param string $id
     * @param array $data
     * @return void
     */
    public function write(string $id, array $data)
    {
        $path = $this->directory->path . DIRECTORY_SEPARATOR . $id . ".session";

        if(File::exists($path)) {
            return file_put_contents($path, serialize($data));
        }

        File::create($path, serialize($data));
    }

    /**
     * Removes the file with the 
     * Session id passed as parameter
     *
     * @param string $id
     * @return void
     */
    public function delete(string $id)
    {
        File::remove($this->directory->path . DIRECTORY_SEPARATOR . $id . ".session");
    }
}