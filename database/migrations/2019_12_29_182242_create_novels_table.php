<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNovelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('novels', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('category_id')->unsigned()->default(0)->comment('所属分类');
            $table->char('title', 80)->default('')->comment('名称');
            $table->integer('author_id')->unsigned()->default(0)->comment('所属分类');
            $table->char('author', 30)->nullable()->default('')->comment('作者');
            $table->text('content')->nullable()->comment('说明');
            $table->string('tag', 100)->nullable()->default('')->comment('标签');
            $table->tinyInteger('serialize')->nullable()->default(0)->comment('连载');
            $table->smallInteger('chapter_count')->nullable()->default(0)->after('serialize');
            $table->integer('favorites')->unsigned()->default(0)->comment('收藏');
            $table->integer('total_score')->unsigned()->default(0)->comment('总得分');
            $table->integer('voter_count')->unsigned()->default(0)->comment('评分人数');
            $table->float('score', 3, 1)->unsigned()->default(0)->comment('总得分');
            $table->tinyInteger('status')->default(0)->comment('状态');
            $table->integer('site_id')->default(0)->comment('源站ID');
            $table->integer('source_id')->default(0)->comment('源书ID');
            $table->mediumText('chapters')->nullable()->comment('章节表');
            $table->tinyInteger('display')->default(0)->comment('是否显示');
            $table->integer('created_at');
            $table->integer('updated_at');

            $table->index('title');
            $table->index('author_id');
            $table->index('category_id');
            $table->index('updated_at');
            $table->index('author');
            $table->index('serialize');
            $table->unique(array('site_id', 'source_id'), 'source_idx');
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('novels');
    }
}
