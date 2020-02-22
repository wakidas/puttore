<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Product;
use App\Category;
use App\Difficulty;
use App\CategoryProduct;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Log;

class ProfilesController extends Controller
{
    /**
     * 詳細表示機能
     */
    public function show($id)
    {
        if (!ctype_digit($id)) {
            return redirect('/products')->with('flash_message', __('Invalid operation was performed.'));
        }

        Log::debug('SHOW!!!');
        // $product = Product::find($id);
        
        // ユーザー情報の取得
        $user = User::find($id);
        
        Log::debug('$user');
        Log::debug($user);

        // $categoryAndDifficulty = Product::all();
        //プロダクト件数
        $all_products = Auth::user()->products()->get();
        //ログインユーザーのプロダクト（ページング）
        $products = Auth::user()->products()->paginate(10);

        //ページング用変数 始点
        $pageNum_from =  $products->currentPage()*10-9;
        //ページング用変数 終点
        $pageNum_to = $products->currentPage()*10;

        //価格をカンマ入れて表示

        //画像有無判定フラグ
        $is_image = false;
        if (Storage::disk('local')->exists('public/profile_images/' . Auth::id() . '.jpg')) {
        $is_image = true;
        }

        $product_category = Product::all();
        $product_difficulty = Product::all();



        
        return view('profile.show', [
        'products' => $products,
        'product_categories' => $product_category,
        'product_difficulties' => $product_difficulty,
        'all_products'=>$all_products,
        'pageNum_from' => $pageNum_from,
        'pageNum_to' => $pageNum_to,
        'is_image' => $is_image,
        // 'category' => $category,
        // 'difficult' => $difficult,
        // 'product' => $product,
        'user' => $user,
        // 'categoryAndDifficulty' => $categoryAndDifficulty,
        ]);
    }

    /**
     * 編集機能
     */
    public function edit($id)
    {
        // GETパラメータが数字かどうかをチェックする
        // 事前にチェックしておくことでDBへの無駄なアクセスが減らせる（WEBサーバーへのアクセスのみで済む）
        if (!ctype_digit($id)) {
            return redirect('/products')->with('flash_message', __('Invalid operation was performed.'));
        }

        $user = User::find($id);
        return view('profile.edit', ['user' => $user]);
    }

    /**
     * 更新機能
     */
    public function update(Request $request, $id)
    {
        Log::debug('プロフィール更新');
        // GETパラメータが数字かどうかをチェックする
        if (!ctype_digit($id)) {
            return redirect('/products/mypage')->with('flash_message', __('Invalid operation was performed.'));
        }
        
        Log::debug('プロフィール更新');
        $user = User::find($id);
        $user->fill($request->all())->save();
        
        $path = $request->pic->store('public/profile_images');
        Log::debug('$request->pic');
        Log::debug($request->pic);
        $user->pic = str_replace('public/', '', $path);
        $user->save();

        return redirect('/products/mypage')->with('flash_message', __('Registered.'));

    }
}