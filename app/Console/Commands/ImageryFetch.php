<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Intervention\Image\ImageManager;

/**
 * Fills public/images/race-ink/<set>/NN.jpg from free-licence stock (Unsplash or Pexels), per the
 * slot list in the Daylight Track brief §5: searches, downloads, resizes to 2400 px on the long side,
 * compresses under 400 KB and appends the credit line to CREDITS.md. Never hot-links.
 * Needs UNSPLASH_ACCESS_KEY or PEXELS_API_KEY in .env. Existing files are kept unless --force.
 */
class ImageryFetch extends Command
{
    protected $signature = 'imagery:fetch {--only= : One file, e.g. running/01} {--force : Replace files that already exist}';

    protected $description = 'Download licensed stock photos into the SportImagery slots and credit them';

    /** file => stock search terms (brief §5). */
    public const SLOTS = [
        'marketing/01' => 'marathon start line morning',
        'marketing/02' => 'cyclists hill climb sunset',
        'marketing/03' => 'inline skates close up rink',
        'marketing/04' => 'running track aerial',
        'running/01' => 'marathon start dawn',
        'running/02' => 'runner mid stride',
        'running/03' => 'running shoes race bib',
        'running/04' => 'stadium running track runners',
        'running/05' => 'marathon finish line arms up',
        'running/06' => 'runners park road group',
        'cycling/01' => 'night cycling lights',
        'cycling/02' => 'cyclist climbing out of saddle',
        'cycling/03' => 'road bike chainring close up',
        'cycling/04' => 'hairpin road aerial',
        'cycling/05' => 'cycling race finish',
        'cycling/06' => 'peloton road',
        'skating/01' => 'speed skating race rink',
        'skating/02' => 'inline speed skater',
        'skating/03' => 'inline skate wheels close up',
        'skating/04' => 'indoor skating rink empty',
        'skating/05' => 'medals podium close up',
        'skating/06' => 'group inline skaters race',
        'generic/01' => 'athletics stadium morning',
        'generic/02' => 'sprinter track side view',
        'generic/03' => 'starting blocks track',
        'generic/04' => 'heritage city road sunrise runners',
        'generic/05' => 'finishers medals celebration',
        'generic/06' => 'marathon crowd cheering',
    ];

    public function handle(): int
    {
        $unsplash = config('services.unsplash.key');
        $pexels = config('services.pexels.key');
        if (! $unsplash && ! $pexels) {
            $this->components->error('Set UNSPLASH_ACCESS_KEY or PEXELS_API_KEY in .env first.');

            return self::FAILURE;
        }

        $slots = $this->option('only') ? array_intersect_key(self::SLOTS, [$this->option('only') => true]) : self::SLOTS;
        $base = public_path('images/race-ink');
        $images = ImageManager::gd();

        foreach ($slots as $slot => $query) {
            $path = "{$base}/{$slot}.jpg";
            if (is_file($path) && ! $this->option('force')) {
                $this->components->twoColumnDetail($slot, 'kept');

                continue;
            }

            $hit = $unsplash ? $this->unsplash($unsplash, $query) : $this->pexels($pexels, $query);
            if (! $hit) {
                $this->components->twoColumnDetail($slot, '<fg=yellow>no result</>');

                continue;
            }

            $image = $images->read(Http::timeout(60)->get($hit['download'])->throw()->body())->scaleDown(2400, 2400);
            // Step quality down until the file is under 400 KB (brief §5).
            foreach ([80, 74, 68, 62] as $quality) {
                $jpeg = (string) $image->toJpeg($quality);
                if (strlen($jpeg) < 400 * 1024) {
                    break;
                }
            }

            @mkdir(dirname($path), 0755, true);
            file_put_contents($path, $jpeg);
            file_put_contents("{$base}/CREDITS.md", "| `{$slot}.jpg` | {$hit['url']} | {$hit['by']} | {$hit['licence']} |\n", FILE_APPEND);
            $this->components->twoColumnDetail($slot, round(strlen($jpeg) / 1024).' KB · '.$hit['by']);
        }

        return self::SUCCESS;
    }

    /** @return array{download: string, url: string, by: string, licence: string}|null */
    private function unsplash(string $key, string $query): ?array
    {
        $photo = Http::withHeaders(['Authorization' => "Client-ID {$key}"])
            ->get('https://api.unsplash.com/search/photos', ['query' => $query, 'orientation' => 'landscape', 'per_page' => 1, 'content_filter' => 'high'])
            ->throw()->json('results.0');
        if (! $photo) {
            return null;
        }
        // Unsplash API guidelines: count the download.
        Http::withHeaders(['Authorization' => "Client-ID {$key}"])->get($photo['links']['download_location']);

        return ['download' => $photo['urls']['raw'].'&w=2400&q=85&fm=jpg', 'url' => $photo['links']['html'], 'by' => $photo['user']['name'], 'licence' => 'Unsplash License'];
    }

    /** @return array{download: string, url: string, by: string, licence: string}|null */
    private function pexels(string $key, string $query): ?array
    {
        $photo = Http::withHeaders(['Authorization' => $key])
            ->get('https://api.pexels.com/v1/search', ['query' => $query, 'orientation' => 'landscape', 'per_page' => 1])
            ->throw()->json('photos.0');

        return $photo ? ['download' => $photo['src']['original'], 'url' => $photo['url'], 'by' => $photo['photographer'], 'licence' => 'Pexels License'] : null;
    }
}
