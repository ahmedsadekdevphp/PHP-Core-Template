<?php
namespace Core;
use Core\QueryBuilder;

class Model
{
    protected $QueryBuilder;
    public function __construct()
    {
        $this->QueryBuilder = new QueryBuilder();
    }
}
