<?php
namespace Database\Seeders;
use App\Models\User;use App\Models\Setting\Setting;use Illuminate\Database\Seeder;use Illuminate\Support\Facades\Hash;
class DatabaseSeeder extends Seeder {public function run():void{
 User::updateOrCreate(['email'=>'admin@example.com'],['name'=>'Administrator','password'=>Hash::make('Admin@12345'),'role'=>'admin','status'=>'active','phone'=>'']);
 foreach(['company_name'=>'Your Software Company','company_tagline'=>'Business software, built around your workflow.','company_email'=>'sales@example.com','company_phone'=>'+91 00000 00000','company_whatsapp'=>'910000000000','company_address'=>'India','company_about'=>'We design and deliver reliable web and mobile software solutions with practical implementation and ongoing support.','privacy_notice'=>'This Smart Page records section views, active time, tool usage and button clicks for sales follow-up.'] as $k=>$v) Setting::updateOrCreate(['key'=>$k],['group'=>'company','value'=>$v]);
 $this->call(SmartLinkSeeder::class);
 }}
