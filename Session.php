<?php

namespace Pantheion\Session;

use Pantheion\Facade\Arr;
use Pantheion\Facade\Str;

/**
 * Represents a session 
 */
class Session
{
    /**
     * File handler
     *
     * @var SessionFileHandler
     */
    protected $handler;

    /**
     * Session ID
     *
     * @var string
     */
    public $id;

    /**
     * Session's data
     *
     * @var array
     */
    protected $data;

    /**
     * Session constructor function
     *
     * @param SessionFileHandler $handler
     * @param string $id
     */
    public function __construct(SessionFileHandler $handler)
    {
        $this->handler = $handler;
        $this->start();
    }

    /**
     * Starts the session instance and
     * fills it with the current data
     *
     * @return void
     */
    public function start()
    {
        $file = $this->handler->lastSessionFile();
        if(!$file) {
            return $this->initialize();
        }

        $this->id = $file->name;
        $this->data = $this->handler->read($file);

        $this->tickFlash();
    }

    /**
     * Initializes a new session
     *
     * @param array $data
     * @return void
     */
    protected function initialize(array $data = [])
    {
        $this->id = Str::random(40);
        $this->data = $data;

        if(!$this->has('_token')) {
            $this->generateToken();
        }

        $this->save();
    }

    /**
     * Checks if the item is present
     * in the storage
     *
     * @param string $key
     * @return boolean
     */
    public function has(string $key)
    {
        return isset($this->data[$key]);
    }

    /**
     * Returns all items in the session storage
     *
     * @return array
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * Gets an item from the storage or
     * a default value if that item doesn't
     * exist
     *
     * @param string $key
     * @param $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        if(!$this->has($key)) {
            if(!is_null($default)) {
                return $default;
            }

            return null;
        }

        return $this->data[$key];
    }

    /**
     * Puts an item in the session storage
     *
     * @param string $key
     * @param $value
     * @return void
     */
    public function put(string $key, $value)
    {
        $this->data[$key] = $value;
        $this->save();
    }

    /**
     * Removes an item from the storage
     *
     * @param string $key
     * @return void
     */
    public function remove(string $key)
    {
        if($this->has($key)) {
            unset($this->data[$key]);
        }

        $this->save();
    }

    /**
     * Resets the data array to be empty
     *
     * @return void
     */
    public function flush()
    {
        $this->data = [];
    }

    /**
     * Flashes for the subsequent
     * request some data
     *
     * @param string $key
     * @param $value
     * @return void
     */
    public function flash(string $key, $value) 
    {        
        $this->put('_flash.new', Arr::merge($this->get('_flash.new', []), [$key]));
        $this->put($key, $value);
    }

    /**
     * Flashes for one request
     * some data
     *
     * @param string $key
     * @param $value
     * @return void
     */
    public function now(string $key, $value) 
    {
        $this->put('_flash.old', Arr::merge($this->get('_flash.old', []), [$key]));
        $this->put($key, $value);
    }

    /**
     * Passes the new flash data to the
     * old flash data and deletes the
     * previous old flash data
     *
     * @return void
     */
    protected function tickFlash()
    {
        $oldKeys = $this->get('_flash.old', []);
        foreach($oldKeys as $key) {
            $this->remove($key);
        }

        $this->remove('_flash.old');
        
        $this->put('_flash.old', $this->get('_flash.new', []));

        $this->put('_flash.new', []);
    }

    /**
     * Sets the _old_input flash data
     *
     * @param array $data
     * @return void
     */
    public function flashInput(array $data)
    {
        $this->flash('_old_input', $data);
    }

    /**
     * Gets an item from the _old_input
     * flash data
     *
     * @param string $key
     * @param $default
     * @return mixed
     */
    public function getOldInput(string $key, $default = null)
    {
        $oldInput = $this->get('_old_input', []);

        if(Arr::empty($oldInput)) {
            return $default;
        }

        return Arr::has($oldInput, $key) ? $this->get('_old_input')[$key] : $default;
    }

    /**
     * Regenerates the ID of the session
     *
     * @return void
     */
    public function regenerate()
    {
        $this->initialize($this->data);
    }

    /**
     * Saves the session into a file
     *
     * @return void
     */
    protected function save()
    {
        $this->handler->write($this->id, $this->data);
    }

    /**
     * Returns the CSRF token
     */
    public function token()
    {
        return $this->get('_token');
    }

    /**
     * Generates a new CSRF token
     *
     * @return string
     */
    protected function generateToken()
    {
        $this->put('_token', Str::random(40));
    }
}