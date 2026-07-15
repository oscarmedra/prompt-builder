# PromptBuilder

[![Tests](https://github.com/oscarmedra/prompt-builder/actions/workflows/tests.yml/badge.svg)](https://github.com/oscarmedra/prompt-builder/actions/workflows/tests.yml)
[![PHP](https://img.shields.io/badge/php-8.1%2B-777bb4.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENCE.txt)

**PromptBuilder** composes structured AI prompts with a fluent, query-builder-style
API and executes them against an LLM (Ollama today). Its defining idea is a clean
split between three concerns, so you can build a prompt once and render it however
your target model prefers, in whatever language you want, and send it through
whichever driver you like:

```
compose ─────────────▶ render ─────────────▶ execute
PromptBuilder          RendererInterface      PromptDriverInterface
→ PromptSpec (data)    → text / chat / XML    → talks to the model
(pure, no I/O)         (pure, localizable)    (the only layer doing I/O)
```

- 🧩 **Composable** — persona, context, instructions (`must`/`mustNot`), few-shot
  examples, nested sub-instructions, `{param}` interpolation, conditionals.
- 🖨️ **Multi-format rendering** — plain text, chat messages, or XML from the same spec.
- 🌍 **Localizable** — section labels in 6 languages (English default), pluggable.
- 🧠 **Conversation memory** — pluggable history stores (in-memory or Laravel cache).
- 🔌 **Driver-based execution** — framework-agnostic (Guzzle) or Laravel-native.
- 🧪 **Framework-free core** — compose and render with zero Laravel required; the
  Laravel bits (facade, cache history, translator bridge) are strictly opt-in.

## Contents

- [Requirements](#requirements) · [Installation](#installation) · [Quick start](#quick-start)
- [Architecture](#architecture)
- [Composition](#composition) · [Rendering](#rendering) · [Language (i18n)](#language-i18n)
- [Execution](#execution) · [Conversation history](#conversation-history)
- [Laravel integration](#laravel-integration) · [Testing](#testing)

## Requirements

- PHP **8.1+**
- `guzzlehttp/guzzle` `^7` (installed automatically) for the standalone driver
- Laravel is **optional**. When used inside an app, `illuminate/support`
  `^10 | ^11 | ^12` is supported — needed only for the facade, the Laravel driver,
  the cache history store and the translator bridge.

## Installation

```bash
composer require noah-medra/prompt-builder
```

> This is a library: `composer.lock` and `vendor/` are intentionally not shipped
> with the source. Your application resolves the dependency against its own lock.

## Quick start

```php
use NoahMedra\PromptBuilder\PromptBuilder;
use NoahMedra\PromptBuilder\Drivers\OllamaDriver;

$builder = PromptBuilder::make()
    ->persona('Tu es un professeur de mathématiques bienveillant et rigoureux.')
    ->context("L'élève est en terminale et prépare son bac.")
    ->must('Réponds en français')
    ->mustNot('Ne donne jamais la réponse finale sans expliquer le raisonnement')
    ->example('Résous x + 2 = 5', 'On isole x : x = 5 - 2 = 3.')
    ->withParams(['sujet' => 'les suites numériques', 'ton' => 'encourageant'])
    ->instruction('Adopte un ton {ton} en abordant {sujet}.')
    ->language('fr')                       // section labels in French (default: English)
    ->ask('Explique ce qu\'est une suite arithmétique.');

// 1. Preview the composed prompt WITHOUT any network call:
echo $builder->toPrompt();

// 2. Execute it against a model:
$output = $builder->driver(new OllamaDriver())->process()->getOutput();

echo $output->get('message.content');
```

`toPrompt()` above prints:

```text
# Rôle
Tu es un professeur de mathématiques bienveillant et rigoureux.

# Contexte
L'élève est en terminale et prépare son bac.

# Exemples

Exemple 1 :
Entrée : Résous x + 2 = 5
Sortie attendue : On isole x : x = 5 - 2 = 3.

# Instructions
- [Obligatoire] Réponds en français
- [Interdit] Ne donne jamais la réponse finale sans expliquer le raisonnement
- Adopte un ton encourageant en abordant les suites numériques.

# Question
Explique ce qu'est une suite arithmétique.
```

A runnable, network-free demo lives in [`examples/basic-usage.php`](examples/basic-usage.php).

## Architecture

Each layer has one job and never reaches into the next. That's what lets you unit
test composition in isolation, render the same prompt differently per model, and
swap execution backends.

| Layer | Classes | Responsibility |
| --- | --- | --- |
| **Compose** | `PromptBuilder` → `PromptSpec`, `Instructions\Instruction`, `Examples\Example` | Build the prompt as plain data. Pure, no I/O, no framework. |
| **Render** | `Rendering\RendererInterface` → `TextRenderer`, `ChatMessagesRenderer`, `XmlRenderer` | Turn a `PromptSpec` into a string or chat-message array. Pure and localizable. |
| **Execute** | `Drivers\PromptDriverInterface` → `Drivers\OllamaDriver`, `Drivers\Laravel\OllamaDriver` | Render the spec and send it to a model. The only layer that does I/O. |

`PromptSpec` is the hand-off: the builder fills it, a renderer reads it, a driver
sends it. You can grab it directly with `getSpec()` for full control.

## Composition

Every method below only mutates the internal `PromptSpec` — no I/O, no driver.

| Method | Purpose |
| --- | --- |
| `persona(string)` | Who the model should act as |
| `context(string)` | Background information |
| `instruction(string, ?Closure)` | A neutral instruction (optionally with nested sub-instructions) |
| `must(string, ?Closure)` | A positive constraint, rendered with a `[Required]` marker |
| `mustNot(string, ?Closure)` | A negative constraint, rendered with a `[Forbidden]` marker |
| `example(string $input, string $output)` | A few-shot input/output pair |
| `expectResponseFormat(string $json)` | Ask for a specific JSON output shape (throws if the sample isn't valid JSON) |
| `withParams(array)` / `setParams(array)` | Values for `{placeholder}` interpolation |
| `language(string)` / `locale(string)` | Language of the rendered labels (default English) |
| `ask(string)` | The actual question |
| `when(bool, Closure $ifTrue, ?Closure $ifFalse)` | Conditional composition |
| `getSpec()` | Escape hatch to the raw `PromptSpec` |

### Nested instructions

`instruction()`, `must()` and `mustNot()` accept a closure to add nested
sub-instructions. Each `->add()` appends a sibling at the same depth; pass a
closure to `add()` to go one level deeper.

```php
$builder->instruction('Structure your answer', function ($ist) {
    $ist->add('Start with a recap')
        ->add('Give a worked example')
        ->add('End with an exercise', callback: function ($sub) {
            $sub->add('Include its solution');
        });
});
```

### Parameter interpolation

Any text you pass supports `{key}` and `{nested.key}` placeholders, resolved from
`withParams()`. **Unknown placeholders are left untouched** (not silently emptied)
so typos are easy to spot.

```php
$builder->withParams(['tone' => 'formal', 'user' => ['name' => 'Sam']])
        ->instruction('Answer in a {tone} tone to {user.name}.');
// -> "Answer in a formal tone to Sam."
```

### Conditionals

```php
$builder->when($user->isPremium(),
    fn ($b) => $b->must('Include an in-depth analysis'),
    fn ($b) => $b->must('Keep it short'),
);
```

### Structured JSON output

```php
$builder->expectResponseFormat('{"summary": "…", "answer": "…"}');
```

Adds an explicit "answer only with valid JSON matching this shape" instruction to
the rendered prompt, and validates that the sample you pass is itself valid JSON
(throwing otherwise).

## Rendering

`toPrompt()` renders the composed prompt so you can iterate on quality for free.
It defaults to `TextRenderer`; pass any renderer to get a different shape.

```php
use NoahMedra\PromptBuilder\Rendering\ChatMessagesRenderer;
use NoahMedra\PromptBuilder\Rendering\XmlRenderer;

$builder->toPrompt();                           // string (default)
$builder->toPrompt(new ChatMessagesRenderer()); // [['role' => 'system', ...], ['role' => 'user', ...]]
$builder->toPrompt(new XmlRenderer());          // <prompt><persona>…</persona>…</prompt>
```

| Renderer | Output | Use for |
| --- | --- | --- |
| `TextRenderer` | Single string with `# Role`, `# Context`, … sections | Completion-style APIs, previews |
| `ChatMessagesRenderer` | `{role, content}` message array (system + history + question) | Chat APIs (Ollama `/api/chat`, OpenAI, Anthropic) |
| `XmlRenderer` | Well-formed, escaped XML with explicit tags | Models that follow XML-delimited structure better |

Write your own by implementing `Rendering\RendererInterface` — it receives a
`PromptSpec` and returns a string or a `{role, content}` array.

## Language (i18n)

The section labels the renderer emits (`# Role`, `[Required]`, `Example n:`, the
JSON-output instruction, …) are localized. **English is the default**; pick another
language per builder:

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
locale, or a key missing in a locale, falls back to English. Only the labels are
translated — your persona/context/instruction text is emitted verbatim, and
`XmlRenderer` tag names stay English on purpose (they're structural).

Translation goes through a framework-free `Translation\TranslatorInterface`. The
default `Translation\ArrayTranslator` reads bundled PHP language files with no
framework. Point it at your own directory, or implement the interface for full
control:

```php
use NoahMedra\PromptBuilder\Rendering\TextRenderer;
use NoahMedra\PromptBuilder\Translation\ArrayTranslator;

// Custom catalog directory, laid out as {locale}/labels.php
$renderer = new TextRenderer(new ArrayTranslator('es', '/path/to/lang'));
```

### Using Laravel's translator

Inside Laravel, the service provider registers the bundled strings under the
`promptbuilder` translation namespace. Publish them to customize:

```bash
php artisan vendor:publish --tag=promptbuilder-lang
```

Then render through Laravel's translator (which follows the app locale and any
overrides you published) with `Translation\Laravel\LaravelTranslator`:

```php
use NoahMedra\PromptBuilder\Rendering\TextRenderer;
use NoahMedra\PromptBuilder\Translation\Laravel\LaravelTranslator;

$renderer = new TextRenderer(new LaravelTranslator(app('translator')));
```

## Execution

```php
$builder->driver($driver)  // a PromptDriverInterface instance or class-string
        ->process();       // renders the PromptSpec and sends it
$output = $builder->getOutput(); // ?BuilderOutput (null before process())
```

If you call `process()` without setting a driver, the standalone
`Drivers\OllamaDriver` is used by default.

`BuilderOutput` decodes JSON responses and lets you pluck values with dotted
paths, or grab the raw body:

```php
$output->get('message.content'); // dotted-path access into decoded JSON
$output->getRaw();               // the raw response string
```

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
| `History\Laravel\CacheHistoryStore` | Across requests, via the Laravel cache | Pass a conversation id: `new CacheHistoryStore('conv-42')` |

Implement `History\HistoryStoreInterface` (`all()`, `push()`, `clear()`) for a
custom backend. You can also seed turns manually with
`setHistory([['role' => 'user', 'content' => '…']])`.

## Laravel integration

Everything above works without Laravel. When you *are* in a Laravel app, these
opt-in conveniences light up automatically via package auto-discovery
(`PromptBuilderServiceProvider`):

- **Facade** — `'promptbuilder'` is bound in the container; each
  `PromptBuilder::make()` returns a fresh, isolated builder.
- **Translations** — bundled strings are registered under the `promptbuilder`
  namespace and publishable (`--tag=promptbuilder-lang`).
- **Laravel-native pieces** — `Drivers\Laravel\OllamaDriver`,
  `History\Laravel\CacheHistoryStore`, `Translation\Laravel\LaravelTranslator`.

```php
use NoahMedra\PromptBuilder\Facades\PromptBuilder;

$prompt = PromptBuilder::make()
    ->context('...')
    ->ask('...')
    ->toPrompt();
```

## Testing

```bash
composer install
vendor/bin/phpunit                 # everything
vendor/bin/phpunit --testsuite Unit    # framework-free unit tests
vendor/bin/phpunit --testsuite Feature # Laravel-integration tests (Testbench)
```

The **Unit** suite covers the framework-free core (composition, every renderer,
translation, history) with plain PHPUnit. The **Feature** suite uses Orchestra
Testbench to boot a real Laravel app for the facade, the Laravel driver (via
`Http::fake()`), the cache history store and the translator bridge. The
framework-agnostic Ollama driver is tested with a Guzzle `MockHandler`, asserting
the composed prompt is really what gets sent.

## License

Released under the MIT License — see [LICENCE.txt](LICENCE.txt).
