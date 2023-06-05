<?php

namespace App\Models\Pharmacy;

use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Drug extends Model
{
    use Compoships;
    use HasFactory;

    protected $connection = 'hospital';
    protected $table = 'hospital.dbo.hdmhdr';

    public function generic()
    {
        return $this->hasOneThrough(DrugGeneric::class, DrugGroup::class, 'grpcode', 'gencode', 'grpcode', 'gencode');
    }

    public function strength()
    {
        return $this->belongsTo(DrugStrength::class, 'strecode', 'strecode');
    }

    public function form()
    {
        return $this->belongsTo(DrugForm::class, 'formcode', 'formcode');
    }

    public function route()
    {
        return $this->belongsTo(DrugRoute::class, 'rtecode', 'rtecode');
    }

    public function sub()
    {
        return $this->belongsTo(DrugSub::class, ['dmdcomb', 'dmdctr'], ['dmdcomb', 'dmdctr']);
    }

    public function drug_name()
    {
        $generic = $this->generic->gendesc;
        $dmdnost = $this->dmdnost;
        $strength = $this->strength->stredesc ?? $this->strecode;
        $form = $this->form->fomdesc ?? $this->formcode;

        $drug = $generic." ".$dmdnost.$strength." ".$form;

        return $drug;
    }
}
