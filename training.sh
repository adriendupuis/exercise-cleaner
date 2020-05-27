#!/usr/bin/env zsh

# USAGE:
# Set remote_*, local_working_directory, exercise_cleaner, path_list and step_list variables according to your needs

# Training repository config:
remote_training_repository='git@github.com:adriendupuis/training.git';
remote_training_branch='master';

# Reference repository config:
remote_reference_repository='https://github.com/adriendupuis/exercise-cleaner';
remote_reference_branch='feature/automation';
#remote_reference_branch='develop';

# Local repositories config:
local_working_directory=~/training;
local_training_repository='training';
local_training_branch='training';
local_reference_repository='reference';
local_reference_branch=$remote_reference_branch; # TODO: Make this variable customizable
#local_reference_branch='reference';

# Exercises config:
exercise_cleaner_bin='src/Application.php';
#exercise_cleaner_bin='./exercise-cleaner.phar';
exercise_cleaner_config='examples/config.yaml';
path_list=(examples/);
step_list=(1 1.1 1.2 2 3);
state_list=('exercise' 'solution');

if [ -e $local_working_directory ]; then
  echo "Error: $local_working_directory already exists.";
  exit 1;
fi;

if [ ! -f $exercise_cleaner_bin ]; then
  echo "Error: $exercise_cleaner_bin not found.";
  exit 2;
fi;

if [ -n "$exercise_cleaner_config" ]; then
  if [ -f $exercise_cleaner_config ]; then
    exercise_cleaner="$exercise_cleaner_bin --config $exercise_cleaner_config";
  else
    echo "Error: $exercise_cleaner_config not found.";
    exit 3;
  fi;
else
  exercise_cleaner="$exercise_cleaner_bin"
fi;

# Stop on error
set -e;

echo 'Initialization: Clone reference branch';
git clone --single-branch --depth 1 --origin $local_reference_repository $remote_reference_repository --branch $remote_reference_branch $local_working_directory;
cd $local_working_directory;
if [[ $exercise_cleaner_bin == *'Application.php'* ]]; then
  echo 'Notice: Running source code (instead of phar archive); Composer install is needed.';
  composer install --no-dev;
  exercise_cleaner="php $exercise_cleaner";
fi
eval "$exercise_cleaner --version;";
echo 'Initialization: Add remote training repository';
git remote add $local_training_repository $remote_training_repository;
git remote -v;
echo 'Initialization: Create local training branch';
git checkout --orphan $local_training_branch;
echo 'Initialization: Ignore Exercise Cleaner';
{
  echo "###> training ###";
  echo "$0";
  if [[ $exercise_cleaner_bin != *'Application.php'* ]]; then
    echo "$exercise_cleaner_bin";
  fi;
  if [ -n "$exercise_cleaner_config" ]; then
    echo "$exercise_cleaner_config";
  fi
  echo "###< training ###"
} >> .gitignore;
git add .gitignore;
git rm --cached $0 $exercise_cleaner_config;
if [[ $exercise_cleaner_bin != *'Application.php'* ]]; then
  git rm --cached $exercise_cleaner_bin;
fi;
echo 'Initialization: Apply and commit step 0';
eval "$exercise_cleaner 0 $path_list";
git add $path_list;
git commit --message "Initialization";
echo 'Initialization: Force push step 0 on remote training branch (replace possible previous content)';
git push --force --set-upstream $local_training_repository $local_training_branch:$remote_training_branch;
git branch -vv;

echo "\nTraining: Entering steps' loop\n";
for step in $step_list; do
  for state in $state_list; do
    echo "Prepare step $step $state…";
    git checkout $local_reference_branch -- $path_list;
    eval "$exercise_cleaner $step --$state $path_list";
    git add $path_list;
    if [[ -n "$(git status --short;)" ]]; then
      git commit --quiet --message "Step $step $state";
      echo "Step $step $state ready: Press 'enter' key to push it to training's remote repository…";
      read -r -n 0;
      echo "Push step $step $state…\n";
      git push --quiet $local_training_repository $local_training_branch:$remote_training_branch;
    else
      echo "Nothing to update for this step and state.\n"
    fi
  done;
done;

exit 0;
