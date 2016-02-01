<?php

namespace Elixir\HTTP\Session\Handler;

use Elixir\DB\DBInterface;
use Elixir\DB\Query\QueryBuilderFactory;
use Elixir\DB\Query\SQL\MySQL\CreateTable;
use Elixir\DB\QueryBuilderInterface;
use Elixir\DB\SQL\ColumnFactory;
use Elixir\DB\SQL\ConstraintFactory;

/**
 * @author Cédric Tanghe <ced.tanghe@gmail.com>
 */
class SQL implements \SessionHandlerInterface
{
    /**
     * @param string $table
     * @param string $driver
     * @return CreateTable
     * @throws \RuntimeException
     */
    public static function build($table = 'sessions', $driver = QueryBuilderInterface::DRIVER_MYSQL)
    {
        $create = QueryBuilderFactory::createTable($table, $driver);
        
        switch ($driver)
        {
            case QueryBuilderInterface::DRIVER_MYSQL:
                static::tableMySQL($create);
                break;
            default:
                throw new \RuntimeException(
                    sprintf('Table creation for driver "%s" has not yet been implemented.', $driver)
                );
        }
        
        return $create;
    }
    
    /**
     * @param CreateTable $create
     */
    protected static function tableMySQL(CreateTable &$create)
    {
        $create->ifNotExists(true)
               ->column(ColumnFactory::varchar('id', 255, false)->setCollating('utf8_unicode_ci'))
               ->column(ColumnFactory::binary('data', false)->setCollating('utf8_unicode_ci'))
               ->column(ColumnFactory::dateTime('expires', false))
               ->constraint(ConstraintFactory::primary('id'))
               ->option(CreateTable::OPTION_ENGINE, CreateTable::ENGINE_INNODB)
               ->option(CreateTable::OPTION_CHARSET, CreateTable::CHARSET_UTF8);
    }
    
    /**
     * @var mixed
     */
    protected $DB;
    
    /**
     * @var integer 
     */
    protected $lifeTime;
    
    /**
     * @param mixed $DB
     * @param integer $lifeTime
     * @throws \InvalidArgumentException
     */
    public function __construct($DB, $lifeTime = -1) 
    {
        if (!$DB instanceof DBInterface || !$DB instanceof QueryBuilderInterface)
        {
            throw new \InvalidArgumentException(
                'Database object must implement the "\Elixir\DB\DBInterface" and 
                 "\Elixir\DB\QueryBuilderInterface" interfaces.'
            );
        }
        
        $this->DB = $DB;
        
        if ($lifeTime != -1)
        {
            $this->lifeTime = $lifeTime;
            ini_set('session.gc_maxlifetime', $this->lifeTime);
        }
        else
        {
            $this->lifeTime = ini_get('session.gc_maxlifetime');
        }
    }
    
    /**
     * @return mixed
     */
    public function getDB()
    {
        return $this->DB;
    }
    
    /**
     * @return integer
     */
    public function getLifeTime()
    {
        return $this->lifeTime;
    }

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $name)
    {
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->gc($this->_lifeTime);
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function read($id)
    {
        $query = $this->DB->createSelect('`sessions`')
                 ->columns('`data`')
                 ->where('`id` = ?', $id)
                 ->where('`expires` > ?', time());
        
        $result = $this->DB->query($query);
        $row = $result->first();
        
        if ($row)
        {
            return $row['data'];
        }
        
        return '';
    }
    
    /**
     * {@inheritdoc}
     */
    public function write($id, $data)
    {
        $life = time() + $this->lifeTime;
        
        $query = $this->DB->createSelect('`sessions`')->column('COUNT(*)')->where('`id` = ?', $id);
        $result = $this->DB->query($query);
        
        if ((int)$result->column(0) > 0)
        {
            $query = $this->DB->createUpdate('`sessions`')
                     ->set(['`data`' => $data, '`expires`' => $life])
                     ->where('`id` = ?', $id);
            
            $this->DB->exec($query);
        }
        else
        {
            $query = $this->DB->createInsert('`sessions`')
                     ->values([
                         '`id`' => $id, 
                         '`data`' => $data, 
                         '`expires`' => $life
                     ]);
            
            $this->DB->exec($query);
        }
        
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function destroy($id)
    {
        $query = $this->DB->createDelete('`sessions`')->where('`id` = ?', $id);
        $this->DB->exec($query);
        
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function gc($mMaxLifetime)
    {
        $query = $this->DB->createDelete('`sessions`')->where('`expires` < ?', time());
        $this->DB->exec($query);
        
        return true;
    }
}
