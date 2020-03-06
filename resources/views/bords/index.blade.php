@extends('layouts.app')

@section('content')

<div class="c-bords__head">取引一覧</div>

<div class="c-bords">
    @foreach ($bords as $bord)
    
    @php

    $buy_userId = $bord->user_id;
    $sell_userId = $bord->{'p.user_id'};
    $order_type = ($buy_userId == $id) ? "購入" : "販売";
    $class_order_type = ($buy_userId == $id) ? "buy" : "sell";
    
    if($order_type == "購入"){
        $pic = $user->find($sell_userId)->pic;
    }else{
        $pic = $user->find($buy_userId)->pic;
    }
    @endphp

    <a class="c-bord__list" href="{{ route('bords.show',$bord->id) }}">
        <div class="c-bord__inner">

            <div class="c-bord__userImg__wrapper">
                <img src="/storage/{{ $pic }}" alt="" class="c-bord__userImg">
            </div>
            <div>
                <div class="c-bord__order {{$class_order_type}}">
                   {{ $order_type }}
                </div>
                <div class="c-bord__title">
                   {{ $bord->name }} 
                </div>
                <div class="c-bord__msg">
                    
                </div>
            
            </div>
        </div>
    </a>
    @endforeach
</div>

@endsection