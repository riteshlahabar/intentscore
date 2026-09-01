<?php

namespace App\Models\Client;

use App\Models\Lead\Lead;
use App\Models\Presentation\Presentation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_name','contact_name','email','phone','whatsapp','city','state','country','status','notes','owner_id'];

    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
    public function leads() { return $this->hasMany(Lead::class); }
    public function presentations() { return $this->hasMany(Presentation::class); }
}
