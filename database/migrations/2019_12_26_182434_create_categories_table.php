<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('caption', 50)->default('')->comment('分类名称');
            $table->string('shortname', 20)->default('')->comment('分类简称');
            $table->string('pinyin', 50)->default('')->comment('分类拼音');
            $table->integer('book_count')->default(0)->comment('小说数量');
            $table->enum('type', ['male', 'female', 'other'])->default('other')->comment('频道');
            $table->smallInteger('sort')->default(0)->comment('排序（同级有效）');
            $table->tinyInteger('status')->default(0)->comment('状态');
            $table->index('type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('categories');
    }
}
