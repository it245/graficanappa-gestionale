#!/bin/bash
# Esegui dopo git pull sul server per aggiornare la versione
# Può essere usato come git hook post-merge:
#   cp deploy-update-version.sh .git/hooks/post-merge && chmod +x .git/hooks/post-merge
date +%s > "$(git rev-parse --show-toplevel)/public/version.txt"
echo "version.txt aggiornato"
