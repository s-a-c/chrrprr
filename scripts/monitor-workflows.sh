#!/bin/bash

# Monitor GitHub Actions workflows for develop branch
# Notifies when all workflows complete (success or failure)

REPO="s-a-c/lw4fm5"
BRANCH="develop"
CHECK_INTERVAL=10  # Check every 10 seconds
MAX_WAIT=1800      # Maximum wait time: 30 minutes

printf "🔍 Monitoring GitHub Actions workflows for branch: %s\n" "$BRANCH"
printf "⏱️  Checking every %d seconds (max wait: %ds)\n" "${CHECK_INTERVAL}" "${MAX_WAIT}"
printf "\n"

start_time=$(date +%s)
last_status=""

while true; do
    current_time=$(date +%s)
    elapsed=$((current_time - start_time))

    if [ $elapsed -gt $MAX_WAIT ]; then
        printf "⏰ Maximum wait time exceeded (%ds)\n" "${MAX_WAIT}"
        exit 1
    fi

    # Get workflow runs
    runs=$(gh run list --repo "$REPO" --branch "$BRANCH" --limit 3 --json status,conclusion,workflowName,url,createdAt --jq '.[] | "\(.status)|\(.conclusion // "none")|\(.workflowName)|\(.url)|\(.createdAt)"')

    if [ -z "$runs" ]; then
        printf "⚠️  No workflow runs found. Waiting...\n"
        sleep $CHECK_INTERVAL
        continue
    fi

    all_complete=true
    all_success=true
    status_summary=""

    printf "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n"
    printf "📊 Workflow Status (%ds elapsed):\n" "${elapsed}"
    printf "\n"

    while IFS='|' read -r status conclusion workflow url created; do
        case "$status" in
            "completed")
                if [ "$conclusion" = "success" ]; then
                    icon="✅"
                    all_complete=true
                else
                    icon="❌"
                    all_success=false
                    all_complete=true
                fi
                ;;
            "in_progress"|"queued")
                icon="⏳"
                all_complete=false
                ;;
            *)
                icon="❓"
                all_complete=false
                ;;
        esac

        status_summary="${status_summary}${icon} ${workflow}: ${status}"
        if [ "$status" = "completed" ]; then
            status_summary="${status_summary} (${conclusion})"
        fi
        status_summary="${status_summary}\n"

        printf "  %s %s\n" "${icon}" "${workflow}"
        printf "     Status: %s\n" "${status}"
        if [ "$status" = "completed" ]; then
            printf "     Conclusion: %s\n" "${conclusion}"
        fi
        printf "     URL: %s\n" "${url}"
        printf "\n"

    done <<< "$runs"

    # Check if status changed
    current_status=$(printf "%s" "$runs" | head -1 | cut -d'|' -f1)
    last_status="$current_status"
    if [ "$current_status" != "$last_status" ] && [ -n "$last_status" ]; then
        printf "🔄 Status changed!\n"
    fi
    last_status="$current_status"

    if [ "$all_complete" = true ]; then
        printf "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n"
        printf "\n"
        if [ "$all_success" = true ]; then
            printf "🎉 All workflows completed successfully!\n"
            printf "\n"
            printf "✅ You can now proceed to create a PR:\n"
            printf "   gh pr create --base main --head develop --title \"...\" --body \"...\"\n"
            exit 0
        else
            printf "❌ Some workflows failed. Please check the logs:\n"
            printf "\n"
            while IFS='|' read -r status conclusion workflow url created; do
                if [ "$status" = "completed" ] && [ "$conclusion" != "success" ]; then
                    printf "   ❌ %s: %s\n" "${workflow}" "${url}"
                fi
            done <<< "$runs"
            exit 1
        fi
    fi

    printf "⏳ Waiting %ds before next check...\n" "${CHECK_INTERVAL}"
    printf "\n"
    sleep $CHECK_INTERVAL
done

