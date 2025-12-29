#!/bin/bash

# Monitor GitHub Actions workflows for develop branch
# Notifies when all workflows complete (success or failure)

REPO="s-a-c/lw4fm5"
BRANCH="develop"
CHECK_INTERVAL=10  # Check every 10 seconds
MAX_WAIT=1800      # Maximum wait time: 30 minutes

echo "🔍 Monitoring GitHub Actions workflows for branch: $BRANCH"
echo "⏱️  Checking every ${CHECK_INTERVAL} seconds (max wait: ${MAX_WAIT}s)"
echo ""

start_time=$(date +%s)
last_status=""

while true; do
    current_time=$(date +%s)
    elapsed=$((current_time - start_time))
    
    if [ $elapsed -gt $MAX_WAIT ]; then
        echo "⏰ Maximum wait time exceeded (${MAX_WAIT}s)"
        exit 1
    fi
    
    # Get workflow runs
    runs=$(gh run list --repo "$REPO" --branch "$BRANCH" --limit 3 --json status,conclusion,workflowName,url,createdAt --jq '.[] | "\(.status)|\(.conclusion // "none")|\(.workflowName)|\(.url)|\(.createdAt)"')
    
    if [ -z "$runs" ]; then
        echo "⚠️  No workflow runs found. Waiting..."
        sleep $CHECK_INTERVAL
        continue
    fi
    
    all_complete=true
    all_success=true
    status_summary=""
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "📊 Workflow Status (${elapsed}s elapsed):"
    echo ""
    
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
        
        echo "  ${icon} ${workflow}"
        echo "     Status: ${status}"
        if [ "$status" = "completed" ]; then
            echo "     Conclusion: ${conclusion}"
        fi
        echo "     URL: ${url}"
        echo ""
        
    done <<< "$runs"
    
    # Check if status changed
    current_status=$(echo "$runs" | head -1 | cut -d'|' -f1)
    if [ "$current_status" != "$last_status" ] && [ -n "$last_status" ]; then
        echo "🔄 Status changed!"
    fi
    last_status="$current_status"
    
    if [ "$all_complete" = true ]; then
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        echo ""
        if [ "$all_success" = true ]; then
            echo "🎉 All workflows completed successfully!"
            echo ""
            echo "✅ You can now proceed to create a PR:"
            echo "   gh pr create --base main --head develop --title \"...\" --body \"...\""
            exit 0
        else
            echo "❌ Some workflows failed. Please check the logs:"
            echo ""
            while IFS='|' read -r status conclusion workflow url created; do
                if [ "$status" = "completed" ] && [ "$conclusion" != "success" ]; then
                    echo "   ❌ ${workflow}: ${url}"
                fi
            done <<< "$runs"
            exit 1
        fi
    fi
    
    echo "⏳ Waiting ${CHECK_INTERVAL}s before next check..."
    echo ""
    sleep $CHECK_INTERVAL
done

