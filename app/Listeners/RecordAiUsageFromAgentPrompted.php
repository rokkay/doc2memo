<?php

declare(strict_types=1);

namespace App\Listeners;

use Laravel\Ai\Events\AgentPrompted;

final class RecordAiUsageFromAgentPrompted
{
    /**
     * @var array<string,array{prompt_tokens:int,completion_tokens:int,cache_write_input_tokens:int,cache_read_input_tokens:int,reasoning_tokens:int}>
     */
    private static array $usageByAgent = [];

    public function handle(AgentPrompted $event): void
    {
        $agentClass = $event->prompt->agent::class;

        self::$usageByAgent[$agentClass] = [
            'prompt_tokens' => $event->response->usage->promptTokens,
            'completion_tokens' => $event->response->usage->completionTokens,
            'cache_write_input_tokens' => $event->response->usage->cacheWriteInputTokens,
            'cache_read_input_tokens' => $event->response->usage->cacheReadInputTokens,
            'reasoning_tokens' => $event->response->usage->reasoningTokens,
        ];
    }

    /**
     * @param  array{prompt_tokens:int,completion_tokens:int,cache_write_input_tokens?:int,cache_read_input_tokens?:int,reasoning_tokens?:int}  $usage
     */
    public static function recordUsageForAgent(string $agentClass, array $usage): void
    {
        self::$usageByAgent[$agentClass] = [
            'prompt_tokens' => max(0, (int) ($usage['prompt_tokens'] ?? 0)),
            'completion_tokens' => max(0, (int) ($usage['completion_tokens'] ?? 0)),
            'cache_write_input_tokens' => max(0, (int) ($usage['cache_write_input_tokens'] ?? 0)),
            'cache_read_input_tokens' => max(0, (int) ($usage['cache_read_input_tokens'] ?? 0)),
            'reasoning_tokens' => max(0, (int) ($usage['reasoning_tokens'] ?? 0)),
        ];
    }

    /**
     * @return array{prompt_tokens:int,completion_tokens:int,cache_write_input_tokens:int,cache_read_input_tokens:int,reasoning_tokens:int}|null
     */
    public static function pullUsageForAgent(string $agentClass): ?array
    {
        $usage = self::$usageByAgent[$agentClass] ?? null;

        if ($usage === null) {
            return null;
        }

        unset(self::$usageByAgent[$agentClass]);

        return $usage;
    }

    public static function flush(): void
    {
        self::$usageByAgent = [];
    }
}
