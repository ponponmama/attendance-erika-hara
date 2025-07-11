<table width="100%" style="max-width: 1500px; margin: auto; border-spacing: 0; border-collapse: collapse;">
    <tr>
        <td style="text-align: center; padding: 20px;">
            <h1 style="font-size: 2rem; color: black;">メールアドレスの確認</h1>
            <p style="font-size: 1.5rem; color: black;">{{ $user->name }} さん</p>
            <p style="font-size: 1.2rem; color: black;">以下のボタンをクリックして登録を完了してください。</p>
            <a href="{{ $verificationUrl }}"
                style="padding: 10px 20px; background-color: black; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 1.2rem; display: inline-block; margin: 20px;">登録を完了する</a>
            <p style="font-size: 1.2rem; color: black;">もしこのメールに心当たりがない場合は、このメッセージを無視してください。</p>
            <p style="font-size: 1.2rem; color: black;">よろしくお願いいたします。<br>{{ config('app.name') }}</p>
        </td>
    </tr>
</table>
