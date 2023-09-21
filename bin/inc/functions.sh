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

# $1 Local directory to merge
# $2 Equivalent directory in upstream code
# $3 Version to update from (commit hash)
# $4 Version to update to (commit hash)
# $5 Repository name
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
    for current in $localDir/*{.ini,.yaml,.phtml,/}
    do
        local coreEquivalent=$coreDir${current:$localDirLength}
        if [ -d "$current" ]
        then
          current=${current%*/}
          coreEquivalent=${coreEquivalent%*/}
          merge_directory "$current" "$coreEquivalent" "$old_commit" "$new_commit" "$repository"
        else
          merge_file "$current" "$coreEquivalent" "$old_commit" "$new_commit" "$repository"
        fi
    done
}

# $1 Filename to update
# $2 Core equivalent name
# $3 Version to update from (commit hash)
# $4 Version to update to (commit hash)
# $5 Repository name
function merge_file
{
    echo_debug "merge_file $1 $2 $3 $4 $5"
    local filename=$1
    local coreEquivalent=$2
    local oldCommit=$3
    local newCommit=$4
    local repository=$5
    local baseApiUrl="https://raw.githubusercontent.com/$repository"
    local oldOriginalFile="/tmp/tmp-merge-old-original-`basename "$coreEquivalent"`"
    local updatedOriginalFile="/tmp/tmp-merge-old-updated-`basename "$coreEquivalent"`"
    local mergedFile="/tmp/tmp-merge-merged-`basename "$coreEquivalent"`"
    curl -s -f "$baseApiUrl/$oldCommit/$coreEquivalent" > $oldOriginalFile
    if [ $? -eq 0 ]
    then
        curl -s "$baseApiUrl/$newCommit/$coreEquivalent" > $updatedOriginalFile
        diff3 -L 'Our original' -L 'Upstream original' -L 'Upstream updated' -m "$filename" "$oldOriginalFile" "$updatedOriginalFile" > "$mergedFile"
        if [ $? == 1 ]
        then
          echo -e "\e[1;31mCONFLICT: $filename\e[0m"
        fi
        cp $mergedFile $filename
    else
        echo_debug "Skipping $filename; no equivalent in core code."
    fi
}

