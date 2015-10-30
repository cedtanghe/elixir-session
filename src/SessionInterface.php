<?php

namespace Elixir\Session;

use Elixir\Dispatcher\DispatcherInterface;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */
interface SessionInterface extends DispatcherInterface
{
    /**
     * @param \SessionHandlerInterface $value
     */
    public function setHandler(\SessionHandlerInterface $value);

    /**
     * @return \SessionHandlerInterface
     */
    public function geHandler();

    /**
     * @return boolean
     */
    public function exist();

    /**
     * @param string $value
     */
    public function setId($value);

    /**
     * @return string
     */
    public function getId();

    /**
     * @param string $value
     */
    public function setName($value);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param boolean $deleteOldSession
     * @return boolean
     */
    public function regenerate($deleteOldSession = true);

    /**
     * @return boolean
     */
    public function start();

    /**
     * @param string|array $key
     * @return boolean
     */
    public function has($key);

    /**
     * @param string|array $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null);

    /**
     * @param string|array $key
     * @param mixed $value
     */
    public function set($key, $value);

    /**
     * @param string|array $key
     */
    public function remove($key);

    /**
     * @return array
     */
    public function all();

    /**
     * @param array $data
     */
    public function replace(array $data);

    /**
     * @param string|array $key
     * @param mixed $value
     * @return mixed|void
     */
    public function flash($key = null, $value = null);

    /**
     * @return boolean
     */
    public function clear();

    /**
     * @return boolean
     */
    public function destroy();
}
