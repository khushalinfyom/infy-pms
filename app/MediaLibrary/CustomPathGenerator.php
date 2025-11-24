<?php

namespace App\MediaLibrary;

use App\Models\Client;
use App\Models\Expense;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

/**
 * Class CustomPathGenerator
 */
class CustomPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        $path = '{PARENT_DIR}' . DIRECTORY_SEPARATOR . $media->id . DIRECTORY_SEPARATOR;

        switch ($media->collection_name) {
            case Setting::APP_LOGO:
                return str_replace('{PARENT_DIR}', Setting::APP_LOGO, $path);
            case Setting::APP_FAVICON:
                return str_replace('{PARENT_DIR}', Setting::APP_FAVICON, $path);
            case Task::PATH:
                return str_replace('{PARENT_DIR}', Task::PATH, $path);
            case User::IMAGE_PATH:
                return str_replace('{PARENT_DIR}', User::IMAGE_PATH, $path);
            case Client::IMAGE_PATH:
                return str_replace('{PARENT_DIR}', Client::IMAGE_PATH, $path);
            case Expense::ATTACHMENT_PATH:
                return str_replace('{PARENT_DIR}', Expense::ATTACHMENT_PATH, $path);
            case Project::PATH:
                return str_replace('{PARENT_DIR}', Project::PATH, $path);
            case TaskAttachment::PATH:
                return str_replace('{PARENT_DIR}', TaskAttachment::PATH, $path);
            case 'default':
                return '';
        }
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media) . 'thumbnails/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media) . 'rs-images/';
    }
}
