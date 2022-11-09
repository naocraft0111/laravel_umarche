<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\User;
use App\Models\Stock;
use Illuminate\Support\Facades\Auth;
use App\Services\CartService;
use App\Jobs\SendThanksMail;
use App\Jobs\SendOrderedMail;

class CartController extends Controller
{
    public function index()
    {
        // ログインしているユーザーを取得
        $user = User::findOrFail(Auth::id());
        // ユーザーと紐づいている全ての商品を取得
        $products = $user->products;
        // 合計金額の初期値
        $totalPrice = 0;

        foreach($products as $product){
            // 金額 * 数量(中間テーブルを経由) = 合計金額
            $totalPrice += $product->price * $product->pivot->quantity;
        }

        // dd($products, $totalPrice);
        return view('user.cart',
            compact('products', 'totalPrice'));
    }
    public function add(Request $request)
    {
        // カートに商品があるか確認
        $itemInCart = Cart::where('product_id', $request->product_id)
        ->where('user_id', Auth::id())->first();

        // 商品があれば数量追加
        if($itemInCart){
            $itemInCart->quantity += $request->quantity;
            $itemInCart->save();

        } else {
            // なければ新規追加
            Cart::create([
                'user_id' => Auth::id(),
                'product_id' => $request->product_id,
                'quantity' => $request->quantity
            ]);
        }

        return redirect()->route('user.cart.index');
    }

    public function delete($id)
    {
        Cart::where('product_id', $id)
        ->where('user_id', Auth::id())
        ->delete();

        return redirect()->route('user.cart.index');
    }

    public function checkout()
    {
        // ログインしているユーザーを取得
        $user = User::findOrFail(Auth::id());
        // ユーザーと紐づいている全ての商品を取得
        $products = $user->products;

        // カート入っている情報をこの配列に追加
        $lineItems = [];
        // 在庫確認し、決済前に在庫を減らしておく
        foreach($products as $product){
            $quantity = '';
            // 在庫を確認
            $quantity = Stock::where('product_id', $product->id)->sum('quantity');

            // カート内の商品がstockテーブルよりも多かったら購入できないようにする
            if ($product->pivot->quantity > $quantity) {
                return view('user.cart.index');
            } else {
                $lineItem = [
                    'price_data' => [
                        'currency' => 'jpy',
                        'unit_amount' => $product->price,
                        'product_data' => [
                            'name' => $product->name,
                            'description' => $product->information,
                        ],
                    ],
                    'quantity' => $product->pivot->quantity,
                ];
                array_push($lineItems, $lineItem);
            }
        }

        // 商品の数量を減らす
        foreach ($products as $product) {
            Stock::create([
                'product_id' => $product->id,
                'type' => \Constant::PRODUCT_LIST['reduce'],
                'quantity' => $product->pivot->quantity * -1
            ]);
        }


        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

        $session = \Stripe\Checkout\Session::create([
            // 支払情報
            'payment_method_types' => ['card'],
            // 商品情報
            'line_items' => [$lineItems],
            // 支払方法
            'mode' => 'payment',
            'success_url' => route('user.cart.success'),
            'cancel_url' => route('user.cart.cancel'),
        ]);

        $publicKey = env('STRIPE_PUBLIC_KEY');

        // dd($lineItems);
        return view('user.checkout',
            compact('session', 'publicKey'));
    }

    public function success()
    {
        ////
        $items = Cart::where('user_id', Auth::id())->get();
        $products = CartService::getItemsInCart($items);
        $user = User::findOrFail(Auth::id());


        SendThanksMail::dispatch($products, $user);
        foreach ($products as $product) {
            SendOrderedMail::dispatch($product, $user);
        }
        // dd('ユーザーメール送信テスト');
        ////
        Cart::where('user_id', Auth::id())->delete();

        return redirect()->route('user.items.index');
    }

    public function cancel()
    {
        $user = User::findOrFail(Auth::id());

        foreach ($user->products as $product) {
            Stock::create([
                'product_id' => $product->id,
                'type' => \Constant::PRODUCT_LIST['add'],
                'quantity' => $product->pivot->quantity
            ]);
        }

        return redirect()->route('user.cart.index');
    }
}
