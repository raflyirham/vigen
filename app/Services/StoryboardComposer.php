<?php

namespace App\Services;

class StoryboardComposer
{
    /**
     * @param  array<string, mixed>  $input
     * @return array{prompt: string, script: string, storyboard: array<int, array<string, mixed>>}
     */
    public function compose(array $input): array
    {
        $duration = (int) $input['duration'];
        $template = $this->template($input['template_key'] ?? null);
        $keywords = $this->keywords($input['keywords'] ?? '');
        $sceneCount = $duration >= 60 ? 8 : 5;
        $secondsPerScene = (int) ceil($duration / $sceneCount);

        $prompt = $this->prompt($input, $template, $keywords, $duration);
        $storyboard = [];
        $scriptLines = [
            "Video type: {$input['video_type']}",
            "Topic: {$input['topic']}",
            "Audience: {$input['target_audience']}",
            "Tone: {$input['tone']}",
            '',
        ];

        for ($i = 1; $i <= $sceneCount; $i++) {
            $start = ($i - 1) * $secondsPerScene;
            $end = min($duration, $i * $secondsPerScene);
            $scene = $this->scene($i, $sceneCount, $start, $end, $input, $template, $keywords);
            $storyboard[] = $scene;
            $scriptLines[] = "Scene {$i} ({$scene['timing']}): {$scene['narration']}";
            $scriptLines[] = "On-screen text: {$scene['on_screen_text']}";
            $scriptLines[] = '';
        }

        return [
            'prompt' => $prompt,
            'script' => trim(implode("\n", $scriptLines)),
            'storyboard' => $storyboard,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function template(?string $key): array
    {
        $templates = config('video_templates');

        return $templates[$key] ?? [
            'structure' => 'Open with a strong hook, develop the idea clearly, and end with a memorable next step.',
            'visual_style' => 'modern editorial visuals, purposeful transitions, readable captions',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function keywords(?string $keywords): array
    {
        return collect(explode(',', (string) $keywords))
            ->map(fn (string $keyword) => trim($keyword))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, string>  $template
     * @param  array<int, string>  $keywords
     */
    private function prompt(array $input, array $template, array $keywords, int $duration): string
    {
        $keywordText = $keywords === [] ? 'No required keywords.' : 'Include these keywords: '.implode(', ', $keywords).'.';

        return implode("\n", [
            "Create a {$duration}-second {$input['video_type']} about {$input['topic']}.",
            "Target audience: {$input['target_audience']}.",
            "Tone: {$input['tone']}.",
            $keywordText,
            "Structure: {$template['structure']}",
            "Visual style: {$template['visual_style']}",
            'Return a concise video concept with cinematic, brand-safe visual direction.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, string>  $template
     * @param  array<int, string>  $keywords
     * @return array<string, mixed>
     */
    private function scene(int $number, int $sceneCount, int $start, int $end, array $input, array $template, array $keywords): array
    {
        $keyword = $keywords[($number - 1) % max(count($keywords), 1)] ?? $input['topic'];
        $roles = [
            1 => ['Hook', "Open on a striking visual that makes {$input['topic']} feel urgent and relevant."],
            $sceneCount => ['Close', "End with a confident call to action for {$input['target_audience']}."],
        ];
        [$purpose, $visual] = $roles[$number] ?? [
            'Build',
            "Show a focused moment around {$keyword}, using {$template['visual_style']}.",
        ];

        return [
            'scene' => $number,
            'purpose' => $purpose,
            'timing' => sprintf('%02ds-%02ds', $start, $end),
            'visual_direction' => $visual,
            'narration' => $this->narration($number, $sceneCount, $input, $keyword),
            'on_screen_text' => $this->caption($number, $sceneCount, $input, $keyword),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function narration(int $number, int $sceneCount, array $input, string $keyword): string
    {
        if ($number === 1) {
            return "What if {$input['target_audience']} could understand {$input['topic']} in under a minute?";
        }

        if ($number === $sceneCount) {
            return "Bring {$input['topic']} into your next decision and start with one clear action today.";
        }

        return "Here is how {$keyword} connects to the result {$input['target_audience']} cares about most.";
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function caption(int $number, int $sceneCount, array $input, string $keyword): string
    {
        if ($number === 1) {
            return strtoupper((string) $input['topic']);
        }

        if ($number === $sceneCount) {
            return 'Ready for the next step?';
        }

        return ucfirst($keyword).' made simple';
    }
}
