<?php
namespace App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
class Setting extends Model {
    protected $fillable=['group','key','value','type','is_public']; protected $casts=['is_public'=>'boolean'];
    public static function value(string $key, mixed $default=null): mixed { return static::query()->where('key',$key)->value('value') ?? $default; }
}
