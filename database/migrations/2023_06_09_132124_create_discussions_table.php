<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('discussions', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);
            $table->string('slug', 100);
            $table->text('description')->nullable();
            $table->char('access_level', 10);
            $table->char('status', 12);
            $table->unsignedBigInteger('category_id');
            $table->timestamp('discussion_date')->nullable();
            $table->string('discussion_link');
            $table->unsignedTinyInteger('max_attendees');
            $table->boolean('is_private')->nullable();
            $table->boolean('registering_alert')->nullable();
            $table->boolean('comment_alert')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->unsignedBigInteger('checked_out')->nullable();
            $table->timestamp('checked_out_time')->nullable();
            $table->unsignedBigInteger('owned_by');
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
        Schema::dropIfExists('discussions');
    }
};
