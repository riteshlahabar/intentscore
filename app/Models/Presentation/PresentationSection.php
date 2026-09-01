<?php
namespace App\Models\Presentation;
use Illuminate\Database\Eloquent\Model;
class PresentationSection extends Model {
    protected $fillable=['presentation_id','section_key','custom_title','custom_content','settings','sort_order','is_enabled'];
    protected $casts=['settings'=>'array','is_enabled'=>'boolean'];
    public function presentation(){return $this->belongsTo(Presentation::class);}
}
