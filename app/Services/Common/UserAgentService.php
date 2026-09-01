<?php
namespace App\Services\Common;
class UserAgentService {
    public function parse(?string $ua): array {
        $ua=$ua??'';
        $device=preg_match('/Mobile|Android|iPhone|iPad/i',$ua) ? (preg_match('/iPad|Tablet/i',$ua)?'Tablet':'Mobile') : 'Desktop';
        $browser='Other'; foreach(['Edg'=>'Edge','OPR'=>'Opera','Chrome'=>'Chrome','Firefox'=>'Firefox','Safari'=>'Safari'] as $needle=>$name){if(str_contains($ua,$needle)){ $browser=$name; break; }}
        $os='Other'; foreach(['Windows'=>'Windows','Android'=>'Android','iPhone'=>'iOS','iPad'=>'iPadOS','Macintosh'=>'macOS','Linux'=>'Linux'] as $needle=>$name){if(str_contains($ua,$needle)){ $os=$name; break; }}
        return compact('device','browser','os');
    }
}
