<?php

namespace App\Models\Record\Prescriptions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescriptionDuration extends Model
{
    use HasFactory;

    protected $connection = 'webapp';
    protected $table = 'webapp.dbo.prescription_duration';
}
