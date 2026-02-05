@component('mail::message')
# 取引完了のお知らせ

{{ $seller?->name ?? '出品者' }} 様

以下の商品について、購入者が「取引完了」しました。

- 商品名：{{ $item?->name ?? '' }}
- 購入者：{{ $buyer?->name ?? '' }}

取引内容の確認はアプリのマイページから行えます。

@component('mail::button', ['url' => url('/mypage?tab=deal') ])
取引中一覧を開く
@endcomponent

引き続きよろしくお願いいたします。
{{ config('app.name') }}
@endcomponent
