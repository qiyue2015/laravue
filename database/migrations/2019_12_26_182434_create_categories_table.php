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
            $table->integer('pid')->unsigned()->index()->default(0)->comment('上级分类ID');
            $table->string('title', 50)->default('')->comment('分类名称');
            $table->string('ftitle', 50)->default('')->comment('分类别名');
            $table->string('pinyin', 50)->default('')->comment('拼音');
            $table->integer('book_count')->default(0)->comment('小说数量');
            $table->smallInteger('sort')->default(0)->comment('排序（同级有效）');
            $table->tinyInteger('status')->default(0)->comment('状态');
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
