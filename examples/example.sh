#!/usr/bin/env zsh
U='\e[4m'; # Underline
RU='\e[24m'; # Reset Underline

echo "Never treated line, always present";


echo -e "${U}Simple Tag Usage${RU}";

# TRAINING EXERCISE START STEP 1
echo "Line from step 1, removed in step 1's exercise, kept in step 1's solution, kept in next steps' exercises and solutions.";
# TRAINING EXERCISE STOP STEP 1

# TRAINING EXERCISE START STEP 2
echo "Line from step 2, removed in steps 1 and 2's exercise, kept in step 2's solution, kept in step 3's exercise and solution.";
# TRAINING EXERCISE STOP STEP 2


echo -e "${U}Single Action Tag Usage${RU}";

# TRAINING EXERCISE START STEP 1 COMMENT
echo "Line from step 1, removed in step 1's exercise, kept in step 1's solution, commented in next steps' exercises and solutions.";
# TRAINING EXERCISE STOP STEP 1

# TRAINING EXERCISE START STEP 2 REMOVE
echo "Line from step 2, kept only in step 2's solution, elsewhere removed.";
# TRAINING EXERCISE STOP STEP 2


echo -e  "${U}Threshold Tag Usage${RU}";

# TRAINING EXERCISE START STEP 1 KEEP UNTIL 2 THEN COMMENT
echo "Line from step 1, removed in step 1's exercise, kept in step 1's solution and in step 2's exercise and solution, commented in step 3's exercise and solution.";
# TRAINING EXERCISE STOP STEP 1


echo -e "${U}Nested Tags Usage${RU}";

# TRAINING EXERCISE START STEP 1
echo "Line from step 1, removed in step 1's exercise, kept in step 1's solution, kept in next steps' exercises and solutions.";

  # TRAINING EXERCISE START STEP 1 COMMENT
  echo "Nested line from step 1, removed in step 1's exercise, kept in step 1's solution, commented in next steps' exercises and solutions.";
  # TRAINING EXERCISE STOP STEP 1

  echo "Nested line from step 1, removed in step 1's exercise, kept in step 1's solution, kept in next steps' exercises and solutions.";

  # TRAINING EXERCISE START STEP 2
  echo "Nested line from step 2, removed in steps 1 and 2's exercise, kept in step 2's solution, kept in step 3's exercise and solution.";
  # TRAINING EXERCISE STOP STEP 2

  # TRAINING EXERCISE START STEP 1 KEEP UNTIL 2 THEN COMMENT
  echo "Line from step 1, removed in step 1's exercise, kept in step 1's solution and in step 2's exercise and solution, commented in step 3's exercise and solution.";
  # TRAINING EXERCISE STOP STEP 1

echo "Line from step 1, removed in step 1's exercise, kept in step 1's solution, kept in next steps' exercises and solutions.";
# TRAINING EXERCISE STOP STEP 1

# TRAINING EXERCISE START STEP 2
echo "Line from step 2, removed in steps 1 and 2's exercise, kept in step 2's solution, kept in step 3's exercise and solution.";
  # TRAINING EXERCISE START STEP 1
  echo "Nested line from step 1, removed in step 1's exercise, kept in step 1's solution, kept in next steps' exercises and solutions.";
  # TRAINING EXERCISE STOP STEP 1
echo "Line from step 2, removed in steps 1 and 2's exercise, kept in step 2's solution, kept in step 3's exercise and solution.";
# TRAINING EXERCISE STOP STEP 2