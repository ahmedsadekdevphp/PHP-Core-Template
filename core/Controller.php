<?php
namespace Core;

class Controller
{
    protected $data;

    public function __construct()
    {
        $this->data = $this->getInputData();
    }

    protected function getInputData()
    {
        $input = file_get_contents("php://input");
        return json_decode($input, true);
    }
}
