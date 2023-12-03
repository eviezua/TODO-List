<?php

namespace App\Entity;

enum Status: string
{
    case Done = "Done";
    case ToDo = "ToDo";
}