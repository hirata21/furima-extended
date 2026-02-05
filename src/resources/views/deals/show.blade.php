@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/deals/show.css') }}">
@endsection

@section('content')
@php
    $authId   = auth()->id();
    $isBuyer  = $purchase->isBuyer($authId);
    $isSeller = $purchase->isSeller($authId);

    $seller = $purchase->seller();
    $buyer  = $purchase->user;

    $partner = $isBuyer ? $seller : $buyer;
    $partnerName = $partner?->name ?? 'ユーザー';

    $item = $purchase->item;
    $itemName  = $item?->name ?? '';
    $itemPrice = $item?->price ?? null;

    // ✅ 評価状態
    $buyerRated  = $purchase->hasBuyerRated();
    $sellerRated = $purchase->hasSellerRated();

    // ✅ モーダル表示可否
    $canBuyerReview  = $isBuyer  && !$buyerRated;
    $canSellerReview = $isSeller && $buyerRated && !$sellerRated;

    // ✅ 表示用
    $isDealCompleted = $buyerRated && $sellerRated;

    // ✅ 自動オープン（必要ならtrueに）
    $autoOpenBuyerReview  = false;
    $autoOpenSellerReview = $canSellerReview;
@endphp

<main class="deal">
  <div class="deal__container">
    <div class="deal__layout">

      {{-- Sidebar --}}
      <aside class="deal__sidebar">
        <div class="sidebar__title">その他の取引</div>

        <div class="sidebar__list">
          @forelse($otherPurchases as $t)
            @php
              $unread = $t->unread_count ?? 0;
              $tItem  = $t->item;
            @endphp
            <a class="sidebar__item" href="{{ route('deals.show', $t) }}">
              <span class="sidebar__item-name">{{ $tItem?->name ?? '商品' }}</span>
              @if($unread > 0)
                <span class="sidebar__badge">{{ $unread }}</span>
              @endif
            </a>
          @empty
            <div class="sidebar__empty">取引はありません</div>
          @endforelse
        </div>
      </aside>

      {{-- Main --}}
      <section class="deal__main">

        <header class="deal__header">
          <div class="deal__header-left">
            <div class="deal__user">
              <div class="deal__avatar"></div>
              <span class="deal__username">{{ $partnerName }}</span>
            </div>
            <div class="deal__header-title">さんとの取引画面</div>
          </div>

          <div class="deal__header-right">
            {{-- 購入者側 --}}
            @if($isBuyer)
              @if($isDealCompleted)
                <span class="deal__completed-label">取引完了</span>
              @elseif($canBuyerReview)
                <button type="button" class="deal__complete-button" id="openReviewModalBuyer">
                  取引を完了する
                </button>
              @else
                <span class="deal__completed-label">出品者の評価待ち</span>
              @endif
            @endif

            {{-- 出品者側 --}}
            @if($isSeller)
              @if($isDealCompleted)
                <span class="deal__completed-label">取引完了</span>
              @elseif($canSellerReview)
                <button type="button" class="deal__complete-button" id="openReviewModalSeller">
                  評価して完了する
                </button>
              @elseif($buyerRated && !$sellerRated)
                <span class="deal__completed-label">評価をお願いします</span>
              @endif
            @endif
          </div>
        </header>

        {{-- 商品情報 --}}
        <section class="deal__item">
          <div class="deal__item-thumb">
            @if(!empty($item?->image_path))
              <img src="{{ asset('storage/' . $item->image_path) }}" alt="{{ $itemName }}">
            @else
              <div class="deal__item-thumb--placeholder"></div>
            @endif
          </div>

          <div class="deal__item-meta">
            <div class="deal__item-name">{{ $itemName }}</div>
            @if(!is_null($itemPrice))
              <div class="deal__item-price">¥{{ number_format($itemPrice) }}</div>
            @endif
          </div>
        </section>

        {{-- Chat --}}
        <section class="deal__chat">
          <div class="chat__messages">

            @foreach($messages as $m)
              @php
                $mine = ((int)$m->sender_id === (int)$authId);
                $senderName = $m->sender?->name ?? 'ユーザー';
              @endphp

              <div class="chat__row {{ $mine ? 'is-mine' : 'is-theirs' }}" data-message-row>
                {{-- 上段：アイコン＋名前 --}}
                <div class="chat__meta {{ $mine ? 'is-right' : '' }}">
                  <div class="chat__avatar"></div>
                  <div class="chat__name">{{ $senderName }}</div>
                </div>

                {{-- 下段：吹き出し --}}
                <div class="chat__bubble-wrap {{ $mine ? 'is-right' : '' }}">
                  <div class="chat__bubble" data-bubble>
                    {{-- 本文（編集対象） --}}
                    <div class="chat__body"
                        data-body-text
                        data-original="{{ e($m->body) }}"
                        @if($mine) data-editable="1" @endif
                    >{{ $m->body }}</div>

                    {{-- 画像 --}}
                    @if(!empty($m->image_path))
                      <div class="chat__image">
                        <img src="{{ asset('storage/' . $m->image_path) }}" alt="添付画像">
                      </div>
                    @endif

                    {{-- ✅ 保存用フォーム（hidden） --}}
                    @if($mine)
                      <form method="POST"
                            action="{{ route('deals.messages.update', [$purchase, $m]) }}"
                            data-edit-form
                            class="chat__edit-form-hidden">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="body" value="">
                      </form>
                    @endif
                  </div>

                  {{-- 編集/削除（自分だけ） --}}
                  @if($mine)
                    <div class="chat__actions">
                      <button type="button" class="chat__action-link" data-edit-open>編集</button>

                      <form method="POST"
                            action="{{ route('deals.messages.destroy', [$purchase, $m]) }}"
                            class="chat__action-form"
                            onsubmit="return confirm('このメッセージを削除しますか？');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="chat__action-link">削除</button>
                      </form>
                    </div>
                  @endif
                </div>
              </div>
            @endforeach
          </div>

          {{-- 送信フォーム（下の入力欄は残すならここ。要らないなら @if 全体を消す） --}}
          @if(\Illuminate\Support\Facades\Route::has('deals.messages.store'))
            <form id="chatSendForm"
                  class="chat__form"
                  method="POST"
                  action="{{ route('deals.messages.store', $purchase) }}"
                  enctype="multipart/form-data">
              @csrf

              @if($errors->any())
                <div class="chat__errors" role="alert" aria-live="polite">
                  @foreach($errors->all() as $e)
                    <div class="chat__error">{{ $e }}</div>
                  @endforeach
                </div>
              @endif

              <div class="chat__form-row">
                <input class="chat__input"
                      type="text"
                      name="body"
                      value="{{ old('body') }}"
                      placeholder="取引メッセージを入力してください"
                      data-draft-key="deal_draft_{{ $purchase->id }}">

                <label class="chat__image-btn">
                  <input type="file" name="image" accept=".png,.jpeg,.jpg">
                  画像を追加
                </label>

                <button type="submit" class="chat__send" aria-label="送信">
                  <img src="{{ asset('images/send-plane.jpg') }}" alt="">
                </button>
              </div>
            </form>
          @endif
        </section>
      </section>
    </div>
  </div>

  {{-- ✅ 購入者用モーダル --}}
  @if($canBuyerReview)
    <div class="review-modal {{ $autoOpenBuyerReview ? 'is-open' : '' }}"
         id="reviewModalBuyer"
         aria-hidden="{{ $autoOpenBuyerReview ? 'false' : 'true' }}">
      <div class="review-modal__backdrop" data-close="1"></div>

      <div class="review-modal__panel" role="dialog" aria-modal="true">
        <div class="review-modal__head">
          <div class="review-modal__title">取引が完了しました。</div>
        </div>

        <div class="review-modal__body">
          <div class="review-modal__sub">今回の取引相手はどうでしたか？</div>

          <form method="POST" action="{{ route('deals.complete', $purchase) }}" class="review-modal__form">
            @csrf
            <input type="hidden" name="score" id="reviewScoreBuyer" value="0">

            <div class="review-modal__stars" id="reviewStarsBuyer" aria-label="評価">
              @for($i=1; $i<=5; $i++)
                <button type="button" class="review-star" data-index="{{ $i }}" aria-label="{{ $i }}星">★</button>
              @endfor
            </div>

            <div class="review-modal__foot">
              <button type="submit" class="review-modal__submit">送信する</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  @endif

  {{-- ✅ 出品者用モーダル --}}
  @if($canSellerReview)
    <div class="review-modal {{ $autoOpenSellerReview ? 'is-open' : '' }}"
         id="reviewModalSeller"
         aria-hidden="{{ $autoOpenSellerReview ? 'false' : 'true' }}">
      <div class="review-modal__backdrop" data-close="1"></div>

      <div class="review-modal__panel" role="dialog" aria-modal="true">
        <div class="review-modal__head">
          <div class="review-modal__title">取引が完了しました。</div>
        </div>

        <div class="review-modal__body">
          <div class="review-modal__sub">今回の取引相手はどうでしたか？</div>

          <form method="POST" action="{{ route('deals.complete_seller', $purchase) }}" class="review-modal__form">
            @csrf
            <input type="hidden" name="score" id="reviewScoreSeller" value="0">

            <div class="review-modal__stars" id="reviewStarsSeller" aria-label="評価">
              @for($i=1; $i<=5; $i++)
                <button type="button" class="review-star" data-index="{{ $i }}" aria-label="{{ $i }}星">★</button>
              @endfor
            </div>

            <div class="review-modal__foot">
              <button type="submit" class="review-modal__submit">送信する</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  @endif

  {{-- ✅ JS --}}
  <script>
  document.addEventListener('DOMContentLoaded', function () {

    // ==========================
    // Review Modal（既存）
    // ==========================
    function initReviewModal(openBtnId, modalId, starsId, scoreId) {
      const openBtn    = document.getElementById(openBtnId);
      const modal      = document.getElementById(modalId);
      const starsWrap  = document.getElementById(starsId);
      const scoreInput = document.getElementById(scoreId);

      if (!modal || !starsWrap || !scoreInput) return;

      const closeEls = modal.querySelectorAll('[data-close="1"]');
      const buttons  = starsWrap.querySelectorAll('.review-star');

      function open() {
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add('is-open');
      }
      function close() {
        modal.setAttribute('aria-hidden', 'true');
        modal.classList.remove('is-open');
      }

      function paint(score) {
        const s = Number(score || 0);
        buttons.forEach(btn => {
          const idx = Number(btn.dataset.index);
          const full = idx;
          const half = idx - 0.5;

          btn.classList.toggle('is-full', s >= full);
          btn.classList.toggle('is-half', s >= half && s < full);
        });

        scoreInput.value = s;
        starsWrap.dataset.score = s;
      }

      if (openBtn) openBtn.addEventListener('click', open);
      closeEls.forEach(el => el.addEventListener('click', close));

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('is-open')) close();
      });

      buttons.forEach(btn => {
        btn.addEventListener('click', (e) => {
          const rect = btn.getBoundingClientRect();
          const x = e.clientX - rect.left;
          const half = (x < rect.width / 2) ? 0.5 : 1.0;

          const idx = Number(btn.dataset.index);
          const score = (idx - 1) + half;

          paint(score);
        });
      });

      paint(Number(scoreInput.value || 0));
    }

    initReviewModal('openReviewModalBuyer',  'reviewModalBuyer',  'reviewStarsBuyer',  'reviewScoreBuyer');
    initReviewModal('openReviewModalSeller', 'reviewModalSeller', 'reviewStarsSeller', 'reviewScoreSeller');


    // ==========================
    // Draft保存（本文のみ）
    // ==========================
    const draftInput = document.querySelector('.chat__input[name="body"][data-draft-key]');
    const chatSendForm = document.getElementById('chatSendForm');

    if (draftInput) {
      const key = draftInput.dataset.draftKey;

      const saved = sessionStorage.getItem(key);
      if (!draftInput.value && saved) draftInput.value = saved;

      draftInput.addEventListener('input', () => {
        sessionStorage.setItem(key, draftInput.value || '');
      });

      if (chatSendForm) {
        chatSendForm.addEventListener('submit', () => {
          sessionStorage.removeItem(key);
        });
      }

      window.addEventListener('beforeunload', () => {
        sessionStorage.setItem(key, draftInput.value || '');
      });
    }


    // ==========================
    // インライン編集（吹き出し内でそのまま編集）
    // ==========================
    const sendForm = document.getElementById('chatSendForm');

    function focusAtEnd(el) {
      const range = document.createRange();
      range.selectNodeContents(el);
      range.collapse(false);
      const sel = window.getSelection();
      sel.removeAllRanges();
      sel.addRange(range);
    }

    function closeAllEdits() {
      document.querySelectorAll('[data-body-text][contenteditable="true"]').forEach(el => {
        const bubble = el.closest('[data-bubble]');
        el.removeAttribute('contenteditable');
        el.classList.remove('is-editing');
        if (bubble) bubble.classList.remove('is-editing');
        el.textContent = el.dataset.original ?? el.textContent;
      });
    }

    function openEdit(row) {
      const body  = row.querySelector('[data-body-text][data-editable="1"]');
      const form  = row.querySelector('[data-edit-form]');
      const bubble = row.querySelector('[data-bubble]');
      if (!body || !form || !bubble) return;

      closeAllEdits();

      body.setAttribute('contenteditable', 'true');
      body.classList.add('is-editing');
      bubble.classList.add('is-editing');

      body.focus();
      focusAtEnd(body);

      // 編集中は下の送信フォームを隠す（Figmaっぽく邪魔にならない）
      if (sendForm) sendForm.style.display = 'none';

      // Enterで保存 / Escでキャンセル
      const onKeyDown = (e) => {
        if (body.getAttribute('contenteditable') !== 'true') return;

        if (e.key === 'Escape') {
          e.preventDefault();
          cancel();
          return;
        }

        // Enter保存（Shift+Enterは改行したい時だけ許可）
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          save();
          return;
        }
      };

      const onClickOutside = (e) => {
        if (row.contains(e.target)) return;
        cancel();
      };

      function save() {
        const newText = (body.textContent || '').trim();
        if (newText.length === 0) { cancel(); return; }

        const hidden = form.querySelector('input[name="body"]');
        hidden.value = newText;
        form.submit();
      }

      function cancel() {
        body.textContent = body.dataset.original ?? body.textContent;
        body.removeAttribute('contenteditable');
        body.classList.remove('is-editing');
        bubble.classList.remove('is-editing');

        document.removeEventListener('mousedown', onClickOutside);
        body.removeEventListener('keydown', onKeyDown);

        if (sendForm) sendForm.style.display = '';
      }

      body.addEventListener('keydown', onKeyDown);
      document.addEventListener('mousedown', onClickOutside);
    }

    document.querySelectorAll('[data-message-row]').forEach(row => {
      const openBtn = row.querySelector('[data-edit-open]');
      if (!openBtn) return;

      openBtn.addEventListener('click', () => openEdit(row));
    });

  });
  </script>
</main>
@endsection