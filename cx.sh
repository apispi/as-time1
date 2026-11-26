#!/bin/bash
#================================================================
# Copyright © 2026 ApiSpi
#
# LOCAL command-line assistant — cx.sh
#
#   ./cx.sh                      # interactive menu
#   ./cx.sh 20                   # run an option directly
#   ./cx.sh 33 "route:list"      # …with a second parameter
#
# ⚠ Runs on your LOCAL machine only. Its counterpart on the server is
#   deploy.sh (pull + dependencies + migrate + caches).
#
# Version 1.2
# - 27 checks the server .env parses; 20 runs that check before deploying,
#   because an unquoted value with a space 500s the site and stops a
#   deploy halfway (the copy of deploy.sh on the server may be too old to
#   catch it itself)
#
# Version 1.1
# - settings live in this file (overridable from the environment); vars.sh
#   is gone
#
# Version 1.0
# - the routine operations this project actually has: deploy, what's-live,
#   caches, composer, health, MCP endpoint check, logs, artisan, config
#   readout, disk, maintenance mode
#================================================================

cd "$(dirname "$0")" || exit 1

# Settings. Each can be overridden from the environment for one run, e.g.
#   REMOTE=user@host APP_SSH="ssh -p 18765" ./cx.sh 22
PROJECT="${PROJECT:-time.apispi.com}"        # directory under ~/www/ on the server
REMOTE="${REMOTE:-as}"                       # SSH alias from ~/.ssh/config (user, host, port)
APP_SSH="${APP_SSH:-ssh -x}"
REMOTE_APP="${REMOTE_APP:-www/$PROJECT}"     # app root on the server, relative to ~
APP_URL="${APP_URL:-https://$PROJECT}"       # what the health checks (30, 38) request
# "user:password" for the hosting password protection, if it is on. A secret,
# so it comes from the environment only and is never written in this file.
HTTP_AUTH="${HTTP_AUTH:-}"

# This script drives the server from outside; on the server it would ssh to
# itself. The app directory living under ~/www is what the server looks like.
case "$PWD" in
  "$HOME/www/"*) echo "⚠ This looks like the server — cx.sh is the LOCAL script. Use ./deploy.sh here."; exit 1 ;;
esac

#---------------------------------------------------------------
# Helpers
#---------------------------------------------------------------

# Prepend a line to the history log (newest first), creating it if needed.
log_history() {
  mkdir -p ./log
  touch ./log/history.log
  printf '%s  %s\n' "$(date '+%Y-%m-%d %H:%M')" "$1" | cat - ./log/history.log > ./log/history.tmp \
    && mv ./log/history.tmp ./log/history.log
}

# Run a command on the server, in the app directory.
remote() {
  $APP_SSH "$REMOTE" "cd ~/$REMOTE_APP && $*"
}

# Same, with a terminal attached — for anything interactive or long-running.
remote_tty() {
  $APP_SSH -t "$REMOTE" "cd ~/$REMOTE_APP && $*"
}

# curl against the public site, with the password-protection login if set.
site_curl() {
  if [ -n "$HTTP_AUTH" ]; then
    curl -sS --max-time 25 -u "$HTTP_AUTH" "$@"
  else
    curl -sS --max-time 25 "$@"
  fi
}

# Explain a 401 from the web server's own password protection, which
# otherwise reads as the app being broken.
explain_status() {
  if [ "$1" = "401" ] && [ -z "$HTTP_AUTH" ]; then
    echo "  401 is the hosting password protection, not the app. To check through"
    echo "  it: HTTP_AUTH=user:password ./cx.sh $SELECTION. MCP clients and Google's"
    echo "  OAuth redirect are blocked by it in the same way."
  fi
}

# The server .env lines Laravel cannot parse: unquoted values with a space.
# Prints them and returns 1 if there are any.
check_env() {
  local bad
  bad=$(remote "test -f .env || { echo 'MISSING'; exit 0; }; grep -nE '^[A-Za-z_][A-Za-z0-9_]*=[^\"\x27#]*[^\"\x27#[:space:]][[:space:]]+[^#[:space:]]' .env" 2>/dev/null)
  if [ "$bad" = "MISSING" ]; then
    echo "✗ the server has no .env"; return 1
  elif [ -n "$bad" ]; then
    echo "✗ server .env has unquoted values containing spaces (the site 500s until fixed):"
    echo "$bad" | sed -E 's/^([0-9]+):([^=]+)=(.*)$/    line \1:  \2=\3   →   \2="\3"/'
    echo "  Fix on the server:  ssh $REMOTE, cd ~/$REMOTE_APP, edit .env"
    return 1
  fi
  echo "✓ server .env parses"
}

# Ask for PARAM2 only when the chosen option needs it.
need_param() {
  if [ -z "$PARAM2" ]; then
    read -r -p "$1: " PARAM2
  fi
}

banner() {
  echo ===========================================================
  echo "$1"
  echo ===========================================================
}

# Gate for anything that takes the site down or is hard to undo.
confirm() {
  echo "⚠ $1"
  read -r -p "Type 'yes' to continue: " reply
  [ "$reply" = "yes" ] || { echo "Skipped $SELECTION."; exit 0; }
}

#---------------------------------------------------------------
# Menu
#---------------------------------------------------------------

if [ -n "$1" ]; then
  SELECTION=$1
else
  clear
  echo =============================================================
  echo "Hi $USER — acting on $PROJECT ($REMOTE)"
  echo What do you want to do?
  echo -------------------------------------------------------------
  echo "ROUTINE — day to day"
  echo "20 : DEPLOY: pull + composer + migrate + caches on $PROJECT"
  echo "22 : WHAT'S LIVE: compare the server's HEAD with local"
  echo "23 : MIGRATIONS: status (what is pending on the server)"
  echo "24 : CACHES: refresh config/routes/views"
  echo "25 : CACHES: clear everything (when a cached value looks stale)"
  echo "26 : COMPOSER: install production dependencies"
  echo "27 : ENV: check the server .env parses"
  echo ----------------------------------------------
  echo DIAGNOSE
  echo "30 : HEALTH: $APP_URL and /up"
  echo "31 : LOGS: tail the Laravel log"
  echo "32 : LOGS: last errors"
  echo "33 : ARTISAN: run a command (e.g. 'route:list')"
  echo "34 : CONFIG: key settings on the server (secrets hidden)"
  echo "35 : DISK: what is using the account quota"
  echo "36 : LOGS: file sizes"
  echo "38 : MCP: call the live endpoint and list its tools"
  echo ----------------------------------------------
  echo MAINTENANCE
  echo "72 : MAINTENANCE MODE: on  [site goes down]"
  echo "73 : MAINTENANCE MODE: off"
  echo "74 : LOGS: truncate the Laravel log  [discards log history]"
  echo ----------------------------------------------
  echo "00 : CONNECT: shell on $REMOTE, in the app directory"
  echo "qq : Exit [Quit]"
  echo Enter [Selection] to continue
  echo =============================================================
  read -r -n 2 SELECTION
  echo
fi
PARAM2="${2:-}"


case "$SELECTION" in

  #-------------------------------------------------------------
  # ROUTINE
  #-------------------------------------------------------------

  "20" )
  banner "DEPLOY: $PROJECT"
  # The server deploys by pulling from git, so anything not pushed is not
  # deployed. Without this check the deploy "succeeds" on the previous
  # commit and the change appears to have silently done nothing.
  if [ -n "$(git status --porcelain 2>/dev/null)" ]; then
    echo "⚠ Uncommitted local changes — these will NOT deploy:"
    git status --short
    echo
  fi
  git fetch --quiet 2>/dev/null
  UNPUSHED=$(git log --oneline '@{u}..' 2>/dev/null)
  if [ -n "$UNPUSHED" ]; then
    echo "⚠ Commits not pushed — these will NOT deploy:"
    echo "$UNPUSHED"
    echo
    confirm "Deploying now would publish the previous commit."
  fi
  echo "Local HEAD : $(git log --oneline -1)"
  echo
  check_env || { echo "  Nothing was deployed."; log_history "Deploy refused on $PROJECT: bad .env"; exit 1; }
  echo
  # Server edits that are now committed block the pull even when identical.
  # The server's deploy.sh may predate handling that, so do it here first.
  remote "git fetch --quiet && for f in \$(git diff --name-only); do [ \"\$(git hash-object \"\$f\")\" = \"\$(git rev-parse \"@{u}:\$f\" 2>/dev/null)\" ] && git checkout -- \"\$f\" && echo \"ℹ \$f: server edit is now in the repo — kept the repo copy\"; done; true"
  # A deploy.sh that is new or changed arrives with the pull it performs, so
  # the copy on the server is one commit behind. Pull first when it is absent.
  if remote_tty "test -x ./deploy.sh || { echo '→ first run: fetching deploy.sh'; git pull --ff-only; }; ./deploy.sh"; then
    log_history "Deployed $PROJECT at $(git log --oneline -1)"
  else
    echo "✗ deploy failed — see the output above"
    log_history "Deploy FAILED on $PROJECT"
    exit 1
  fi
  ;;


  "22" )
  banner "WHAT'S LIVE on $PROJECT"
  echo "--- server ---"
  remote "git log --oneline -3 && git status --short"
  echo
  echo "--- local ---"
  git log --oneline -3
  echo
  LOCAL_HEAD=$(git rev-parse HEAD 2>/dev/null)
  SERVER_HEAD=$(remote "git rev-parse HEAD" 2>/dev/null | tr -d '\r')
  if [ "$LOCAL_HEAD" = "$SERVER_HEAD" ]; then
    echo "✓ server matches local HEAD"
  else
    echo "✗ server is NOT on your local HEAD"
    echo "  local : $LOCAL_HEAD"
    echo "  server: $SERVER_HEAD"
  fi
  ;;


  "23" )
  banner "MIGRATIONS: status on $PROJECT"
  remote "php artisan migrate:status"
  ;;


  "24" )
  banner "CACHES: refresh on $PROJECT"
  remote "php artisan optimize"
  log_history "Refreshed caches on $PROJECT"
  ;;


  "25" )
  banner "CACHES: clear everything on $PROJECT"
  # The counterpart to 24. A stale config cache is why an .env change can
  # appear to have been ignored. Run 24 afterwards to rebuild.
  remote "php artisan optimize:clear"
  log_history "Cleared all caches on $PROJECT"
  ;;


  "26" )
  banner "COMPOSER: install production dependencies"
  # deploy.sh does this itself whenever composer.lock moves; this is for
  # reinstalling without a deploy.
  remote_tty "composer install --no-dev --optimize-autoloader --no-interaction"
  log_history "Installed composer dependencies on $PROJECT"
  ;;


  "27" )
  banner "ENV: check on $PROJECT"
  check_env || exit 1
  ;;


  #-------------------------------------------------------------
  # DIAGNOSE
  #-------------------------------------------------------------

  "30" )
  banner "HEALTH: $APP_URL"
  # Checked from here rather than on the server so it exercises the real
  # path in: DNS, TLS and the web server, not just PHP.
  for path in "/" "/up" "/login"; do
    STATUS=$(site_curl -o /dev/null -w '%{http_code} in %{time_total}s' "$APP_URL$path" 2>/dev/null) || STATUS="no response"
    case "$STATUS" in
      200*) echo "✓ $path : $STATUS" ;;
      *)    echo "✗ $path : $STATUS"; explain_status "${STATUS%% *}" ;;
    esac
  done
  ;;


  "31" )
  banner "LOGS: tail on $PROJECT (Ctrl-C to stop)"
  remote_tty "tail -n 40 -f storage/logs/laravel.log"
  ;;


  "32" )
  banner "LOGS: recent errors on $PROJECT"
  remote "grep -nE '\.(ERROR|CRITICAL|ALERT|EMERGENCY):' storage/logs/laravel.log | tail -n 25 | cut -c1-400" \
    || echo "No matching lines (or no log file yet)."
  ;;


  "33" )
  banner "ARTISAN on $PROJECT"
  need_param "artisan command (without the word 'artisan')"
  # SSH php on SiteGround is the cgi-fcgi binary, which is why `tinker`
  # dies here; non-interactive commands are fine.
  case "$PARAM2" in
    tinker*) echo "⚠ tinker needs an interactive PHP CLI; SSH php here is cgi-fcgi and will die." ;;
  esac
  remote_tty "php artisan $PARAM2"
  log_history "Ran artisan $PARAM2 on $PROJECT"
  ;;


  "34" )
  banner "CONFIG on $PROJECT"
  # Values are not printed for anything that looks like a secret — this
  # runs in a terminal that scrolls back.
  echo "--- environment ---"
  remote "grep -E '^(APP_ENV|APP_DEBUG|APP_URL|SESSION_DRIVER|CACHE_STORE|QUEUE_CONNECTION|MAIL_MAILER|DB_CONNECTION|GOOGLE_REDIRECT_URI)=' .env | sort"
  echo
  echo "--- secrets present (values hidden) ---"
  remote "grep -oE '^[A-Z_]*(KEY|SECRET|TOKEN|PASSWORD|CLIENT_ID)[A-Z_]*=.+' .env | sed 's/=.*//' | sort | sed 's/^/  /'"
  ;;


  "35" )
  banner "DISK: quota and the biggest directories"
  remote "du -sh . 2>/dev/null; echo; echo 'largest directories:'; du -sh */ 2>/dev/null | sort -rh | head -12"
  ;;


  "36" )
  banner "LOGS: sizes on $PROJECT"
  remote "ls -lh storage/logs/ 2>/dev/null | tail -n +2; echo; echo 'total:'; du -sh storage/logs 2>/dev/null"
  ;;


  "38" )
  banner "MCP: $APP_URL/mcp"
  # A real JSON-RPC call over the public URL — the same path an MCP client
  # takes. Listing the tools proves the server class booted, not just routing.
  BODY=$(site_curl -w '\n%{http_code}' -X POST "$APP_URL/mcp" \
    -H 'Content-Type: application/json' \
    -H 'Accept: application/json, text/event-stream' \
    -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}') || { echo "✗ no response"; exit 1; }
  STATUS="${BODY##*$'\n'}"
  TOOLS=$(printf '%s' "$BODY" | grep -oE '"name":"[a-z_]+"' | cut -d'"' -f4)
  if [ "$STATUS" = "200" ] && [ -n "$TOOLS" ]; then
    echo "✓ $STATUS — tools:"
    echo "$TOOLS" | sed 's/^/    /'
  else
    echo "✗ status $STATUS, no tools listed"
    explain_status "$STATUS"
    [ "$STATUS" = "401" ] || printf '%s\n' "$BODY" | head -c 600
    exit 1
  fi
  ;;


  #-------------------------------------------------------------
  # MAINTENANCE
  #-------------------------------------------------------------

  "72" )
  banner "MAINTENANCE MODE: ON"
  confirm "$APP_URL (including /mcp) will return 503 to everyone until you run 73."
  # --retry sets Retry-After so clients and crawlers come back.
  remote_tty "php artisan down --retry=60"
  log_history "Maintenance mode ON for $PROJECT"
  ;;


  "73" )
  banner "MAINTENANCE MODE: OFF"
  remote_tty "php artisan up"
  log_history "Maintenance mode OFF for $PROJECT"
  ;;


  "74" )
  banner "LOGS: truncate the Laravel log"
  confirm "This discards the Laravel log on $PROJECT. Run 32 first if you have not read the recent errors."
  # Truncated in place rather than deleted, so the file keeps the
  # permissions the web user needs.
  remote "test -f storage/logs/laravel.log && : > storage/logs/laravel.log && echo 'laravel.log truncated.' || echo 'No laravel.log to truncate.'"
  log_history "Truncated the Laravel log on $PROJECT"
  ;;


  "00" )
  banner "CONNECT: to $REMOTE"
  remote_tty 'exec $SHELL -l'
  ;;


  "qq" )
  echo Quit
  exit 0
  ;;


  * )
  echo
  echo "Not a recognized option: '$SELECTION'. Run ./cx.sh with no arguments for the menu."
  exit 1
  ;;

esac
