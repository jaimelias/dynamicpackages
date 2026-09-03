#!/bin/bash
set -e  # stop on first error, so a bad path doesn't cascade into worse damage

# Check if a custom comment is provided as a command-line argument
if [ -z "$1" ]; then
    echo "Usage: $0 <custom_comment>"
    exit 1
fi

CUSTOM_COMMENT="$1"

# Anchor to the script's own directory so cwd assumptions don't break it
cd "$(dirname "$0")"

# Function to perform the git actions in the CURRENT directory
perform_git_actions() {
    git add .
    git commit -m "$CUSTOM_COMMENT" || echo "Nothing to commit in $(pwd)"
    git push origin master
}

# --- 1. Commit dy-core itself ---
# Gets to /wp-content/plugins/dynamicpackages
cd ..
perform_git_actions

# --- 2. Sync into dynamicaviation and commit there ---
rm -rf ../dynamicaviation/submodules/dy-core
mkdir -p ../dynamicaviation/submodules
cp -r dynamicpackages/dy-core ../dynamicaviation/submodules

(
    cd ../dynamicaviation
    perform_git_actions
)

# --- 3. Sync into minimalizr theme and commit there ---
rm -rf ../../themes/minimalizr/submodules/dy-core
mkdir -p ../../themes/minimalizr/submodules
cp -r dynamicpackages/dy-core ../../themes/minimalizr/submodules

(
    cd ../../themes/minimalizr
    perform_git_actions
)

echo "Done."