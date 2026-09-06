# AI Rotation Manager

Laravel package untuk rotasi API key multi-provider dan multi-account. Groq diprioritaskan, lalu Gemini dipakai sebagai fallback ketika semua key Groq sedang cooldown atau gagal dengan status yang dapat diputar.

## Requirements

- PHP 8.0+ (PHP 8.3+ untuk Laravel 13)
- Laravel 9 sampai Laravel 13

## Installation

```bash
composer require taufik464/ai-rotation-manager
php artisan vendor:publish --tag=ai-rotation-config
```

Package service provider dan facade didaftarkan otomatis melalui Composer.

## Configuration

Atur key sebagai daftar yang dipisahkan koma di `.env`:

```dotenv
GROQ_API_KEYS=groq-key-1,groq-key-2
GEMINI_API_KEYS=gemini-key-1,gemini-key-2
AI_ROTATION_COOLDOWN_SECONDS=60
```

File konfigurasi hasil publish berada di `config/ai-rotation.php`. Urutan default adalah `groq`, lalu `gemini`.

Cooldown disimpan melalui Laravel cache, sehingga Redis, file, atau array store dapat digunakan sesuai konfigurasi aplikasi. API key tidak disimpan sebagai nilai cache; hanya hash key yang digunakan pada nama cache.

## Usage

```php
use YourVendor\AIRotationManager\Facades\AIRotation;

$text = AIRotation::generate('Explain Laravel service providers in one paragraph.');
```

Opsi request dapat diteruskan sebagai argumen kedua:

```php
$text = AIRotation::generate('Write a short haiku.', [
    'temperature' => 0.4,
    'max_tokens' => 200,
]);
```

Status HTTP `429`, `401`, dan `5xx` akan memasukkan key ke cooldown lalu mencoba key berikutnya. Jika semua key dalam provider habis, manager melanjutkan ke provider berikutnya.

## Adding a provider

Implementasikan `YourVendor\AIRotationManager\Contracts\AIServiceInterface`, terima `api_key` dari options, lalu daftarkan class tersebut di `config/ai-rotation.php` pada bagian `providers` dan `priority`.

## Testing

```bash
composer install
composer test
```
