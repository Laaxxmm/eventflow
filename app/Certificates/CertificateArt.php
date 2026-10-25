<?php

namespace App\Certificates;

/**
 * Print art for the certificate gallery, as data URIs DomPDF can draw:
 * GD-rendered paper and glow textures (JPEG) and metallic foil strips (PNG),
 * plus vector motifs as SVG. DomPDF has no CSS gradients and patchy SVG
 * opacity, so gradients are baked into bitmaps and SVG colours are pre-mixed
 * against their background instead of using alpha.
 */
final class CertificateArt
{
    /** Metallic ramps (dark, light, mid, light, dark) for foil strips. */
    public const FOILS = [
        'gold' => ['#8a651e', '#e8cb7d', '#b0872e', '#f3de96', '#8a651e'],
        'silver' => ['#6e7682', '#e2e6ec', '#969eaa', '#f0f2f6', '#6e7682'],
    ];

    /** @var array<string, string> */
    private static array $memo = [];

    /** Representative flat colour of a foil, for SVG strokes and hairlines. */
    public static function foilColour(string $foil): string
    {
        return $foil === 'silver' ? '#8d95a1' : '#b08a3a';
    }

    /**
     * Paper (or a dark field) with soft radial washes and a faint grain, as a JPEG.
     *
     * @param  array<int, array{0: float, 1: float, 2: float, 3: string, 4: float}>  $washes  [x, y, radius, colour, strength] in page fractions
     */
    public static function paper(string $base, array $washes = [], bool $portrait = false, int $grain = 3): string
    {
        $key = 'paper'.md5(serialize(func_get_args()));

        return self::$memo[$key] ??= (function () use ($base, $washes, $portrait, $grain) {
            [$w, $h] = $portrait ? [424, 600] : [600, 424];
            $im = imagecreatetruecolor($w, $h);
            [$br, $bg, $bb] = Palette::rgb($base);
            $ws = array_map(fn ($wash) => [$wash[0] * $w, $wash[1] * $h, max(1, $wash[2] * max($w, $h)), Palette::rgb($wash[3]), $wash[4]], $washes);
            mt_srand(7); // stable grain, so identical inputs give identical files

            for ($y = 0; $y < $h; $y++) {
                for ($x = 0; $x < $w; $x++) {
                    [$r, $g, $b] = [$br, $bg, $bb];
                    foreach ($ws as [$cx, $cy, $rad, $c, $strength]) {
                        $d = hypot($x - $cx, $y - $cy) / $rad;
                        if ($d < 1) {
                            $t = $strength * (1 - $d) ** 2; // soft falloff
                            $r += ($c[0] - $r) * $t;
                            $g += ($c[1] - $g) * $t;
                            $b += ($c[2] - $b) * $t;
                        }
                    }
                    $n = $grain ? mt_rand(-$grain, $grain) : 0;
                    imagesetpixel($im, $x, $y, imagecolorallocate($im, self::clamp($r + $n), self::clamp($g + $n), self::clamp($b + $n)));
                }
            }

            ob_start();
            imagejpeg($im, null, 86);

            return 'data:image/jpeg;base64,'.base64_encode((string) ob_get_clean());
        })();
    }

    /** A metallic foil strip (horizontal, or vertical for side rules), as a PNG. */
    public static function foil(string $foil = 'gold', bool $vertical = false): string
    {
        $key = "foil{$foil}".($vertical ? 'v' : 'h');

        return self::$memo[$key] ??= (function () use ($foil, $vertical) {
            $stops = array_map(fn (string $hex) => Palette::rgb($hex), self::FOILS[$foil] ?? self::FOILS['gold']);
            $len = 900;
            $im = $vertical ? imagecreatetruecolor(4, $len) : imagecreatetruecolor($len, 4);
            $segments = count($stops) - 1;

            for ($i = 0; $i < $len; $i++) {
                $p = $i / ($len - 1) * $segments;
                $s = min((int) floor($p), $segments - 1);
                $t = $p - $s;
                $t = $t * $t * (3 - 2 * $t); // smoothstep: soft highlights
                $c = array_map(fn ($k) => self::clamp($stops[$s][$k] + ($stops[$s + 1][$k] - $stops[$s][$k]) * $t), [0, 1, 2]);
                $colour = imagecolorallocate($im, ...$c);
                $vertical ? imageline($im, 0, $i, 3, $i, $colour) : imageline($im, $i, 0, $i, 3, $colour);
            }

            ob_start();
            imagepng($im);

            return 'data:image/png;base64,'.base64_encode((string) ob_get_clean());
        })();
    }

    public static function svg(string $body, int $w, int $h): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode("<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"{$w}\" height=\"{$h}\" viewBox=\"0 0 {$w} {$h}\">{$body}</svg>");
    }

    /** Corner ornament: double L-rule with a diamond and a bead (top-left; mirror with CSS position). */
    public static function corner(string $colour, bool $flipX = false, bool $flipY = false): string
    {
        $pts = fn (array $p) => implode(' ', array_map(fn ($xy) => sprintf('%.1f,%.1f', $flipX ? 120 - $xy[0] : $xy[0], $flipY ? 120 - $xy[1] : $xy[1]), $p));

        return self::svg(
            '<polyline points="'.$pts([[4, 116], [4, 4], [116, 4]]).'" fill="none" stroke="'.$colour.'" stroke-width="2.4"/>'.
            '<polyline points="'.$pts([[14, 80], [14, 14], [80, 14]]).'" fill="none" stroke="'.$colour.'" stroke-width="1"/>'.
            '<polygon points="'.$pts([[24, 14], [34, 24], [24, 34], [14, 24]]).'" fill="'.$colour.'"/>'.
            '<circle cx="'.($flipX ? 120 - 44 : 44).'" cy="'.($flipY ? 120 - 14 : 14).'" r="2.6" fill="'.$colour.'"/>'.
            '<circle cx="'.($flipX ? 120 - 14 : 14).'" cy="'.($flipY ? 120 - 44 : 44).'" r="2.6" fill="'.$colour.'"/>',
            120, 120,
        );
    }

    /** Laurel wreath: two leafy branches rising from the bottom, open at the top. */
    public static function laurel(string $colour): string
    {
        [$cx, $cy, $r] = [150, 125, 100];
        $leaves = '';
        $stem = [];
        foreach (range(0, 9) as $i) {
            $deg = 105 + $i * 14;
            $a = deg2rad($deg);
            $x = $cx + $r * cos($a);
            $y = $cy + $r * sin($a);
            $stem[] = [$x, $y];
            foreach ([-1, 1] as $side) {
                $lx = $x + $side * 10 * cos($a);
                $ly = $y + $side * 10 * sin($a);
                $tilt = $deg + $side * 25;
                $leaves .= sprintf('<ellipse cx="%.1f" cy="%.1f" rx="5.5" ry="14" transform="rotate(%.1f %.1f %.1f)" fill="%s"/>', $lx, $ly, $tilt, $lx, $ly, $colour);
                $leaves .= sprintf('<ellipse cx="%.1f" cy="%.1f" rx="5.5" ry="14" transform="rotate(%.1f %.1f %.1f)" fill="%s"/>', 300 - $lx, $ly, -$tilt, 300 - $lx, $ly, $colour);
            }
        }
        $line = fn (bool $mirror) => implode(' ', array_map(fn ($p) => sprintf('%.1f,%.1f', $mirror ? 300 - $p[0] : $p[0], $p[1]), $stem));

        return self::svg('<polyline points="'.$line(false).'" fill="none" stroke="'.$colour.'" stroke-width="2.5"/><polyline points="'.$line(true).'" fill="none" stroke="'.$colour.'" stroke-width="2.5"/>'.$leaves, 300, 250);
    }

    /** Guilloché rosette (overlaid hypotrochoids), the security-paper pattern. */
    public static function rosette(string $colour, int $size = 400, float $weight = 0.8, int $layers = 3): string
    {
        $c = $size / 2;
        $body = '';
        for ($l = 0; $l < $layers; $l++) {
            [$R, $r, $d] = [$c * 0.62, $c * (0.62 / (7 + $l * 2)), $c * (0.30 - $l * 0.05)];
            $pts = [];
            for ($i = 0; $i <= 1440; $i++) {
                $t = $i / 1440 * 2 * M_PI;
                $k = ($R - $r) / $r;
                $pts[] = sprintf('%.1f,%.1f', $c + ($R - $r) * cos($t) + $d * cos($k * $t), $c + ($R - $r) * sin($t) - $d * sin($k * $t));
            }
            $body .= '<polyline points="'.implode(' ', $pts).'" fill="none" stroke="'.$colour.'" stroke-width="'.$weight.'"/>';
        }

        return self::svg($body, $size, $size);
    }

    /**
     * Medallion seal: serrated foil rim, guilloché field, plain centre for a logo
     * or star, with two ribbon tails.
     */
    public static function medallion(string $rim, string $field, string $line, string $ribbon, bool $star = true): string
    {
        $teeth = [];
        foreach (range(0, 71) as $i) {
            foreach ([[$i * 5, 112], [$i * 5 + 2.5, 103]] as [$deg, $rad]) {
                $teeth[] = sprintf('%.1f,%.1f', 130 + $rad * cos(deg2rad($deg)), 120 + $rad * sin(deg2rad($deg)));
            }
        }
        $guilloche = '';
        for ($l = 0; $l < 2; $l++) {
            $pts = [];
            [$R, $r, $d] = [62.0, 62.0 / (9 + $l * 4), 22.0 - $l * 6];
            for ($i = 0; $i <= 1080; $i++) {
                $t = $i / 1080 * 2 * M_PI;
                $k = ($R - $r) / $r;
                $pts[] = sprintf('%.1f,%.1f', 130 + ($R - $r) * cos($t) + $d * cos($k * $t), 120 + ($R - $r) * sin($t) - $d * sin($k * $t));
            }
            $guilloche .= '<polyline points="'.implode(' ', $pts).'" fill="none" stroke="'.$line.'" stroke-width="0.7"/>';
        }
        $centre = $star
            ? '<polygon points="130,96 137,113 155,113 141,124 146,142 130,131 114,142 119,124 105,113 123,113" fill="'.$rim.'"/>'
            : '';

        return self::svg(
            '<polygon points="88,190 112,200 98,262 84,246 66,254" fill="'.$ribbon.'"/>'.
            '<polygon points="172,190 148,200 162,262 176,246 194,254" fill="'.$ribbon.'"/>'.
            '<polygon points="'.implode(' ', $teeth).'" fill="'.$rim.'"/>'.
            '<circle cx="130" cy="120" r="96" fill="'.$field.'"/>'.
            '<circle cx="130" cy="120" r="92" fill="none" stroke="'.$rim.'" stroke-width="1.6"/>'.
            $guilloche.
            '<circle cx="130" cy="120" r="34" fill="'.$field.'" stroke="'.$rim.'" stroke-width="2"/>'.
            $centre,
            260, 270,
        );
    }

    /** Round rubber-stamp ring: double circle with a band of dots (text is laid over in HTML). */
    public static function stamp(string $colour): string
    {
        $dots = '';
        foreach (range(0, 35) as $i) {
            $a = deg2rad($i * 10);
            $dots .= sprintf('<circle cx="%.1f" cy="%.1f" r="1.6" fill="%s"/>', 100 + 72 * cos($a), 100 + 72 * sin($a), $colour);
        }

        return self::svg(
            '<circle cx="100" cy="100" r="94" fill="none" stroke="'.$colour.'" stroke-width="5"/>'.
            '<circle cx="100" cy="100" r="84" fill="none" stroke="'.$colour.'" stroke-width="1.5"/>'.$dots.
            '<circle cx="100" cy="100" r="60" fill="none" stroke="'.$colour.'" stroke-width="1.5"/>',
            200, 200,
        );
    }

    /** Thin concentric rings, clipped to the box (modern-minimal decoration). */
    public static function rings(string $colour, int $w = 600, int $h = 600): string
    {
        $body = '';
        foreach ([80, 130, 180, 230, 280, 330] as $i => $rad) {
            $body .= self::arc($w * 0.78, $h * 0.42, $rad, $w, $h, $colour, $i === 2 ? 2.2 : 0.9);
        }

        return self::svg($body, $w, $h);
    }

    /** Fine line work for dark fields: sweeping arcs plus a hairline lattice corner. */
    public static function lineWork(string $colour, string $strong, int $w = 1188, int $h = 840): string
    {
        $body = '';
        foreach (range(0, 11) as $i) {
            $body .= self::arc(-$w * 0.04, $h * 1.06, 150 + $i * 21, $w, $h, $i % 4 === 0 ? $strong : $colour, $i % 4 === 0 ? 1.4 : 0.7);
        }
        foreach (range(0, 9) as $i) {
            $body .= self::arc($w * 1.05, -$h * 0.08, 110 + $i * 20, $w, $h, $i % 4 === 0 ? $strong : $colour, $i % 4 === 0 ? 1.2 : 0.7);
        }

        return self::svg($body, $w, $h);
    }

    /** Diagonal sport panel for stadium-strip: main colour block, accent stripe and foil edge. */
    public static function panel(string $main, string $stripe, string $edge, string $lines, int $w = 520, int $h = 840): string
    {
        $speed = '';
        foreach (range(0, 6) as $i) {
            $x = 230 + $i * 42;
            $speed .= sprintf('<line x1="%d" y1="0" x2="%d" y2="%d" stroke="%s" stroke-width="1.2"/>', $x + 160, $x - 60, $h, $lines);
        }

        return self::svg(
            '<polygon points="200,0 '.$w.',0 '.$w.','.$h.' 0,'.$h.'" fill="'.$main.'"/>'.
            $speed.
            '<polygon points="168,0 192,0 -8,'.$h.' -32,'.$h.'" fill="'.$stripe.'"/>'.
            '<polygon points="196,0 202,0 2,'.$h.' -4,'.$h.'" fill="'.$edge.'"/>',
            $w, $h,
        );
    }

    /** Guilloché wave band (interleaved sine lines), the security-print border strip. */
    public static function waves(string $colour, int $w = 1100, int $h = 24): string
    {
        $body = '';
        foreach (range(0, 5) as $i) {
            $pts = [];
            for ($x = 0; $x <= $w; $x += 3) {
                $pts[] = sprintf('%d,%.1f', $x, $h / 2 + ($h / 2 - 2) * sin($x / 14 + $i * M_PI / 3));
            }
            $body .= '<polyline points="'.implode(' ', $pts).'" fill="none" stroke="'.$colour.'" stroke-width="0.6"/>';
        }

        return self::svg($body, $w, $h);
    }

    /** Sampled arc of a circle, keeping only the parts inside the box (no SVG clipping needed). */
    private static function arc(float $cx, float $cy, float $r, int $w, int $h, string $colour, float $weight): string
    {
        $out = '';
        $run = [];
        for ($i = 0; $i <= 720; $i++) {
            $a = $i / 720 * 2 * M_PI;
            [$x, $y] = [$cx + $r * cos($a), $cy + $r * sin($a)];
            if ($x >= 0 && $x <= $w && $y >= 0 && $y <= $h) {
                $run[] = sprintf('%.1f,%.1f', $x, $y);
            } elseif ($run) {
                $out .= count($run) > 1 ? '<polyline points="'.implode(' ', $run).'" fill="none" stroke="'.$colour.'" stroke-width="'.$weight.'"/>' : '';
                $run = [];
            }
        }
        if (count($run) > 1) {
            $out .= '<polyline points="'.implode(' ', $run).'" fill="none" stroke="'.$colour.'" stroke-width="'.$weight.'"/>';
        }

        return $out;
    }

    private static function clamp(float $v): int
    {
        return (int) max(0, min(255, round($v)));
    }
}
