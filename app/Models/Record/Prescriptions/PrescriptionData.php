<?php

namespace App\Models\Record\Prescriptions;

use App\Models\Pharmacy\Drug;
use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PrescriptionData extends Model
{
    use HasFactory;
    use Compoships;

    protected $connection = 'webapp';
    protected $table = 'webapp.dbo.prescription_data';

    public function issued()
    {
        return $this->hasMany(PrescriptionDataIssued::class, 'presc_data_id');
    }

    public function dm()
    {
        return $this->belongsTo(Drug::class, ['dmdcomb', 'dmdctr'], ['dmdcomb', 'dmdctr']);
    }
}
