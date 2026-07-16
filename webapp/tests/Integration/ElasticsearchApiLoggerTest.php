<?php

use PHPUnit\Framework\TestCase;

class ElasticsearchApiLoggerTest extends TestCase
{
    /**
     * When no logger (null) is passed, updateMasses() must not echo anything.
     * This is the scenario used by the /calendar/generate API endpoint.
     */
    public function testUpdateMassesWithNullLoggerProducesNoOutput(): void
    {
        ob_start();
        \ExternalApi\ElasticsearchApi::updateMasses([2025], [1], null);
        $output = ob_get_clean();

        $this->assertEmpty(
            $output,
            "updateMasses with null logger should produce no echo output, got: "
            . substr($output, 0, 200)
        );
    }

    /**
     * When a collector callback is passed, it should receive progress messages
     * without any echo output.
     */
    public function testUpdateMassesWithCallbackCollectsMessages(): void
    {
        $messages = [];
        ob_start();
        \ExternalApi\ElasticsearchApi::updateMasses([2025], [1],
            function ($msg) use (&$messages) { $messages[] = $msg; }
        );
        $output = ob_get_clean();

        $this->assertEmpty(
            $output,
            "Callback logger should not produce echo output, got: "
            . substr($output, 0, 200)
        );
        $this->assertNotEmpty(
            $messages,
            "Logger callback should have received progress messages"
        );
        $allMessages = implode("\n", $messages);
        $this->assertStringContainsString('Talált', $allMessages);
    }

    /**
     * When an echo callback is passed (CRON scenario), output should stream to stdout.
     */
    public function testUpdateMassesWithEchoLoggerProducesOutput(): void
    {
        ob_start();
        \ExternalApi\ElasticsearchApi::updateMasses([2025], [1],
            function ($msg) { echo $msg . "\n"; }
        );
        $output = ob_get_clean();

        $this->assertNotEmpty(
            $output,
            "Echo logger should produce output"
        );
        $this->assertStringContainsString('Talált', $output);
    }
}
