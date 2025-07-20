# MongoDB Setup and Usage Guide

## Overview
This guide explains how MongoDB has been integrated into your Laravel LMS backend application.

## What's Been Installed

### 1. PHP MongoDB Extension
- **Extension**: `mongodb` PHP extension (version 2.1.1)
- **Status**: ✅ Installed and working
- **Command to verify**: `php -m | grep mongodb`

### 2. Laravel MongoDB Package
- **Package**: `mongodb/laravel-mongodb` (version 5.4.1)
- **Status**: ✅ Installed and configured
- **Provides**: Eloquent ORM support for MongoDB

### 3. MongoDB Server
- **Version**: MongoDB Community 7.0.22
- **Status**: ✅ Running on localhost:27017
- **Service**: Managed by Homebrew

## Configuration

### Database Configuration
The MongoDB connection has been added to `config/database.php`:

```php
'mongodb' => [
    'driver' => 'mongodb',
    'host' => env('MONGODB_HOST', '127.0.0.1'),
    'port' => env('MONGODB_PORT', 27017),
    'database' => env('MONGODB_DATABASE', 'lms_backend'),
    'username' => env('MONGODB_USERNAME', ''),
    'password' => env('MONGODB_PASSWORD', ''),
    'options' => [
        'database' => env('MONGODB_AUTHENTICATION_DATABASE', 'admin'),
    ],
],
```

### Environment Variables
Add these to your `.env` file:

```env
# MongoDB Configuration
MONGODB_HOST=127.0.0.1
MONGODB_PORT=27017
MONGODB_DATABASE=lms_backend
MONGODB_USERNAME=
MONGODB_PASSWORD=
MONGODB_AUTHENTICATION_DATABASE=admin
```

## Example Usage

### 1. ActivityLog Model
A sample MongoDB model has been created at `app/Models/ActivityLog.php` that demonstrates:

- MongoDB connection configuration
- Soft deletes
- Relationships with other models
- Query scopes
- Static helper methods

### 2. ActivityLog Controller
A controller at `app/Http/Controllers/ActivityLogController.php` shows:

- CRUD operations with MongoDB
- Filtering and pagination
- Statistics and aggregations
- API responses

### 3. API Routes
Routes have been added to `routes/api.php`:

```php
Route::prefix('activity-logs')->group(function () {
    Route::get('/', [ActivityLogController::class, 'index']);
    Route::post('/', [ActivityLogController::class, 'store']);
    Route::get('/statistics', [ActivityLogController::class, 'statistics']);
    Route::get('/{activityLog}', [ActivityLogController::class, 'show']);
});
```

## Creating MongoDB Models

### Basic Model Structure
```php
<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\SoftDeletes;

class YourModel extends Model
{
    use SoftDeletes;

    protected $connection = 'mongodb';
    protected $collection = 'your_collection_name';

    protected $fillable = [
        'field1',
        'field2',
        // ... other fields
    ];

    protected $casts = [
        'array_field' => 'array',
        'date_field' => 'datetime',
    ];
}
```

### Key Differences from SQL Models
1. **Connection**: Always specify `protected $connection = 'mongodb';`
2. **Collection**: Use `protected $collection = 'collection_name';` instead of table
3. **ID Field**: MongoDB uses `_id` instead of `id`
4. **No Migrations**: MongoDB is schema-less, so no migration files needed

## Useful MongoDB Features

### 1. Aggregation Pipelines
```php
$results = YourModel::raw(function($collection) {
    return $collection->aggregate([
        ['$match' => ['status' => 'active']],
        ['$group' => ['_id' => '$category', 'count' => ['$sum' => 1]]],
        ['$sort' => ['count' => -1]]
    ]);
});
```

### 2. Geospatial Queries
```php
$nearby = Location::where('location', 'near', [
    '$geometry' => [
        'type' => 'Point',
        'coordinates' => [$longitude, $latitude]
    ],
    '$maxDistance' => 5000
])->get();
```

### 3. Text Search
```php
$results = Post::where('$text', ['$search' => 'search term'])->get();
```

## Management Commands

### Start MongoDB Service
```bash
brew services start mongodb/brew/mongodb-community@7.0
```

### Stop MongoDB Service
```bash
brew services stop mongodb/brew/mongodb-community@7.0
```

### Check MongoDB Status
```bash
brew services list | grep mongodb
```

### Connect to MongoDB Shell
```bash
mongosh
```

## Testing

### Test Connection
```bash
php artisan tinker --execute="
try {
    \$log = new App\Models\ActivityLog();
    \$log->user_id = 1;
    \$log->action = 'test';
    \$log->save();
    echo 'MongoDB working! ID: ' . \$log->_id;
} catch (Exception \$e) {
    echo 'Error: ' . \$e->getMessage();
}
"
```

### API Testing
Test the activity logs API:

```bash
# Get all logs
curl -X GET http://localhost:8000/api/v1/activity-logs

# Create a log
curl -X POST http://localhost:8000/api/v1/activity-logs \
  -H "Content-Type: application/json" \
  -d '{"user_id": 1, "action": "login", "description": "User logged in"}'

# Get statistics
curl -X GET http://localhost:8000/api/v1/activity-logs/statistics
```

## Best Practices

1. **Use MongoDB for**: Logs, analytics, real-time data, document storage
2. **Use SQL for**: User accounts, transactions, structured data
3. **Hybrid approach**: Use both databases where appropriate
4. **Indexing**: Create indexes for frequently queried fields
5. **Connection pooling**: Configure connection limits in production

## Troubleshooting

### Common Issues

1. **Connection refused**: MongoDB service not running
   ```bash
   brew services restart mongodb/brew/mongodb-community@7.0
   ```

2. **Extension not loaded**: PHP MongoDB extension not installed
   ```bash
   pecl install mongodb
   ```

3. **Authentication failed**: Check username/password in .env file

4. **Permission denied**: Check MongoDB data directory permissions

### Useful Commands

```bash
# Check MongoDB logs
tail -f /usr/local/var/log/mongodb/mongo.log

# Check if MongoDB is listening
lsof -i :27017

# Test MongoDB connection
mongosh --eval "db.runCommand('ping')"
```

## Next Steps

1. **Add authentication** to MongoDB if needed
2. **Create indexes** for better performance
3. **Set up monitoring** for MongoDB
4. **Configure backups** for production
5. **Add more MongoDB models** as needed for your LMS features

## Resources

- [Laravel MongoDB Package Documentation](https://github.com/mongodb/laravel-mongodb)
- [MongoDB PHP Driver Documentation](https://docs.mongodb.com/php-library/current/)
- [MongoDB Manual](https://docs.mongodb.com/manual/) 