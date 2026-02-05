@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/mypage/index.css') }}">
@endsection

@section('content')
<main class="mypage">
    <h1 class="visually-hidden">マイページ</h1>

    {{-- Profile --}}
    <section class="mypage__profile profile">
        <img
            src="{{ $user->profile_image ? asset('storage/' . $user->profile_image) : '' }}"
            class="profile__image {{ $user->profile_image ? '' : 'profile__image--default' }}"
            alt=""
        >

        <div class="profile__meta">
            <div class="profile__name">{{ $user->name }}</div>

            @php
                $rating = (int)($rating ?? 0);
            @endphp

            @if($rating > 0)
                <div class="profile__stars" aria-label="評価 {{ $rating }} / 5">
                    @for($i = 1; $i <= 5; $i++)
                        <span class="star {{ $i <= $rating ? 'is-on' : '' }}">★</span>
                    @endfor
                </div>
            @endif
        </div>

        <a href="{{ route('profile.create') }}" class="profile__edit-button">
            プロフィールを編集
        </a>
    </section>

    {{-- Tabs (Nav) --}}
    <nav class="mypage__tabs tabs" aria-label="マイページのタブ">
        <ul class="tabs__list">
            <li class="tabs__item">
                <a href="{{ route('mypage', ['tab' => 'sell']) }}"
                   class="tabs__link {{ $tab === 'sell' ? 'is-active' : '' }}">
                    出品した商品
                </a>
            </li>

            <li class="tabs__item">
                <a href="{{ route('mypage', ['tab' => 'buy']) }}"
                   class="tabs__link {{ $tab === 'buy' ? 'is-active' : '' }}">
                    購入した商品
                </a>
            </li>

            <li class="tabs__item">
                <a href="{{ route('mypage', ['tab' => 'deal']) }}"
                   class="tabs__link {{ $tab === 'deal' ? 'is-active' : '' }}">
                    取引中の商品
                    @if(($unreadDealCount ?? 0) > 0)
                        <span class="tabs__badge">{{ $unreadDealCount }}</span>
                    @endif
                </a>
            </li>
        </ul>
    </nav>

    {{-- Contents --}}
    <section class="mypage__items" aria-label="商品一覧">
        <h2 class="visually-hidden">一覧</h2>

        @if ($tab === 'deal')
            @if ($purchases->isEmpty())
                <p class="item-list__empty">取引中の商品はまだありません。</p>
            @else
                <ul class="item-list" aria-live="polite">
                    @foreach ($purchases as $purchase)
                        @php $item = $purchase->item; @endphp
                        <li class="item-card">
                            <a class="item-card__link" href="{{ route('deals.show', $purchase) }}">
                                <div class="item-card__thumb">
                                    <img
                                        src="{{ asset('storage/' . $item->image_path) }}"
                                        class="item-card__image"
                                        alt="{{ $item->name }}の商品画像"
                                    >
                                    @if($purchase->unread_count > 0)
                                        <span class="item-card__badge">{{ $purchase->unread_count }}</span>
                                    @endif
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        @else
            @if ($items->isEmpty())
                <p class="item-list__empty">
                    {{ $tab === 'buy' ? '購入した商品はまだありません。' : '出品した商品はまだありません。' }}
                </p>
            @else
                <ul class="item-list" aria-live="polite">
                    @foreach ($items as $item)
                        <li class="item-card">
                            <a class="item-card__link" href="{{ route('items.show', $item) }}">
                                <div class="item-card__thumb">
                                    <img
                                        src="{{ asset('storage/' . $item->image_path) }}"
                                        class="item-card__image"
                                        alt="{{ $item->name }}の商品画像"
                                    >
                                    @if ($item->is_sold)
                                        <span class="item-card__badge item-card__badge--sold">SOLD</span>
                                    @endif
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        @endif
    </section>
</main>
@endsection