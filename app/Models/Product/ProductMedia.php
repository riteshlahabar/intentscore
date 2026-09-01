<?php
namespace App\Models\Product;
use Illuminate\Database\Eloquent\Model;
class ProductMedia extends Model {
    protected $table='product_media';
    protected $fillable=['product_id','title','type','file_path','external_url','sort_order','is_active'];
    protected $casts=['is_active'=>'boolean'];
    public function product(){ return $this->belongsTo(Product::class); }
}
