<?php
namespace App\Models\Analytics;
use App\Models\Presentation\Presentation;
use Illuminate\Database\Eloquent\Model;
class PresentationEvent extends Model {
    protected $fillable=['presentation_id','session_id','event_type','section_key','element_key','target_url','duration_ms','meta','occurred_at'];
    protected $casts=['meta'=>'array','occurred_at'=>'datetime','duration_ms'=>'integer'];
    public function presentation(){return $this->belongsTo(Presentation::class);} public function session(){return $this->belongsTo(PresentationSession::class,'session_id');}
}
