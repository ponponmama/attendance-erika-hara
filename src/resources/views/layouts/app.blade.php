<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COACHTECH</title>
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    @yield('css')
</head>

<body class="body-container">
    <header class="header-container">
        <div class="header-logo">
            <img class="header-logo__image" src="{{ asset('images/CoachTech_White 1.svg') }}" alt="COACHTECH">
        </div>
        @auth
            <nav class="header-nav">
                <ul class="header-nav__list">
                    @if (Auth::user()->role === 'admin')
                        {{-- 管理者用ヘッダー --}}
                        <li class="header-nav__item">
                            <a href="/admin/attendance/list" class="header-nav__link">勤怠一覧</a>
                        </li>
                        <li class="header-nav__item">
                            <a href="#" class="header-nav__link">スタッフ一覧</a>
                        </li>
                        <li class="header-nav__item">
                            <a href="/admin/attendance/stamp_correction_list" class="header-nav__link">申請一覧</a>
                        </li>
                        <li class="header-nav__item">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="header-nav__link button">ログアウト</button>
                            </form>
                        </li>
                    @else
                        {{-- 一般ユーザー用ヘッダー --}}
                        <li class="header-nav__item"><a href="{{ route('attendance_index') }}"
                                class="header-nav__link">勤怠</a></li>
                        <li class="header-nav__item"><a href="{{ route('attendance_list') }}"
                                class="header-nav__link">勤怠一覧</a></li>
                        <li class="header-nav__item">
                            <a href="{{ route('stamp_correction_list') }}" class="header-nav__link">申請一覧</a>
                        </li>
                        <li class="header-nav__item">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="header-nav__link button">ログアウト</button>
                            </form>
                        </li>
                    @endif
                </ul>
            </nav>
        @endauth
    </header>
    <main class="main-content">
        @yield('content')
    </main>
</body>

</html>
