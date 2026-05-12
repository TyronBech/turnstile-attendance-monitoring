<?php

namespace App\Console\Commands;

use App\Models\Turnstile;
use Illuminate\Console\Command;

class GenerateTurnstileToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'turnstile:token {id? : The ID of the turnstile} {--name= : Filter by turnstile name} {--clear : Revoke all existing tokens before generating new one}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a Sanctum token for a turnstile device';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $id = $this->argument('id');
        $name = $this->option('name');

        $query = Turnstile::query();

        if ($id) {
            $query->where('id', $id);
        } elseif ($name) {
            $query->where('name', 'like', "%{$name}%");
        } else {
            $turnstiles = Turnstile::all(['id', 'name', 'location']);
            if ($turnstiles->isEmpty()) {
                $this->error('No turnstiles found in database.');

                return 1;
            }

            $choice = $this->choice(
                'Select a turnstile to generate a token for:',
                $turnstiles->map(fn ($t) => "[{$t->id}] {$t->name} ({$t->location})")->toArray()
            );

            preg_match('/\[(\d+)\]/', $choice, $matches);
            $query->where('id', $matches[1]);
        }

        $turnstile = $query->first();

        if (! $turnstile) {
            $this->error('Turnstile not found.');

            return 1;
        }

        if ($this->option('clear')) {
            $turnstile->tokens()->delete();
            $this->warn("Cleared existing tokens for {$turnstile->name}.");
        }

        $tokenName = "esp32-{$turnstile->id}-".now()->format('YmdHis');
        $token = $turnstile->createToken($tokenName, ['attendance:scan'])->plainTextToken;

        $this->info("Successfully generated token for [{$turnstile->id}] {$turnstile->name}:");
        $this->line('');
        $this->info($token);
        $this->line('');
        $this->comment('Add this to Postman as a Bearer Token.');

        return 0;
    }
}
