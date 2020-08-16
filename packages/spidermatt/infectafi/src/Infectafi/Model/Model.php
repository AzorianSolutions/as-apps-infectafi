<?php

namespace Spidermatt\Infectafi\Model;

use Purekid\Mongodm\Model as BaseModel;

class Model extends BaseModel
{
    protected function __preInsert()
    {
        parent::__preInsert();
        if($this->createdOn == '')
            $this->createdOn = time();
    }

    protected function __preUpdate()
    {
        parent::__preUpdate();
        if($this->updatedOn == '')
            $this->updatedOn = time();
    }

    public static function getAttributes()
    {
    	return static::getAttrs();
    }
}