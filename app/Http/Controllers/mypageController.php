<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\UpdateBankInfoRequest;

use Illuminate\Support\Carbon;
use App\Transfer;
use App\FromBank;
use App\User;
use App\Like;
use App\SystemCommission;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\DB;
use App\Product;
use App\Order;
use App\CategoryProduct;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class mypageController extends Controller
{
    /**
     *  処理済み売り上げ表示
     */

    public function showTransfer(Request $request, $id)
    {
        Log::debug('<<<<<<    transferShow    >>>>>>>>>>>');

        $transfer = Transfer::find($id);
        $orders =  Order::where('transfer_id', '=', $id)
            ->join('products', 'orders.product_id', 'products.id')
            ->get();

        return view('mypage.transfer', compact(['orders', 'transfer']));
    }

    /**
     *  振込依頼作成
     */
    public function requestTransfer(Request $request)
    {
        Log::debug('<<<<<<    requesttransfer    >>>>>>>>>>>');

        //=========   プライスフラグを見て、各売上の手数料を引いて振込額を決める
        $untransferred = Order::query()
            ->join('products', 'orders.product_id', '=', 'products.id')
            ->where('products.user_id', Auth::user()->id)
            ->where('status', 0)
            ->where('orders.created_at', '<', Carbon::now()->startOfMonth())
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->select('orders.id', 'orders.created_at as created_at', 'sale_price', 'transfer_price', 'status')
            ->orderBy('created_at', 'desc')
            ->get();

        // =========      各売上の手数料を引いて振込額を決める
        //$transfer_price_before 振込手数料を引く前の金額
        $transfer_price_before = $untransferred->groupBy(function ($row) {
            return $row->status;
        })
            ->map(function ($day) {
                return $day->sum('transfer_price');
            });

        //数値に変換
        $transfer_price_before = $transfer_price_before[0];

        //==================振込テーブルに新規レコードをつくる

        //振込手数料計算（今後変更あり）
        if ($transfer_price_before > 30000) {
            //FromBank::find(1)->commission1がメイン
            $commission = FromBank::find(1)->commission1;
        } else {
            //FromBank::find(1)->commission1がメイン
            $commission = FromBank::find(1)->commission1;
        }

        $transfer = new Transfer;
        $transfer->user_id = Auth::user()->id;
        //振込手数料を引いたものを振込む
        $transfer->transfer_price = $transfer_price_before; //運営が振り込む金額
        // $transfer->transferred_price = $transfer_price_after; //システム手数料を引いて実際に振り込まれる金額 nullableにしとく
        $transfer->commission = $commission;
        $transfer->from_bank_id = 1;
        $transfer->payment_date = Carbon::parse('last day of next month');
        $transfer->save();

        //オーダーに結びつく振込テーブルidを格納

        $ids = $untransferred->pluck('id');

        //新規作成した振込依頼テーブルに結びつく注文情報を更新
        $orders = Order::whereIn('id', $ids)->update(['status' => 1]); //１は申請中（振込前）
        $orders = Order::whereIn('id', $ids)->update(['transfer_id' => $transfer->id]);

        return redirect()->route('mypage.order');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        return view('mypage.index', compact('user'));
    }

    /**
     * プロダクト一覧機能
     */

    public function products(Request $request)
    {
        // 下書き保存中の作品数
        $drafts = Auth::user()->products()
            ->where('open_flg', 1)
            ->get();

        //出品作品
        $products = Auth::user()->products()->latest()->get();
        $product_category = Product::all();
        $product_difficulty = Product::all();

        //購入済み作品
        $buy_products = DB::table('orders')
            ->where('orders.user_id', Auth::user()->id)
            ->join('products', 'orders.product_id', '=', 'products.id')
            ->get();

        return view('mypage.product', [
            'products' => $products,
            'product_categories' => $product_category,
            'product_difficulties' => $product_difficulty,
            'drafts' => $drafts,
            'buy_products' => $buy_products,
        ]);
    }

    /**
     * 注文管理機能
     */

    public function order()
    {
        //========  今月の売上 ==============
        $user = Auth::user();
        $payjp_sk = config('services.payjp.sk_test_p');
        $tenant_id = $user->payjp_tenant_id;

        \Payjp\Payjp::setApiKey($payjp_sk);
        $thisMonth_sale = array_sum(array_column(\Payjp\Charge::all(array(
            "tenant" => $tenant_id,
            "since" => strtotime(Carbon::now()->startOfMonth()) //月初のタイムスタンプ
        ))["data"], "amount"));

        //=========   未振込売上履歴  ==============
        $all =
            \Payjp\Charge::all(array(
                "tenant" => $tenant_id,
            ))["data"];
        $filter = array_filter($all, function ($arr) {
            return $arr->paid === false;
        });
        $untransferred_sale = array_sum(array_column($filter, "amount"));

        //==========  振込履歴  ==============
        $transfers = Transfer::where('user_id', Auth::user()->id)->get();

        return view('mypage.order', [
            'thisMonth_sale' => $thisMonth_sale,
            'untransferred_sale' => $untransferred_sale,
            'transfers' => $transfers,
            'user' => $user,
        ]);
    }
    //=============================================
    //========  1ヶ月の売上リストページ   ============
    //=============================================
    public function orderMonth(Request $request, $year_month)
    {
        $targetYear = substr($year_month, 0, 4);
        $targetMonth = substr($year_month, 7, 2);

        //売上履歴（振込依頼済）
        $sales = Order::query()
            ->join('products', 'orders.product_id', '=', 'products.id')
            ->where('products.user_id', Auth::user()->id)
            ->whereYear('orders.created_at', $targetYear)
            ->whereMonth('orders.created_at', $targetMonth)

            ->join('users', 'orders.user_id', '=', 'users.id')
            ->select('orders.id', 'orders.created_at as created_at', 'sale_price', 'status', 'products.name')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('mypage.orderMonth', [
            'sales' => $sales,
            'year_month' => $year_month,
        ]);
    }

    //=============================================
    //==========        振込履歴ページ     ==========
    //=============================================
    public function paid(Request $request)
    {
        $paids = Transfer::where('user_id', Auth::user()->id)
            ->where('status', 1)
            ->get();

        return view('mypage.paid', [
            'paids' => $paids,
        ]);
    }

    //=============================================
    //==========       下書き　ページ     ==========
    //=============================================

    public function draft(Request $request)
    {

        $products = Product::where('user_id', Auth::user()->id)
            ->where('open_flg', 1)
            ->latest()->get();

        Log::debug(' <<<<<<   マイページ（　下書き　）   >>>>>>>');

        $product_category = Product::all();
        $product_difficulty = Product::all();

        return view('mypage.draft', [
            'products' => $products,
            'product_categories' => $product_category,
            'product_difficulties' => $product_difficulty,
        ]);
    }
    //=============================================
    //==========       お気に入り　ページ     ==========
    //=============================================
    public function like()
    {
        $likeIds = Like::where('likes.user_id', Auth::user()->id)->get('product_id');
        $products = Product::whereIn('id', $likeIds)->get();

        Log::debug(' <<<<<<   マイページ（　お気に入り　）   >>>>>>>');

        $product_category = Product::all();
        $product_difficulty = Product::all();

        return view('mypage.like', [
            'products' => $products,
            'product_categories' => $product_category,
            'product_difficulties' => $product_difficulty,
        ]);
    }

    //=============================================
    //==========       購入作品　ページ     ==========
    //=============================================
    public function buy()
    {
        $productsIds =  Order::where('orders.user_id', Auth::user()->id)->get('product_id');
        $products = Product::whereIn('id', $productsIds)->get();

        Log::debug(' <<<<<<   マイページ（ 購入作品　）   >>>>>>>');

        $product_category = Product::all();
        $product_difficulty = Product::all();

        return view('mypage.buy', [
            'products' => $products,
            'product_categories' => $product_category,
            'product_difficulties' => $product_difficulty,
        ]);
    }
    //=============================================
    //==========       出品作品　ページ     ==========
    //=============================================
    public function sale(Request $request)
    {
        $products = Product::where('user_id', Auth::user()->id)
            ->where('open_flg', 0)
            ->latest()->get();

        Log::debug(' <<<<<<   マイページ（ 出品作品　）   >>>>>>>');

        $product_category = Product::all();
        $product_difficulty = Product::all();

        return view('mypage.sale', [
            'products' => $products,
            'product_categories' => $product_category,
            'product_difficulties' => $product_difficulty,
        ]);
    }

    //=============================================
    //=====    （銀行情報）編集　ページ     ==========
    //=============================================
    public function edit()
    {
        $id = Auth::user()->id;
        $bank = User::find($id);

        return view('mypage.edit', [
            'id' => $id,
            'bank' => $bank,
        ]);
    }

    //=============================================
    //=====    （銀行情報）更新　　　　　     ==========
    //=============================================
    public function update(UpdateBankInfoRequest $request, $id)
    {
        Log::debug('<<<<   update    >>>>>>');
        Log::debug('<<<<   request    >>>>>>');
        Log::debug($request);

        $user = User::find($id);
        try {
            $payjp_sk = config('services.payjp.sk_test_p');
            $tenant_id = $user->payjp_tenant_id;

            \Payjp\Payjp::setApiKey($payjp_sk);

            if (empty($tenant_id)) {
                // ユーザーのpayjp_tenand_idカラムにデータがない場合、テナント新規作成
                $tenant = \Payjp\Tenant::create(
                    array(
                        //テナントidは指定せず、自動生成させる
                        "name" => $user->email,
                        "platform_fee_rate" => SystemCommission::find(1)->commission_rate * 100,
                        "minimum_transfer_amount" => 5000,
                        "bank_account_holder_name" => $request->bank_account_holder_name,
                        "bank_code" => $request->bank_code,
                        "bank_branch_code" => $request->bank_branch_code,
                        "bank_account_type" => $request->bank_account_type === '0' ? "普通" : "当座",
                        "bank_account_number" => $request->bank_account_number,
                    )
                );
            } else {
                // ユーザーのpayjp_tenand_idカラムにデータが存在する場合は、テナント情報の更新
                $tenant = \Payjp\Tenant::retrieve($tenant_id);

                $tenant->bank_account_holder_name = $request->bank_account_holder_name;
                $tenant->bank_code = $request->bank_code;
                $tenant->bank_branch_code = $request->bank_branch_code;
                $tenant->bank_account_type = $request->bank_account_type === '0' ? "普通" : "当座";
                $tenant->bank_account_number = $request->bank_account_number;
                $tenant->save();
            }
        } catch (\Payjp\Error\Card $e) {
            //
            return redirect()->route('mypage')->with('flash_message', 'テナント情報の登録に失敗しました。');
        }
        $user->payjp_tenant_id = $tenant->id;
        $user->fill($request->all())->save();

        return redirect()->route('mypage')->with('flash_message', '口座情報を変更しました');
    }
}
