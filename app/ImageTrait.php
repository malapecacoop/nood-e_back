<?php

namespace App;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use \Illuminate\Support\Facades\Log;

trait ImageTrait
{
    public static $ACCEPTED_IMAGE_MIME_TYPES = ['image/gif','image/jpeg','image/png','image/x-png','image/webp'];

    public static $MIME_TYPE_EXTENSION = [
        'image/gif' => 'gif',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/x-png' => 'png',
        'image/webp' => 'webp',
    ];

    public $disk = 'public';

    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->image_fields)) {
            $this->setImageAttribute($value, $key);
            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    public function setImageAttribute($value, $key = 'image')
    {
        $attribute_name = $key;
        $prefix = isset($this->image_prefixes[$key]) ? $this->image_prefixes[$key] : ($key . '-');

        // if the image was erased
        if ($value == null) {
            if (isset($this->attributes[$attribute_name])) {
                $this->deleteImage($this->attributes[$attribute_name]);
            }

            // set null in the database column
            $this->attributes[$attribute_name] = null;

            return;
        }

        //if a new image was loaded
        if (Str::startsWith($value, 'data:image')) {
            if (isset($this->attributes[$attribute_name])) {
                $this->deleteImage($this->attributes[$attribute_name]);
            }

            $imageName = $this->uploadImage($value, $prefix);
            if(!$imageName) {
                throw new \Exception("The file type is not supported.");
                return;
            }
            $this->attributes[$attribute_name] = $imageName;
            return;
        }
    }

    public function uploadImage($value, $prefix)
    {
        $destination_path = '';

        $mime_type = mime_content_type($value);
        $accepted_mime_types = self::$ACCEPTED_IMAGE_MIME_TYPES;
        if (!in_array($mime_type, $accepted_mime_types)) {
            Log::error('User uploaded a file of type '.$mime_type.' but accepted types are '.implode(',', $accepted_mime_types));
            return false;
        }

        if ($mime_type == 'image/gif') {
            $data = substr($value, strpos($value, ',') + 1);
            $processed_contents = base64_decode($data);
        } else {
            $processed_contents = $this->resampleImage($value);
        }

        $filename = $prefix . Str::random(32).'.'.last(explode('.', self::$MIME_TYPE_EXTENSION[$mime_type]));
        Storage::disk($this->disk)->put($destination_path . $filename, $processed_contents);

        return $destination_path . $filename;
    }

    public function resampleImage($file_contents)
    {
        $max_res = 2600;
        $quality = 80;
        $image = Image::read($file_contents);

        // only resize if width larger than max_res
        $w = $image->width();
        $h = $image->height();
        if ($w > $max_res || $h > $max_res) {
            // resize the longest side and keep aspect ratio for the other one.
            $max_res_w = ($w > $h) ? $max_res : null;
            $max_res_h = ($w > $h) ? null : $max_res;
            // resize the image to a width of $max_res and constrain aspect ratio (auto height)
            $image = $image->resize($max_res_w, $max_res_h, function ($constraint) {
                $constraint->aspectRatio();
            });
        }

        return $image->toJpeg()->toFilePointer(null, $quality);  // use format to reconvert the image.
    }

    public function deleteImage($image)
    {
        if (Storage::disk($this->disk)->exists($image)) {
            return Storage::disk($this->disk)->delete($image);
        }
    }
    
}
