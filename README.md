Exercise Cleaner
================

Training exercise's steps manager and cleaner.

The Exercise Cleaner removes code between tags which number is equal or greater than the desired step.

Usage
-----

### Tag Usage

Tags can be add as comment in files and the cleaner will remove what's inside.
The tags include a step number. The cleaning script get a step as an argument and will clean inside tags having this step or greater.

The script will first check for lines containing `TRAINING EXERCISE START STEP`.

Tags can be embed in several comment syntax, or whatever. It's followed by a step number. If the step number is equal or greater than the wanted step, the script search for the closing tag with the same number and remove all in between.

Notice: The tag doesn't care if its embed in a comment or something else. For examples comment doesn't exist in JSON, there is some trick to not trigger syntax errors but with a limitation for last line without ending coma.

Simple:
- `TRAINING EXERCISE START STEP <step_number>`
- `TRAINING EXERCISE STOP STEP <step_number>`

With afterward action:
- `TRAINING EXERCISE START STEP <step_number> <action>`
- `TRAINING EXERCISE STOP STEP <step_number>`

When `<step_number>` is smaller than the wanted step number, execute `<action>`.

Available actions:
* KEEP (default)
* COMMENT
* REMOVE

With afterward conditional actions:
- `TRAINING EXERCISE START STEP <step_number> <action_b> UNTIL <threshold_step_number> THEN <action_a>`
- `TRAINING EXERCISE STOP STEP <step_number>`

When `<step_number>` is smaller than the wanted step number, execute one action:
* When `<threshold_step_number>` is greater than or equal to the wanted step number, execute `<action_b>`;
* When `<threshold_step_number>` is smaller than the wanted step number, execute `<action_a>`.

`<threshold_step_number>` must be greater than `<step_number>`.

#### Examples

See [exercise-cleaner-test/](exercise-cleaner-test) folder for some examples.

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

### Script Usage

`php exercise-cleaner [--keep-orig] [--keep-tag] <step> [folder [folder...]]`

Options:
* `--help`: Display usage help
* `--keep-orig`: Instead of replacing content in file, write a new file with .cleaned added extension.
* `--keep-tag`: Do not remove tags
* `--solution`: Compile exercise's solution (by default, it compile the exercise itself)

Arguments:
* first argument: step number: clean inside this and higher tags; By default, step 1
* following arguments: folder to search in; By default, it looks inside app/ and src/

About
-----

### Compile Phar

```shell
php -d phar.readonly=Off compile-phar.php;
php exercise-cleaner --version;
```

### Run unit tests

Note: A `composer install --dev` (or alike) must have been previously executed.

`./vendor/bin/phpunit --colors tests;`

### Run examples

Run example without compiling:
```shell
rm -f exercise-cleaner-test/*.cleaned; php src/Command.php --keep-orig 1 examples;
rm -f exercise-cleaner-test/*.cleaned; php src/Command --keep-orig 2 examples;
rm -f exercise-cleaner-test/*.cleaned; php src/Command --keep-orig --keep-tags 3 examples;
```

Run examples after compiling:
```shell
php -d phar.readonly=0 compile-phar.php;
rm -f exercise-cleaner-test/*.cleaned; php exercise-cleaner.phar --keep-orig 1 examples;
rm -f exercise-cleaner-test/*.cleaned; php exercise-cleaner.phar --keep-orig 2 examples;
rm -f exercise-cleaner-test/*.cleaned; php exercise-cleaner.phar --keep-orig --keep-tags 3 examples;
```

### TODO

* More unit tests
* Maybe use [symfony/console](https://packagist.org/packages/symfony/console) now that there is a .phar
* Test with / Update for eZ Platform v3
* Stop writing "exercise" with two 'c'
