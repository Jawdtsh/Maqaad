
set -e

if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force --no-interaction
fi

php artisan migrate --force

STUDENT_COUNT=$(php artisan tinker --execute="echo DB::table('students')->count();" | tail -n 1)

if [ "$STUDENT_COUNT" = "0" ]; then
    php artisan db:seed --force
fi

exec php artisan serve --host=0.0.0.0 --port=8000