<p>決済ページにリダイレクトします。</p>
<script src="https://js.stripe.com/v3/"></script>
<script>
    // コントローラーから渡ってきた公開鍵を受け取る
    const publicKey = '{{ $publicKey }}'
    const stripe = Stripe(publicKey)

    // 画面を読み込んだ時にredirectTotCheckoutを発火させる
    window.onload = function() {
        stripe.redirectToCheckout({
            sessionId: '{{ $session->id }}'
        }).then(function (result) {
            window.location.href = '{{ route('user.cart.cancel')}}'
        });
    }
</script>
