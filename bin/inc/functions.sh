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

function echo_debug {
  [[ "$debug" ]] && echo "$1"
}

function merge_directory
{
    echo_debug "merge_directory $1 $2 $3 $4 $5"
    local localDir=$1
    local localDirLength=${#localDir}
    local coreDir=$2
    local old_commit=$3
    local new_commit=$4
    local repository=$5
    local baseApiUrl="https://raw.githubusercontent.com/$repository"

    shopt -s nullglob
    for current in $localDir/*{.ini,.phtml,/}
    do
        local coreEquivalent=$coreDir${current:$localDirLength}
        if [ -d "$current" ]
        then
          current=${current%*/}
          coreEquivalent=${coreEquivalent%*/}
          merge_directory "$current" "$coreEquivalent" "$old_commit" "$new_commit" "$repository"
        else
          local oldOriginalFile="/tmp/tmp-merge-old-original-`basename "$coreEquivalent"`"
          local updatedOriginalFile="/tmp/tmp-merge-old-updated-`basename "$coreEquivalent"`"
          local mergedFile="/tmp/tmp-merge-merged-`basename "$coreEquivalent"`"
          curl -s "$baseApiUrl/$old_commit/$coreEquivalent" > $oldOriginalFile
          status=$(head -c 14 $oldOriginalFile)
          if [ "$status" != "404: Not Found" ]
          then
            curl -s "$baseApiUrl/$new_commit/$coreEquivalent" > $updatedOriginalFile
            diff3 -m "$current" "$oldOriginalFile" "$updatedOriginalFile" > "$mergedFile"
            if [ $? == 1 ]
            then
              echo -e "\e[1;31mCONFLICT: $current\e[0m"
            fi
            cp $mergedFile $current
          else
            echo_debug "Skipping $current; no equivalent in core code."
          fi
        fi
    done
}

