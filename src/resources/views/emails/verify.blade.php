<table width="100%" style="max-width: 1500px; margin: auto; border-spacing: 0; border-collapse: collapse;">
    <tr>
        <td style="text-align: center; padding: 20px;">
            <h1 style="font-size: 2rem; color: black; font-weight: 700; margin-bottom: 2%;">メールアドレスの確認</h1>
            <p style="font-size: 1.5rem; color: black; margin-bottom: 2%;">{{ $user->name }} さん</p>
            <p style="font-size: 1.25rem; font-weight: 400; color: black; margin-bottom: 2%;">以下のボタンをクリックしてメール認証を完了してください</p>
            <a href="{{ $verificationUrl }}"
                style="padding: 15px 25px; background-color: #D9D9D9; color: black; border: 1px solid #D9D9D9; text-decoration: none; border-radius: 10px; font-weight: 700; font-size: 2rem; display: inline-block; margin: 5%;">認証を完了する</a>
            <p style="font-size: 1.2rem; color: black;">もしこのメールに心当たりがない場合は、このメッセージを無視してください。</p>
            <p style="font-size: 1.2rem; color: black;">よろしくお願いいたします。<br>{{ config('app.name') }}</p>
        </td>
    </tr>
</table>
