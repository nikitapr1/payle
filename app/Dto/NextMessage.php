<?php

namespace App\Dto;

class NextMessage
{
    /**
     * @var callable
     */
    private $handler;
    private array $data;

    public function __construct(callable $handler, array $data)
    {

        $this->handler = $handler;
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return callable
     */
    public function getHandler(): callable
    {
        return $this->handler;
    }
}
