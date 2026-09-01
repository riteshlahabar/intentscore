<?php
namespace App\Models\Product;
use Illuminate\Database\Eloquent\Model;
class ProductFeature extends Model {
    protected $fillable=['product_id','title','description','icon','sort_order','is_active'];
    protected $casts=['is_active'=>'boolean'];
    public function product(){ return $this->belongsTo(Product::class); }
}
