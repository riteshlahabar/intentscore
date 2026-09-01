<?php
namespace App\Http\Requests\Admin\Client;
use Illuminate\Foundation\Http\FormRequest;
class ClientRequest extends FormRequest { public function authorize(): bool{return auth()->check();} public function rules():array{return ['company_name'=>'required|string|max:180','contact_name'=>'nullable|string|max:120','email'=>'nullable|email|max:180','phone'=>'nullable|string|max:30','whatsapp'=>'nullable|string|max:30','city'=>'nullable|string|max:100','state'=>'nullable|string|max:100','country'=>'nullable|string|max:100','status'=>'required|in:prospect,active,inactive,won,lost','notes'=>'nullable|string|max:5000','owner_id'=>'nullable|exists:users,id'];} }
