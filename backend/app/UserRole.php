<?php

namespace App;

enum UserRole: string
{
    case Admin = 'admin';
    case User = 'user';
    case Qc = 'qc';
    case Leader = 'leader';
}
