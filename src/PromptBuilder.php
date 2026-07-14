<?php

namespace NoahMedra\PromptBuilder;

use Closure;
use Exception;
use NoahMedra\PromptBuilder\Drivers\OllamaDriver;
use NoahMedra\PromptBuilder\Drivers\PromptDriverInterface;
use NoahMedra\PromptBuilder\Examples\Example;
use NoahMedra\PromptBuilder\History\HistoryStoreInterface;
use NoahMedra\PromptBuilder\History\InMemoryHistoryStore;
use NoahMedra\PromptBuilder\Instructions\Instruction;
use NoahMedra\PromptBuilder\Rendering\RendererInterface;
use NoahMedra\PromptBuilder\Rendering\TextRenderer;

/**
 * Fluent API for COMPOSING a prompt. Every method here only touches
 * the internal PromptSpec — none of them do any I/O, and none of them
 * know anything about a specific LLM API.
 *
 * Execution (driver()/process()/getOutput()) is a thin, separate layer
 * bolted on top: it hands the finished PromptSpec to a driver and does
 * nothing else. You can call toPrompt() to inspect/debug the composed
 * prompt without ever touching a driver.
 */
class PromptBuilder
{
    private PromptSpec $spec;

    private ?PromptDriverInterface $driver = null;

    private ?BuilderOutput $output = null;

    private ?HistoryStoreInterface $historyStore = null;

    public function __construct()
    {
        $this->spec = new PromptSpec();
    }

    public static function make(): self
    {
        return new self();
    }

    // ---------------------------------------------------------------
    // Composition — pure, no I/O
    // ---------------------------------------------------------------

    public function persona(string $text): self
    {
        $this->spec->persona = $text;
        return $this;
    }

    public function context(string $context): self
    {
        $this->spec->context = $context;
        return $this;
    }

    /** A neutral instruction, same as v1's instruction(). */
    public function instruction(string $text, ?Closure $callback = null): self
    {
        $this->spec->instructions[] = $this->buildInstruction($text, Instruction::TYPE_GENERAL, $callback);
        return $this;
    }

    /** A positive constraint, rendered with a "[Obligatoire]" marker. */
    public function must(string $text, ?Closure $callback = null): self
    {
        $this->spec->instructions[] = $this->buildInstruction($text, Instruction::TYPE_MUST, $callback);
        return $this;
    }

    /** A negative constraint, rendered with an "[Interdit]" marker. */
    public function mustNot(string $text, ?Closure $callback = null): self
    {
        $this->spec->instructions[] = $this->buildInstruction($text, Instruction::TYPE_MUST_NOT, $callback);
        return $this;
    }

    private function buildInstruction(string $text, string $type, ?Closure $callback): Instruction
    {
        $instruction = new Instruction($text, $type);

        if ($callback instanceof Closure) {
            $callback($instruction);
        }

        return $instruction;
    }

    /** Add a few-shot example (input/expected output) to guide the model. */
    public function example(string $input, string $output): self
    {
        $this->spec->examples[] = new Example($input, $output);
        return $this;
    }

    public function expectResponseFormat(string $jsonFormat): self
    {
        json_decode($jsonFormat);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Format JSON invalide.');
        }

        $this->spec->outputFormat = $jsonFormat;
        return $this;
    }

    /** Values usable as {placeholder} inside any text passed above. */
    public function withParams(array $params): self
    {
        $this->spec->params = $params;
        return $this;
    }

    public function setParams(array $params): self
    {
        return $this->withParams($params);
    }

    /**
     * Turn on conversation memory backed by a store. Prior turns already
     * in the store are loaded into the prompt immediately, and process()
     * will append this turn's question + response back into the store so
     * the NEXT builder using the same store sees this exchange.
     *
     * Defaults to an in-memory store (framework-free, works anywhere). Pass
     * History\Laravel\CacheHistoryStore to persist across requests.
     */
    public function useHistory(?HistoryStoreInterface $store = null): self
    {
        $this->historyStore = $store ?? new InMemoryHistoryStore();
        $this->spec->history = [...$this->spec->history, ...$this->historyStore->all()];
        return $this;
    }

    /**
     * Manually seed prior turns. Always feeds the current prompt; if a
     * history store is active (via useHistory()), the turns are recorded
     * in it too.
     *
     * @param array<int, array{role: string, content: string}> $history
     */
    public function setHistory(array $history): self
    {
        $this->spec->history = [...$this->spec->history, ...$history];

        if ($this->historyStore !== null) {
            foreach ($history as $message) {
                $this->historyStore->push($message['role'] ?? 'user', $message['content'] ?? '');
            }
        }

        return $this;
    }

    public function ask(string $question): self
    {
        $this->spec->question = $question;
        return $this;
    }

    public function when(bool $condition, Closure $ifTrue, ?Closure $ifFalse = null): self
    {
        if ($condition) {
            $ifTrue($this);
        } elseif ($ifFalse) {
            $ifFalse($this);
        }

        return $this;
    }

    /** Escape hatch to the raw data object, e.g. for a custom Renderer. */
    public function getSpec(): PromptSpec
    {
        return $this->spec;
    }

    /**
     * Renders the composed prompt WITHOUT sending it anywhere. This is
     * the preview/debug tool: iterate on prompt quality for free.
     *
     * @return string|array<int, array{role: string, content: string}>
     */
    public function toPrompt(?RendererInterface $renderer = null): string|array
    {
        $renderer ??= new TextRenderer();
        return $renderer->render($this->spec);
    }

    public function __toString(): string
    {
        $rendered = $this->toPrompt();
        return is_string($rendered) ? $rendered : json_encode($rendered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    // ---------------------------------------------------------------
    // Execution — the only place that talks to a driver
    // ---------------------------------------------------------------

    /** @param class-string<PromptDriverInterface>|PromptDriverInterface $driver */
    public function driver(string|PromptDriverInterface $driver): self
    {
        if (is_string($driver)) {
            if (!class_exists($driver) || !is_subclass_of($driver, PromptDriverInterface::class)) {
                throw new Exception("Le driver spécifié n'existe pas ou ne respecte pas PromptDriverInterface : {$driver}");
            }
            $driver = new $driver();
        }

        $this->driver = $driver;
        return $this;
    }

    public function process(): self
    {
        $this->driver ??= new OllamaDriver();
        $this->output = $this->driver->process($this->spec);

        $this->recordExchange();

        return $this;
    }

    /**
     * When history is enabled, append this turn to the store: the question
     * as a user message, and the model reply as an assistant message. For
     * chat-shaped responses we store the extracted `message.content`; for
     * anything else we fall back to the raw response body so the store is
     * never silently empty.
     */
    private function recordExchange(): void
    {
        if ($this->historyStore === null || $this->output === null) {
            return;
        }

        if ($this->spec->question !== null && $this->spec->question !== '') {
            $this->historyStore->push('user', $this->spec->question);
        }

        $assistant = $this->output->get('message.content');
        $this->historyStore->push('assistant', is_string($assistant) ? $assistant : $this->output->getRaw());
    }

    public function getOutput(): ?BuilderOutput
    {
        return $this->output;
    }
}
