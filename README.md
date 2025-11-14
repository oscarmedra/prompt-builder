# PromptBuilder

**PromptBuilder** is a Laravel package that allows you to create and execute AI prompts in a flexible and structured manner. This package enables you to generate dynamic queries, customize parameters, and manage conversation history to improve the relevance of responses.

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

### Adding **instructions**

Instructions are rules or constraints you want to apply to the AI's generated response. You can add them dynamically by chaining calls to the `instruction()` method.

```php
$builder->instruction("Provide a concise response.")
        ->instruction("The response must be in JSON format.");
```

### Adding **dynamic parameters**

You can pass custom parameters to your prompt with the `withParams()` method:

```php
$builder->withParams(['key' => 'value']);
```

### Defining a **context**

You can add additional context to guide the AI in generating the response.

```php
$builder->context("You are a virtual assistant with expertise in computer science.");
```

### Asking a **question**

You can ask a question that will be included in the final prompt.

```php
$builder->ask("How can I optimize my SQL queries?");
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

### Complete Example

Here is a complete example of how to use **PromptBuilder**:

```php
use NoahMedra\PromptBuilder\PromptBuilder;

$builder = PromptBuilder::make();

// Add parameters, instructions, context, and a question
$builder->withParams(['lang' => 'en'])
        ->instruction("Provide a clear and concise answer.")
        ->context("You are an expert in software development.")
        ->ask("How can I optimize a sorting algorithm?");

// Generate and get the response
$builder->process();
$output = $builder->getOutput();

echo $output->get('message.content');
```

## Features

- **Driver flexibility**: Easily add or replace text-processing engines.
- **Dynamic instructions**: Add custom instructions to guide the AI's response.
- **History management**: Store previous conversations to provide context.
- **JSON response support**: Format the AI's responses in JSON for easy integration.
- **Dynamic parameters**: Pass custom parameters to your prompts.

## Customization

You can easily extend **PromptBuilder** by creating your own **drivers** or by customizing the **PromptDriverInterface**. To do this, simply create a class that implements the `PromptDriverInterface` and pass it to the `driver()` method.

## Contributions

Contributions are welcome! If you have ideas to improve the package, feel free to submit an issue or a pull request.

<!-- ## License -->

<!-- This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for more details. -->
