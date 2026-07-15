<?php

/**
 * Runnable demo: `php examples/basic-usage.php`
 *
 * Uses an in-memory FakeDriver so this works with no network and no
 * Ollama instance running — it exists to prove the composition/execution
 * separation end to end.
 */

require __DIR__ . '/../vendor/autoload.php';

use NoahMedra\PromptBuilder\BuilderOutput;
use NoahMedra\PromptBuilder\Drivers\PromptDriverInterface;
use NoahMedra\PromptBuilder\PromptBuilder;
use NoahMedra\PromptBuilder\PromptSpec;
use NoahMedra\PromptBuilder\Rendering\ChatMessagesRenderer;

class FakeDriver implements PromptDriverInterface
{
    public function process(PromptSpec $spec): BuilderOutput
    {
        // A real driver would send this to an LLM. Here we just echo
        // back what it WOULD have sent, proving the spec reaches it intact.
        $messages = (new ChatMessagesRenderer())->render($spec);
        return new BuilderOutput(json_encode(['received_messages' => $messages]));
    }
}

$builder = PromptBuilder::make()
    ->persona('Tu es un professeur de mathématiques bienveillant et rigoureux.')
    ->context("L'élève est en terminale et prépare son bac.")
    ->must('Réponds en français')
    ->must('Donne toujours un exemple concret')
    ->mustNot('Ne donne jamais la réponse finale sans expliquer le raisonnement')
    ->example('Résous x + 2 = 5', 'On isole x : x = 5 - 2 = 3.')
    ->withParams(['sujet' => 'les suites numériques', 'ton' => 'encourageant'])
    ->instruction('Adopte un ton {ton} en abordant {sujet}.')
    ->language('fr') // section labels in French (default is English)
    ->ask('Explique ce qu\'est une suite arithmétique.');

echo "=== Prompt texte (preview, sans I/O) ===\n";
echo $builder->toPrompt() . "\n\n";

echo "=== Rendu en messages de chat ===\n";
print_r($builder->toPrompt(new ChatMessagesRenderer()));

echo "\n=== Exécution via un driver (factice ici) ===\n";
$builder->driver(FakeDriver::class)->process();
echo $builder->getOutput()->get('received_messages.0.role') . "\n";
