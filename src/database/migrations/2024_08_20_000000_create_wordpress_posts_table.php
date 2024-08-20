<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWordpressPostsTable extends Migration
{
    public function up()
    {
        Schema::create('wordpress_posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wp_post_id');
            $table->morphs('postable');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('wordpress_posts');
    }
}
