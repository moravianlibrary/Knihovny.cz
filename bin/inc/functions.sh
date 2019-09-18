#!/bin/bash

# Function to determine last commit of repository on certain branch
# Parameters
#       $1 - Branch name
#       $2 - Repository name in form of organization/repository. e.g. "moravianlibrary/Knihovny.cz"

function last_commit {
    local branch=$1
    local repo=$2
    local url="https://api.github.com/repos/$repo/commits?sha=$branch"
    local sha=`curl -s "$url" | php -r "echo (string) json_decode(file_get_contents('php://stdin'))[0]->sha;"`
    echo $sha
}