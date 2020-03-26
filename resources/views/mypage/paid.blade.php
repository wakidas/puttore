@extends('layouts.app')
@section('title','マイページ')
@section('content')

<div class="c-mypage__nav">
    <div class="c-mypage__nav__list"><a href="/mypage">アカウント</a></div>
    <div class="c-mypage__nav__list"><a href="/mypage">お気に入り</a></div>
    <div class="c-mypage__nav__list"><a href="/mypage">下書き</a></div>
    <div class="c-mypage__nav__list"><a href="/mypage">購入作品</a></div>
    <div class="c-mypage__nav__list"><a href="/mypage">出品作品</a></a></div>
    <div class="c-mypage__nav__list"><a href="/mypage/order">販売管理</a></div>
    <div class="c-mypage__nav__list active"><a href="/mypage/paid">振込履歴</a></div>
</div>

@yield('header')
<div class="c-mypage__order">
    <div class="c-mypage__sale">
        <div class="c-mypage__products__title c-mypage__products--paid">
            <h2>振込履歴</h2>
        </div>

        <div class="c-mypage__sale__paid">
            <table>
                <thead>
                    <tr>
                        <th class="c-mypage__sale__list c-mypage__sale__list--request">振込依頼日</th>
                        <th class="c-mypage__sale__list c-mypage__sale__list--done">振込日</th>
                        <th class="c-mypage__sale__list c-mypage__sale__list--price">振込額</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($paids as $paid)
                    <tr>
                        <td class="c-mypage__sale__list c-mypage__sale__list--day">
                            {{$paid->created_at->format('Y年m月d日')}}</td>
                        <td class="c-mypage__sale__list c-mypage__sale__list--title">
                            {{$paid->paid_date->format('Y年m月d日')}}</td>
                        <td class="c-mypage__sale__list c-mypage__sale__list--price">¥
                            {{number_format($paid->sale_price)}}</td>
                    </tr>
                    @endforeach

                </tbody>
            </table>
            <div class="c-mypage__sale__nothing">
                @if ($paids->count() == 0)
                ありません
                @endif
            </div>
        </div>

    </div>
</div>


@endsection