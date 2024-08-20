<?php
namespace EslamFaroug\LaravelWordpressSync;

use Exception;
use GuzzleHttp\Client;

trait SyncsWithWordpress
{
    protected static function bootSyncsWithWordpress()
    {
        static::creating(function (WordpressSyncInterface $model) {
            $model->syncWithWordpress('create');
        });

        static::updating(function (WordpressSyncInterface $model) {
            $model->syncWithWordpress('update');
        });

        static::deleting(function (WordpressSyncInterface $model) {
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

        $data = $this->prepareDataForSync($statusField);

        try {
            $this->performAction($client, $action, $data);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->handleClientException($e);
        }
    }

    protected function validateStatusField($statusField)
    {
        if (!isset($this->{$statusField})) {
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

    protected function prepareDataForSync($statusField)
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

        $data['status'] = $this->{$statusField} === 'true' ? 'publish' : 'draft';

        return $data;
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

    protected function updatePostInWordpress($client, $data)
    {
        if (!$this->wordpressPost) {
            throw new \Exception('Wordpress post ID is missing for update.');
        }

        $client->put("posts/{$this->wordpressPost->wp_post_id}", ['json' => $data]);
    }

    protected function deletePostFromWordpress($client)
    {
        if (!$this->wordpressPost) {
            throw new \Exception('Wordpress post ID is missing for deletion.');
        }

        $client->delete("posts/{$this->wordpressPost->wp_post_id}");
        $this->wordpressPost()->delete();
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

    public function wordpressPost()
    {
        return $this->morphOne(WordpressPost::class, 'postable');
    }

    /**
     * Get the mapping of WordPress fields to model fields.
     *
     * @return array
     */
    abstract public function getWordpressFieldsMapping();

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
}
