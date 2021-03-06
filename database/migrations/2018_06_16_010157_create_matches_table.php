<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->increments('id');
            $table->string('venue');
            $table->string('location');
            $table->string('datetime');
            $table->string('status');
            $table->string('time')->nullable();
            $table->string('fifa_id');
            $table->string('stage_name');
            $table->json('weather')->nullable();
            $table->string('attendance')->nullable();
            $table->json('officials')->nullable();
            $table->json('home_team');
            $table->json('away_team');
            $table->json('home_team_events')->nullable();
            $table->json('away_team_events')->nullable();
            $table->json('home_team_statistics')->nullable();
            $table->json('away_team_statistics')->nullable();
            $table->string('home_team_country')->nullable();
            $table->string('away_team_country')->nullable();
            $table->string('winner')->nullable();
            $table->string('winner_code')->nullable();
            $table->string('last_event_update_at')->nullable();
            $table->string('last_score_update_at')->nullable();
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
        Schema::dropIfExists('matches');
    }
}
