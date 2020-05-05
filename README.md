Exercise Cleaner
================

Training exercises' steps manager and cleaner.

The Exercise Cleaner removes code between tags which number is equal or greater than the desired step.

Usage
-----

### Tag Usage

Tags can be add as comment in files and the cleaner will remove what's inside.

The tags include a step number which can be a float.
The cleaning script get a step as an argument and will clean inside tags having this step or greater.

For each given folder, the script will first search recursively for files containing `TRAINING EXERCISE START STEP`.

Tags can be embed in several comment syntax, or whatever. It's followed by a step number. If the step number is equal or greater than the wanted step, the script search for the closing tag with the same number and remove all in between.

Notice: The tag doesn't care if its embed in a comment or something else. For examples comment doesn't exist in JSON, there is some tricks to not trigger syntax errors but with a limitation for last line without ending coma.

### Simple Tag

- `TRAINING EXERCISE START STEP <step_number>`
- `TRAINING EXERCISE STOP STEP <step_number>`

When `<step_number>` is **greater than** the wanted step number, **remove** inside content.

When `<step_number>` is **equal to** the wanted step number:
* **remove** inside content from **exercise**;
* **keep** inside content into **solution**.

When `<step_number>` is **smaller than** the wanted step number, **keep** inside content.

### Single Afterward Action Tag

- `TRAINING EXERCISE START STEP <step_number> <action>`
- `TRAINING EXERCISE STOP STEP <step_number>`

When `<step_number>` is **greater than or equal to** the wanted step number, **remove** inside content.

When `<step_number>` is **smaller than** the wanted step number, **execute `<action>`** on inside content.

Available actions:
* `KEEP` (default)
* `COMMENT`
* `REMOVE`

### Threshold Conditional Afterward Action Tag

- `TRAINING EXERCISE START STEP <step_number> <action_b> UNTIL <threshold_step_number> THEN <action_a>`
- `TRAINING EXERCISE STOP STEP <step_number>`

When `<step_number>` is **greater than or equal to** than the wanted step number, **remove** inside content.

When `<step_number>` is **smaller than** the wanted step number, **execute** one action:
* When `<threshold_step_number>` is **greater than or equal to** the wanted step number, **execute `<action_b>`**;
* When `<threshold_step_number>` is **smaller than** the wanted step number, **execute `<action_a>`**.

`<threshold_step_number>` must be greater than `<step_number>`.

#### Examples

Note: See [examples/](examples) folder and *[Run ](#run-examples)* section for more.

Tagged Reference:
```json
{
  "TRAINING EXERCISE START STEP 1": "",
  "key1": "value1",
  "TRAINING EXERCISE START STEP 2": "",
  "key2": "value2",
  "TRAINING EXERCISE STOP STEP 2": "",
  "key3": "value3",
  "TRAINING EXERCISE STOP STEP 1": "",
  "key4": "value4"
}
```

Step 1:
```json
{
  "key4": "value4"
}
```

Step 2:
```json
{
  "key1": "value1",
  "key3": "value3",
  "key4": "value4"
}
```

Step 3:
```json
{
  "key1": "value1",
  "key2": "value2",
  "key3": "value3",
  "key4": "value4"
}
```

Tagged Reference:
```php
protected function configure()
{
    // TRAINING EXERCISE START STEP 1
    $this
        // TRAINING EXERCISE START STEP 1 COMMENT
        ->setDescription('Step 1')
        // TRAINING EXERCISE STOP STEP 1
        // TRAINING EXERCISE START STEP 2
        ->setDescription('Step 2+')
        // TRAINING EXERCISE STOP STEP 2
        ->setHelp('Just an example');
    // TRAINING EXERCISE STOP STEP 1
}
```

Step 1:
```php
protected function configure()
{
}
```

Step 2:
```php
    protected function configure()
    {
        $this
//            ->setDescription('Step 1')
            ->setHelp('Just an example');
    }
```

Step 3:
```php
    protected function configure()
    {
        $this
//            ->setDescription('Step 1')
            ->setDescription('Step 2+')
            ->setHelp('Just an example');
    }
```

Note: [tests/ExerciseCleanerTest.php](tests/ExerciseCleanerTest.php) also contains some (harder to read) usage examples

### Script Usage

`php exercise-cleaner [--keep-orig] [--keep-tag] <step> [folder [folder...]]`

Options:
* `--help`: Display usage help
* `--keep-orig`: Instead of replacing content in file, write a new file with extension .step<step_number>.<exercise|solution>.
* `--keep-tag`: Do not remove tags
* `--solution`: Compile exercise's solution (by default, it compile the exercise itself)
* `--config YAML_CONFIG_FILE`: associate a config file
* `--verbose`: Display information about found tags
* `--quiet`: Do not display information about found files

Arguments:
* first argument: step number: clean inside this and higher tags; By default, step 1
* following arguments: folder to search in; By default, it looks inside app/ and src/

### Config File

#### Step Naming

Step naming is used by `--verbose` option.

```yaml
steps:
    names:
        - { number: 1.1, name: 'First Step: Exercise 1' }
        - { n: 1.2, name: 'First Step: Exercise 2' }
        - { n: 2, name: 'Second Step' }
```

About
-----

### Compile Phar

```shell
php -d phar.readonly=Off compile-phar.php;
./exercise-cleaner.phar --version;
```

### Run Unit Tests

Note: A `composer install --dev` (or alike) must have been previously executed.

`./vendor/bin/phpunit --colors tests;`

### Run Examples

Treat examples without compiling:
```shell
find examples -name *.step*.exercise -o -name *.step*.solution | xargs rm -f; # Clean previous runs
for step in 1 2 3; do
    php src/Application.php --keep-orig $step examples/;
    php src/Application.php --keep-orig --solution $step examples/;
done;
```

Treat examples after compiling and with verbosity:
```shell
php -d phar.readonly=0 compile-phar.php;
./exercise-cleaner.phar --version;
rm -f examples/*.step*.*;
for step in 1 2 3; do
    ./exercise-cleaner.phar --config examples/config2.yml --verbose --keep-orig $step examples;
    ./exercise-cleaner.phar --config examples/config2.yml --verbose --keep-orig --solution $step examples;
done;
```

Treat shell example and execute the result:
```shell
for step in 1 2 3; do
    echo "\nSTEP $step EXERCISE";
    php src/Application.php $step examples/example.sh;
    zsh examples/example.sh;
    git checkout -- examples/example.sh;
    echo "\nSTEP $step SOLUTION";
    php src/Application.php --solution $step examples/example.sh;
    zsh examples/example.sh;
    git checkout -- examples/example.sh;
done;
```

### TODO

* Document solution treatment
* Increase verbosity
* Version string as step numbers
* Config file to name and describe steps
* Dedicated file extension for original files
* Placeholder: When $step === $targetStep, have an optional placeholder (to give instructions, clues, resources, etc.)
* Handle just `TRAINING EXERCISE START STEP <step_number> <action_b> UNTIL <threshold_step_number>` (with default/implicit `THEN REMOVE`)
* Handle just `TRAINING EXERCISE START STEP <step_number> UNTIL <threshold_step_number>` (with default/implicit `KEEP UNTIL <n> THEN REMOVE`)
* More unit tests
* Test with / Update for eZ Platform v3
* How to easily distribute the .phar?
* Define an license (at least in the [composer.json](https://getcomposer.org/doc/04-schema.md#license))
* Stop writing "exercise" with two 'c'
