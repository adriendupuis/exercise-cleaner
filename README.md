Exercise Cleaner
================

Training exercise's steps manager and cleaner.

The Exercise Cleaner removes code between tags which number is equal or greater than the desired step.

Usage
-----

### Tags

Opening tag: `TRAINING EXERCISE START STEP <step_number>`
Closing tag: `TRAINING EXERCISE STOP STEP <step_number>`

Can be nested.

See test examples for more.

### Command

`php exercise-cleaner.php <step_number>;`

`<step_number>`: exercise step for which the code must be cleaned.