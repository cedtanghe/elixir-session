<?php

namespace Elixir\Session;

use Elixir\Dispatcher\DispatcherTrait;
use Elixir\Session\SessionEvent;
use Elixir\Session\SessionInterface;
use function Elixir\STDLib\array_get;
use function Elixir\STDLib\array_has;
use function Elixir\STDLib\array_remove;
use function Elixir\STDLib\array_set;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */
class Session implements SessionInterface, \Iterator, \Countable
{
    use DispatcherTrait;
    
    /**
     * @var string
     */
    const FLASH_KEY = '___SESSION_FLASH___';
    
    /**
     * @var self
     */
    protected static $instance;
    
    /**
     * @var \SessionHandlerInterface
     */
    protected $handler;
    
    /**
     * @throws \LogicException
     */
    public function __construct() 
    {
        if(null !== static::$instance)
        {
            throw new \LogicException('A session can have only one instance.');
        }
        
        static::$instance = $this;
    }
    
    /**
     * @return self
     */
    public static function instance()
    {
        return static::$instance;
    }
    
    /**
     * {@inheritdoc}
     * @throws \LogicException
     */
    public function setHandler(\SessionHandlerInterface $value)
    {
        if ($this->exist())
        {
            throw new \LogicException('Cannot set session handler after a session has already started.');
        }

        $this->handler = $value;
    }

    /**
     *{@inheritdoc}
     */
    public function geHandler()
    {
        return $this->handler;
    }

    /**
     * {@inheritdoc}
     */
    public function exist()
    {
        $sid = defined('SID') ? constant('SID') : false;

        if (false !== $sid && $this->getId())
        {
            return true;
        }

        if (headers_sent())
        {
            return true;
        }

        return false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getStatus()
    {
        return session_status();
    }

    /**
     * {@inheritdoc}
     * @throws \LogicException
     */
    public function setId($value)
    {
        if ($this->exist()) 
        {
            throw new \LogicException('Cannot set session id after a session has already started.');
        }

        session_id($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getId() 
    {
        return session_id();
    }

    /**
     * {@inheritdoc}
     * @throws \LogicException
     * @throws \InvalidArgumentException
     */
    public function setName($value)
    {
        if ($this->exist())
        {
            throw new \LogicException('Cannot set name handler after a session has already started.');
        }

        if (!preg_match('/^[a-zA-Z0-9]+$/', $value))
        {
            throw new \InvalidArgumentException('Session name contains invalid characters.');
        }

        session_name($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getName() 
    {
        return session_name();
    }

    /**
     * {@inheritdoc}
     */
    public function regenerate($deleteOldSession = true) 
    {
        return session_regenerate_id($deleteOldSession);
    }

    /**
     * {@inheritdoc}
     */
    public function start() 
    {
        if (!$this->exist()) 
        {
            if (null !== $this->handler) 
            {
                session_set_save_handler($this->handler, true);
            }

            $result = session_start();
            $this->dispatch(new SessionEvent(SessionEvent::START));
            
            return $result;
        }
        
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return array_has($key, $_SESSION);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        return array_get($key, $_SESSION, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        array_set($key, $value, $_SESSION);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key) 
    {
        array_remove($key, $_SESSION);

        if (count($this->all()) === 0)
        {
            $this->dispatch(new SessionEvent(SessionEvent::CLEAR));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        return $_SESSION;
    }

    /**
     * @see SessionInterface::sets()
     */
    public function replace(array $data)
    {
        $_SESSION = $data;
        
        if ($this->count() === 0)
        {
            $this->dispatch(new SessionEvent(SessionEvent::CLEAR));
        }
    }

    /**
     * @ignore
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * @ignore
     */
    public function offsetSet($key, $value) 
    {
        if (null === $key)
        {
            throw new \InvalidArgumentException('The key can not be undefined.');
        }

        $this->set($key, $value);
    }

    /**
     * @ignore
     */
    public function offsetGet($key) 
    {
        return $this->get($key);
    }

    /**
     * @ignore
     */
    public function offsetUnset($key)
    {
        $this->remove($key);
    }

    /**
     * @ignore
     */
    public function rewind() 
    {
        return reset($_SESSION);
    }

    /**
     * @ignore
     */
    public function current() 
    {
        return $this->get($this->key());
    }

    /**
     * @ignore
     */
    public function key() 
    {
        return key($_SESSION);
    }

    /**
     * @ignore
     */
    public function next()
    {
        return next($_SESSION);
    }

    /**
     * @ignore
     */
    public function valid() 
    {
        return null !== $this->key();
    }
    
    /**
     * @ignore
     */
    public function count() 
    {
        return count($_SESSION);
    }
    
    /**
     * {@inheritdoc}
     */
    public function flash($key = null, $value = null)
    {
        $bag = array_get(self::FLASH_KEY, $_SESSION, []);

        if (null === $key) 
        {
            $result = $bag;
            $bag = [];
        } 
        else 
        {
            if (null === $value) 
            {
                $result = null;

                if (isset($bag[$key])) 
                {
                    $result = $bag[$key];
                    unset($bag[$key]);
                }
            } 
            else
            {
                $bag[$key] = $value;
            }
        }

        array_set(self::FLASH_KEY, $bag, $_SESSION);

        if (isset($result)) 
        {
            return $result;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        if (!$this->exist())
        {
            return false;
        }
        
        $_SESSION = [];
        $this->dispatch(new SessionEvent(SessionEvent::CLEAR));
        
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy() 
    {
        if (!$this->exist())
        {
            return false;
        }
        else
        {
            $this->clear();
        }

        if (ini_get('session.use_cookies'))
        {
            $params = session_get_cookie_params();

            setcookie(
                $this->getName(), 
                '', 
                time() - 42000, 
                $params['path'], 
                $params['domain'], 
                $params['secure'], 
                $params['httponly']
            );
        }

        $result = session_destroy();
        $this->dispatch(new SessionEvent(SessionEvent::DESTROY));
        
        return $result;
    }
    
    /**
     * @ignore
     */
    public function __debugInfo()
    {
        return $this->all();
    }
}
