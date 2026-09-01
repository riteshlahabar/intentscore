<?php
namespace App\Http\Requests\Admin\Lead;
use Illuminate\Foundation\Http\FormRequest;
class LeadRequest extends FormRequest { public function authorize():bool{return auth()->check();} public function rules():array{return ['title'=>'required|string|max:200','client_id'=>'nullable|exists:clients,id','product_id'=>'nullable|exists:products,id','owner_id'=>'nullable|exists:users,id','source'=>'nullable|string|max:40','status'=>'required|in:new,contacted,demo_sent,proposal_sent,negotiation,won,lost','priority'=>'required|in:low,medium,high,urgent','expected_value'=>'nullable|numeric|min:0','next_follow_up_at'=>'nullable|date','requirement'=>'nullable|string|max:10000','notes'=>'nullable|string|max:10000'];} }
