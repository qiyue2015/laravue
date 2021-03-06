<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChaptersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chapters', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('novel_id');
            $table->integer('volume_id')->default(0)->comment('分卷ID');
            $table->string('chapter_name', 100)->default('');
            $table->tinyInteger('chapter_type')->default(0);
            $table->smallInteger('chapter_order')->default(0);
            $table->integer('source_chapter_id')->default(0)->comment('源站章节ID');
            $table->tinyInteger('has_content')->default(0)->comment('是否有内容');
            $table->tinyInteger('display')->default(0)->comment('是否显示');
            $table->integer('created_at')->default(0);
            $table->integer('updated_at')->default(0);
            // $table->timestamps();
            $table->index('chapter_type', 'chapter_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chapters');
    }
}
