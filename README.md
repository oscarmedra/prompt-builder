# PromptBuilder

**PromptBuilder** is a Laravel package that allows you to create and execute AI prompts in a flexible and structured way. This package enables you to generate dynamic queries, customize parameters, and manage conversation history to improve the relevance of responses.

## Installation

### Composer

To install **PromptBuilder** via Composer, run the following command:

```bash
composer require noahmedra/promptbuilder
```

## Usage

### Creating a `PromptBuilder` object

Start by instantiating a `PromptBuilder` object:

```php
use NoahMedra\PromptBuilder\PromptBuilder;

$builder = PromptBuilder::make();
```

### Defining the **Driver** (Processing Engine)

By default, **PromptBuilder** uses the `OllamaDriver`. If you want to use another driver, you can specify it using the `driver()` method.

```php
use NoahMedra\PromptBuilder\Drivers\HuggingFaceDriver;

$builder->driver(HuggingFaceDriver::class);
```

### Adding **instructions** (with Sub-Instructions and Conditions)

Instructions are rules or constraints you want to apply to the AI's generated response. These instructions can have **sub-instructions** or be **condition-dependent**. You can chain instructions infinitely and even create **nested conditions**.

Here is an example where instructions can contain sub-instructions, and conditional logic can be applied using the `when()` method.

#### Example with Instructions and Sub-Instructions:

```php
$builder->instruction("### Financial History")
        ->instruction("We will also review your financial history to identify trends.")
        ->when(
            true,  // This condition is true, so the sub-instructions will be applied
            function($inst) {
                $inst->instruction("Here is a summary of your financial history for the last three months.");
                $inst->instruction("Your balance has fluctuated between X and Y. It seems like you had some unexpected expenses.");
            }
        );
```

In this example:

1. **Main instructions**: The first two instructions introduce the financial history topic and explain the review process.
2. **Conditional instructions**: The `when()` method checks a condition (in this case, `true`), and if true, additional **sub-instructions** are added (e.g., a summary of the financial history).

### Adding **dynamic parameters**

You can pass custom parameters to your prompt with the `withParams()` method:

```php
$builder->withParams(['key' => 'value']);
```

### Defining a **context**

You can add additional context to guide the AI in generating the response.

```php
$builder->context("You are a virtual assistant with expertise in financial analysis.");
```

### Asking a **question**

You can ask a question that will be included in the final prompt.

```php
$builder->ask("What are the main trends in the past three months of my financial data?");
```

### Managing **history**

If you want the AI to use previous conversation history to provide more contextual responses, enable the `useHistory()` option.

```php
$builder->useHistory(true);
```

### Generating and retrieving the **response**

Once all instructions and parameters are defined, you can generate the prompt and obtain the AI's response by calling the `getOutput()` method:

```php
$builder->process();
$output = $builder->getOutput();
echo $output->('message.content'); // Display the generated response
echo $output->('model'); // Display the model's response
```

### Handling **JSON** responses

If you want the AI's response to be formatted as JSON, use the `jsonify()` method.

```php
$builder->jsonify('{"resume": "Summary of the response", "response": "Your response here"}');
```

### **Driver: How It Works (Input/Output)**

The `Driver` is responsible for handling the input (your prompt) and generating the output (the AI's response). Each driver implements the `DriverInterface` and provides a `process()` method that takes a `BuilderInput` object, processes the prompt, and returns a `BuilderOutput`.

For example, the `OllamaDriver` sends the prompt to a local Ollama service and receives the AI's response.

Here is an example of the **OllamaDriver**:

```php
namespace App\Drivers;

use Exception;
use Illuminate\Support\Facades\Http;
use NoahMedra\PromptBuilder\BuilderInput;
use NoahMedra\PromptBuilder\BuilderOutput;
use NoahMedra\PromptBuilder\Drivers\DriverInterface;

class OllamaDriver implements DriverInterface
{
    public function process(BuilderInput $input) : BuilderOutput
    {
        return new BuilderOutput($this->executePrompt($input));
    }

    private function executePrompt(BuilderInput $input) : string
    {
        $output = '';
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->post('http://localhost:11434/api/chat', [
                'model' => 'llama3.1',
                'stream' => false,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $input->getPromptText(),
                    ]
                ],
                ...($input->getParams() ?? [])
            ]);

            if ($response->failed()) {
                throw new Exception($response->body());
            }

            $output = $response->body();
        } catch (Exception $e) {
            $output = $e->getMessage();
        }

        return $output;
    }
}
```

In this example:

1. The `process()` method receives a `BuilderInput` object which contains the prompt text.
2. The `executePrompt()` method sends the prompt to a remote API (Ollama) and receives the AI response.
3. The response is returned as a `BuilderOutput` object.

### Complete Example

Here is a complete example of how to use **PromptBuilder**:

```php
use NoahMedra\PromptBuilder\PromptBuilder;

$builder = PromptBuilder::make();

// Add parameters, instructions, context, and a question
$builder->withParams(['lang' => 'en'])
        ->instruction("Provide a clear and concise answer.")
        ->instruction("The response should be in JSON format.")
        ->instruction("Include the following sub-instructions:")
            ->instruction("Ensure the answer is clear and easy to understand.")
            ->instruction("Be concise and avoid unnecessary details.")
        ->context("You are an expert in financial analysis.")
        ->ask("What are the main trends in the past three months of my financial data?");

// Generate and get the response
$builder->process();
$output = $builder->getOutput();

echo $output->get('message.content');
```

## Features

- **Driver flexibility**: Easily add or replace text-processing engines.
- **Dynamic instructions with sub-instructions and conditions**: Add custom instructions that can have nested sub-instructions, and create complex prompts with **conditional instructions** using the `when()` method.
- **History management**: Store previous conversations to provide context.
- **JSON response support**: Format the AI's responses in JSON for easy integration.
- **Dynamic parameters**: Pass custom parameters to your prompts.

## Customization

You can easily extend **PromptBuilder** by creating your own **drivers** or by customizing the **PromptDriverInterface**. To do this, simply create a class that implements the `PromptDriverInterface` and pass it to the `driver()` method.

## Contributions

Contributions are welcome! If you have ideas to improve the package, feel free to submit an issue or a pull request.

<!-- ## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for more details. -->
