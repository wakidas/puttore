@extends('layouts.app')
@section('title','マイページ')
@section('content')

<div class="c-mypage__nav">
    <div class="c-mypage__nav__list active"><a href="/mypage">アカウント</a></div>
    <div class="c-mypage__nav__list"><a href="/mypage/products">作品一覧<br><span class="small"><br>（下書き / 購入 /
                販売）</span></a>
    </div>
    <div class="c-mypage__nav__list"><a href="/mypage/order">販売管理</a></div>
</div>
<div class="c-mypage__account">
    <div class="c-mypage__account__inner">

        <div class="c-mypage__title">
            <h2>アカウント設定</h2>
        </div>

        <div class="c-mypage__account__list img">
            <img src="/storage/{{ $user->pic }}" alt="" width="50" height="50">
        </div>

        <div class="c-mypage__account__list">
            <p class="c-mypage__account__label">名前 <span class="c-mypage__editLink"><a
                        href="{{route('profile.edit',$user->id)}}"> 変更</a></span></p>
            {{ $user->account_name }}
        </div>
        <div class="c-mypage__account__list">
            <p class="c-mypage__account__label">肩書き</p>
            {{ $user->account_title }}
        </div>
        <div class="c-mypage__account__list">
            <p class="c-mypage__account__label">自己紹介</p>
            {{ $user->account_detail }}
        </div>
        <div class="c-mypage__account__list">
            <p class="c-mypage__account__label">メールアドレス <span class="c-mypage__editLink"><a href="">
                        変更</a></span></p>
            {{ $user->email }}
        </div>
        <div class="c-mypage__account__list">
            <p class="c-mypage__account__label">パスワード <span class="c-mypage__editLink"><a href="/changepassword">
                        変更</a></span></p>
            ●●●●●●●●●
        </div>
        <div class="c-mypage__account__list c-mypage__account__list--withdraw">
            <a href="">退会する</a>
        </div>

    </div>
</div>




@endsection