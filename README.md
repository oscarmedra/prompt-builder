# PromptBuilder

**PromptBuilder** is a PHP package for **composing** structured AI prompts with a
fluent, query-builder-style API, and **executing** them against an LLM (Ollama
today). Composition, rendering and execution are three separate concerns, so you
can build a prompt once and render it as plain text, as chat messages, or as XML,
and send it through whichever driver you like.

- **Composition** is pure and framework-free — no I/O, no Laravel required.
- **Rendering** turns a prompt into text / chat messages / XML.
- **Execution** hands the prompt to a driver that talks to a model.

## Requirements

- PHP **8.1+**
- `guzzlehttp/guzzle` (installed automatically) for the standalone driver
- Laravel is **optional** — only needed for the facade and the Laravel-flavoured
  driver/history store

## Installation

```bash
composer require noah-medra/prompt-builder
```

> This is a library: `composer.lock` and `vendor/` are intentionally not shipped
> with the source. Your application resolves the dependency against its own lock.

## Quick start

```php
use NoahMedra\PromptBuilder\PromptBuilder;

$builder = PromptBuilder::make()
    ->persona('Tu es un professeur de mathématiques bienveillant et rigoureux.')
    ->context("L'élève est en terminale et prépare son bac.")
    ->must('Réponds en français')
    ->must('Donne toujours un exemple concret')
    ->mustNot('Ne donne jamais la réponse finale sans expliquer le raisonnement')
    ->example('Résous x + 2 = 5', 'On isole x : x = 5 - 2 = 3.')
    ->withParams(['sujet' => 'les suites numériques', 'ton' => 'encourageant'])
    ->instruction('Adopte un ton {ton} en abordant {sujet}.')
    ->ask('Explique ce qu\'est une suite arithmétique.');

// Preview the composed prompt WITHOUT any network call:
echo $builder->toPrompt();

// Execute it:
$output = $builder->driver(new \NoahMedra\PromptBuilder\Drivers\OllamaDriver())
    ->process()
    ->getOutput();

echo $output->get('message.content');
```

A runnable, network-free demo lives in [`examples/basic-usage.php`](examples/basic-usage.php).

## Composition API

Every method below only mutates the internal `PromptSpec`. None of them do I/O.

| Method | Purpose |
| --- | --- |
| `persona(string)` | Who the model should act as |
| `context(string)` | Background information |
| `instruction(string, ?Closure)` | A neutral instruction (optionally with nested sub-instructions) |
| `must(string, ?Closure)` | A positive constraint, rendered with an `[Obligatoire]` marker |
| `mustNot(string, ?Closure)` | A negative constraint, rendered with an `[Interdit]` marker |
| `example(string $input, string $output)` | A few-shot input/output pair |
| `expectResponseFormat(string $json)` | Ask for a specific JSON output shape (throws if the sample isn't valid JSON) |
| `withParams(array)` / `setParams(array)` | Values for `{placeholder}` interpolation |
| `ask(string)` | The actual question |
| `when(bool, Closure $ifTrue, ?Closure $ifFalse)` | Conditional composition |
| `getSpec()` | Escape hatch to the raw `PromptSpec` |

### Nested instructions

`instruction()`, `must()` and `mustNot()` accept a closure to add nested
sub-instructions. Each `->add()` appends a sibling at the same depth; pass a
closure to `add()` to go one level deeper.

```php
$builder->instruction('Structure ta réponse', function ($ist) {
    $ist->add('Commence par un rappel du cours')
        ->add('Donne un exemple chiffré')
        ->add('Termine par un exercice', callback: function ($sub) {
            $sub->add('Avec sa correction');
        });
});
```

### Parameter interpolation

Any text you pass supports `{key}` and `{nested.key}` placeholders, resolved from
`withParams()`. **Unknown placeholders are left untouched** (not silently emptied)
so typos are easy to spot.

```php
$builder->withParams(['ton' => 'formel', 'user' => ['name' => 'Sam']])
        ->instruction('Réponds sur un ton {ton} à {user.name}.');
// -> "Réponds sur un ton formel à Sam."
```

## Rendering (preview without I/O)

`toPrompt()` renders the composed prompt so you can iterate on quality for free.
It defaults to `TextRenderer`; pass any renderer to get a different shape.

```php
use NoahMedra\PromptBuilder\Rendering\ChatMessagesRenderer;
use NoahMedra\PromptBuilder\Rendering\XmlRenderer;

$builder->toPrompt();                          // Markdown-ish string (default)
$builder->toPrompt(new ChatMessagesRenderer()); // [['role' => 'system', ...], ['role' => 'user', ...]]
$builder->toPrompt(new XmlRenderer());          // <prompt><persona>…</persona>…</prompt>
```

| Renderer | Output | Use for |
| --- | --- | --- |
| `TextRenderer` | Single string with `# Role`, `# Context`, … sections | Completion-style APIs, previews |
| `ChatMessagesRenderer` | `{role, content}` message array | Chat APIs (Ollama `/api/chat`, OpenAI, Anthropic) |
| `XmlRenderer` | Well-formed XML with explicit tags | Models that follow XML-delimited structure better |

Write your own by implementing `Rendering\RendererInterface`.

## Language (i18n)

The section labels the renderer emits (`# Role`, `[Required]`, `Example n:`, …)
are localized. **English is the default**; pick another language per builder:

```php
PromptBuilder::make()
    ->context('...')
    ->must('...')
    ->language('fr')   // or ->locale('fr')
    ->ask('...')
    ->toPrompt();
// -> "# Rôle", "# Contexte", "[Obligatoire] …", "# Question"
```

Bundled locales: **`en` (default), `es`, `fr`, `de`, `zh`, `ar`**. An unknown
locale, or a key missing in a locale, falls back to English. Only the labels
are translated — your own persona/context/instruction text is emitted verbatim,
and `XmlRenderer` tag names stay in English on purpose (they're structural).

Translation is handled by a framework-free `Translation\TranslatorInterface`.
The default `Translation\ArrayTranslator` reads bundled PHP language files and
needs no framework. Point it at your own files or supply your own
implementation for full control:

```php
use NoahMedra\PromptBuilder\Rendering\TextRenderer;
use NoahMedra\PromptBuilder\Translation\ArrayTranslator;

$renderer = new TextRenderer(new ArrayTranslator('es', '/path/to/lang'));
```

### Using Laravel's translator

Inside Laravel, the service provider registers the bundled strings under the
`promptbuilder` translation namespace. Publish them to customize:

```bash
php artisan vendor:publish --tag=promptbuilder-lang
```

Use `Translation\Laravel\LaravelTranslator` to render through Laravel's
translator (honouring the app locale and any overrides you published):

```php
use NoahMedra\PromptBuilder\Rendering\TextRenderer;
use NoahMedra\PromptBuilder\Translation\Laravel\LaravelTranslator;

$renderer = new TextRenderer(new LaravelTranslator(app('translator')));
```

## Execution

```php
$builder->driver($driver)  // a PromptDriverInterface instance or class-string
        ->process();       // hands the PromptSpec to the driver
$output = $builder->getOutput(); // ?BuilderOutput (null before process())
```

`BuilderOutput` decodes JSON responses and lets you pluck values with dotted
paths, or grab the raw body:

```php
$output->get('message.content'); // dotted-path access into decoded JSON
$output->getRaw();               // the raw response string
```

If you call `process()` without setting a driver, the standalone
`Drivers\OllamaDriver` is used by default.

### Choosing an Ollama driver

Two interchangeable implementations ship with the package:

| Driver | Built on | Use when |
| --- | --- | --- |
| `Drivers\OllamaDriver` | Guzzle directly | You want a **framework-agnostic** driver that works in any PHP script, no booted Laravel app. This is the default. Accepts an injectable Guzzle client for testing. |
| `Drivers\Laravel\OllamaDriver` | Laravel `Http` facade | You're **already in a Laravel app** and want `Http::fake()` in your tests. Requires a booted application. |

Both accept `model`, `endpoint`, a `renderer`, and a `timeoutSeconds`:

```php
use NoahMedra\PromptBuilder\Drivers\OllamaDriver;
use NoahMedra\PromptBuilder\Rendering\ChatMessagesRenderer;

$driver = new OllamaDriver(
    model: 'llama3.1',
    endpoint: 'http://localhost:11434/api/chat',
    renderer: new ChatMessagesRenderer(),
    timeoutSeconds: 30,
);
```

### Writing a custom driver

Implement `Drivers\PromptDriverInterface`. A driver receives a `PromptSpec`
directly (never a pre-rendered string) and picks its own renderer:

```php
use NoahMedra\PromptBuilder\BuilderOutput;
use NoahMedra\PromptBuilder\Drivers\PromptDriverInterface;
use NoahMedra\PromptBuilder\PromptSpec;
use NoahMedra\PromptBuilder\Rendering\ChatMessagesRenderer;

class MyDriver implements PromptDriverInterface
{
    public function process(PromptSpec $spec): BuilderOutput
    {
        $messages = (new ChatMessagesRenderer())->render($spec);
        // ...send $messages to your API...
        return new BuilderOutput($responseBody);
    }
}
```

## Conversation history

Call `useHistory()` to enable multi-turn memory. Prior turns are loaded into the
prompt, and `process()` appends this turn's question and the model's reply back
into the store — so the next builder using the same store sees the full exchange.

```php
use NoahMedra\PromptBuilder\History\InMemoryHistoryStore;

$store = new InMemoryHistoryStore(); // default store; works anywhere

PromptBuilder::make()->useHistory($store)->driver($driver)->ask('Bonjour')->process();
PromptBuilder::make()->useHistory($store)->driver($driver)->ask('Et ensuite ?')->process();
// The second call is sent the first question + answer as chat history.
```

| Store | Persistence | Notes |
| --- | --- | --- |
| `History\InMemoryHistoryStore` | Process lifetime | Default, framework-free |
| `History\Laravel\CacheHistoryStore` | Across requests via Laravel cache | Pass a conversation id: `new CacheHistoryStore('conv-42')` |

Implement `History\HistoryStoreInterface` (`all()`, `push()`, `clear()`) for a
custom backend. You can also seed turns manually with
`setHistory([['role' => 'user', 'content' => '…']])`.

## Laravel integration

The package auto-registers `PromptBuilderServiceProvider` (via
`extra.laravel.providers`), which binds `'promptbuilder'` and enables the facade:

```php
use NoahMedra\PromptBuilder\Facades\PromptBuilder;

$prompt = PromptBuilder::make()
    ->context('...')
    ->ask('...')
    ->toPrompt();
```

Each `PromptBuilder::make()` returns a fresh, isolated builder.

## JSON output

```php
$builder->expectResponseFormat('{"resume": "…", "response": "…"}');
```

This adds an explicit "answer only with valid JSON matching this shape"
instruction to the rendered prompt, and validates that the sample you pass is
itself valid JSON (throwing otherwise).

## Testing

```bash
composer install
vendor/bin/phpunit
```

The suite mixes plain PHPUnit tests (framework-free composition, rendering, the
Guzzle driver via a Guzzle `MockHandler`) with Orchestra Testbench tests (the
facade, the Laravel driver via `Http::fake()`, the cache history store).

## License

Released under the MIT License — see [LICENCE.txt](LICENCE.txt).
