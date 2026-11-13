<?php
declare(strict_types=1);

namespace App\Services;

final class AvatarGenerator
{
    private const HAIR = [
        ['base' => '#5a4b8a', 'light' => '#8a7bc0'],
        ['base' => '#c95b9c', 'light' => '#e58db8'],
        ['base' => '#3d6ea8', 'light' => '#6f9ed8'],
        ['base' => '#2d8a7a', 'light' => '#5bbaa6'],
        ['base' => '#8a4b3a', 'light' => '#b57a6a'],
        ['base' => '#2a2a30', 'light' => '#5a5a68'],
        ['base' => '#d4a03a', 'light' => '#ebc46b'],
        ['base' => '#b03e2e', 'light' => '#d66651'],
        ['base' => '#5a7a9a', 'light' => '#8aacc8'],
        ['base' => '#d87030', 'light' => '#f0a06a'],
    ];

    private const EYES = ['#5a7fb8', '#5a8a65', '#8b5a3a', '#8a5aa0', '#b8782a', '#c94b5c', '#4a9a9a'];

    private const SKIN = [
        ['face' => '#ffe4cc', 'shadow' => '#e8c1a0'],
        ['face' => '#f6d1a8', 'shadow' => '#d4a884'],
        ['face' => '#d0966a', 'shadow' => '#a66f45'],
        ['face' => '#a06840', 'shadow' => '#7a4d2e'],
    ];

    private const BG = ['#eaf1fb', '#fbe8ed', '#e8f5ea', '#f5ebe0', '#ede8f5', '#fdf2d8', '#ddeef5', '#f8e0e0'];

    private const COLLAR = ['#3a4a6a', '#6a3a3a', '#3a6a4a', '#5a3a6a', '#6a5a3a', '#2a2a3a', '#4a4a4a'];

    public function render(string $username): string
    {
        $hash = sha1($username, true);
        $b    = static fn (int $i) => ord($hash[$i]);

        $hair   = self::HAIR[$b(0) % count(self::HAIR)];
        $eye    = self::EYES[$b(1) % count(self::EYES)];
        $skin   = self::SKIN[$b(2) % count(self::SKIN)];
        $bg     = self::BG[$b(3) % count(self::BG)];
        $collar = self::COLLAR[$b(4) % count(self::COLLAR)];
        $style  = $b(5) % 4;
        $mood   = $b(6) % 3;

        $hairSvg  = $this->hair($style, $hair['base'], $hair['light']);
        $mouthSvg = $this->mouth($mood);
        $label    = htmlspecialchars('Avatar for ' . $username, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" role="img" aria-label="{$label}">
              <defs><clipPath id="c"><circle cx="32" cy="32" r="32"/></clipPath></defs>
              <g clip-path="url(#c)">
                <rect width="64" height="64" fill="{$bg}"/>
                <!-- Shoulders -->
                <path d="M6 64 Q20 52 32 52 Q44 52 58 64 Z" fill="{$collar}"/>
                <path d="M28 52 L28 58 L32 60 L36 58 L36 52 Z" fill="{$skin['face']}"/>
                {$hairSvg['back']}
                <!-- Face -->
                <ellipse cx="32" cy="34" rx="13" ry="15" fill="{$skin['face']}"/>
                <ellipse cx="19" cy="34" rx="2" ry="3" fill="{$skin['face']}"/>
                <ellipse cx="45" cy="34" rx="2" ry="3" fill="{$skin['face']}"/>
                {$hairSvg['front']}
                <!-- Eyes -->
                <ellipse cx="26" cy="36" rx="2.6" ry="4" fill="#2a3b58"/>
                <ellipse cx="38" cy="36" rx="2.6" ry="4" fill="#2a3b58"/>
                <ellipse cx="26" cy="36" rx="1.5" ry="2.8" fill="{$eye}"/>
                <ellipse cx="38" cy="36" rx="1.5" ry="2.8" fill="{$eye}"/>
                <circle cx="26.8" cy="34.5" r="0.9" fill="#ffffff"/>
                <circle cx="38.8" cy="34.5" r="0.9" fill="#ffffff"/>
                <circle cx="25.4" cy="37.4" r="0.4" fill="#ffffff" opacity="0.8"/>
                <circle cx="37.4" cy="37.4" r="0.4" fill="#ffffff" opacity="0.8"/>
                <!-- Eyebrows -->
                <path d="M23 31 Q26 30 29 31" stroke="#3a2d58" stroke-width="1.2" fill="none" stroke-linecap="round"/>
                <path d="M35 31 Q38 30 41 31" stroke="#3a2d58" stroke-width="1.2" fill="none" stroke-linecap="round"/>
                <!-- Blush -->
                <ellipse cx="23" cy="41" rx="2.2" ry="1.1" fill="#ffb6a6" opacity="0.6"/>
                <ellipse cx="41" cy="41" rx="2.2" ry="1.1" fill="#ffb6a6" opacity="0.6"/>
                <!-- Nose -->
                <path d="M32 40 L31.4 41.4" stroke="{$skin['shadow']}" stroke-width="0.7" fill="none" stroke-linecap="round"/>
                {$mouthSvg}
              </g>
            </svg>
            SVG;
    }

    private function hair(int $style, string $base, string $light): array
    {
        return match ($style) {

            0 => [
                'back' => '<path d="M10 32 Q10 10 32 10 Q54 10 54 32 L54 52 Q50 44 44 42 L44 30 Q44 20 32 20 Q20 20 20 30 L20 42 Q14 44 10 52 Z" fill="' . $base . '"/>',
                'front' => '<path d="M19 30 Q20 18 32 16 Q44 18 45 30 Q42 26 37 25 Q32 22 28 26 Q23 29 19 30 Z" fill="' . $base . '"/>'
                    . '<path d="M19 30 Q17 36 19 42 Q21 36 21 30 Z" fill="' . $base . '"/>'
                    . '<path d="M45 30 Q47 36 45 42 Q43 36 43 30 Z" fill="' . $base . '"/>'
                    . '<path d="M24 22 Q30 18 36 20 Q31 22 26 26 Z" fill="' . $light . '" opacity="0.7"/>',
            ],

            1 => [
                'back' => '<path d="M8 32 Q8 10 32 10 Q56 10 56 32 L56 62 Q52 50 44 46 L44 30 Q44 20 32 20 Q20 20 20 30 L20 46 Q12 50 8 62 Z" fill="' . $base . '"/>',
                'front' => '<path d="M17 28 Q17 20 32 18 Q47 20 47 28 L47 31 Q32 29 17 31 Z" fill="' . $base . '"/>'
                    . '<path d="M17 28 Q16 38 18 44 Q22 38 21 28 Z" fill="' . $base . '"/>'
                    . '<path d="M47 28 Q48 38 46 44 Q42 38 43 28 Z" fill="' . $base . '"/>'
                    . '<path d="M22 24 Q32 22 42 24 Q32 26 22 26 Z" fill="' . $light . '" opacity="0.6"/>',
            ],

            2 => [
                'back' => '<path d="M14 32 Q14 10 32 10 Q50 10 50 32 L50 44 Q46 36 32 36 Q18 36 14 44 Z" fill="' . $base . '"/>'
                    . '<path d="M14 36 Q4 46 8 62 Q16 54 20 44 Z" fill="' . $base . '"/>'
                    . '<path d="M50 36 Q60 46 56 62 Q48 54 44 44 Z" fill="' . $base . '"/>',
                'front' => '<path d="M18 28 Q20 18 30 20 Q31 26 30 30 Q24 30 18 28 Z" fill="' . $base . '"/>'
                    . '<path d="M34 30 Q33 26 34 20 Q44 18 46 28 Q40 30 34 30 Z" fill="' . $base . '"/>'
                    . '<path d="M26 22 Q30 19 30 25 Z" fill="' . $light . '" opacity="0.7"/>'
                    . '<path d="M34 25 Q34 19 38 22 Z" fill="' . $light . '" opacity="0.7"/>',
            ],

            3 => [
                'back' => '<path d="M14 32 Q14 10 32 10 Q50 10 50 32 L50 42 Q46 38 32 38 Q18 38 14 42 Z" fill="' . $base . '"/>',
                'front' => '<path d="M16 28 L18 18 L22 26 L24 16 L28 24 L32 14 L36 24 L40 18 L42 26 L46 16 L48 28 Q40 25 32 25 Q24 25 16 28 Z" fill="' . $base . '"/>'
                    . '<path d="M22 20 L24 14 L28 22 L30 16 L30 22 Z" fill="' . $light . '" opacity="0.7"/>',
            ],
            default => ['back' => '', 'front' => ''],
        };
    }

    private function mouth(int $mood): string
    {
        return match ($mood) {
            0 => '<path d="M30 45 Q32 47 34 45" stroke="#a9654a" stroke-width="1.1" fill="none" stroke-linecap="round"/>',
            1 => '<ellipse cx="32" cy="45.3" rx="1.3" ry="1" fill="#8b4a3a"/>',
            2 => '<path d="M30.5 45.6 L33.5 45.6" stroke="#a9654a" stroke-width="1.1" fill="none" stroke-linecap="round"/>',
            default => '',
        };
    }
}
