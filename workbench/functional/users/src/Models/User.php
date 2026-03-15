<?php

namespace Functional\Users\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Functional\Users\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
}
