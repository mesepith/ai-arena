<?php
namespace App\Contracts;

interface AIServiceInterface
{
    public function generateResponse($conversation, $model);
}
