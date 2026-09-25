<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MembershipApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_type',
        'full_name',
        'email',
        'phone',
        'health_goals',
        'status',
    ];
}
