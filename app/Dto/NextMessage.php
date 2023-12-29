<?php

namespace App\Dto;

class NextMessage
{
    private string $command;
    private array $data;



    public function __construct(string $command, array $data)
    {

        $this->command = $command;
        $this->data = $data;

    }



    public function getData(): array
    {
        return $this->data;
    }

    public function getAction(): string
    {
        return $this->command;
    }
}
