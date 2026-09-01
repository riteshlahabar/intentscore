<?php
namespace App\Models\Product;
use Illuminate\Database\Eloquent\Model;
class ProductDemoLink extends Model {
    protected $fillable=['product_id','label','url','username','password','type','sort_order','is_active'];
    protected $casts=['password'=>'encrypted','is_active'=>'boolean'];
    public function product(){ return $this->belongsTo(Product::class); }
}
