#!/usr/bin/env zsh

# Training repository config:
remote_training_repository='git@github.com:adriendupuis/training.git';
remote_training_branch='master';

# Reference repository config:
#reference_repository='git@gitlab.com:ez-ps/training/v3/ezplatform.git';
#reference_branch='exercise-cleaner';
remote_reference_repository='https://github.com/adriendupuis/exercise-cleaner';
remote_reference_branch='develop';

# Local repositories config:
local_working_directory=~/training;
local_training_repository='training';
local_training_branch='training';
local_reference_repository='origin'; # TODO: Make this variable customizable
local_reference_branch=$remote_reference_branch;

# Exercises config:
#paths='webpack.config.js config/ src/ templates/';
#exercise_cleaner='bin/exercise-cleaner.phar';
#step_list=('TODO');
paths='examples/';
exercise_cleaner='php src/Application.php';
step_list=(1 1.1 1.2 2 3);
state_list=('exercise' 'solution');

if [ -e $local_working_directory ]; then
  echo "$local_working_directory already exists.";
  exit 1;
fi;

# Stop on error
set -e;

echo 'Initialization: Clone reference branch';
git clone --single-branch $remote_reference_repository --branch $remote_reference_branch $local_working_directory;
cd $local_working_directory;
composer install --no-dev;#TMP TODO: Remove when ready to use .phar
eval "$exercise_cleaner --version;";
echo 'Initialization: Add training repository';
git remote add $local_training_repository $remote_training_repository;
git remote -v;
echo 'Initialization: Push step 0 on training branch';
git checkout --orphan $local_training_branch;
eval "$exercise_cleaner 0 $paths";
git add $paths;
git commit -m "Initialization";
git push --set-upstream $local_training_repository $local_training_branch:$remote_training_branch;
git branch -vv;

echo "Training: Entering step loop"
for step in $step_list; do
  for state in $state_list; do
    git checkout $local_reference_branch -- $paths;
    eval "$exercise_cleaner $step --$state $paths";
    echo "Step $step $state ready: Press 'enter' key to push it to training's remote repository";
    read -r -n 0;
    git add $paths;
    git commit -m "Step $step $state";
    git push $local_training_repository $local_training_branch:$remote_training_branch;
  done;
done;
