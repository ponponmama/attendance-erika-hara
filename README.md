<p align="center">
      <img src="https://img.shields.io/badge/-Laravel-black.svg?logo=laravel&style=plastic"> <img src="https://img.shields.io/badge/-Html5-pink.svg?logo=html5&style=plastic"> <img src="https://img.shields.io/badge/-CSS3-blue.svg?logo=css3&style=plastic"> <img src="https://img.shields.io/badge/-Php-orange.svg?logo=php&style=plastic"> <img src="https://img.shields.io/badge/-Mysql-green.svg?logo=mysql&style=plastic"> <img src="https://img.shields.io/badge/-Windows-0078D6.svg?logo=windows&style=plastic"> <img src="https://img.shields.io/badge/-Docker-1488C6.svg?logo=docker&style=plastic"> <img src="https://img.shields.io/badge/-Nginx-red.svg?logo=nginx&style=plastic"> <img src="https://img.shields.io/badge/-Github-181717.svg?logo=github&style=plastic">
</p>

### サービス概要

企業が開発した独自の勤怠管理アプリです。ユーザーの勤怠管理と管理者による勤怠監視を目的としています。

### 制作の背景と目的

- ユーザーの勤怠管理を効率化
- 管理者による勤怠監視の簡素化
- シンプルで使いやすい勤怠管理システムの提供

### 制作の目標

- 初年度でのユーザー数 1000 人達成

### 主な機能一覧

### 認証機能

- ユーザー登録・ログイン
- メール認証（一般ユーザーのみ）
- ロールベースアクセス制御（ユーザー・管理者）
- Laravel Fortify による認証システム

### ユーザー機能

- 勤怠打刻（出勤・退勤・休憩開始・休憩終了）
- 勤怠一覧表示
- 勤怠詳細表示・修正申請
- 修正申請一覧表示

#### ユーザー画面

- ユーザー登録 <br>

    <img src="attendance_register.png" alt="ユーザー登録画面">

- ログイン <br>

    <img src="attendance_login.png" alt="ユーザーログイン画面">

- 勤怠打刻画面 <br>

    <img src="attendance.png" alt="ユーザー打刻勤務前画面">
    <img src="attendance_working_start.png" alt="ユーザー打刻勤務中画面">
    <img src="attendance_leave_work.png" alt="ユーザー打刻休憩中画面">
    <img src="attendance_clock_out.png" alt="ユーザー打刻勤務終了画面">

- 勤怠一覧画面 <br>

    <img src="attendance_list.png" alt="ユーザー勤怠一覧画面">

- 勤怠詳細画面 <br>

    <img src="attendance_detail.png" alt="ユーザー修正申請画面">

- 修正申請一覧画面 <br>

    <img src="attendance_stamp_correction_request_list.png" alt="修正申請一覧画面">

### 管理者機能

- 全ユーザーの勤怠一覧表示
- スタッフ一覧表示
- スタッフ別勤怠一覧表示
- 勤怠詳細表示・直接修正
- 修正申請一覧表示
- 修正申請の承認
- CSV 出力機能

### 管理者画面

- ログイン <br>

    <img src="admin_login.png" alt="ログイン画面">

- 勤怠一覧画面 <br>

    <img src="admin_attendance_list.png" alt="勤怠一覧画面">

- スタッフ一覧画面 <br>

    <img src="admin_staff_list.png" alt="スタッフ一覧画面">

- スタッフ別勤怠一覧画面 <br>

    <img src="admin_attendance_staff_list.png" alt="スタッフ別勤怠一覧画面">

- 勤怠詳細・修正画面 <br>

    <img src="admin_detail.png" alt="勤怠詳細・修正画面">

- 修正申請一覧画面 <br>

    <img src="admin_stamp_correction_request_list.png" alt="修正申請一覧画面">

- 修正申請の承認画面 <br>

    <img src="admin_pending.png" alt="承認待画面">
    <img src="admin_approved.png" alt="承認済画面">


### 開発言語・フレームワーク

- **開発言語**: PHP
- **フレームワーク**: Laravel 12.x
- **データベース**: MySQL
- **バージョン管理**: GitHub
- **コンテナ化技術**: Docker

### 開発プロセス

- 設計 → コーディング → テスト

### ER 図

![Attendance Diagram](attendance.drawio.png)

### 環境構築

> **2026年10月 アップデート**
> 2026年10月に公表された PHP・MySQL・Laravel の脆弱性（PHP2Shell 等）への対策として、
> 以下のバージョンへアップデートしました。
>
> | | 提出時 | 現在 |
> |---|---|---|
> | PHP | 7.4.9 | 8.4.27 |
> | Laravel | 8.83.29 | 12.70.1 |
> | MySQL | 8.0.26 | 8.4.13 |

- **PHP**: 8.4.27
- **MySQL**: 8.4.13
- **Composer**: 2.10.3
- **Docker**: 29.9.2
- **Laravel Framework**: 12.70.1

- ＊ご使用の PC に合わせて各種必要なファイル(.env や docker-compose.yml 等)は作成、編集してください。

### セットアップ手順

####クローン作製手順

1. Github リポジトリのクローン

```bash
git clone https://github.com/ponponmama/attendance-erika-hara.git
```

```bash
cd attendance-erika-hara
```

2. 必要なパッケージのインストール

```bash
sudo apt-get update
```

Docker コンテナのビルドと起動

```bash
docker-compose up -d --build
```

3. Composer を使用した依存関係のインストール

- github からクローンを作成するといくつかのフォルダが足りません。src に setup.sh を作成してあります。プロジェクトはフレームワーク内にインストールするので、先にフォルダ作成お願いします。

- 3-1. コンテナに入ります。

```bash
docker-compose exec php bash
```

- 3-2. スクリプトに実行権限を付与します。

```bash
chmod +x setup.sh
```

```bash
./setup.sh
```

- 以下のフォルダが作成されます

```
      bootstrap/cache \
      storage \
      storage/framework/cache \
      storage/framework/cache/data \
      storage/framework/sessions \
      storage/framework/testing \
      storage/framework/views \
      storage/logs \
      storage/logs/app \
      storage/logs/app/public \
      tests/Unit
```

<br>

#### "ディレクトリが正常に作成されました。" ← このメッセージが出ます。<br>

<br>

- 3-3 Docker 環境で PHP コンテナに入り、依存関係をインストールします。<br>

```bash
docker-compose exec php bash
```

```bash
composer install
```

<br>

4. 環境設定ファイルの設定

- .env.example ファイルを .env としてコピーし、必要に応じてデータベースなどの設定を行います。

```bash
cp .env.example .env
```

- 環境設定を更新した後、設定キャッシュをクリアするために以下のコマンドを実行します。これにより、新しい設定がアプリケーションに反映されます。

```bash
docker-compose exec php bash
```

```bash
php artisan config:clear
```

この手順は、特に環境変数が更新された後や、`.env` ファイルに重要な変更を加えた場合に重要です。設定キャッシュをクリアすることで、古い設定が引き続き使用されることを防ぎます。

5.アプリケーションキーの生成

```bash
php artisan key:generate
```

6. データベースのセットアップ

#### データベースのマイグレーション

```bash
php artisan migrate
```

7. データベースシーダーの実行

```bash
php artisan db:seed
```

＊マイグレーションとシーダーを同時に実行する場合

```bash
php artisan migrate --seed
```

## 使用方法

### ユーザーとして使用

1. ユーザー登録
2. メール認証
3. 勤怠打刻
4. 勤怠確認・修正申請

### 管理者として使用

1. 管理者アカウントでログイン
2. 勤怠一覧・スタッフ管理
3. 修正申請の承認
4. 勤怠データの直接修正

## ダミーデータログイン情報

### 管理者ユーザー

- メールアドレス: admin@example.com
- パスワード: user_pass

### 一般ユーザー

- メールアドレス: user1@example.com 〜 user20@example.com
- パスワード: user_pass

>**注意**: これらのユーザーは `php artisan db:seed` コマンドで作成されます。

\*user英数字＝id 番号　例:user1← 数字が入ります。@example.com

※ シーダー実行後に上記アカウントが作成されます。

## テスト

### テスト実行

```bash
docker-compose exec php bash
```

```bash
php artisan test
```

### テスト項目

- **機能テスト**

  - `AdminUserTest.php` - 管理者ユーザー機能テスト（管理者）
  - `Auth/EmailVerificationTest.php` - メール認証機能テスト（一般ユーザー）
  - `Attendance/TimeStampTest.php` - 勤怠打刻機能テスト（一般ユーザー）
  - `AdminCorrectionRequestTest.php` - 管理者修正申請機能テスト（管理者）
  - `AdminAttendanceDetailTest.php` - 管理者勤怠詳細機能テスト（管理者）
  - `Attendance/AttendanceListTest.php` - 勤怠一覧機能テスト（一般ユーザー）
  - `AdminAttendanceListTest.php` - 管理者勤怠一覧機能テスト（管理者）
  - `AttendanceDetailCorrectionTest.php` - 勤怠詳細修正機能テスト（一般ユーザー）
  - `Auth/UserLoginTest.php` - ログイン認証機能テスト（一般ユーザー）
  - `Auth/RegisterTest.php` - ユーザー登録機能テスト（一般ユーザー）
  - `Auth/AdminLoginTest.php` - ログイン認証機能テスト（管理者）
  - `Auth/LogoutTest.php` - ログアウト機能テスト（一般ユーザー・管理者共通）

    全 112 項目

  \*テストで一旦 db がリセットされます。マイグレートリフレッシュとシーダーしてください。

```bash
php artisan migrate:fresh --seed
```

### メール設定

開発環境でのメール送信の確認には MailHog を使用しています。`docker-compose.yml` に含まれているため、アカウント作成などは不要です。

`.env` に以下を設定してください（`.env.example` に設定済み）。

```plaintext
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

送信されたメール（会員登録時の認証メールなど）は実際には配信されず、MailHog の画面（http://localhost:8025/）で確認できます。

**注意**: `MAIL_FROM_ADDRESS` が空（null）の場合、メール送信時にエラーになります。

### URL

- **開発環境:** [http://localhost/](http://localhost/)
- **phpMyAdmin:** [http://localhost:8080/](http://localhost:8080/)
- **MailHog:** [http://localhost:8025/](http://localhost:8025/)
