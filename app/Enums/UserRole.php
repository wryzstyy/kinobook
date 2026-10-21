<?php

namespace App\Enums;

enum UserRole: string
{
    case Viewer = 'viewer';
    case Admin  = 'admin';
}