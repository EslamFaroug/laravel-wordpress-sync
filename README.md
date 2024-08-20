
# Laravel WordPress Sync

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

Laravel WordPress Sync is a Laravel package designed to sync Laravel posts with WordPress using the REST API based on specific conditions and publication status. You can easily customize the fields that are sent to WordPress and define the sync conditions.

## Requirements

- Laravel 8.x or higher
- PHP 7.4 or higher
- GuzzleHTTP 7.0 or higher
- WordPress 4.7 or higher

## Installation

You can install the package via Composer:

```bash
composer require eslamfaroug/laravel-wordpress-sync
```

## Usage Instructions

### 1. Setting Up the Model

After installing the package, the first step is to set up your Laravel model. Let's assume you have a model named `Post`.

### 2. Implement `WordpressSyncInterface` and Use the `SyncsWithWordpress` Trait

Modify your `Post` model to implement `WordpressSyncInterface` and use the `SyncsWithWordpress` trait.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use EslamFaroug\LaravelWordpressSync\SyncsWithWordpress;
use EslamFaroug\LaravelWordpressSync\WordpressSyncInterface;

class Post extends Model implements WordpressSyncInterface
{
    use HasFactory, SyncsWithWordpress;

    protected $fillable = [
        'id', 'type', 'lang', 'title', 'content', 'excerpt', 'publish', 'user_id', 'views',
        'created_at', 'updated_at'
    ];
    
    /**
     * Define the fields to be sent to WordPress
     */
    public function getWordpressFieldsMapping()
    {
        return [
            'title'   => 'title',       // The 'title' field in WordPress maps to the 'title' field in the model
            'content' => 'content',     // The 'content' field in WordPress maps to the 'content' field in the model
            'excerpt' => 'excerpt',     // The 'excerpt' field in WordPress maps to the 'excerpt' field in the model
            'author'  => 'user_id',     // The 'author' field in WordPress maps to the 'user_id' field in the model
        ];
    }

    /**
     * Define the condition for syncing with WordPress
     */
    public function shouldSyncWithWordpress()
    {
        // Sync only if 'publish' is 'true' and the view count is greater than 100
        return $this->publish === 'true' && $this->views > 100;
    }

    /**
     * Define the name of the status field in the model
     */
    public function getStatusField()
    {
        return 'publish'; // The status field in the model is named 'publish'
    }
}
```

### 3. Defining the Fields to Send to WordPress

In `getWordpressFieldsMapping()`, you define the fields in the `Post` model that you want to send to WordPress and the corresponding fields in WordPress.

### 4. Defining the Sync Condition

In `shouldSyncWithWordpress()`, you define a specific condition that must be met for the post to be sent to WordPress. For example, syncing may occur only if the `publish` status is `true` and the `views` count is greater than 100.

### 5. Defining the Status Field

In `getStatusField()`, you define the name of the status field in the `Post` model. This field will be used to determine whether the post will be published (`publish`) or saved as a draft (`draft`) in WordPress.

### 6. Configuring the WordPress API Settings

To make the library more flexible, you can configure the WordPress domain and API path in the `.env` file of your Laravel project. Add the following entries to your `.env` file:

```env
WORDPRESS_URL=https://yourwordpresssite.com
WORDPRESS_PATH=/wp-json/wp/v2/
WORDPRESS_USERNAME=your-username
WORDPRESS_PASSWORD=your-application-password
```

- `WORDPRESS_URL`: The domain of your WordPress site.
- `WORDPRESS_PATH`: The path to the WordPress REST API.
- `WORDPRESS_USERNAME`: Your WordPress username for API authentication.
- `WORDPRESS_PASSWORD`: The application password or API key.

These environment variables will be used by the package to connect to your WordPress site.

### 7. Testing the Sync

After completing these steps, posts will automatically sync with WordPress when they are created, updated, or deleted, based on the conditions defined in the `Post` model.

## License

This package is distributed under the MIT License. See the [LICENSE](LICENSE) file for more information.
