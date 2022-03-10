<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wish extends Model
{
    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';

    use HasFactory;
}
