mkdir -p bootstrap/cache \
        storage \
        storage/framework/cache \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/logs \
        storage/logs/app \
        storage/logs/app/public

# コンテナ内で実行されるため、sudoは不要
chown -R www-data:www-data storage

# 必要な権限を設定
chmod -R 775 storage


echo "ディレクトリが正常に作成され、必要な権限が設定されました。"
