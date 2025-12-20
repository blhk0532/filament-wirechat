<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Use UUIDs for Conversations
    |--------------------------------------------------------------------------
    |
    | Determines the primary key type for the conversations table and related
    | relationships. When enabled, UUIDs (version 7 if supported, otherwise
    | version 4) will be used during initial migrations.
    |
    | ⚠️ This setting is intended for **new applications only** and does not
    | affect how new conversations are created at runtime. It controls whether
    | migrations generate UUID-based keys or unsigned big integers.
    |
    */
    'uses_uuid_for_conversations' => false,

    /*
    |--------------------------------------------------------------------------
    | Table Prefix
    |--------------------------------------------------------------------------
    |
    | This value will be prefixed to all Wirechat-related database tables.
    | Useful if you're sharing a database with other apps or packages.
    | ⚠️ This setting is intended for **new applications only**
    |
    */
    'table_prefix' => 'wirechat_',

    /*
     |--------------------------------------------------------------------------
     | Storage
     |--------------------------------------------------------------------------
     |
     | Global configuration for Wirechat file storage. Defines the disk,
     | directory, and visibility used for saving attachments.
     |
     */
    'storage' => [
        'disk' => 'public',
        'visibility' => 'public',
        'directories' => [
            'attachments' => 'attachments',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Broadcasting
    |--------------------------------------------------------------------------
    |
    | Configuration for real-time broadcasting. Make sure your broadcasting
    | driver is configured in your .env file (BROADCAST_DRIVER).
    |
    */
    'broadcasting' => [
        'enabled' => true,
        'driver' => env('BROADCAST_DRIVER', 'pusher'),
    ],

    /*
     |--------------------------------------------------------------------------
     | Queue
     |--------------------------------------------------------------------------
     |
     | Configuration for queue processing. Make sure your queue connection
     | is configured in your .env file (QUEUE_CONNECTION).
     |
     */
    'queue' => [
        'connection' => env('QUEUE_CONNECTION', 'database'),
    ],

    /*
     |--------------------------------------------------------------------------
     | Broadcasting Queues
     |--------------------------------------------------------------------------
     |
     | Queue names for broadcasting messages and notifications.
     |
     */
    'broadcasting' => [
        'messages_queue' => env('WIRECHAT_MESSAGES_QUEUE', 'default'),
        'notifications_queue' => env('WIRECHAT_NOTIFICATIONS_QUEUE', 'default'),
    ],

    /*
     |--------------------------------------------------------------------------
     | Notifications
     |--------------------------------------------------------------------------
     |
     | Configuration for notifications.
     |
     */
    'notifications' => [
        'enabled' => env('WIRECHAT_NOTIFICATIONS_ENABLED', false),
    ],

    /*
     |--------------------------------------------------------------------------
     | Color Theme
     |--------------------------------------------------------------------------
     |
     | Theme colors for Wirechat. By default, these will use Filament's panel
     | colors. You can override them here if needed.
     |
     | Set to null to use Filament's theme colors automatically.
     |
     */
    'theme' => [
        'brand_primary' => env('WIRECHAT_THEME_BRAND_PRIMARY', null), // null = use Filament primary color
        
        // Light mode colors (null = use Filament defaults)
        'light_primary' => env('WIRECHAT_THEME_LIGHT_PRIMARY', null), // null = white
        'light_secondary' => env('WIRECHAT_THEME_LIGHT_SECONDARY', null), // null = gray-100
        'light_accent' => env('WIRECHAT_THEME_LIGHT_ACCENT', null), // null = gray-200
        'light_border' => env('WIRECHAT_THEME_LIGHT_BORDER', null), // null = gray-200
        
        // Dark mode colors (null = use Filament defaults)
        'dark_primary' => env('WIRECHAT_THEME_DARK_PRIMARY', null), // null = gray-950
        'dark_secondary' => env('WIRECHAT_THEME_DARK_SECONDARY', null), // null = gray-900
        'dark_accent' => env('WIRECHAT_THEME_DARK_ACCENT', null), // null = gray-800
        'dark_border' => env('WIRECHAT_THEME_DARK_BORDER', null), // null = gray-800
    ],

    /*
     |--------------------------------------------------------------------------
     | UI Features
     |--------------------------------------------------------------------------
     |
     | Enable/disable UI features.
     |
     */
    'show_new_group_modal_button' => env('WIRECHAT_SHOW_NEW_GROUP_BUTTON', false),
    'show_new_chat_modal_button' => env('WIRECHAT_SHOW_NEW_CHAT_BUTTON', false),
    'max_group_members' => env('WIRECHAT_MAX_GROUP_MEMBERS', 1000),

    /*
     |--------------------------------------------------------------------------
     | Attachments
     |--------------------------------------------------------------------------
     |
     | Configuration for file attachments.
     |
     */
    'attachments' => [
        'storage_disk' => env('WIRECHAT_ATTACHMENTS_DISK', 'public'),
        'disk_visibility' => env('WIRECHAT_ATTACHMENTS_VISIBILITY', 'public'),
        'storage_folder' => env('WIRECHAT_ATTACHMENTS_FOLDER', 'attachments'),
        'media_mimes' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
        'file_mimes' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv', 'zip', 'rar', '7z', 'ppt', 'pptx', 'odt', 'ods', 'rtf'],
        'max_uploads' => env('WIRECHAT_MAX_UPLOADS', 10),
        'file_max_upload_size' => env('WIRECHAT_FILE_MAX_UPLOAD_SIZE', 12288), // in KB
        'media_max_upload_size' => env('WIRECHAT_MEDIA_MAX_UPLOAD_SIZE', 12288), // in KB
    ],

    /*
     |--------------------------------------------------------------------------
     | Searchable Attributes
     |--------------------------------------------------------------------------
     |
     | Fields used to search for users when creating new conversations.
     |
     */
    'searchable_attributes' => ['name', 'email'],
];
