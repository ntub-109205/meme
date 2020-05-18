<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeyToMemeTagTagId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('meme_tag', function (Blueprint $table) {
            $table->foreign('tag_id')
                  ->references('id')->on('tags')
                  ->onDelete('restrict')
                  ->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('meme_tag', function (Blueprint $table) {
            $table->dropForeign(['tag_id']);
        });
    }
}
