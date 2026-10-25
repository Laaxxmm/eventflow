<?php

namespace App\View;

/**
 * Vector motifs for the certificate gallery, as SVG data URIs for DomPDF <img> tags
 * (DomPDF renders basic SVG shapes and paths; no gradients or filters).
 */
final class CertificateArt
{
    public const GOLD = '#c9a227';

    public const GOLD_DARK = '#8a6a12';

    public static function uri(string $svg): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /** Gold medal with a star, hanging from a V ribbon in two brand colours. */
    public static function medal(string $ribbonA, string $ribbonB): string
    {
        return self::uri(<<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="240" height="320" viewBox="0 0 240 320">
  <polygon points="60,0 110,0 140,150 100,170" fill="{$ribbonA}"/>
  <polygon points="130,0 180,0 140,170 100,150" fill="{$ribbonB}"/>
  <circle cx="120" cy="215" r="92" fill="#8a6a12"/>
  <circle cx="120" cy="215" r="84" fill="#c9a227"/>
  <circle cx="120" cy="215" r="66" fill="none" stroke="#f3d77a" stroke-width="4"/>
  <polygon points="120,160 135,200 178,200 143,224 156,266 120,241 84,266 97,224 62,200 105,200" fill="#fff6d6"/>
</svg>
SVG);
    }

    /** Laurel wreath: two leafy branches rising from the bottom, open at the top. */
    public static function laurel(string $colour): string
    {
        [$cx, $cy, $r] = [150, 125, 100];
        $leaves = '';
        $stem = [];
        foreach (range(0, 9) as $i) {
            $deg = 105 + $i * 14; // 105 deg = bottom, 231 deg = upper left (SVG y points down)
            $a = deg2rad($deg);
            $x = $cx + $r * cos($a);
            $y = $cy + $r * sin($a);
            $stem[] = sprintf('%.1f,%.1f', $x, $y);
            foreach ([-1, 1] as $side) { // a leaf either side of the stem
                $lx = $x + $side * 10 * cos($a);
                $ly = $y + $side * 10 * sin($a);
                $tilt = $deg + $side * 25;
                $leaves .= sprintf('<ellipse cx="%.1f" cy="%.1f" rx="6" ry="15" transform="rotate(%.1f %.1f %.1f)" fill="%s"/>', $lx, $ly, $tilt, $lx, $ly, $colour);
                $leaves .= sprintf('<ellipse cx="%.1f" cy="%.1f" rx="6" ry="15" transform="rotate(%.1f %.1f %.1f)" fill="%s"/>', 300 - $lx, $ly, -$tilt, 300 - $lx, $ly, $colour);
            }
        }
        $left = implode(' ', $stem);
        $right = implode(' ', array_map(function (string $pt) {
            [$x, $y] = explode(',', $pt);

            return sprintf('%.1f,%s', 300 - (float) $x, $y);
        }, $stem));

        return self::uri(<<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="300" height="250" viewBox="0 0 300 250">
  <polyline points="{$left}" fill="none" stroke="{$colour}" stroke-width="3"/>
  <polyline points="{$right}" fill="none" stroke="{$colour}" stroke-width="3"/>
  {$leaves}
</svg>
SVG);
    }

    /** Round seal with ribbon tails and a centre star. */
    public static function seal(string $colour, string $ribbon): string
    {
        $teeth = '';
        foreach (range(0, 35) as $i) {
            $a = deg2rad($i * 10);
            $teeth .= sprintf('%.1f,%.1f ', 100 + 70 * cos($a), 90 + 70 * sin($a));
            $a2 = deg2rad($i * 10 + 5);
            $teeth .= sprintf('%.1f,%.1f ', 100 + 62 * cos($a2), 90 + 62 * sin($a2));
        }

        return self::uri(<<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="200" height="230" viewBox="0 0 200 230">
  <polygon points="62,130 92,140 74,228 56,206 36,214" fill="{$ribbon}"/>
  <polygon points="138,130 108,140 126,228 144,206 164,214" fill="{$ribbon}"/>
  <polygon points="{$teeth}" fill="{$colour}"/>
  <circle cx="100" cy="90" r="50" fill="none" stroke="#ffffff" stroke-width="3"/>
  <polygon points="100,58 109,82 135,82 114,97 122,122 100,107 78,122 86,97 65,82 91,82" fill="#ffffff"/>
</svg>
SVG);
    }

    /** Five-point star badge on a disc. */
    public static function star(string $disc, string $star = '#ffffff'): string
    {
        return self::uri(<<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">
  <circle cx="100" cy="100" r="96" fill="{$disc}"/>
  <circle cx="100" cy="100" r="80" fill="none" stroke="{$star}" stroke-width="3" stroke-dasharray="6 6"/>
  <polygon points="100,38 116,82 162,82 125,110 139,154 100,128 61,154 75,110 38,82 84,82" fill="{$star}"/>
</svg>
SVG);
    }

    /** Diagonal speed stripes (right edge of the page). */
    public static function stripes(string $a, string $b, string $c = '#0b1020'): string
    {
        return self::uri(<<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="400" height="800" viewBox="0 0 400 800">
  <polygon points="180,0 260,0 120,800 40,800" fill="{$a}"/>
  <polygon points="290,0 340,0 200,800 150,800" fill="{$b}"/>
  <polygon points="365,0 400,0 400,40 260,800 230,800" fill="{$c}"/>
</svg>
SVG);
    }

    /** Chequered finish-line band. */
    public static function checkers(string $dark = '#0b1020', int $cols = 60, int $rows = 2): string
    {
        $cells = '';
        for ($r = 0; $r < $rows; $r++) {
            for ($c = 0; $c < $cols; $c++) {
                if (($r + $c) % 2 === 0) {
                    $cells .= sprintf('<rect x="%d" y="%d" width="10" height="10" fill="%s"/>', $c * 10, $r * 10, $dark);
                }
            }
        }
        $w = $cols * 10;
        $h = $rows * 10;

        return self::uri("<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"{$w}\" height=\"{$h}\" viewBox=\"0 0 {$w} {$h}\"><rect width=\"{$w}\" height=\"{$h}\" fill=\"#ffffff\"/>{$cells}</svg>");
    }

    /** Concentric rink arcs (skating track lines). */
    public static function rink(string $colour): string
    {
        $arcs = '';
        foreach ([60, 110, 160, 210, 260] as $i => $r) {
            $arcs .= sprintf('<rect x="%d" y="%d" width="%d" height="%d" rx="%d" fill="none" stroke="%s" stroke-width="%d"/>', 300 - $r * 1.4, 220 - $r * 0.8, $r * 2.8, $r * 1.6, $r * 0.8, $colour, $i === 2 ? 5 : 2);
        }

        return self::uri("<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"600\" height=\"440\" viewBox=\"0 0 600 440\">{$arcs}</svg>");
    }
}
