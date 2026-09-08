<?php

return [
    // "public" is convenient locally; Render must use Cloudinary because its disk is ephemeral.
    'driver' => env('MEDIA_DRIVER', 'public'),
];
