Exercise Cleaner
================

*Training exercises' steps manager and cleaner*

* [Install](#install)
* [Usage](#usage)
  - [Tags](#tags)
  - [Command](#command)
  - [Config File](#config-file)
* [Development](#development)
  - [Development Requirements](#development-requirements)
  - [Compile Phar](#compile-phar)
  - [Run Tests](#run-tests)
  - [Conform to Standards](#conform-to-standards)
  - [Run Examples](#run-examples)
  - [To Do](#todo)  


Install
-------

To quickly install Exercise Cleaner's phar asset:
- Download last release's asset *exercise-cleaner.phar* from [Releases page](https://github.com/adriendupuis/exercise-cleaner/releases) to your project;
- Make it executable for yourself with `chmod u+x exercise-cleaner.phar`;
- Check executability, for example from same directory, with `./exercise-cleaner.phar --version`.


Usage
-----

The Exercise Cleaner is used to prepare exercises' worksheets (code with missing parts that trainees must fill, the exercise itself) or their solutions.

The Exercise Cleaner mainly cleans code between tags of current and next steps. The Exercise Cleaner can execute several actions on previous steps' code (keep it, comment it, remove it). When preparing an exercise's worksheets, code can be replaced by placeholders (instruction, references, clues, etc.).

### Tags

Tags can be added in exercises' files and the Exercise Cleaner's command will treat what's inside.

The tags include a step number as an integer or a float.

The command get a step as an argument and will remove content inside tags having a greater one. When it's equal or smaller, see details in below subsections.

The command get paths as arguments; for each given folder, if there is no dedicated extension, the script will first search recursively for files containing `TRAINING EXERCISE` (case sensitive), the core part of a tag.

A tag doesn't care if is embed in a comment or something else. When matching, the whole line containing the tag becomes the tag. For example, comment doesn't exist in JSON but there is some tricks to not trigger syntax errors but with a limitation for last line without ending comma. The rest of the line can be used for internal notes.

Syntax: `TRAINING EXERCISE <boundary> STEP <step_number> [<state>] [<action>] [UNTIL <step_number> [THEN <action>]]`

* `<boundary>` defines if it's an opening or closing tag:
  - `START`
  - `STOP`
* `<step_number>`: float
* `<state>` defines what to do when the wanted tag equals the tag's step number:
  - `SOLUTION` (default) means succinctly that enclosed content is present only in solution
  - `WORKSHEET` (or deprecated `INTRO`) means succinctly that enclosed content is present in both exercise and solution
  - `PLACEHOLDER` means succinctly that enclosed content is present only in exercise
* `<action>`:
  - `KEEP` (`SOLUTION` and `WORKSHEET`'s default)
  - `COMMENT`
  - `REMOVE` (`PLACEHOLDER`'s default)

#### Simple Tag

- `TRAINING EXERCISE START STEP <step_number>`
- `TRAINING EXERCISE STOP STEP <step_number>`

When `<step_number>` is **greater than** the wanted step number, **remove** inside content.

When `<step_number>` is **equal to** the wanted step number:
* **remove** inside content from **exercise**;
* **keep** inside content into **solution**.

When `<step_number>` is **smaller than** the wanted step number, **keep** inside content.

The following is equivalent:

- `TRAINING EXERCISE START STEP <step_number> SOLUTION`
- `TRAINING EXERCISE STOP STEP <step_number>`

#### State Tag

- `TRAINING EXERCISE START STEP <step_number> <state>`
- `TRAINING EXERCISE STOP STEP <step_number>`

The `<state>` defines what will done when `<step_number>` is equal to or smaller than the wanted step number.

- `TRAINING EXERCISE START STEP <step_number> SOLUTION`
- `TRAINING EXERCISE STOP STEP <step_number>`

When `<step_number>` is **greater than** the wanted step number, **remove** inside content.

When `<step_number>` is **equal to** the wanted step number:
* **remove** inside content from **exercise**;
* **keep** inside content into **solution**.

When `<step_number>` is **smaller than** the wanted step number, **keep** inside content.

- `TRAINING EXERCISE START STEP <step_number> WORKSHEET`
- `TRAINING EXERCISE STOP STEP <step_number>`

When `<step_number>` is **greater than** the wanted step number, **remove** inside content.

When `<step_number>` is **equal to** the wanted step number, keep in both **exercise and solution**.

When `<step_number>` is **smaller than** the wanted step number, **keep** inside content.

- `TRAINING EXERCISE START STEP <step_number> PLACEHOLDER`
- `TRAINING EXERCISE STOP STEP <step_number>`

When `<step_number>` is **greater than** the wanted step number, **remove** inside content.

When `<step_number>` is **equal to** the wanted step number:
* **keep** inside content into **exercise**;
* **remove** inside content from **solution**.

When `<step_number>` is **smaller than** the wanted step number, **remove** inside content.

#### Single Afterward Action Tag

- `TRAINING EXERCISE START STEP <step_number> <action>`
- `TRAINING EXERCISE STOP STEP <step_number>`

When `<step_number>` is **greater than** the wanted step number, **remove** inside content.

When `<step_number>` is **equal to** the wanted step number:
* **remove** inside content from **exercise**;
* **keep** inside content into **solution**.

When `<step_number>` is **smaller than** the wanted step number, **execute `<action>`** on inside content.

Available actions:
* `KEEP` (`SOLUTION` and `WORKSHEET` states' default)
* `COMMENT`
* `REMOVE` (`PLACEHOLDER` state's default)

The following is equivalent:

- `TRAINING EXERCISE START STEP <step_number> SOLUTION  <action>`
- `TRAINING EXERCISE STOP STEP <step_number>`

#### Threshold Conditioned Afterward Action Tag

- `TRAINING EXERCISE START STEP <step_number> <action_b> UNTIL <threshold_step_number> THEN <action_a>`
- `TRAINING EXERCISE STOP STEP <step_number>`

When `<step_number>` is **greater than** the wanted step number, **remove** inside content.

When `<step_number>` is **equal to** the wanted step number:
* **remove** inside content from **exercise**;
* **keep** inside content into **solution**.

When `<step_number>` is **smaller than** the wanted step number, **execute** one action:
* When `<threshold_step_number>` is **greater than or equal to** the wanted step number, **execute `<action_b>`**;
* When `<threshold_step_number>` is **smaller than** the wanted step number, **execute `<action_a>`**.

`<threshold_step_number>` must be greater than `<step_number>`.

#### Intro Keyword (deprecated)

Previous tags can contain keyword `INTRO` anywhere after their step number.

- `TRAINING EXERCISE STOP STEP <step_number> INTRO [...]`

With `INTRO`, when `<step_number>` is **equal to** the wanted step number, **keep** inside content in both **exercise and solution**.

#### One-Line Placeholder Tag

- `TRAINING EXERCISE STEP PLACEHOLDER`

When `<step_number>` is **greater than** the wanted step number, **remove** line containing this tag from **solution**.

When `<step_number>` is **equal to** the wanted step number:
* **keep** line containing this tag into **exercise** with this tag removed;
* **remove** line containing this tag from **solution**.

When `<step_number>` is **smaller than** the wanted step number, **remove** line containing this tag from **solution**.

The two following examples are equivalent:

```php
<?php
// TRAINING EXERCISE START STEP 1
// TRAINING EXERCISE STEP PLACEHOLDER TODO: Output the solution
echo 'Solution';
// TRAINING EXERCISE START STOP 1
```

```php
<?php
// TRAINING EXERCISE START STEP 1 PLACEHOLDER
// TODO: Output the solution
// TRAINING EXERCISE START STOP 1
// TRAINING EXERCISE START STEP 1
 echo 'Solution';
// TRAINING EXERCISE START STOP 1
```

#### Examples

Notes:
- See [examples/](examples) folder and *[About/Run Examples](#run-examples)* section for more.
- [tests/ExerciseCleanerTest.php](tests/ExerciseCleanerTest.php) also contains some (harder to read) usage examples

##### JSON Example with Simple Tag

Exercise's Tagged Reference:
```json
{
  "TRAINING EXERCISE START STEP 1": "Step 1's needs",
  "key1": "value1",
  "Step 2's begin": "TRAINING EXERCISE START STEP 2",
  "key2": "value2",
  "Step 2's end": "TRAINING EXERCISE STOP STEP 2",
  "key3": "value3",
  "TRAINING EXERCISE STOP STEP 1": null,
  "key4": "value4"
}
```

Step 1's worksheet:
```json
{
  "key4": "value4"
}
```

Step 1's solution & step 2's worksheet:
```json
{
  "key1": "value1",
  "key3": "value3",
  "key4": "value4"
}
```

Step 2's solution:
```json
{
  "key1": "value1",
  "key2": "value2",
  "key3": "value3",
  "key4": "value4"
}
```

#### PHP Examples with Nested Tags and Action Tag

Tagged Reference:
```php
protected function configure(): void
{
    // TRAINING EXERCISE START STEP 1
    // TRAINING EXERCISE STEP PLACEHOLDER TODO: Set the description and the help
    $this
        ->setDescription('Just an example')
        // TRAINING EXERCISE START STEP 1 COMMENT
        ->setHelp('Step 1 feature')
        // TRAINING EXERCISE STOP STEP 1
        // TRAINING EXERCISE START STEP 2 PLACEHOLDER
        /* TODO:
            - Add argument(s)
            - Add option(s)
            - Update help
        */
        // TRAINING EXERCISE STOP STEP 2
        // TRAINING EXERCISE START STEP 2
        ->addArgument('argument', InputArgument::OPTIONAL, 'An optional argument')
        ->addOption('option', 'o', InputOption::VALUE_OPTIONAL, 'An option with an optional value')
        ->setHelp("Step 1 feature\nStep 2 feature")
        // TRAINING EXERCISE STOP STEP 2
    ;
    // TRAINING EXERCISE STOP STEP 1
}
```

Step 1's worksheet:
```php
protected function configure(): void
{
    // TODO: Set the description and the help
}
```

Step 1's solution:
```php
protected function configure(): void
{
    $this
        ->setDescription('Just an example')
        ->setHelp('Step 1 feature')
    ;
}
```

Step 2's worksheet:
```php
protected function configure(): void
{
    $this
        ->setDescription('Just an example')
        // ->setHelp('Step 1 feature')
        /* TODO:
            - Add argument(s)
            - Add option(s)
            - Update help
        */
    ;
}
```
Step 2's solution (and next steps' worksheets and solutions):
```php
protected function configure(): void
{
    $this
        ->setDescription('Just an example')
        // ->setHelp('Step 1 feature')
        ->addArgument('argument', InputArgument::OPTIONAL, 'An optional argument')
        ->addOption('option', 'o', InputOption::VALUE_OPTIONAL, 'An option with an optional value')
        ->setHelp("Step 1 feature\nStep 2 feature")
    ;
}
```

### Command

`./exercise-cleaner.phar [input-ext <extension>] [--output-ext] [--keep-tag] [--exercise|--solution] [--config <file>] <step> [path [path...]]`

Options:
* `--help`: Display usage help
* `--input-ext`: Treat only file having this extension and remove this extension before saving.
* `--output-ext`: Instead of replacing content in file, write a new file with extension .step<step_number>.<exercise|solution>.
* `--keep-tag`: Do not remove tags
* `--solution`: Compile exercise's solution (by default, it compile the exercise's worksheet)
* `--config <yaml_config_file>`: associate a config file
* `--quiet`: Do not display information about steps
* `-v`: Display information about treated files
* `-vv`: Also display information about found tags

Arguments:
* first argument: step number: clean inside this and higher tags; By default, step 1
* following arguments: path(s) to file or folder to search in; By default, it looks inside app/ and src/

#### File Extensions

* `./exercise-cleaner.phar 1 example.php;` will clean for first step's exercise and **replace _example.php_**
* `./exercise-cleaner.phar 1 example.php --output-ext;` will clean _example.php_ for first step's exercise and **save into file _example.php.step1.exercise_**
* `./exercise-cleaner.phar 1 example.php.ec --input-ext ec;` will clean _example.php.ec_ for first step's exercise and **save into _example.php_**
* `./exercise-cleaner.phar 1 example.php.ec --input-ext ec --output-ext;` will clean _example.php.ec_ for first step's exercise and **save into _example.php.step1.exercise_**

Using an extension can prevent the application to consider unstable files. When using an extension, some file associations could be added to IDE; like, for example, *.php.ec → PHP.

To migrate from a no-extension to an extension structure, for example with `ec` extension:
- `grep 'TRAINING EXERCISE START STEP' -Rl examples/ | xargs -I {} mv -v {} {}.ec;`

To migrate from an extension to a no-extension structure, for example with `ec` extension, you can use one of this:
- `grep 'TRAINING EXERCISE START STEP' -Rl examples/ | xargs -I {} mv -v {}.ec {};`
- `find examples/ -name *.ec | while read file; do mv -v $file ${file%.ec}; done;`

In those migration examples, `git mv` may be used instead of `mv`.

### Config File

Add `--config <file>` option to give the path to a config file. A config file must be in YAML. See what it can define below.

#### Step Naming

Step naming enhances output.

`--step-name` option make the command return the name of a step instead of treating files; for example, `./exercise-cleaner.phar 1.1 --step-name --config examples/config.yaml;` will return the name of the step number 1.1 if it's defined.

##### Examples

Using floats:
```yaml
steps:
    names:
        - { number: 1.1, name: 'First Step: First Part' }
        - { n: 1.2, name: 'First Step: Second Part' }
        - { n: 2, name: 'Second Step' }
```
Note: `n` is a shorthand for `number`, they are strictly equivalents.

Using only integers:
```yaml
steps:
    names:
        - 'First Step'
        - 'Second Step'
```
Note: If floats are finally used, a conversion to object list will be needed; So, using the previous format even if there is only integers could be recommended.

#### File Extension

Instead of using each time `--input-ext` with the command, the input file extension can be declared in config file.

##### Examples

The following sequence already using a config file to name steps can be simplified:
```shell
./exercise-cleaner.phar --config exercise-cleaner.yml --input-ext ec 1 examples/;
./exercise-cleaner.phar --config exercise-cleaner.yml --input-ext ec 1 examples/ --solution;
./exercise-cleaner.phar --config exercise-cleaner.yml --input-ext ec 2 examples/;
./exercise-cleaner.phar --config exercise-cleaner.yml --input-ext ec 2 examples/ --solution;
```

Using the following config file:
```yaml
steps:
    names:
        - 'First Step'
        - 'Second Step'

files:
    input:
        extension: 'ec'
```
```shell
./exercise-cleaner.phar --config exercise-cleaner.yml 1 examples/;
./exercise-cleaner.phar --config exercise-cleaner.yml 1 examples/ --solution;
./exercise-cleaner.phar --config exercise-cleaner.yml 2 examples/;
./exercise-cleaner.phar --config exercise-cleaner.yml 2 examples/ --solution;
```


Development
-----------

### Development Requirements

* [Composer](https://getcomposer.org/) usable as `composer` (like in [global install](https://getcomposer.org/doc/00-intro.md#globally)).

### Compile Phar

```shell
composer run compile;
./exercise-cleaner.phar --version;
```

Note: When creating a release, an asset is automatically compiled and attached to it (see [*Release Asset* workflow](.github/workflows/release.yml))

### Run Unit Tests

`composer run test;`

Note: When a push to `develop` branch, to `master` branch or to a pull request targeting one of this two branches is done, tests are automatically run (see [*Unit Tests* workflow](.github/workflows/tests.yml))

### Conform to Standards

The last [Symfony coding standards](https://symfony.com/doc/current/contributing/code/standards.html)' rules are to be applied.

To conform code (using [PHP Coding Standards Fixer](https://cs.symfony.com/)): `composer run conform;`

Note: When a push to `develop` branch, to `master` branch or to a pull request targeting one of this two branches is done, conformity tests are automatically run (see [*Coding Standards* workflow](.github/workflows/standards.yml))

### Run Examples

Treat examples with the source code (without compiling):
```shell
find examples -name *.step*.exercise -o -name *.step*.solution | xargs rm -f; # Clean previous runs
for step in 1 1.1 1.2 2 3; do
    php src/Application.php --keep-orig $step examples/;
    php src/Application.php --keep-orig --solution $step examples/;
done;
```

Treat examples after compiling and with verbosity:
```shell
composer run compile;
./exercise-cleaner.phar --version;
rm -f examples/*.step*.*; # Clean previous runs
for step in 1 1.1 1.2 2 3; do
    ./exercise-cleaner.phar --config examples/config.yaml --verbose --keep-orig $step examples;
    ./exercise-cleaner.phar --config examples/config.yaml --verbose --keep-orig --solution $step examples;
done;
```

Treat shell example and execute the result:
```shell
composer run compile;
for step in 1 1.1 1.2 2 3; do
    for state in exercise solution; do
        echo "\nStep $step $state";
        ./exercise-cleaner.phar --$state $step examples/example.sh;
        zsh examples/example.sh;
        git checkout -- examples/example.sh;
    done;
done;
```

### TODO

* Features
  - Version string as step numbers
  - Define a license (at least in the [composer.json](https://getcomposer.org/doc/04-schema.md#license))
* Development & Quality
  - Detailed requirements
  - Test with other versions of PHP than PHP 7.4.3
  - More unit tests; smaller unit tests
  - Test with / Update for eZ Platform v3
* Stop writing "exercise" with two 'c'
