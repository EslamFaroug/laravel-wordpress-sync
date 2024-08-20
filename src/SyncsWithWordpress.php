<?php
namespace EslamFaroug\LaravelWordpressSync;

use GuzzleHttp\Client;
use Exception;

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

        if (!isset($this->{$statusField})) {
            throw new Exception("The status field '{$statusField}' must be defined in the model.");
        }

        $baseUrl = env('WORDPRESS_URL');
        $apiPath = env('WORDPRESS_PATH');
        $username = env('WORDPRESS_USERNAME');
        $password = env('WORDPRESS_PASSWORD');

        $client = new Client([
            'base_uri' => rtrim($baseUrl, '/') . '/' . ltrim($apiPath, '/'),
            'auth' => [$username, $password],
        ]);


        $fieldsMapping = $this->getWordpressFieldsMapping();

        $data = [];
        foreach ($fieldsMapping as $wpField => $modelField) {
            if (isset($this->{$modelField})) {
                $data[$wpField] = $this->{$modelField};
            } else {
                throw new Exception("The model field '{$modelField}' is not defined.");
            }
        }

        $data['status'] = $this->{$statusField} === 'true' ? 'publish' : 'draft';

        if ($action === 'create') {
            $response = $client->post('posts', [
                'json' => $data,
            ]);
        } elseif ($action === 'update') {
            $response = $client->put("posts/{$this->id}", [
                'json' => $data,
            ]);
        } elseif ($action === 'delete') {
            $client->delete("posts/{$this->id}");
        }
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
