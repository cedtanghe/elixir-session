<?php

namespace Elixir\Session;

use Elixir\Dispatcher\DispatcherInterface;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */
interface SessionInterface extends \ArrayAccess, DispatcherInterface
{
    /**
     * @var string
     */
    const FLASH_INFO = 'flash_info';

    /**
     * @var string
     */
    const FLASH_SUCCESS = 'flash_success';

    /**
     * @var string
     */
    const FLASH_ERROR = 'flash_error';

    /**
     * @var string
     */
    const FLASH_REDIRECT = 'flash_redirect';

    /**
     * @param \SessionHandlerInterface $value
     */
    public function setHandler(\SessionHandlerInterface $value);

    /**
     * @return \SessionHandlerInterface
     */
    public function geHandler();

    /**
     * @return bool
     */
    public function exist();

    /**
     * @return int
     */
    public function getStatus();

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
     * @param bool $deleteOldSession
     *
     * @return bool
     */
    public function regenerate($deleteOldSession = true);

    /**
     * @return bool
     */
    public function start();

    /**
     * @param string|array $key
     *
     * @return bool
     */
    public function has($key);

    /**
     * @param string|array $key
     * @param mixed        $default
     *
     * @return mixed
     */
    public function get($key, $default = null);

    /**
     * @param string|array $key
     * @param mixed        $value
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
     * @param mixed        $value
     *
     * @return mixed|void
     */
    public function flash($key = null, $value = null);

    /**
     * @return bool
     */
    public function clear();

    /**
     * @return bool
     */
    public function destroy();
}
