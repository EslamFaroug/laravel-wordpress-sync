<?php
namespace EslamFaroug\LaravelWordpressSync\Models;

use Illuminate\Database\Eloquent\Model;

class WordpressPost extends Model
{
    protected $fillable = ['wp_post_id'];

    /**
     * Get the owning postable model.
     */
    public function postable()
    {
        return $this->morphTo();
    }
}
