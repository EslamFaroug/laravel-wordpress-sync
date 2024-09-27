<?php

namespace EslamFaroug\LaravelWordpressSync;

use EslamFaroug\LaravelWordpressSync\Models\WordpressPost;
use Exception;

trait SyncsWithWordpress
{
    protected static function bootSyncsWithWordpress()
    {
        static::created(function (WordpressSyncInterface $model) {
            $model->syncWithWordpress('create');
        });

        static::updated(function (WordpressSyncInterface $model) {
            $model->syncWithWordpress('update');
        });

        static::deleted(function (WordpressSyncInterface $model) {
            $model->syncWithWordpress('delete');
        });
    }

    /**
     * Sync the model with WordPress based on the given action.
     *
     * @param string $action
     * @return void
     * @throws Exception
     */
    protected function syncWithWordpress($action)
    {
        if (!$this->shouldSyncWithWordpress()) {
            return;
        }

        $statusField = $this->getStatusField();

        $this->validateStatusField($statusField);

        $client = $this->createHttpClient();


        $data = $this->prepareDataForSync($statusField, $client);
        try {
            $this->performAction($client, $action, $data);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->handleClientException($e);
        }
    }

    /**
     * Determine if the model should sync with WordPress.
     *
     * @return bool
     */
    abstract public function shouldSyncWithWordpress();

    /**
     * Get the name of the status field in the model.
     *
     * @return string
     */
    abstract public function getStatusField();

    protected function validateStatusField($statusField)
    {
        if (!isset($statusField)) {
            throw new \Exception("The status field '{$statusField}' must be defined in the model.");
        }
    }

    protected function createHttpClient()
    {
        $baseUrl = rtrim(env('WORDPRESS_URL'), '/');
        $apiPath = ltrim(env('WORDPRESS_PATH'), '/');
        $username = env('WORDPRESS_USERNAME');
        $password = env('WORDPRESS_PASSWORD');

        return new \GuzzleHttp\Client([
            'base_uri' => "{$baseUrl}/{$apiPath}",
            'auth' => [$username, $password],
        ]);
    }

    protected function prepareDataForSync($statusField, $client)
    {
        $fieldsMapping = $this->getWordpressFieldsMapping();
        $data = [];

        foreach ($fieldsMapping as $wpField => $modelField) {
            if (isset($this->{$modelField})) {
                $data[$wpField] = $this->{$modelField};
            } else {
                throw new \Exception("The model field '{$modelField}' is not defined.");
            }
        }

        if (isset($data['featured_image'])) {
            $imageId = $this->uploadImageToWordpress($client, $data['featured_image']);
            if ($imageId) {
                $data['featured_media'] = $imageId;
            }
        }

        $data['status'] = $this->{$statusField} === 'true' ? 'publish' : 'draft';

        return $data;
    }

    /**
     * Get the mapping of WordPress fields to model fields.
     *
     * @return array
     */
    abstract public function getWordpressFieldsMapping();

    protected function uploadImageToWordpress($client, $imagePath)
    {
        try {
            if (file_exists($imagePath)) {
                $imageData = fopen($imagePath, 'r');
                $response = $client->post('media', [
                    'headers' => [
                        'Content-Disposition' => 'attachment; filename="' . basename($imagePath) . '"',
                    ],
                    'body' => $imageData,
                ]);

                $body = json_decode($response->getBody(), true);
                return $body['id'] ?? null;
            }
            return null;

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            echo $e->getMessage();
            if ($e->hasResponse()) {
                echo $e->getResponse()->getBody();
            }
            return null;
        }
    }

    protected function performAction($client, $action, $data)
    {
        switch ($action) {
            case 'create':
                $this->createPostInWordpress($client, $data);
                break;
            case 'update':
                $this->updatePostInWordpress($client, $data);
                break;
            case 'delete':
                $this->deletePostFromWordpress($client);
                break;
            default:
                throw new \InvalidArgumentException("Invalid action: {$action}");
        }
    }

    protected function createPostInWordpress($client, $data)
    {
        $response = $client->post('posts', ['json' => $data]);
        $body = json_decode($response->getBody(), true);

        $wordpressPost = new WordpressPost(['wp_post_id' => $body['id']]);

        $this->wordpressPost()->save($wordpressPost);
    }

    public function wordpressPost()
    {
        return $this->morphOne(WordpressPost::class, 'postable');
    }

    protected function updatePostInWordpress($client, $data)
    {
        if (!$this->wordpressPost) {
            return true;
        }
        $client->put("posts/{$this->wordpressPost->wp_post_id}", ['json' => $data]);
    }

    protected function deletePostFromWordpress($client)
    {
        if (!$this->wordpressPost) {
            return true;
        }
        $client->post("posts/{$this->wordpressPost->wp_post_id}", [
            'json' => ['status' => 'draft']
        ]);
    }

    protected function handleClientException(\GuzzleHttp\Exception\ClientException $e)
    {

        $responseBody = $e->getResponse()->getBody()->getContents();
        $errorData = json_decode($responseBody, true);

        if (isset($errorData['code']) && $errorData['message']) {
            dd($errorData);
            throw $errorData;
        } else {
            throw $e; // Re-throw the exception if it's not handled
        }
    }
}
