<?php

namespace App\Services;
use App\Models\Product;
use App\Models\Cart;
class CartService
{
    public static function getItemsInCart($items)
    {
        $products = []; // 空の配列を準備

        // カート情報を元に配列を作成
        foreach ($items as $item) // カート内の商品を一つずつ処理
        {
            $p = Product::findOrFail($item->product_id);
            $owner = $p->shop->owner->select('name', 'email')->first()->toArray(); // オーナー情報
            $values = array_values($owner); // 連想配列の値を取得
            $keys = ['ownerName', 'email'];
            $ownerInfo = array_combine($keys, $values); // オーナー情報のキーを変更

            $product = Product::where('id', $item->product_id)
                        ->select('id', 'name', 'price')->get()->toArray(); // 商品情報の配列
                        
            $quantity = Cart::where('product_id', $item->product_id)
                        ->select('quantity')->get()->toArray(); // 在庫数の配列
            $result = array_merge($product[0], $ownerInfo, $quantity[0]); // 配列の結合
            array_push($products, $result); // 配列に追加
        }
        return $products; // 新しい配列を返す


    }
}
