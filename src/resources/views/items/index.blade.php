@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/items/index.css') }}">
@endsection

@section('content')
<div class="item-list-page">

    @php
    $tab = $tab ?? 'recommend';
    $keyword = request('keyword');
    $qs = $keyword ? ['keyword' => $keyword] : [];
    @endphp

    <div class="tab-menu">
        <a href="{{ route('items.index', array_merge(['tab' => 'recommend'], $qs)) }}"
            class="{{ $tab === 'recommend' ? 'active' : '' }}">
            おすすめ
        </a>

        {{-- マイリスト：/?tab=mylist 固定 --}}
        <a href="{{ route('items.index', array_merge(['tab' => 'mylist'], $qs)) }}"
            class="{{ $tab === 'mylist' ? 'active' : '' }}">
            マイリスト
        </a>
    </div>

    {{-- 商品一覧 --}}
    <div class="item-grid">
        @foreach ($items as $item)
        <div class="item-card">
            <div class="item-image">
                <a href="{{ route('items.show', $item->id) }}">
                    <img src="{{ asset('storage/' . $item->image_path) }}" alt="商品画像">
                    @if($item->is_sold)
                    <span class="sold-label">SOLD</span>
                    @endif
                </a>
            </div>
            <div class="item-name">{{ $item->name }}</div>
        </div>
        @endforeach
    </div>

</div>
@endsection