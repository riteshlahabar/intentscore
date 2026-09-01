<?php
namespace App\Models\Analytics;
use App\Models\Presentation\Presentation;
use Illuminate\Database\Eloquent\Model;
class PresentationSession extends Model {
    protected $fillable=['presentation_id','session_uuid','visitor_uuid','ip_address','country','city','device_type','browser','operating_system','user_agent','referrer','source','current_section','started_at','last_activity_at','ended_at','active_seconds'];
    protected $casts=['started_at'=>'datetime','last_activity_at'=>'datetime','ended_at'=>'datetime','active_seconds'=>'integer'];
    public function presentation(){return $this->belongsTo(Presentation::class);} public function events(){return $this->hasMany(PresentationEvent::class,'session_id');}
}
