<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeyToMemeTagsMemeId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('meme_tags', function (Blueprint $table) {
            $table->foreign('meme_id')
                  ->references('id')->on('meme')
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
        Schema::table('meme_tags', function (Blueprint $table) {
            $table->dropForeign(['meme_id']);
        });
    }
}
