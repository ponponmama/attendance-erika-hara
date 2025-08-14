mkdir -p bootstrap/cache \
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

echo "ディレクトリ作成完了"

# コンテナ内で実行されるため、sudoは不要
chown -R www-data:www-data storage
echo "chown実行完了"

# 必要な権限を設定
chmod -R 775 storage
echo "chmod実行完了"

echo "ディレクトリが正常に作成され、必要な権限が設定されました。"
