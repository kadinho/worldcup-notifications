<?php

namespace App\Commands;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use Illuminate\Support\Facades\DB;

class MatchAnnounce extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'match:announce';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Announce score change';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $client = new Client(['base_uri' => env('WORLDCUP_API')]);
        $res = $client->request('GET', 'matches');
        $data = json_decode($res->getBody(), true);

        foreach ($data as $key => $row) {
            $matchData = DB::table('matches')->where('fifa_id', $row['fifa_id'])->first();
            // Notify start and end
            if ($matchData->status == 'future' && $row['status'] == 'in progress') {
                $message = "MATCH STARTED | {$row['home_team']['country']} - {$row['away_team']['country']}";
                $this->postToSlack($message);
                $this->updateMatch($matchData->id,$row);
            } elseif($matchData->status == 'in progress' && $row['status'] == 'completed'){
                $message = "MATCH ENDED | {$row['home_team']['country']} {$row['home_team']['goals']} - {$row['away_team']['goals']} {$row['away_team']['country']}";
                $this->postToSlack($message);
                $this->updateMatch($matchData->id,$row);
            }
            // If the match not live continue
            if ($row['status'] != 'in progress') {
                continue;
            }
            $matchHomeTeamData = json_decode($matchData->home_team, true);
            $matchAwayTeamData = json_decode($matchData->away_team, true);
            // Compare goals if are diferent send notification
            if (
                (int)$row['home_team']['goals'] !== (int)$matchHomeTeamData['goals'] ||
                (int)$row['away_team']['goals'] !== (int)$matchAwayTeamData['goals']
            ) {
                $message = "{$row['time']} | {$row['home_team']['country']} {$row['home_team']['goals']} - {$row['away_team']['goals']} {$row['away_team']['country']}";
                $this->postToSlack($message);
                $this->info("notification sent - {$message}");
            }
            $this->updateMatch($matchData->id,$row);
        }
    }

    protected function updateMatch($matchId,array $data){
        // Update the match in db
        $updateData = $data;
        unset($updateData['home_team_events']);
        unset($updateData['away_team_events']);
        $updateData['home_team'] = json_encode($data['home_team']);
        $updateData['away_team'] = json_encode($data['away_team']);
        $updateData['updated_at'] = Carbon::now();

        $result = DB::table('matches')->where('id', $matchId)->update($updateData);

        return $result;
    }
    protected function postToSlack($message)
    {
        $client = new Client();
        $webhook = env('SLACK_WEBOOK_URL');
        if (empty($webhook)) {
            return false;
        }
        // Prepare data
        $data = [
            'channel' => env('SLACK_CHANNEL'),
            'icon_emoji' => ':soccer:',
            'text' => $message
        ];
        // Send request
        $client->post($webhook, [
            'headers' => [
                'content-type' => 'application/json'
            ],
            'body' => json_encode($data)
        ]);

        return true;
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     */
    public function schedule(Schedule $schedule): void
    {
        $schedule->command(static::class)->everyMinute()->withoutOverlapping();
    }
}