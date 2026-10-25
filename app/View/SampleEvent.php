<?php

namespace App\View;

use App\Landing\ConfigCompiler;
use App\Landing\RegistrationForm;
use App\Landing\Theme;
use Symfony\Component\Yaml\Yaml;

/**
 * Sample content for template previews (wizard picker, README screenshots):
 * the demo marathon, re-skinned with the requested template and colours.
 */
final class SampleEvent
{
    public const YAML = 'events/demo/example-marathon.yaml';

    /**
     * @param  array{primary?: string, secondary?: string, name?: string, logo?: string|null, hero?: string|null}  $overrides
     */
    public static function landing(string $template, array $overrides = []): LandingPage
    {
        $data = Yaml::parseFile(base_path(self::YAML));
        $data['template'] = $template;
        $data['date'] = now()->addMonths(2)->setTime(5, 30)->format('Y-m-d H:i');

        foreach (['primary', 'secondary'] as $key) {
            if (isset($overrides[$key]) && preg_match('/^#[0-9a-fA-F]{6}$/', $overrides[$key])) {
                $data['branding'][$key] = strtolower($overrides[$key]);
            }
        }
        if (filled($overrides['name'] ?? null)) {
            $data['name'] = $data['hero']['title'] = mb_substr($overrides['name'], 0, 160);
        }
        // Demo YAML points at files that only exist on demo installs.
        $data['branding']['logo'] = $overrides['logo'] ?? null;
        $data['hero']['image'] = $overrides['hero'] ?? null;

        $config = app(ConfigCompiler::class)->compile(array_filter($data, fn ($v) => $v !== null));

        return new LandingPage(
            config: $config,
            orgName: 'Demo Runners',
            orgSlug: 'demo-runners',
            form: new RegistrationForm($config),
            theme: Theme::for($config->branding),
            remaining: ['5K' => 412, '10K' => 88, 'HM' => 9, 'FM' => 0],
            open: true,
            preview: true,
        );
    }
}
