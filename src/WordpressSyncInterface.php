<?php

namespace EslamFaroug\LaravelWordpressSync;

interface WordpressSyncInterface
{
    /**
     * Get the mapping of WordPress fields to model fields.
     *
     * @return array
     */
    public function getWordpressFieldsMapping();

    /**
     * Determine if the model should sync with WordPress.
     *
     * @return bool
     */
    public function shouldSyncWithWordpress();

    /**
     * Get the name of the status field in the model.
     *
     * @return string
     */
    public function getStatusField();
}
